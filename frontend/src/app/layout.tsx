import type { Metadata } from "next";
import "./globals.css";
export const metadata: Metadata = { title: "Northstar | Portfolio intelligence", description: "Secure multi-asset wealth tracking" };
export default function RootLayout({ children }: {
    children: React.ReactNode;
}) { return <html lang="en"><body>{children}</body></html>; }

