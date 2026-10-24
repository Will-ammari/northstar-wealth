import { cookies } from "next/headers";
import { NextRequest, NextResponse } from "next/server";
import { createCipheriv, createDecipheriv, randomBytes } from "node:crypto";
const host = process.env.API_BASE_URL ?? "http://127.0.0.1:8000/api";
const COOKIE = "wp_session";
const cookieOptions = { httpOnly: true, secure: process.env.NODE_ENV === "production", sameSite: "strict" as const, path: "/", maxAge: 60 * 60 * 8 };
export async function apiFetch(path: string, init: RequestInit = {}, tokenOverride?: string): Promise<Response> {
    const token = tokenOverride ?? readSessionToken((await cookies()).get(COOKIE)?.value);
    if (!token)
        return Response.json({ message: "Authentication required" }, { status: 401 });
    if (!path.startsWith("/") || path.startsWith("//") || path.includes("..") || path.includes("?") || path.includes("#")) {
        return Response.json({ message: "Invalid API path" }, { status: 400 });
    }
    try {
        return await fetch(`${host}${path}`, {
            ...init, cache: "no-store", redirect: "manual",
            headers: { "Accept": "application/json", "Authorization": `Bearer ${token}`,
                ...(init.body ? { "Content-Type": "application/json" } : {}), ...init.headers },
            signal: AbortSignal.timeout(12000),
        });
    }
    catch {
        return Response.json({ message: "Backend is unavailable" }, { status: 502 });
    }
}
export async function forward(response: Response): Promise<NextResponse> {
    const body = await response.text();
    return new NextResponse(body, { status: response.status, headers: { "Content-Type": "application/json", "Cache-Control": "no-store" } });
}
export function checkOrigin(request: NextRequest): NextResponse | null {
    const expected = process.env.APP_ORIGIN;
    if (!expected)
        return NextResponse.json({ message: "APP_ORIGIN is not configured" }, { status: 503 });
    if (request.headers.get("origin") !== expected)
        return NextResponse.json({ message: "Origin rejected" }, { status: 403 });
    if (!request.headers.get("content-type")?.startsWith("application/json"))
        return NextResponse.json({ message: "JSON required" }, { status: 415 });
    return null;
}
function getKey(): Buffer {
    const hex = process.env.BFF_SESSION_SECRET;
    if (!hex || !/^[a-fA-F0-9]{64}$/.test(hex))
        throw new Error("BFF_SESSION_SECRET must be 32 random bytes (hex)");
    return Buffer.from(hex, "hex");
}
export function readSessionToken(value?: string): string | undefined {
    if (!value)
        return undefined;
    try {
        const raw = Buffer.from(value, "base64url");
        if (raw.length < 12 + 16 + 1)
            return undefined;
        const iv = raw.subarray(0, 12);
        const tag = raw.subarray(12, 28);
        const ciphertext = raw.subarray(28);
        const decipher = createDecipheriv("aes-256-gcm", getKey(), iv);
        decipher.setAuthTag(tag);
        return Buffer.concat([decipher.update(ciphertext), decipher.final()]).toString("utf8");
    }
    catch {
        return undefined;
    }
}
export function setSession(response: NextResponse, value: string) {
    const iv = randomBytes(12);
    const cipher = createCipheriv("aes-256-gcm", getKey(), iv);
    const ciphertext = Buffer.concat([cipher.update(value, "utf8"), cipher.final()]);
    const sealed = Buffer.concat([iv, cipher.getAuthTag(), ciphertext]).toString("base64url");
    response.cookies.set(COOKIE, sealed, cookieOptions);
}
export function clearSession(response: NextResponse) { response.cookies.set(COOKIE, "", { ...cookieOptions, maxAge: 0 }); }

