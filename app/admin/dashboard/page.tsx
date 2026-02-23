"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

const quickActions = [
  { href: "/admin/menu", title: "Add Menu Item", desc: "Add dishes, drinks, or snacks to your menu" },
  { href: "/admin/photos", title: "Upload Photos", desc: "Add images for menu items and categories" },
  { href: "/admin/categories", title: "Manage Categories", desc: "Organize your menu with categories" },
  { href: "/admin/gift-shop", title: "Gift Shop Manager", desc: "Manage merchandise and products" },
  { href: "/admin/business-info", title: "Business Information", desc: "Update contact and operating hours" },
  { href: "/admin/settings", title: "Settings", desc: "Site settings and configuration" }
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
        <h2>Dashboard</h2>
      </div>

      <div className="admin-stats-grid">
        <div className="admin-stat-card">
          <h3>{loading ? "…" : stats.menu}</h3>
          <p>Menu Items</p>
        </div>
        <div className="admin-stat-card">
          <h3>{loading ? "…" : stats.photos}</h3>
          <p>Photos</p>
        </div>
        <div className="admin-stat-card">
          <h3>{loading ? "…" : stats.categories}</h3>
          <p>Categories</p>
        </div>
      </div>

      <h3 style={{ marginBottom: "1rem" }}>Quick Actions</h3>
      <div className="admin-quick-actions">
        {quickActions.map(({ href, title, desc }) => (
          <div key={href} className="admin-quick-card">
            <h4 style={{ margin: "0 0 0.25rem" }}>{title}</h4>
            <p style={{ margin: 0, fontSize: "0.9rem", color: "#6c757d" }}>{desc}</p>
            <Link href={href}>
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
