import Link from "next/link";
import { AdminNav } from "@/components/admin-nav";

const cards = [
  ["/admin/menu", "Manage menu items"],
  ["/admin/categories", "Manage categories and category images"],
  ["/admin/photos", "Manage photo metadata and category image assignment"],
  ["/admin/gift-shop", "Manage gift shop products"],
  ["/admin/business-info", "Update contact and operational details"],
  ["/admin/settings", "Site settings values"]
] as const;

export default function AdminDashboardPage() {
  return (
    <main>
      <h1>Admin Dashboard</h1>
      <AdminNav />
      <div className="grid cols-2">
        {cards.map(([href, desc]) => (
          <div key={href} className="card">
            <Link href={href}>{href.replace("/admin/", "").replace("-", " ")}</Link>
            <p className="muted">{desc}</p>
          </div>
        ))}
      </div>
    </main>
  );
}
