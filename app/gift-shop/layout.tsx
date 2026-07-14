import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Geschenkeladen | Das House Katzenkaffee Wien 1060",
  description:
    "Merchandise aus dem Das House Katzenkaffee in Wien 1060 — Tassen, Shirts und mehr. Unterstütze unsere Samtpfoten-Mission in Gumpendorf.",
  openGraph: {
    title: "Geschenkeladen | Das House Katzenkaffee Wien 1060",
    description:
      "Merchandise aus dem Das House Katzenkaffee in Wien 1060 — Tassen, Shirts und mehr.",
    locale: "de_AT",
    type: "website",
  },
};

export default function GiftShopLayout({ children }: { children: React.ReactNode }) {
  return children;
}
