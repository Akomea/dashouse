"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";

const navItems: { href: string; label: string }[] = [
  { href: "/admin/dashboard", label: "Dashboard" },
  { href: "/admin/menu", label: "Menu" },
  { href: "/admin/categories", label: "Categories" },
  { href: "/admin/photos", label: "Photos" },
  { href: "/admin/gift-shop", label: "Gift Shop" },
  { href: "/admin/business-info", label: "Business Info" },
  { href: "/admin/settings", label: "Settings" }
];

export function AdminShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const isLogin = pathname === "/admin";

  if (isLogin) {
    return <div className="admin-login-wrap">{children}</div>;
  }

  return (
    <div className="admin-root">
      <aside className="admin-sidebar ">
        <div className="admin-sidebar-brand text-center">
          <h3 className="text-white">Admin Panel</h3>
        </div>
        <nav className="admin-sidebar-nav">
          {navItems.map(({ href, label }) => (
            <Link
              key={href}
              href={href}
              data-active={pathname === href}
              aria-current={pathname === href ? "page" : undefined}
            >
              {label}
            </Link>
          ))}
        </nav>
        <div className="admin-sidebar-logout">
          <button
            type="button"
            onClick={async () => {
              await fetch("/api/admin/logout", { method: "POST" });
              router.push("/admin");
            }}
          >
            Logout
          </button>
        </div>
      </aside>
      <main className="admin-main">
        <div className="admin-main-content">{children}</div>
      </main>
    </div>
  );
}
