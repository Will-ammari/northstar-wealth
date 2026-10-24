import { apiFetch, forward } from "@/lib/backend";
export async function GET(_req: Request, { params }: {
    params: Promise<{
        id: string;
    }>;
}) {
    const { id } = await params;
    if (!/^\d+$/.test(id))
        return Response.json({ message: "Invalid portfolio" }, { status: 400 });
    return forward(await apiFetch(`/portfolios/${id}`));
}

