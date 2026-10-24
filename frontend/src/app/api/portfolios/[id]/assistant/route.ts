import { NextRequest } from "next/server";
import { cookies } from "next/headers";
import { apiFetch, checkOrigin, readSessionToken } from "@/lib/backend";
import { streamPortfolioAnswer } from "@/lib/llm";

export const runtime = "nodejs";

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const rejected = checkOrigin(req);
  if (rejected) return rejected;
  const { id } = await params;
  if (!/^[1-9]\d*$/.test(id)) return Response.json({ message: "Invalid portfolio" }, { status: 400 });
  const token = readSessionToken((await cookies()).get("wp_session")?.value);
  if (!token) return Response.json({ message: "Sign in first" }, { status: 401 });
  if (!process.env.OPENAI_API_KEY) {
    return Response.json({ message: "Assistant requires OPENAI_API_KEY in frontend/.env.local" }, { status: 503 });
  }
  let body: unknown;
  try { body = await req.json(); } catch { return Response.json({ message: "Invalid JSON" }, { status: 400 }); }
  const message = typeof body === "object" && body !== null && "message" in body ? body.message : undefined;
  if (typeof message !== "string" || !message.trim() || message.length > 600) {
    return Response.json({ message: "Message must be 1-600 characters" }, { status: 422 });
  }
  // Auth, membership and a per-identity throttle are checked BEFORE costly model work.
  const authorized = await apiFetch(`/portfolios/${id}/assistant/authorize`, { method: "POST", body: "{}" }, token);
  if (!authorized.ok) {
    return Response.json({ message: authorized.status === 429 ? "Assistant limit reached" : "Portfolio access denied" },
      { status: authorized.status });
  }

  const encoder = new TextEncoder();
  const stream = new ReadableStream<Uint8Array>({
    async start(controller) {
      const send = (event: string, data: object) => {
        if (!req.signal.aborted) controller.enqueue(encoder.encode(`event: ${event}\ndata: ${JSON.stringify(data)}\n\n`));
      };
      try {
        send("status", { message: "Retrieving authorized portfolio context" });
        await streamPortfolioAnswer(message.trim(), id, token, delta => send("token", { text: delta }));
        send("done", {});
      } catch {
        send("error", { message: "Assistant could not complete this request. Please retry." });
      } finally {
        controller.close();
      }
    },
  });
  return new Response(stream, {
    headers: { "Content-Type": "text/event-stream; charset=utf-8", "Cache-Control": "no-store, no-transform",
      "X-Accel-Buffering": "no", "X-Content-Type-Options": "nosniff" },
  });
}
