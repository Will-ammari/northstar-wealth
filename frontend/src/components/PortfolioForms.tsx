"use client";
import { useState, FormEvent } from "react";
import { X, PlusCircle } from "lucide-react";
import type { Asset, Role } from "@/lib/types";
export function PortfolioForms({ portfolioId, assets, role, onChange }: {
    portfolioId: number;
    assets: Asset[];
    role: Role;
    onChange: () => void;
}) {
    const [mode, setMode] = useState<"asset" | "transaction" | "valuation" | null>(null);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState("");
    const [type, setType] = useState("stock");
    const [name, setName] = useState("");
    const [symbol, setSymbol] = useState("");
    const [assetId, setAssetId] = useState("");
    const [direction, setDirection] = useState("buy");
    const [quantity, setQuantity] = useState("1");
    const [unitPrice, setUnitPrice] = useState("1");
    const [retryKey, setRetryKey] = useState<string | null>(null);
    if (role === "viewer")
        return null;
    async function submit(e: FormEvent) {
        e.preventDefault();
        setPending(true);
        setError("");
        const isAsset = mode === "asset";
        const isValuation = mode === "valuation";
        const idempotencyKey = retryKey ?? crypto.randomUUID();
        if (!isAsset && !isValuation)
            setRetryKey(idempotencyKey);
        const payload = isAsset ? { type, name, symbol: symbol || null, quantity, unit_price: unitPrice } : isValuation ? { unit_price: unitPrice } : { type: direction, quantity, unit_price: unitPrice, idempotency_key: idempotencyKey };
        const uri = isAsset ? `/api/portfolios/${portfolioId}/assets` : isValuation ? `/api/portfolios/${portfolioId}/assets/${assetId}/valuation` : `/api/portfolios/${portfolioId}/assets/${assetId}/transactions`;
        try {
            const r = await fetch(uri, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
            if (!r.ok) {
                const j = await r.json().catch(() => ({}));
                throw new Error(j.message || JSON.stringify(j.errors) || "Unable to save");
            }
            setMode(null);
            setRetryKey(null);
            setName("");
            setSymbol("");
            setQuantity("1");
            onChange();
        }
        catch (err) {
            setError(err instanceof Error ? err.message : "Error saving data");
        }
        finally {
            setPending(false);
        }
    }
    return <><div className="action-row"><button className="secondary" onClick={() => { setError(""); setRetryKey(null); setMode("valuation"); }}>Update valuation</button><button className="secondary" onClick={() => { setError(""); setRetryKey(null); setMode("transaction"); }}><PlusCircle size={16}/>Record transaction</button><button className="primary" onClick={() => { setError(""); setRetryKey(null); setMode("asset"); }}><PlusCircle size={16}/>Add asset</button></div>
 {mode && <div className="modal-backdrop" onMouseDown={e => {
                if (e.target === e.currentTarget)
                    setMode(null);
            }}><div className="modal" role="dialog" aria-modal="true" aria-label={mode === "asset" ? "Add asset" : mode === "valuation" ? "Update valuation" : "Record transaction"}><div className="modal-head"><div><div className="eyebrow">PORTFOLIO WORKSPACE</div><h2>{mode === "asset" ? "Add a new asset" : mode === "valuation" ? "Update unit valuation" : "Record transaction"}</h2></div><button className="icon-button" aria-label="Close" onClick={() => setMode(null)}><X /></button></div><form onSubmit={submit} className="modal-form">
 {mode === "asset" ? <><label>Asset class<select value={type} onChange={e => setType(e.target.value)}><option value="stock">Equity</option><option value="crypto">Crypto</option><option value="real_estate">Real estate</option><option value="cash">Cash</option></select></label><label>Asset name<input required minLength={2} maxLength={120} value={name} onChange={e => setName(e.target.value)} placeholder="e.g. Global Equity Fund"/></label><label>Symbol (optional)<input value={symbol} maxLength={32} onChange={e => setSymbol(e.target.value)} placeholder="e.g. VOO"/></label></> : <><label>Asset<select required value={assetId} onChange={e => { setAssetId(e.target.value); setRetryKey(null); }}><option value="">Choose an asset</option>{assets.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}</select></label>{mode === "transaction" && <label>Operation<select value={direction} onChange={e => { setDirection(e.target.value); setRetryKey(null); }}><option value="buy">Increase holding (buy/deposit)</option><option value="sell">Decrease holding (sell/withdraw)</option></select></label>}</>}
 {mode !== "valuation" && <label>Quantity<input required type="number" min="0.00000001" step="0.00000001" value={quantity} onChange={e => { setQuantity(e.target.value); setRetryKey(null); }}/></label>}<label>Recorded unit valuation (USD)<input required type="number" min="0" step="0.00000001" value={unitPrice} onChange={e => { setUnitPrice(e.target.value); setRetryKey(null); }}/></label><p className="muted-note">Manual valuations only. No external price feed or real trades.</p>{error && <div className="alert" role="alert">{error}</div>}<button className="primary full" disabled={pending || (mode !== "asset" && !assetId)}>{pending ? "Saving…" : "Save record"}</button></form></div></div>}</>;
}

