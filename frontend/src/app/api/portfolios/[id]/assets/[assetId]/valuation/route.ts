import { NextRequest } from "next/server";
import { apiFetch, forward, checkOrigin } from "@/lib/backend";
export async function POST(req: NextRequest, { params }: {
    params: Promise<{
        id: string;
        assetId: string;
    }>;
}) {
    const invalid = checkOrigin(req);
    if (invalid)
        return invalid;
    const { id, assetId } = await params;
    if (!/^\d+$/.test(id) || !/^\d+$/.test(assetId))
        return Response.json({ message: "Invalid ID" }, { status: 400 });
    const body = await req.text();
    if (body.length > 5000)
        return Response.json({ message: "Too large" }, { status: 413 });
    return forward(await apiFetch(`/portfolios/${id}/assets/${assetId}/valuation`, { method: "POST", body }));
}

