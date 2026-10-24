"use client";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { ChartNoAxesCombined, LayoutDashboard, Wallet, History, Shield, ChevronDown, LogOut, ArrowUpRight, ArrowDownLeft, TrendingUp, Landmark, Bitcoin, Banknote, Search, Bell, Menu, X, CircleHelp, Clock3 } from "lucide-react";
import Assistant from "@/components/Assistant";
import { PortfolioForms } from "@/components/PortfolioForms";
import type { Dashboard, PortfolioLink, SessionUser, AssetType } from "@/lib/types";
import { usd, qty, assetLabels, palette } from "@/lib/format";
const icons = { stock: TrendingUp, crypto: Bitcoin, real_estate: Landmark, cash: Banknote };
function LabelIcon({ type }: {
    type: AssetType;
}) { const Icon = icons[type]; return <span className={`asset-icon ${type}`}><Icon size={19}/></span>; }
export default function Home() {
    const router = useRouter();
    const [user, setUser] = useState<SessionUser | null>(null);
    const [links, setLinks] = useState<PortfolioLink[]>([]);
    const [chosen, setChosen] = useState<number | null>(null);
    const [data, setData] = useState<Dashboard | null>(null);
    const [busy, setBusy] = useState(true);
    const [error, setError] = useState("");
    const [openMenu, setOpenMenu] = useState(false);
    const [refreshToken, setRefreshToken] = useState(0);
    useEffect(() => { (async () => { try {
        const [u, p] = await Promise.all([fetch('/api/session', { cache: 'no-store' }), fetch('/api/portfolios', { cache: 'no-store' })]);
        if (u.status === 401 || p.status === 401) {
            router.replace('/login');
            return;
        }
        if (!u.ok || !p.ok)
            throw new Error('Unable to connect to your workspace.');
        const ui = await u.json();
        const pi = await p.json();
        setUser(ui);
        setLinks(pi.data);
        setChosen(pi.data[0]?.id ?? null);
        if (pi.data.length === 0) setBusy(false);
    }
    catch (e) {
        setError(e instanceof Error ? e.message : 'Unable to load');
        setBusy(false);
    } })(); }, [router]);
  
    // Fetch portfolio data when the selected portfolio changes or the user requests a refresh.
    // The AbortController prevents stale requests from overwriting newer portfolio data.
    useEffect(() => {
        if (chosen === null) return;

        const controller = new AbortController();
        void fetch(`/api/portfolios/${chosen}`, {
            cache: 'no-store',
            signal: controller.signal,
        })
            .then(async response => {
                if (response.status === 401) {
                    router.replace('/login');
                    return null;
                }
                if (!response.ok) {
                    throw new Error('Portfolio could not be loaded.');
                }
                return (await response.json()) as { data: Dashboard };
            })
            .then(payload => {
                if (controller.signal.aborted || payload === null) return;
                setData(payload.data);
                setError('');
            })
            .catch(err => {
                if (controller.signal.aborted) return;
                setData(null);
                setError(err instanceof Error ? err.message : 'Error loading');
            })
            .finally(() => {
                if (!controller.signal.aborted) setBusy(false);
            });

        return () => controller.abort();
    }, [chosen, router, refreshToken]);

    function reload() {
        setBusy(true);
        setError('');
        setRefreshToken(token => token + 1);
    }

    const current = links.find(l => l.id === chosen);
    const value = Number(data?.total_value ?? 0);
    const pieces = (data?.allocation ?? []).map(a => ({ ...a, pct: value > 0 ? Number(a.value) / value * 100 : 0 }));
    async function logout() { await fetch('/api/session/logout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' }); router.replace('/login'); }
    return <div className="app-shell"><aside className={`sidebar ${openMenu ? 'open' : ''}`}><div className="side-brand"><span className="brand-mark"><ChartNoAxesCombined size={19}/></span><span>NORTHSTAR <small>WEALTH</small></span></div><div className="side-label">WORKSPACE</div><nav className="side-nav" aria-label="Primary"><a className="nav-item active" href="#overview"><LayoutDashboard size={18}/>Overview</a><a className="nav-item" href="#holdings"><Wallet size={18}/>Holdings</a><a className="nav-item" href="#activity"><History size={18}/>Activity</a><a className="nav-item" href="#assistant"><Shield size={18}/>AI Assistant</a></nav><div className="sidebar-bottom"><div className="support-card"><div className="support-icon"><CircleHelp size={18}/></div><strong>A clearer picture.</strong><p>Thoughtful insights for your entire portfolio.</p><span>Private by design <ArrowUpRight size={13}/></span></div><button className="nav-item signout" onClick={logout}><LogOut size={17}/>Sign out</button><div className="sidebar-user"><span className="avatar">{user?.name?.slice(0, 1) || 'N'}</span><div><strong>{user?.name || 'Workspace'}</strong><small>{current?.role || '—'} access</small></div></div></div></aside>
 <div className="main-area"><header className="topbar"><div className="mobile-brand"><button className="icon-button" onClick={() => setOpenMenu(!openMenu)} aria-label="Toggle navigation">{openMenu ? <X /> : <Menu />}</button><span className="brand-mark"><ChartNoAxesCombined size={16}/></span></div><div className="breadcrumbs">Workspace <span>/</span> <strong>Overview</strong></div><div className="topbar-actions"><div className="search-static"><Search size={16}/>Portfolio dashboard</div><span className="top-divider"/><span className="date-indicator"><Clock3 size={15}/>Snapshot view</span><button className="icon-button" aria-label="Notifications not available" disabled><Bell size={18}/></button></div></header>
 <main className="content" id="overview"><div className="page-head"><div><div className="eyebrow">WEALTH OVERVIEW <span className="eyebrow-dot"/> MANUAL VALUATIONS</div><h1>Portfolio overview<span className="headline-accent">.</span></h1><p>A focused view of your holdings, allocation, and activity.</p></div><div className="portfolio-picker"><span>PORTFOLIO</span><select aria-label="Select portfolio" value={chosen ?? ''} 
