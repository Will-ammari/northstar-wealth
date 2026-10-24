import { NextRequest, NextResponse } from "next/server";
import { apiFetch, checkOrigin, clearSession } from "@/lib/backend";
export async function POST(req: NextRequest) {
    const invalid = checkOrigin(req);
    if (invalid)
        return invalid;
    await apiFetch("/logout", { method: "POST", body: "{}" });
    const res = NextResponse.json({ ok: true });
    clearSession(res);
    return res;
}

