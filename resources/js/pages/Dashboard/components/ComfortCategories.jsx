// resources/js/pages/Dashboard/components/ComfortCategories.jsx
// "Choose your kind of comfort" — one card per room type with live
// available / listed counts. Picking one filters the room browser.
import React from "react";

export default function ComfortCategories({ categories = [], onPick }) {
    return (
        <section className="py-10 sm:py-12" aria-labelledby="comfort-title">
            <div className="text-center max-w-[680px] mx-auto">
                <h2 id="comfort-title" className="text-[24px] sm:text-[30px] font-extrabold tracking-tight text-[#0A1F44] leading-tight">
                    Choose your kind of comfort
                </h2>
                <p className="mt-2.5 text-[14px] sm:text-[15px] leading-relaxed text-slate-600">
                    Three distinct room styles, one welcoming community. Select a category to see what is currently available.
                </p>
            </div>

            {/* Cards stretch to the tallest one; content starts at the top (buttons center it by default) */}
            <div className="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3 items-stretch">
                {categories.map((c) => (
                    <button key={c.type} type="button" onClick={() => onPick(c.type)}
                        aria-label={`${c.title}: ${c.available} available of ${c.listed} listed. Show these rooms.`}
                        className="group h-full flex flex-col justify-start text-left bg-white rounded-2xl overflow-hidden border border-slate-200/70 shadow-[0_8px_24px_-14px_rgba(15,26,51,0.2)] hover:shadow-[0_16px_32px_-14px_rgba(15,26,51,0.28)] transition-shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A] focus-visible:ring-offset-2">
                        <div className="relative w-full aspect-[16/10] shrink-0 overflow-hidden bg-slate-200">
                            <img src={c.img} alt="" loading="lazy"
                                className="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                            <span className="absolute left-4 bottom-4 inline-flex items-center gap-1.5 rounded-md bg-[#0A1F44] text-white text-[12px] font-medium px-2.5 py-1">
                                {c.available} available <span className="opacity-50">|</span> {c.listed} listed
                            </span>
                        </div>
                        <div className="flex-1 w-full px-5 py-5 flex items-center gap-4">
                            <div className="flex-1 min-w-0">
                                <h3 className="text-[18px] sm:text-[19px] font-bold text-slate-900 leading-snug">{c.title}</h3>
                                <p className="mt-1 text-[13.5px] leading-relaxed text-slate-600">{c.desc}</p>
                            </div>
                            <span className="w-10 h-10 shrink-0 rounded-full bg-[#F7860F] group-hover:bg-[#E57700] text-white flex items-center justify-center text-[15px] transition-colors" aria-hidden="true">
                                <i className="fa-solid fa-arrow-right transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </button>
                ))}
            </div>
        </section>
    );
}
