// resources/js/pages/History/HistoryModals.jsx
import React, { useState, useEffect } from "react";
import Modal from "../../components/Modal";

export default function HistoryModals() {
    const [openKey, setOpenKey] = useState(null);
    const [receipt, setReceipt] = useState(null);
    const [items, setItems] = useState({});

    // ============================================================
    // Read items map from mount element (passed by Blade)
    // ============================================================
    useEffect(() => {
        const el = document.getElementById("tenant-history-modals");
        if (!el) return;
        if (el.dataset.items) {
            try {
                setItems(JSON.parse(el.dataset.items));
            } catch (e) {
                console.error("Invalid history JSON", e);
            }
        }
    }, []);

    // ============================================================
    // PROFESSIONAL PRINT LOGIC
    // ============================================================
    const printReceipt = (data) => {
        if (!data) return;

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt - ${data.reference || "JLM Cozy Cabin"}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                    /* THIS IS THE MAGIC FIX */
                    @page { 
                        size: auto; 
                        margin: 0mm; 
                    }
                    
                    body {
                    font-family: 'Inter', Arial, sans-serif;
                    color: #1e293b;
                    margin: 0;
                    padding: 0;
                    background: #fff;
                    -webkit-print-color-adjust: exact;
                    display: flex;
                    align-items: center;      
                    justify-content: center;  
                    min-height: 100vh;        
                }

                .receipt-container {
                    width: 450px; 
                    flex-shrink: 0; 
                    
                    border: 2px dashed #f97316;
                    border-radius: 12px;
                    padding: 30px;
                    background: #fffaf5;
                    box-sizing: border-box; 
                }
                    .header {
                        text-align: center;
                        margin-bottom: 25px;
                    }
                    .header h1 {
                        margin: 0 0 5px 0;
                        font-size: 22px;
                        color: #0f172a;
                    }
                    .header p {
                        margin: 0;
                        font-size: 12px;
                        color: #ea580c;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                    .meta {
                        text-align: center;
                        font-size: 14px;
                        font-weight: 600;
                        color: #475569;
                        margin-bottom: 5px;
                    }
                    .date {
                        text-align: center;
                        font-size: 12px;
                        color: #94a3b8;
                        margin-bottom: 25px;
                    }
                    .divider {
                        height: 1px;
                        background: #fed7aa;
                        margin: 20px 0;
                    }
                    .row {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 12px;
                        font-size: 13px;
                    }
                    .row .label {
                        color: #64748b;
                        font-weight: 500;
                    }
                    .row .value {
                        color: #0f172a;
                        font-weight: 700;
                        text-align: right;
                    }
                    .total-row {
                        display: flex;
                        justify-content: space-between;
                        margin-top: 15px;
                        padding-top: 15px;
                        border-top: 1px solid #fed7aa;
                        font-size: 16px;
                        font-weight: 800;
                        color: #0f172a;
                    }
                    .total-row .amount {
                        color: #ea580c;
                        font-size: 18px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        font-size: 12px;
                        color: #64748b;
                    }
                    .footer strong {
                        color: #0f172a;
                    }
                    .thank-you {
                        text-align: center;
                        color: #f97316;
                        font-weight: 700;
                        font-size: 14px;
                        margin-top: 10px;
                    }
                    @media print {
                        body { padding: 0; }
                        .receipt-container { border: 2px solid #f97316; box-shadow: none; }
                    }
                </style>
            </head>
            <body>
                <div class="receipt-container">
                    <div class="header">
                        <h1>Official Receipt</h1>
                        <p>JLM Cozy Cabin</p>
                    </div>
                    
                    <div class="meta">Transaction No. ${(data.reference || "-").toUpperCase()}</div>
                    <div class="date">Date of Issue: ${data.paid_date || "-"}</div>

                    <div class="divider"></div>

                    <div class="row"><span class="label">Receipt No.</span><span class="value">${(data.reference || "-").toUpperCase()}</span></div>
                    <div class="row"><span class="label">Tenant</span><span class="value">${data.tenant || "-"}</span></div>
                    <div class="row"><span class="label">Unit</span><span class="value">${data.unit || "-"}</span></div>
                    <div class="row"><span class="label">Description</span><span class="value">${(data.description || "-").replace(/₱/g, "\u20B1")}</span></div>
                    <div class="row"><span class="label">Reference No.</span><span class="value">${data.reference || "-"}</span></div>
                    <div class="row"><span class="label">Payment Method</span><span class="value">${(data.method || "-").toUpperCase()}</span></div>

                    <div class="total-row">
                        <span>Total Amount</span>
                        <span class="amount">${(data.amount || "-").replace(/₱/g, "\u20B1")}</span>
                    </div>

                    <div class="footer">
                        Authorized By: <strong>Leah Agujar</strong>
                        <div class="thank-you">Thank You! :)</div>
                    </div>
                </div>
            </body>
            </html>
        `;

        const iframe = document.createElement("iframe");
        iframe.style.position = "fixed";
        iframe.style.right = "0";
        iframe.style.bottom = "0";
        iframe.style.width = "0";
        iframe.style.height = "0";
        iframe.style.border = "0";
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();

        iframe.onload = () => {
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            }, 250);
        };
    };

    // ============================================================
    // Toggle expand / open receipt  (document-level delegation)
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            const receiptBtn = e.target.closest(".view-receipt-btn");
            if (receiptBtn) {
                e.preventDefault();
                e.stopPropagation();
                const key = receiptBtn.dataset.itemKey;
                const data = items[key]?.receipt || items[key];
                if (data) setReceipt(data);
                return;
            }

            const header = e.target.closest(".history-item-header");
            if (header) {
                const item = header.closest(".history-item");
                const key = item?.dataset?.itemKey;
                if (key) {
                    setOpenKey((prev) => (prev === key ? null : key));
                }
                return;
            }

            const rowPrintBtn = e.target.closest(".print-row-btn");
            if (rowPrintBtn) {
                e.preventDefault();
                e.stopPropagation();
                const key = rowPrintBtn.dataset.itemKey;
                const data = items[key]?.receipt || items[key];
                if (data) printReceipt(data);
                return;
            }
        };

        document.addEventListener("click", handler);
        return () => document.removeEventListener("click", handler);
    }, [items]);

    // ============================================================
    // Sync openKey → toggle classes AND inline styles on the DOM
    // ============================================================
    useEffect(() => {
        const panels = document.querySelectorAll(".history-item-details");
        const chevrons = document.querySelectorAll(".expand-chevron");

        panels.forEach((p) => {
            p.classList.remove("expanded");
            p.style.maxHeight = "0px";
            p.style.overflow = "hidden";
            p.style.padding = "0 20px";
            p.style.borderTop = "1px solid transparent";
            p.style.background = "#f8fafc";
            p.style.transition = "max-height 0.35s ease, padding 0.35s ease";
        });
        chevrons.forEach((c) => {
            c.classList.remove("rotated");
            c.style.transition = "transform 0.3s ease";
            c.style.transform = "rotate(0deg)";
        });

        if (!openKey) return;

        const panel = document.getElementById(`details-${openKey}`);
        const chevron = document.querySelector(
            `.history-item[data-item-key="${openKey}"] .expand-chevron`,
        );

        if (panel) {
            panel.classList.add("expanded");
            panel.style.maxHeight = "1200px";
            panel.style.overflow = "hidden";
            panel.style.padding = "20px";
            panel.style.borderTop = "1px solid #f1f5f9";
            panel.style.background = "#f8fafc";
            panel.style.transition =
                "max-height 0.35s ease, padding 0.35s ease";
        }
        if (chevron) {
            chevron.classList.add("rotated");
            chevron.style.transform = "rotate(180deg)";
        }
    }, [openKey]);

    const closeReceipt = () => setReceipt(null);

    return (
        <>
            {/* RECEIPT MODAL */}
            {receipt && (
                <Modal onClose={closeReceipt} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">
                            Payment Receipt
                        </h3>
                        <button
                            onClick={closeReceipt}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto">
                        <div className="border-[1.5px] border-dashed border-orange-400 bg-orange-50/30 rounded-2xl p-5 flex flex-col items-center gap-3">
                            <div className="w-11 h-11 rounded-full bg-orange-500 text-white flex items-center justify-center text-xl">
                                <i className="fa-solid fa-check"></i>
                            </div>
                            <div className="text-center">
                                <h4 className="text-base font-extrabold text-slate-900">
                                    Official Receipt
                                </h4>
                                <p className="text-[11px] text-orange-600 font-semibold">
                                    JLM Cozy Cabin
                                </p>
                            </div>
                            <div className="text-sm font-semibold text-slate-600">
                                Transaction No.{" "}
                                {(receipt.reference || "").toUpperCase() || "-"}
                            </div>
                            <div className="text-[11px] text-slate-400">
                                Date of Issue: {receipt.paid_date || "-"}
                            </div>

                            <div className="w-full border-t border-slate-100 pt-3 flex flex-col gap-2">
                                {[
                                    [
                                        "Receipt No.",
                                        (
                                            receipt.reference || ""
                                        ).toUpperCase() || "-",
                                    ],
                                    [
                                        "Tenant",
                                        receipt.tenant || document.getElementById("tenant-history-modals")?.dataset?.userName || "-",
                                    ],
                                    ["Unit", receipt.unit || "-"],
                                    ["Description", receipt.description || "-"],
                                    ["Reference No.", receipt.reference || "-"],
                                    [
                                        "Payment Method",
                                        (receipt.method || "-").toUpperCase(),
                                    ],
                                ].map((row, i) => (
                                    <div
                                        key={i}
                                        className="flex justify-between text-xs"
                                    >
                                        <span className="text-slate-500 font-medium">
                                            {row[0]}
                                        </span>
                                        <span className="text-slate-900 font-bold">
                                            {typeof row[1] === "string"
                                                ? row[1].replace(/₱/g, "\u20B1")
                                                : row[1]}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100">
                                    <span className="text-slate-900 font-bold">
                                        Total Amount
                                    </span>
                                    <span className="text-slate-900 font-extrabold">
                                        {(receipt.amount || "-").replace(
                                            /₱/g,
                                            "\u20B1",
                                        )}
                                    </span>
                                </div>
                            </div>

                            <div className="text-center mt-2">
                                <p className="text-[11px] text-slate-500">
                                    Authorized By: <strong>Leah Agujar</strong>
                                </p>
                                <p className="text-xs font-bold text-orange-500 mt-1">
                                    Thank You! :)
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-2.5 mt-4">
                            {/* PDF BUTTON ONLY NOW */}
                            <button
                                className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-lg py-2.5 text-[13px] font-bold inline-flex items-center justify-center gap-2 transition"
                                onClick={() => {
                                    const url = new URL(
                                        "/tenants/history/export-pdf",
                                        window.location.origin,
                                    );
                                    // Use the reference (Invoice ID or RSV number) to filter
                                    if (receipt.reference) {
                                        url.searchParams.set(
                                            "search",
                                            receipt.reference,
                                        );
                                    }
                                    window.open(url.toString(), "_blank");
                                }}
                            >
                                <i className="fa-solid fa-download"></i>{" "}
                                Download PDF
                            </button>
                        </div>
                    </div>
                </Modal>
            )}
        </>
    );
}
