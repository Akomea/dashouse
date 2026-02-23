"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

const quickActions = [
  { href: "/admin/menu", icon: "➕", title: "Add Menu Item", desc: "Add dishes, drinks, or snacks to your menu" },
  { href: "/admin/photos", icon: "🖼", title: "Upload Photos", desc: "Add images for menu items and categories" },
  { href: "/admin/categories", icon: "🏷", title: "Manage Categories", desc: "Organize your menu with categories" },
  { href: "/admin/gift-shop", icon: "🎁", title: "Gift Shop Manager", desc: "Manage merchandise and products" },
  { href: "/admin/business-info", icon: "🏢", title: "Business Information", desc: "Update contact and operating hours" },
  { href: "/admin/settings", icon: "⚙", title: "Settings", desc: "Site settings and configuration" }
];

export default function AdminDashboardPage() {
  const [stats, setStats] = useState({ menu: 0, photos: 0, categories: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      fetch("/api/menu-items?is_active=1").then((r) => r.json()),
      fetch("/api/photos").then((r) => r.json()),
      fetch("/api/categories?is_active=1").then((r) => r.json())
    ])
      .then(([menuRes, photosRes, categoriesRes]) => {
        setStats({
          menu: menuRes?.data?.length ?? 0,
          photos: photosRes?.data?.length ?? 0,
          categories: categoriesRes?.data?.length ?? 0
        });
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  return (
    <>
      <div className="admin-page-header">
        <h2>◫ Dashboard</h2>
        <span style={{ color: "#6c757d", fontSize: "0.9rem" }}>Welcome back, Admin!</span>
      </div>

      <div className="admin-stats-grid">
        <div className="admin-stat-card">
          <span className="icon">🍴</span>
          <h3>{loading ? "…" : stats.menu}</h3>
          <p>Menu Items</p>
        </div>
        <div className="admin-stat-card">
          <span className="icon">🖼</span>
          <h3>{loading ? "…" : stats.photos}</h3>
          <p>Photos</p>
        </div>
        <div className="admin-stat-card">
          <span className="icon">🏷</span>
          <h3>{loading ? "…" : stats.categories}</h3>
          <p>Categories</p>
        </div>
      </div>

      <h3 style={{ marginBottom: "1rem" }}>Quick Actions</h3>
      <div className="admin-quick-actions">
        {quickActions.map(({ href, icon, title, desc }) => (
          <div key={href} className="admin-quick-card" style={{ background: "linear-gradient(135deg, #667eea, #764ba2)", color: "white" }}>
            <span className="icon">{icon}</span>
            <h4 style={{ margin: "0 0 0.25rem" }}>{title}</h4>
            <p style={{ margin: 0, fontSize: "0.9rem", opacity: 0.9 }}>{desc}</p>
            <Link href={href} style={{ background: "white", color: "#333" }}>
              Open
            </Link>
          </div>
        ))}
      </div>

      <div className="admin-card" style={{ marginTop: "1.5rem" }}>
        <h5 style={{ margin: "0 0 0.5rem" }}>Recent Activity</h5>
        <p style={{ margin: 0, color: "#6c757d" }}>No recent activity</p>
      </div>
    </>
  );
}
