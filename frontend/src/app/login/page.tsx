"use client";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { ChartNoAxesCombined, ArrowUpRight, ShieldCheck, LockKeyhole } from "lucide-react";
export default function LoginPage() {
    const router = useRouter();
    const [email, setEmail] = useState("owner@example.test");
    const [password, setPassword] = useState("DemoPass123!");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    async function submit(e: FormEvent) {
        e.preventDefault();
        setLoading(true);
        setError("");
        try {
            const r = await fetch("/api/session/login", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ email, password }) });
            if (!r.ok)
                throw new Error("Login failed. Check your credentials or backend connection.");
            router.push("/");
            router.refresh();
        }
        catch (err) {
            setError(err instanceof Error ? err.message : "Login failed");
        }
        finally {
            setLoading(false);
        }
    }
    return <main className="login-page"><div className="login-feature"><div className="login-brand"><span className="brand-mark"><ChartNoAxesCombined size={21}/></span>NORTHSTAR <span className="brand-thin">WEALTH</span></div>
  <div className="login-hero"><div className="eyebrow">WEALTH, WITH CLARITY</div><h1>Every asset.<br /><em>One clear picture.</em></h1><p>Manage your family&apos;s portfolio with permission-aware access and thoughtful AI assistance.</p><div className="login-metrics"><span>04 <small>ASSET CLASSES</small></span><span>03 <small>ACCESS ROLES</small></span><span>01 <small>SECURE VIEW</small></span></div></div><p className="login-foot">An engineering prototype · Manual valuations · Not financial advice</p>
 </div><div className="login-panel"><div className="login-card"><span className="tiny-icon"><LockKeyhole size={18}/></span><div className="eyebrow">SECURE WORKSPACE</div><h2>Welcome back.</h2><p>Sign in to your portfolio dashboard.</p><form onSubmit={submit}><label>Email address<input type="email" autoComplete="username" required value={email} onChange={e => setEmail(e.target.value)}/></label><label>Password<input type="password" autoComplete="current-password" required value={password} onChange={e => setPassword(e.target.value)}/></label>{error && <div className="alert" role="alert">{error}</div>}<button type="submit" className="primary full" disabled={loading}>{loading ? "Signing in…" : "Open dashboard"}<ArrowUpRight size={18}/></button></form><div className="login-hint"><ShieldCheck size={17}/>Local demo accounts: owner / editor / viewer / outsider @example.test, password DemoPass123!</div></div></div></main>;
}

