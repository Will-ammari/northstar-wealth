import { NextRequest } from "next/server";
import { apiFetch, forward, checkOrigin } from "@/lib/backend";
export async function POST(req: NextRequest, { params }: {
    params: Promise<{
        id: string;
    }>;
}) {
    const invalid = checkOrigin(req);
    if (invalid)
        return invalid;
    const { id } = await params;
    if (!/^\d+$/.test(id))
        return Response.json({ message: "Invalid portfolio" }, { status: 400 });
    const body = await req.text();
    if (body.length > 5000)
        return Response.json({ message: "Too large" }, { status: 413 });
    return forward(await apiFetch(`/portfolios/${id}/assets`, { method: "POST", body }));
}

