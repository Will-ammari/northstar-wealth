"use client";
import { useState, useRef } from "react";
import { Sparkles, Send, ShieldCheck, Bot, UserRound } from "lucide-react";
interface Message {
    role: "assistant" | "user";
    text: string;
}
export default function Assistant({ portfolioId }: {
    portfolioId: number;
}) {
    const [messages, setMessages] = useState<Message[]>([{ role: "assistant", text: "Hello. I can explain your portfolio's recorded valuation and holdings. What would you like to know?" }]);
    const [draft, setDraft] = useState("");
    const [waiting, setWaiting] = useState(false);
    const end = useRef<HTMLDivElement | null>(null);
    async function ask(message?: string) {
        const text = (message ?? draft).trim();
        if (!text || waiting)
            return;
        setDraft("");
        setWaiting(true);
        setMessages(p => [...p, { role: "user", text }, { role: "assistant", text: "" }]);
        try {
            const r = await fetch(`/api/portfolios/${portfolioId}/assistant`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ message: text }) });
            if (!r.ok) {
                const data = await r.json().catch(() => ({}));
                throw new Error(data.message || "Assistant unavailable");
            }
            const reader = r.body?.getReader();
            if (!reader)
                throw new Error("Streaming unavailable");
            const decoder = new TextDecoder();
            let buffer = "";
            let accumulated = "";
            while (true) {
                const { done, value } = await reader.read();
                if (done)
                    break;
                buffer += decoder.decode(value, { stream: true });
                let boundary;
                while ((boundary = buffer.indexOf("\n\n")) >= 0) {
                    const part = buffer.slice(0, boundary);
                    buffer = buffer.slice(boundary + 2);
                    const event = part.match(/^event: (.+)$/m)?.[1];
                    const payload = part.match(/^data: (.+)$/m)?.[1];
                    if (!payload)
                        continue;
                    let data: {
                        text?: string;
                        message?: string;
                    } = {};
                    try {
                        data = JSON.parse(payload);
                    }
                    catch {
                        continue;
                    }
                    if (event === "token" && data.text) {
                        accumulated += data.text;
                        setMessages(p => p.map((m, i) => i === p.length - 1 ? { ...m, text: accumulated } : m));
                        end.current?.scrollIntoView({ behavior: "smooth" });
                    }
                    if (event === "error")
                        throw new Error(data.message || "Assistant error");
                }
            }
            if (!accumulated)
                setMessages(p => p.map((m, i) => i === p.length - 1 ? { ...m, text: "No answer was returned." } : m));
        }
        catch (err) {
            setMessages(p => p.map((m, i) => i === p.length - 1 ? { ...m, text: err instanceof Error ? err.message : "Assistant failed" } : m));
        }
        finally {
            setWaiting(false);
            end.current?.scrollIntoView({ behavior: "smooth" });
        }
    }
    return <section className="assistant" aria-label="Portfolio AI assistant"><div className="assistant-header"><span className="assistant-icon"><Sparkles size={19}/></span><div><strong>Portfolio assistant</strong><small>Read-only function calling · no LangChain</small></div><span className="status-dot" title="Read only"/></div><div className="chat-scroll" aria-live="polite">{messages.map((m, i) => <div key={i} className={`chat-message ${m.role}`}><span className="chat-avatar">{m.role === "assistant" ? <Bot size={15}/> : <UserRound size={15}/>}</span><p>{m.text || "Thinking…"}</p></div>)}<div ref={end}/></div><div className="suggestions">{["Summarize my allocation", "Which assets are largest?"].map(q => <button type="button" key={q} disabled={waiting} onClick={() => ask(q)}>{q}</button>)}</div><form className="chat-form" onSubmit={e => { e.preventDefault(); void ask(); }}><input aria-label="Ask the assistant" placeholder="Ask about your portfolio…" value={draft} maxLength={600} onChange={e => setDraft(e.target.value)}/><button aria-label="Send question" disabled={waiting || !draft.trim()}><Send size={17}/></button></form><p className="assistant-disclaimer"><ShieldCheck size={13}/>Read-only · Private by role · No investment advice</p></section>;
}

