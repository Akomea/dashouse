"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

type MenuItem = {
  id: number;
  name: string;
  price: number;
  category_id: number | null;
  category_name?: string;
  description: string | null;
  is_active: boolean;
  is_vegetarian?: boolean;
  is_vegan?: boolean;
  is_gluten_free?: boolean;
  allergens?: string | null;
  sort_order?: number;
};

type Category = { id: number; name: string };

function escapeHtml(text: string) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

export default function AdminMenuPage() {
  const [items, setItems] = useState<MenuItem[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [alert, setAlert] = useState<{ type: "success" | "danger"; message: string } | null>(null);
  const [editModal, setEditModal] = useState<MenuItem | null>(null);
  const [addOpen, setAddOpen] = useState(false);

  const load = useCallback(async () => {
    const [itemsRes1, itemsRes2, categoriesRes] = await Promise.all([
      fetch("/api/menu-items?is_active=1"),
      fetch("/api/menu-items?is_active=0"),
      fetch("/api/categories?is_active=1")
    ]);
    const data1 = await itemsRes1.json();
    const data2 = await itemsRes2.json();
    const catData = await categoriesRes.json();
    const active = data1?.data ?? [];
    const inactive = data2?.data ?? [];
    setItems([...active, ...inactive].sort((a: MenuItem, b: MenuItem) => (a.sort_order ?? 0) - (b.sort_order ?? 0)));
    setCategories(catData?.data ?? []);
  }, []);

  useEffect(() => {
    load().catch(() => setAlert({ type: "danger", message: "Failed to load data" }));
  }, [load]);

  function showAlert(type: "success" | "danger", message: string) {
    setAlert({ type, message });
    setTimeout(() => setAlert(null), 5000);
  }

  async function create(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);
    const body = {
      name: String(formData.get("name") ?? ""),
      price: Number(formData.get("price") ?? 0),
      category_id: Number(formData.get("category_id") ?? 0),
      description: String(formData.get("description") ?? ""),
      is_vegetarian: formData.get("is_vegetarian") === "1",
      is_vegan: formData.get("is_vegan") === "1",
      is_gluten_free: formData.get("is_gluten_free") === "1",
      allergens: String(formData.get("allergens") ?? ""),
      sort_order: Number(formData.get("sort_order") ?? 0),
      is_active: true
    };
    const res = await fetch("/api/menu-items", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body)
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to add item");
      return;
    }
    showAlert("success", "Menu item added.");
    form.reset();
    setAddOpen(false);
    load();
  }

  async function updateItem(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!editModal) return;
    const form = e.currentTarget;
    const formData = new FormData(form);
    const body = {
      id: editModal.id,
      name: String(formData.get("name") ?? ""),
      price: Number(formData.get("price") ?? 0),
      category_id: Number(formData.get("category_id") ?? 0),
      description: String(formData.get("description") ?? ""),
      is_vegetarian: formData.get("is_vegetarian") === "1",
      is_vegan: formData.get("is_vegan") === "1",
      is_gluten_free: formData.get("is_gluten_free") === "1",
      allergens: String(formData.get("allergens") ?? ""),
      sort_order: Number(formData.get("sort_order") ?? 0)
    };
    const res = await fetch("/api/menu-items", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body)
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to update");
      return;
    }
    showAlert("success", "Menu item updated.");
    setEditModal(null);
    load();
  }

  async function remove(id: number) {
    if (!confirm("Delete this menu item?")) return;
    const res = await fetch("/api/menu-items", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to delete");
      return;
    }
    showAlert("success", "Item deleted.");
    load();
  }

  async function toggleActive(id: number, current: boolean) {
    const res = await fetch("/api/menu-items", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, is_active: !current })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to update");
      return;
    }
    showAlert("success", current ? "Item hidden." : "Item visible.");
    load();
  }

  const grouped = items.reduce<Record<string, MenuItem[]>>((acc, item) => {
    const name = item.category_name ?? "Uncategorized";
    if (!acc[name]) acc[name] = [];
    acc[name].push(item);
    return acc;
  }, {});

  return (
    <>
      <div className="admin-page-header">
        <h2>🍴 Menu Manager</h2>
        <button type="button" className="admin-btn admin-btn-primary" onClick={() => setAddOpen(true)}>
          Add Menu Item
        </button>
      </div>

      {alert && (
        <div className={`admin-alert admin-alert-${alert.type}`}>
          {alert.message}
        </div>
      )}

      {Object.keys(grouped).length === 0 && (
        <div className="admin-card">
          <p style={{ margin: 0, color: "#6c757d" }}>No menu items. Add your first item above.</p>
        </div>
      )}

      {Object.entries(grouped).map(([categoryName, categoryItems]) => (
        <div key={categoryName} className="admin-card">
          <h5 style={{ margin: "0 0 1rem" }}>🏷 {escapeHtml(categoryName)}</h5>
          {categoryItems.map((item) => {
            const badges = [];
            if (item.is_vegetarian) badges.push(<span key="v" style={{ marginRight: 4, fontSize: "0.75rem", background: "#28a745", color: "#fff", padding: "2px 6px", borderRadius: 4 }}>Vegetarian</span>);
            if (item.is_vegan) badges.push(<span key="vg" style={{ marginRight: 4, fontSize: "0.75rem", background: "#17a2b8", color: "#fff", padding: "2px 6px", borderRadius: 4 }}>Vegan</span>);
            if (item.is_gluten_free) badges.push(<span key="gf" style={{ marginRight: 4, fontSize: "0.75rem", background: "#ffc107", color: "#000", padding: "2px 6px", borderRadius: 4 }}>Gluten-Free</span>);
            return (
              <div
                key={item.id}
                style={{
                  padding: "0.75rem",
                  marginBottom: 8,
                  borderLeft: "4px solid " + (item.is_active ? "#28a745" : "#6c757d"),
                  borderRadius: 8,
                  background: item.is_active ? "#f8f9fa" : "rgba(0,0,0,0.04)",
                  opacity: item.is_active ? 1 : 0.7
                }}
              >
                <div style={{ display: "flex", flexWrap: "wrap", justifyContent: "space-between", alignItems: "flex-start", gap: 8 }}>
                  <div>
                    <strong>{escapeHtml(item.name)}</strong>
                    <span style={{ marginLeft: 8, color: "#28a745", fontWeight: 600 }}>€{Number(item.price).toFixed(2)}</span>
                    {item.description && <p style={{ margin: "4px 0 0", color: "#555", fontSize: "0.9rem" }}>{escapeHtml(item.description)}</p>}
                    {badges.length > 0 && <div style={{ marginTop: 4 }}>{badges}</div>}
                    {item.allergens && <small style={{ color: "#dc3545" }}>⚠ {escapeHtml(item.allergens)}</small>}
                  </div>
                  <div style={{ display: "flex", gap: 6, flexShrink: 0 }}>
                    <button type="button" className="admin-btn admin-btn-secondary admin-btn-sm" onClick={() => setEditModal(item)}>Edit</button>
                    <button type="button" className="admin-btn admin-btn-danger admin-btn-sm" onClick={() => remove(item.id)}>Delete</button>
                    <button type="button" className="admin-btn admin-btn-sm" style={{ background: "#6c757d", color: "#fff" }} onClick={() => toggleActive(item.id, item.is_active)} title={item.is_active ? "Hide" : "Show"}>
                      {item.is_active ? "👁‍🗨 Hide" : "👁 Show"}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      ))}

      {addOpen && (
        <div className="admin-modal-backdrop" onClick={() => setAddOpen(false)}>
          <div className="admin-modal" onClick={(e) => e.stopPropagation()}>
            <div className="admin-modal-header">
              <h3>Add Menu Item</h3>
              <button type="button" onClick={() => setAddOpen(false)} aria-label="Close">×</button>
            </div>
            <form onSubmit={create}>
              <div className="admin-modal-body">
                <div className="admin-form-group">
                  <label>Name *</label>
                  <input name="name" required placeholder="Item name" />
                </div>
                <div className="admin-form-group">
                  <label>Price (€) *</label>
                  <input name="price" type="number" step="0.01" min="0" required placeholder="0.00" />
                </div>
                <div className="admin-form-group">
                  <label>Category *</label>
                  <select name="category_id" required>
                    <option value="">Select category</option>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div className="admin-form-group">
                  <label>Description</label>
                  <textarea name="description" rows={3} placeholder="Ingredients, etc." />
                </div>
                <div className="admin-form-group">
                  <label>Dietary</label>
                  <div style={{ display: "flex", gap: 16, flexWrap: "wrap" }}>
                    <label style={{ display: "flex", alignItems: "center", gap: 6 }}><input type="checkbox" name="is_vegetarian" value="1" /> Vegetarian</label>
                    <label style={{ display: "flex", alignItems: "center", gap: 6 }}><input type="checkbox" name="is_vegan" value="1" /> Vegan</label>
                    <label style={{ display: "flex", alignItems: "center", gap: 6 }}><input type="checkbox" name="is_gluten_free" value="1" /> Gluten-Free</label>
                  </div>
                </div>
                <div className="admin-form-group">
                  <label>Allergens</label>
                  <input name="allergens" placeholder="e.g. Nuts, Dairy" />
                </div>
                <div className="admin-form-group">
                  <label>Sort order</label>
                  <input name="sort_order" type="number" min="0" defaultValue={0} />
                </div>
              </div>
              <div className="admin-modal-footer">
                <button type="button" className="admin-btn admin-btn-secondary" onClick={() => setAddOpen(false)}>Cancel</button>
                <button type="submit" className="admin-btn admin-btn-primary">Add Item</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {editModal && (
        <div className="admin-modal-backdrop" onClick={() => setEditModal(null)}>
          <div className="admin-modal" onClick={(e) => e.stopPropagation()}>
            <div className="admin-modal-header">
              <h3>Edit Menu Item</h3>
              <button type="button" onClick={() => setEditModal(null)} aria-label="Close">×</button>
            </div>
            <form onSubmit={updateItem}>
              <div className="admin-modal-body">
                <div className="admin-form-group">
                  <label>Name *</label>
                  <input name="name" defaultValue={editModal.name} required />
                </div>
                <div className="admin-form-group">
                  <label>Price (€) *</label>
                  <input name="price" type="number" step="0.01" min="0" defaultValue={editModal.price} required />
                </div>
                <div className="admin-form-group">
                  <label>Category *</label>
                  <select name="category_id" required defaultValue={editModal.category_id ?? ""}>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div className="admin-form-group">
                  <label>Description</label>
                  <textarea name="description" rows={3} defaultValue={editModal.description ?? ""} />
                </div>
                <div className="admin-form-group">
                  <label>Dietary</label>
                  <div style={{ display: "flex", gap: 16, flexWrap: "wrap" }}>
                    <label style={{ display: "flex", alignItems: "center", gap: 6 }}><input type="checkbox" name="is_vegetarian" value="1" defaultChecked={editModal.is_vegetarian} /> Vegetarian</label>
                    <label style={{ display: "flex", alignItems: "center", gap: 6 }}><input type="checkbox" name="is_vegan" value="1" defaultChecked={editModal.is_vegan} /> Vegan</label>
                    <label style={{ display: "flex", alignItems: "center", gap: 6 }}><input type="checkbox" name="is_gluten_free" value="1" defaultChecked={editModal.is_gluten_free} /> Gluten-Free</label>
                  </div>
                </div>
                <div className="admin-form-group">
                  <label>Allergens</label>
                  <input name="allergens" defaultValue={editModal.allergens ?? ""} />
                </div>
                <div className="admin-form-group">
                  <label>Sort order</label>
                  <input name="sort_order" type="number" min="0" defaultValue={editModal.sort_order ?? 0} />
                </div>
              </div>
              <div className="admin-modal-footer">
                <button type="button" className="admin-btn admin-btn-secondary" onClick={() => setEditModal(null)}>Cancel</button>
                <button type="submit" className="admin-btn admin-btn-primary">Update</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
