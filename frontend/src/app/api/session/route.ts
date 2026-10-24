import { apiFetch, forward } from "@/lib/backend";
export async function GET() { return forward(await apiFetch("/me")); }

