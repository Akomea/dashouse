"use client";

import { usePathname } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { GotoTop } from "@/components/goto-top";
import { LanguageProvider } from "@/lib/i18n/language-context";

export function SiteLayoutWrapper({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const isAdmin = pathname?.startsWith("/admin");

  if (isAdmin) {
    return <>{children}</>;
  }

  return (
    <LanguageProvider>
      <SiteHeader />
      {children}
      <SiteFooter />
      <GotoTop />
    </LanguageProvider>
  );
}
