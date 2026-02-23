"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";

const navItems: { href: string; label: string; icon: string }[] = [
  { href: "/admin/dashboard", label: "Dashboard", icon: "◫" },
  { href: "/admin/menu", label: "Menu", icon: "🍴" },
  { href: "/admin/categories", label: "Categories", icon: "🏷" },
  { href: "/admin/photos", label: "Photos", icon: "🖼" },
  { href: "/admin/gift-shop", label: "Gift Shop", icon: "🎁" },
  { href: "/admin/business-info", label: "Business Info", icon: "🏢" },
  { href: "/admin/settings", label: "Settings", icon: "⚙" }
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
      <aside className="admin-sidebar">
        <div className="admin-sidebar-brand">
          <span className="admin-sidebar-brand-icon" aria-hidden>🐱</span>
          <h5>Das House</h5>
          <small>Admin Panel</small>
        </div>
        <nav className="admin-sidebar-nav">
          {navItems.map(({ href, label, icon }) => (
            <Link
              key={href}
              href={href}
              data-active={pathname === href}
              aria-current={pathname === href ? "page" : undefined}
            >
              <span className="nav-icon">{icon}</span>
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
