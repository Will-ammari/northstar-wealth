export type Role = "owner" | "editor" | "viewer";
export type AssetType = "stock" | "crypto" | "real_estate" | "cash";
export interface PortfolioLink {
    id: number;
    name: string;
    base_currency: string;
    role: Role;
}
export interface Asset {
    id: number;
    name: string;
    symbol: string | null;
    type: AssetType;
    quantity: string;
    unit_price: string;
    market_value: string;
    valued_at: string;
}
export interface Allocation {
    type: AssetType;
    value: string;
}
export interface Transaction {
    id: number;
    type: "buy" | "sell";
    asset_name: string;
    quantity: string;
    unit_price: string;
    executed_at: string;
}
export interface Dashboard {
    portfolio: {
        id: number;
        name: string;
        base_currency: string;
    };
    total_value: string;
    asset_count: number;
    allocation: Allocation[];
    assets: Asset[];
    recent_transactions: Transaction[];
}
export interface ApiEnvelope<T> {
    data: T;
}
export interface SessionUser {
    id: number;
    name: string;
    email: string;
}

