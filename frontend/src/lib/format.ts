// Display-only conversion. MySQL performs ALL authoritative financial arithmetic in NUMERIC.
export function usd(value: string | number): string {
    const n = Number(value);
    return Number.isFinite(n) ? new Intl.NumberFormat("en-US", { style: "currency", currency: "USD", maximumFractionDigits: 2 }).format(n) : "—";
}
export function qty(value: string): string {
    const n = Number(value);
    return Number.isFinite(n) ? new Intl.NumberFormat("en-US", { maximumFractionDigits: 8 }).format(n) : value;
}
export const assetLabels = { stock: "Equities", crypto: "Crypto", real_estate: "Real estate", cash: "Cash" } as const;
export const palette = { stock: "#6b8af7", crypto: "#b699ec", real_estate: "#e8ae6d", cash: "#56bfb6" } as const;

