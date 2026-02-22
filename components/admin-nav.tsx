"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";

const links = [
  ["/admin/dashboard", "Dashboard"],
  ["/admin/menu", "Menu"],
  ["/admin/categories", "Categories"],
  ["/admin/photos", "Photos"],
  ["/admin/gift-shop", "Gift Shop"],
  ["/admin/business-info", "Business Info"],
  ["/admin/settings", "Settings"]
] as const;

export function AdminNav() {
  const pathname = usePathname();
  const router = useRouter();

  return (
    <div className="card">
      <div style={{ display: "flex", gap: 12, flexWrap: "wrap" }}>
        {links.map(([href, label]) => (
          <Link key={href} href={href} style={{ opacity: pathname === href ? 1 : 0.75 }}>
            {label}
          </Link>
        ))}
        <button
          style={{ width: "auto", margin: 0, padding: "6px 12px" }}
          onClick={async () => {
            await fetch("/api/admin/logout", { method: "POST" });
            router.push("/admin");
          }}
        >
          Logout
        </button>
      </div>
    </div>
  );
}
