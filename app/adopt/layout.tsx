import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Katzen-Adoption | Das House Katzenkaffee Wien 1060",
  description:
    "Adoptionsantrag für gerettete Katzen vom Das House in Wien 1060 — online ausfüllen oder PDF herunterladen.",
  openGraph: {
    title: "Katzen-Adoption | Das House Katzenkaffee Wien 1060",
    description:
      "Adoptionsantrag für gerettete Katzen vom Das House in Wien 1060 — online ausfüllen oder PDF herunterladen.",
    locale: "de_AT",
    type: "website",
  },
};

export default function AdoptLayout({ children }: { children: React.ReactNode }) {
  return children;
}
