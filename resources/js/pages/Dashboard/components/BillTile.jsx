// resources/js/pages/Dashboard/components/BillTile.jsx
// T2 — only rendered when there's something to pay (never shows ₱0).
//   layout="strip" → slim full-width bar (residents, above My Room)
//   layout="card"  → compact card for the narrow side column
import React from "react";
import { Arrow, BentoTile, btn, CountUp, cx, formatDate } from "./ui";

function StatusChip({ bills }) {
    if (bills.isOverdue) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-2 py-0.5 text-[11px] font-bold whitespace-nowrap">
                <i className="fa-solid fa-circle-exclamation text-[10px]" aria-hidden="true" />
                {bills.daysOverdue > 0 ? `Overdue by ${bills.daysOverdue} day${bills.daysOverdue === 1 ? "" : "s"}` : "Overdue"}
            </span>
        );
    }
    if (bills.isDueSoon) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-orange-50 text-[#934300] ring-1 ring-orange-200 px-2 py-0.5 text-[11px] font-bold whitespace-nowrap">
                <i className="fa-regular fa-clock text-[10px]" aria-hidden="true" />
                {bills.daysUntilDue === 0 ? "Due today" : "Due soon"}
            </span>
        );
    }
    return null;
}

function Meta({ bills }) {
    return (
        <>
            {bills.count} unpaid item{bills.count === 1 ? "" : "s"}
            {bills.nearestDue && <> · {bills.isOverdue ? "was due" : "due"} <span className="font-semibold text-slate-700">{formatDate(bills.nearestDue)}</span></>}
        </>
    );
}

export default function BillTile({ bills, links, layout = "card", xl = 4, md = 6, index = 0 }) {
    if (!bills) return null;
    const overdue = bills.isOverdue;
    const amountCls = cx("font-extrabold leading-none tabular-nums", overdue ? "text-[#BE123C]" : "text-slate-900");
    // .open-payment-modal + data-invoice → handled by the existing Pay Now modal
    const payBtn = (extra = "") => (
        <button type="button" className={cx("open-payment-modal", btn.pay, btn.md, extra)} data-invoice={JSON.stringify(bills.payNow)}>
            <i className="fa-regular fa-credit-card" aria-hidden="true" /> Pay Now <Arrow />
        </button>
    );

    if (layout === "strip") {
        return (
            <BentoTile xl={xl} md={md} index={index} aria-labelledby="bill-title"
                className={cx("relative overflow-hidden px-4 py-3.5 sm:px-5", overdue && "border-rose-200")}>
                {overdue && <span className="absolute inset-y-0 left-0 w-1 bg-[#E11D48]" aria-hidden="true" />}
                <div className="flex flex-wrap items-center gap-x-4 gap-y-3">
                    <span className={cx("w-9 h-9 rounded-xl text-[14px] flex items-center justify-center shrink-0",
                        overdue ? "bg-rose-50 text-[#E11D48]" : "bg-emerald-50 text-[#15803D]")} aria-hidden="true">
                        <i className="fa-solid fa-wallet" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 id="bill-title" className="text-[13px] font-semibold text-slate-600">{overdue ? "Payment overdue" : "Amount due"}</h2>
                            <StatusChip bills={bills} />
                        </div>
                        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 mt-0.5">
                            <CountUp value={bills.total} prefix="₱" className={cx(amountCls, "text-[22px]")} />
                            <span className="text-[12.5px] text-slate-500"><Meta bills={bills} /></span>
                        </div>
                    </div>
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <a href={links.history} className="hidden md:inline-flex text-[12.5px] font-semibold text-slate-500 hover:text-slate-800 h-10 items-center rounded-lg px-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                            History
                        </a>
                        {payBtn("w-full sm:w-auto")}
                    </div>
                </div>
            </BentoTile>
        );
    }

    return (
        <BentoTile xl={xl} md={md} index={index} aria-labelledby="bill-title"
            className={cx("relative overflow-hidden p-4 flex flex-col", overdue && "border-rose-200")}>
            {overdue && <span className="absolute inset-y-0 left-0 w-1 bg-[#E11D48]" aria-hidden="true" />}
            <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2.5">
                    <span className={cx("w-8 h-8 rounded-lg text-[13px] flex items-center justify-center",
                        overdue ? "bg-rose-50 text-[#E11D48]" : "bg-emerald-50 text-[#15803D]")} aria-hidden="true">
                        <i className="fa-solid fa-wallet" />
                    </span>
                    <h2 id="bill-title" className="text-[14px] font-bold text-slate-900">{overdue ? "Payment overdue" : "Amount due"}</h2>
                </div>
                <StatusChip bills={bills} />
            </div>
            <CountUp value={bills.total} prefix="₱" className={cx(amountCls, "mt-3 text-[24px]")} />
            <p className="mt-1 text-[12.5px] text-slate-500"><Meta bills={bills} /></p>
            <div className="mt-3 flex items-center gap-2">
                {payBtn("flex-1")}
                <a href={links.history} className="text-[12.5px] font-semibold text-slate-500 hover:text-slate-800 h-10 px-2 inline-flex items-center rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                    History
                </a>
            </div>
        </BentoTile>
    );
}
