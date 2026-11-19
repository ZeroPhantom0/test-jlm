// resources/js/components/ChatbotWidget.jsx
// Landing-page FAQ assistant — rule-based, answers come from /chatbot (DB).
import React, { useCallback, useEffect, useRef, useState } from "react";
import axios from "axios";

const STORAGE_KEY = "jlm-chatbot-v1";
const TYPING_DELAY = 550; // small pause so replies feel conversational

const http = axios.create({
    headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
});

let uid = 0;
const nextId = () => `${Date.now()}-${++uid}`;

/* ------------------------------------------------------------
   Turn plain text with "• " lines / line breaks into JSX
   ------------------------------------------------------------ */
function RichText({ text }) {
    const lines = String(text ?? "").split("\n");
    return lines.map((line, i) =>
        line.trim() === "" ? (
            <span key={i} className="cb-gap" />
        ) : line.trim().startsWith("•") ? (
            <span key={i} className="cb-bullet">
                {line.trim().slice(1).trim()}
            </span>
        ) : (
            <span key={i} className="cb-line">
                {line}
            </span>
        ),
    );
}

export default function ChatbotWidget() {
    const [open, setOpen] = useState(false);
    const [boot, setBoot] = useState(null); // { settings, categories }
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState("");
    const [typing, setTyping] = useState(false);
    const [error, setError] = useState(false);
    const [showHint, setShowHint] = useState(false);
    const [unread, setUnread] = useState(false);

    const bodyRef = useRef(null);
    const inputRef = useRef(null);

    const settings = boot?.settings ?? {};
    const categories = boot?.categories ?? [];

    /* ---------- restore conversation for this tab ---------- */
    useEffect(() => {
        try {
            const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || "null");
            if (saved?.messages?.length) setMessages(saved.messages);
        } catch {
            /* ignore */
        }
        // Gentle nudge a few seconds after landing (once per tab)
        if (!sessionStorage.getItem(`${STORAGE_KEY}-hint`)) {
            const t = setTimeout(() => {
                setShowHint(true);
                setUnread(true);
            }, 4000);
            return () => clearTimeout(t);
        }
    }, []);

    useEffect(() => {
        try {
            sessionStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({ messages: messages.slice(-60) }),
            );
        } catch {
            /* ignore */
        }
    }, [messages]);

    /* ---------- load DB content the first time it opens ---------- */
    const loadBoot = useCallback(async () => {
        setError(false);
        try {
            const { data } = await http.get("/chatbot");
            setBoot(data);
            return data;
        } catch {
            setError(true);
            return null;
        }
    }, []);

    useEffect(() => {
        if (!open || boot) return;
        loadBoot().then((data) => {
            if (!data) return;
            setMessages((prev) =>
                prev.length
                    ? prev
                    : [
                          {
                              id: nextId(),
                              from: "bot",
                              kind: "welcome",
                              text: data.settings.welcome_message,
                          },
                      ],
            );
        });
    }, [open, boot, loadBoot]);

    /* ---------- auto-scroll ---------- */
    useEffect(() => {
        const el = bodyRef.current;
        if (el) el.scrollTo({ top: el.scrollHeight, behavior: "smooth" });
    }, [messages, typing, open]);

    /* ---------- escape closes ---------- */
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === "Escape" && setOpen(false);
        window.addEventListener("keydown", onKey);
        setTimeout(() => inputRef.current?.focus(), 250);
        return () => window.removeEventListener("keydown", onKey);
    }, [open]);

    const toggle = () => {
        setOpen((o) => !o);
        setShowHint(false);
        setUnread(false);
        sessionStorage.setItem(`${STORAGE_KEY}-hint`, "1");
    };

    /* ---------- messaging helpers ---------- */
    const push = (msg) => setMessages((prev) => [...prev, { id: nextId(), ...msg }]);

    const botReply = async (request, userText) => {
        if (userText) push({ from: "user", text: userText });
        setTyping(true);
        const started = Date.now();
        let reply;
        try {
            const { data } = await request();
            reply = data;
        } catch (e) {
            reply = {
                type: "fallback",
                text:
                    e?.response?.status === 429
                        ? "You're sending messages a little too fast. Please wait a moment and try again."
                        : settings.fallback_message ||
                          "Sorry, something went wrong. Please try again.",
            };
        }
        const wait = Math.max(0, TYPING_DELAY - (Date.now() - started));
        setTimeout(() => {
            setTyping(false);
            push({ from: "bot", reply });
        }, wait);
    };

    const sendText = (e) => {
        e?.preventDefault();
        const text = input.trim();
        if (!text || typing) return;
        setInput("");
        botReply(() => http.post("/chatbot/ask", { message: text }), text);
    };

    const pickCategory = (cat) => {
        if (typing) return;
        botReply(() => http.get(`/chatbot/category/${cat.id}`), cat.name);
    };

    const pickQuestion = (q) => {
        if (typing) return;
        botReply(() => http.get(`/chatbot/faq/${q.id}`), q.question);
    };

    const showMenu = () => {
        if (typing) return;
        push({ from: "user", text: "Explore another category" });
        setTyping(true);
        setTimeout(() => {
            setTyping(false);
            push({ from: "bot", kind: "menu", text: settings.back_message });
        }, TYPING_DELAY);
    };

    const endChat = () => {
        if (typing) return;
        push({ from: "user", text: "That's all, thank you!" });
        setTyping(true);
        setTimeout(() => {
            setTyping(false);
            push({ from: "bot", kind: "closing", text: settings.closing_message });
        }, TYPING_DELAY);
    };

    const restart = () => {
        setMessages([
            {
                id: nextId(),
                from: "bot",
                kind: "welcome",
                text: settings.welcome_message,
            },
        ]);
    };

    /** Suggested-action buttons coming from the DB. */
    const runButton = (button, categorySlug) => {
        const url = button?.url || "";
        if (url.startsWith("chat:")) {
            // In-chat action: list every answer of the category
            const cat = categories.find((c) => c.slug === categorySlug);
            if (!cat) return;
            push({ from: "user", text: button.label });
            setTyping(true);
            Promise.all(cat.questions.map((q) => http.get(`/chatbot/faq/${q.id}`)))
                .then((res) => {
                    const text = res
                        .map((r) => `• ${r.data.question}\n${r.data.text}`)
                        .join("\n\n");
                    push({ from: "bot", reply: { type: "list", title: cat.name, text } });
                })
                .catch(() =>
                    push({ from: "bot", reply: { type: "fallback", text: settings.fallback_message } }),
                )
                .finally(() => setTyping(false));
            return;
        }
        if (url.startsWith("#")) {
            document.getElementById(url.slice(1))?.scrollIntoView({ behavior: "smooth" });
            if (window.innerWidth < 640) setOpen(false);
            return;
        }
        window.location.href = url;
    };

    /* ---------- the last bot message decides which quick replies show ---------- */
    const lastBot = [...messages].reverse().find((m) => m.from === "bot");
    const lastIsMenu =
        lastBot && (lastBot.kind === "welcome" || lastBot.kind === "menu" || lastBot.reply?.type === "greeting");

    return (
        <div className={`cb-root ${open ? "is-open" : ""}`}>
            {/* ================= PANEL ================= */}
            <section
                className="cb-panel"
                role="dialog"
                aria-label={settings.bot_name || "JLM Cozy Cabin Assistant"}
                aria-hidden={!open}
            >
                <header className="cb-header">
                    <div className="cb-avatar">
                        <img src="/images/jlm-logo.png" alt="" />
                        <span className="cb-online" />
                    </div>
                    <div className="cb-title">
                        <strong>{settings.bot_name || "JLM Cozy Cabin Assistant"}</strong>
                        <small>{settings.bot_subtitle || "How can we help you?"}</small>
                    </div>
                    <button
                        type="button"
                        className="cb-icon-btn"
                        onClick={restart}
                        title="Start over"
                        aria-label="Start over"
                        disabled={!boot}
                    >
                        <i className="fa-solid fa-rotate-right" />
                    </button>
                    <button
                        type="button"
                        className="cb-icon-btn"
                        onClick={toggle}
                        title="Minimize"
                        aria-label="Minimize chat"
                    >
                        <i className="fa-solid fa-chevron-down" />
                    </button>
                </header>

                <div className="cb-body" ref={bodyRef}>
                    <div className="cb-daystamp">Today</div>

                    {error && !boot && (
                        <div className="cb-error">
                            <i className="fa-solid fa-wifi" />
                            <p>We couldn't load the assistant.</p>
                            <button type="button" onClick={loadBoot}>
                                Try again
                            </button>
                        </div>
                    )}

                    {messages.map((m) =>
                        m.from === "user" ? (
                            <div key={m.id} className="cb-row cb-row-user">
                                <div className="cb-bubble cb-bubble-user">{m.text}</div>
                            </div>
                        ) : (
                            <BotMessage
                                key={m.id}
                                msg={m}
                                isLast={m === lastBot}
                                categories={categories}
                                onCategory={pickCategory}
                                onQuestion={pickQuestion}
                                onButton={runButton}
                                onMenu={showMenu}
                                onEnd={endChat}
                                onRestart={restart}
                                showMenuChips={m === lastBot && lastIsMenu}
                            />
                        ),
                    )}

                    {typing && (
                        <div className="cb-row">
                            <div className="cb-mini-avatar">
                                <i className="fa-solid fa-house-chimney" />
                            </div>
                            <div className="cb-bubble cb-typing" aria-label="Assistant is typing">
                                <span />
                                <span />
                                <span />
                            </div>
                        </div>
                    )}
                </div>

                <form className="cb-input" onSubmit={sendText}>
                    <input
                        ref={inputRef}
                        type="text"
                        value={input}
                        maxLength={300}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder="Type your message..."
                        aria-label="Type your message"
                        disabled={!boot}
                    />
                    <button
                        type="submit"
                        className="cb-send"
                        disabled={!input.trim() || typing || !boot}
                        aria-label="Send message"
                    >
                        <i className="fa-solid fa-paper-plane" />
                    </button>
                </form>
                <div className="cb-footnote">
                    Automated FAQ assistant · For other concerns, contact the administrator
                </div>
            </section>

            {/* ================= LAUNCHER ================= */}
            {showHint && !open && (
                <button type="button" className="cb-hint" onClick={toggle}>
                    <span className="cb-hint-wave">👋</span>
                    Hi! Have questions about booking? <strong>Chat with us</strong>
                    <span
                        role="button"
                        tabIndex={0}
                        className="cb-hint-close"
                        aria-label="Dismiss"
                        onClick={(e) => {
                            e.stopPropagation();
                            setShowHint(false);
                            sessionStorage.setItem(`${STORAGE_KEY}-hint`, "1");
                        }}
                    >
                        <i className="fa-solid fa-xmark" />
                    </span>
                </button>
            )}

            <button
                type="button"
                className="cb-launcher"
                onClick={toggle}
                aria-label={open ? "Close chat assistant" : "Open chat assistant"}
                aria-expanded={open}
            >
                <span className="cb-launcher-icon cb-icon-chat">
                    <i className="fa-solid fa-comment-dots" />
                </span>
                <span className="cb-launcher-icon cb-icon-close">
                    <i className="fa-solid fa-xmark" />
                </span>
                {unread && !open && <span className="cb-badge">1</span>}
            </button>
        </div>
    );
}

