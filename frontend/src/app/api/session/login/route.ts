import { NextRequest, NextResponse } from "next/server";
import { checkOrigin, setSession } from "@/lib/backend";
export async function POST(req: NextRequest) {
    const invalid = checkOrigin(req);
    if (invalid)
        return invalid;
    let body: unknown;
    try {
        body = await req.json();
    }
    catch {
        return NextResponse.json({ message: "Invalid JSON" }, { status: 400 });
    }
    if (!body || typeof body !== "object" || !("email" in body) || !("password" in body) ||
        typeof body.email !== "string" || typeof body.password !== "string" || body.email.length > 254 || body.password.length > 200) {
        return NextResponse.json({ message: "Invalid login data" }, { status: 422 });
    }
    let r: Response;
    try {
        r = await fetch(`${process.env.API_BASE_URL ?? "http://127.0.0.1:8000/api"}/login`, { method: "POST", headers: { "Accept": "application/json", "Content-Type": "application/json" }, body: JSON.stringify(body), cache: "no-store", signal: AbortSignal.timeout(10000) });
    }
    catch {
        return NextResponse.json({ message: "Backend unavailable" }, { status: 502 });
    }
    const data = await r.json().catch(() => ({ message: "Malformed backend response" }));
    if (!r.ok)
        return NextResponse.json({ message: "Invalid credentials" }, { status: r.status === 422 ? 422 : 502 });
    if (typeof data.token !== "string" || !data.user)
        return NextResponse.json({ message: "Invalid backend response" }, { status: 502 });
    const res = NextResponse.json({ user: data.user });
    setSession(res, data.token);
    return res;
}