onChange={e => {
    setBusy(true);
    setError('');
    setData(null);
    setChosen(Number(e.target.value));
}}
>{links.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}</select><ChevronDown size={16}/></div></div>
 {error && <div role="alert" className="alert">{error} <button onClick={reload}>Retry</button></div>}
 {busy ? <div className="loading-grid" aria-label="Loading portfolio"><div className="skeleton large"/><div className="skeleton"/><div className="skeleton"/></div> : !data ? <div className="empty">No portfolio access yet.</div> : <>
 <div className="overview-grid"><section className="total-card"><div className="total-card-content"><div className="metric-label">TOTAL PORTFOLIO VALUE <span className="snapshot-pill"><span />Recorded prices</span></div><div className="total-number">{usd(data.total_value)}</div><div className="metric-sub">Across {data.asset_count} assets · USD base currency</div><div className="card-divider"/><div className="total-footer"><div><span>VIEW ACCESS</span><strong>{current?.role.toUpperCase()}</strong></div><div><span>VALUATION</span><strong>Manual snapshot</strong></div></div></div><div className="total-card-decor" aria-hidden="true"><svg viewBox="0 0 310 180"><path d="M0 150 C35 140 55 115 80 126 S125 140 150 94 S205 125 232 73 S280 62 310 10" stroke="currentColor" fill="none" strokeWidth="2"/><path d="M0 150 C35 140 55 115 80 126 S125 140 150 94 S205 125 232 73 S280 62 310 10 V180 H0Z" fill="currentColor" opacity="0.06"/></svg></div></section>
 <section className="stat-card"><div className="metric-label">ASSET CLASSES</div><div className="stat-number">{pieces.length.toString().padStart(2, '0')}</div><div className="stat-sub"><span className="stat-icon"><Wallet size={17}/></span><span>Tracked asset classes</span></div><div className="stat-foot">Built for multi-asset clarity <ArrowUpRight size={16}/></div></section>
 <section className="stat-card"><div className="metric-label">RECENT ACTIVITY</div><div className="stat-number">{data.recent_transactions.length.toString().padStart(2, '0')}</div><div className="stat-sub"><span className="stat-icon peach"><History size={17}/></span><span>Latest recorded events</span></div><div className="stat-foot">No external trades executed <ArrowUpRight size={16}/></div></section></div>
 <div className="below-grid"><div className="left-column"><section className="panel" id="holdings"><div className="panel-heading"><div><div className="eyebrow">YOUR ASSETS</div><h2>Holdings <span className="count-badge">{data.asset_count}</span></h2></div><PortfolioForms portfolioId={data.portfolio.id} role={current?.role ?? 'viewer'} assets={data.assets} onChange={reload}/></div><div className="table-scroll"><table><thead><tr><th>ASSET</th><th>CLASS</th><th className="num">QUANTITY</th><th className="num">UNIT VALUE</th><th className="num">MARKET VALUE</th></tr></thead><tbody>{data.assets.map(a => <tr key={a.id}><td><div className="asset-cell"><LabelIcon type={a.type}/><div><strong>{a.name}</strong><small>{a.symbol || 'Private asset'}</small></div></div></td><td><span className="class-label">{assetLabels[a.type]}</span></td><td className="num">{qty(a.quantity)}</td><td className="num">{usd(a.unit_price)}</td><td className="num strong">{usd(a.market_value)}</td></tr>)}</tbody></table></div><div className="panel-foot">Values are snapshots, not real-time quotes. Asset quantity and monetary totals are calculated server-side.</div></section>
 <section className="panel activity" id="activity"><div className="panel-heading"><div><div className="eyebrow">TRANSACTION LEDGER</div><h2>Recent activity</h2></div><span className="muted-tag">Latest 10</span></div><div className="activity-list">{data.recent_transactions.length === 0 ? <p className="empty">No transactions yet.</p> : data.recent_transactions.map(t => <div className="activity-row" key={t.id}><span className={`transaction-icon ${t.type}`}>{t.type === 'buy' ? <ArrowDownLeft size={18}/> : <ArrowUpRight size={18}/>}</span><div className="activity-copy"><strong>{t.type === 'buy' ? 'Holding increased' : 'Holding reduced'} · {t.asset_name}</strong><small>{new Date(t.executed_at).toLocaleString()} · {qty(t.quantity)} units</small></div><div className="activity-value">{t.type === 'buy' ? '+' : '−'}{usd(String(Number(t.quantity) * Number(t.unit_price)))}</div></div>)}</div></section></div>
 <div className="right-column"><section className="panel allocation"><div className="panel-heading"><div><div className="eyebrow">DISTRIBUTION</div><h2>Asset allocation</h2></div><span className="muted-tag">USD</span></div><div className="alloc-chart"><div className="donut" style={{ background: pieces.length ? `conic-gradient(${pieces.reduce<{
                start: number;
                stops: string[];
            }>((acc, a) => { acc.stops.push(`${palette[a.type]} ${acc.start}% ${acc.start + a.pct}%`); acc.start += a.pct; return acc; }, { start: 0, stops: [] }).stops.join(',')})` : 'var(--line)' }}><div className="donut-hole"><small>ASSET CLASSES</small><strong>{pieces.length}</strong></div></div></div><div className="legend">{pieces.map(a => <div className="legend-row" key={a.type}><span className="legend-dot" style={{ background: palette[a.type] }}/><span>{assetLabels[a.type]}</span><strong>{a.pct.toFixed(1)}%</strong></div>)}</div></section><div id="assistant"><Assistant key={chosen} portfolioId={data.portfolio.id}/></div></div></div></>}
 <footer className="app-footer">NORTHSTAR WEALTH <span>•</span> ENGINEERING ASSESSMENT PROTOTYPE <span>•</span> NOT FINANCIAL ADVICE</footer></main></div></div>;
}