/* ============================================================
   ONE BOT MESSAGE (text + its quick actions)
   ============================================================ */
function BotMessage({
    msg,
    isLast,
    categories,
    onCategory,
    onQuestion,
    onButton,
    onMenu,
    onEnd,
    onRestart,
    showMenuChips,
}) {
    const r = msg.reply;
    const text = r ? r.text : msg.text;
    const isFallback = r?.type === "fallback";
    const isClosing = msg.kind === "closing" || r?.type === "closing";

    return (
        <div className="cb-row">
            <div className="cb-mini-avatar">
                <i className="fa-solid fa-house-chimney" />
            </div>
            <div className="cb-stack">
                <div className={`cb-bubble cb-bubble-bot ${isFallback ? "is-fallback" : ""}`}>
                    {r?.type === "answer" && r.category && (
                        <span className="cb-tag">{r.category.name}</span>
                    )}
                    {r?.type === "list" && <span className="cb-tag">{r.title}</span>}
                    {isFallback && <i className="fa-solid fa-circle-info cb-fallback-icon" />}
                    <RichText text={text} />
                </div>

                {/* Category menu (welcome, greeting, "explore another") */}
                {showMenuChips && categories.length > 0 && (
                    <div className="cb-menu">
                        <p className="cb-menu-title">Frequently Asked Questions</p>
                        {categories.map((c) => (
                            <button
                                key={c.id}
                                type="button"
                                className="cb-menu-item"
                                onClick={() => onCategory(c)}
                            >
                                <span className="cb-menu-icon">
                                    <i className={`fa-solid ${c.icon || "fa-circle-question"}`} />
                                </span>
                                {c.name}
                                <i className="fa-solid fa-chevron-right cb-menu-arrow" />
                            </button>
                        ))}
                    </div>
                )}

                {/* Questions inside a category */}
                {r?.type === "category" && isLast && r.category?.questions?.length > 0 && (
                    <div className="cb-chips">
                        {r.category.questions.map((q) => (
                            <button
                                key={q.id}
                                type="button"
                                className="cb-chip"
                                onClick={() => onQuestion(q)}
                            >
                                {q.question}
                            </button>
                        ))}
                    </div>
                )}

                {/* Related questions after a typed answer */}
                {r?.type === "answer" && isLast && r.related?.length > 0 && (
                    <div className="cb-chips">
                        <span className="cb-chips-label">You might also ask:</span>
                        {r.related.map((q) => (
                            <button
                                key={q.id}
                                type="button"
                                className="cb-chip"
                                onClick={() => onQuestion(q)}
                            >
                                {q.question}
                            </button>
                        ))}
                    </div>
                )}

                {/* Suggested DB button + back / end options */}
                {isLast && !showMenuChips && !isClosing && r && (
                    <div className="cb-actions">
                        {r.button && (
                            <button
                                type="button"
                                className="cb-action cb-action-primary"
                                onClick={() => onButton(r.button, r.category?.slug)}
                            >
                                {r.button.label}
                                <i className="fa-solid fa-arrow-right" />
                            </button>
                        )}
                        {(r.type === "answer" || r.type === "list") && r.category && (
                            <button
                                type="button"
                                className="cb-action"
                                onClick={() =>
                                    onCategory(categories.find((c) => c.id === r.category.id) ?? r.category)
                                }
                            >
                                More on {r.category.name}
                            </button>
                        )}
                        <button type="button" className="cb-action" onClick={onMenu}>
                            <i className="fa-solid fa-table-cells-large" /> Other categories
                        </button>
                        <button type="button" className="cb-action cb-action-ghost" onClick={onEnd}>
                            End chat
                        </button>
                    </div>
                )}

                {isClosing && isLast && (
                    <div className="cb-actions">
                        <button type="button" className="cb-action cb-action-primary" onClick={onRestart}>
                            <i className="fa-solid fa-rotate-right" /> Start a new chat
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
