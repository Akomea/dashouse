"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

type GiftItem = { id: number; name: string; description: string | null; image_url: string; active: boolean };

export default function AdminGiftShopPage() {
  const [items, setItems] = useState<GiftItem[]>([]);
  const [alert, setAlert] = useState<{ type: "success" | "danger"; message: string } | null>(null);
  const [editModal, setEditModal] = useState<GiftItem | null>(null);
  const [addOpen, setAddOpen] = useState(false);

  const load = useCallback(async () => {
    const [res1, res2] = await Promise.all([
      fetch("/api/gift-shop?is_active=1"),
      fetch("/api/gift-shop?is_active=0")
    ]);
    const data1 = await res1.json();
    const data2 = await res2.json();
    const active = data1?.data ?? [];
    const inactive = data2?.data ?? [];
    setItems([...active, ...inactive]);
  }, []);

  useEffect(() => {
    load().catch(() => setAlert({ type: "danger", message: "Failed to load" }));
  }, [load]);

  function showAlert(type: "success" | "danger", message: string) {
    setAlert({ type, message });
    setTimeout(() => setAlert(null), 5000);
  }

  async function create(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    const file = form.get("file");
    let image_url = "";
    if (file instanceof File && file.size > 0) {
      const upload = new FormData();
      upload.set("file", file);
      upload.set("folder", "das-house/gift-shop");
      const uploadRes = await fetch("/api/admin/upload", { method: "POST", body: upload });
      const uploadData = await uploadRes.json();
      image_url = uploadData?.data?.url ?? "";
    }
    const res = await fetch("/api/gift-shop", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: String(form.get("name") ?? ""),
        description: String(form.get("description") ?? ""),
        image_url
      })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to add");
      return;
    }
    showAlert("success", "Item added.");
    e.currentTarget.reset();
    setAddOpen(false);
    load();
  }

  async function updateItem(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!editModal) return;
    const form = new FormData(e.currentTarget);
    let image_url = editModal.image_url;
    const newImage = form.get("image") as File | null;
    if (newImage && newImage.size > 0) {
      const upload = new FormData();
      upload.set("file", newImage);
      upload.set("folder", "das-house/gift-shop");
      const uploadRes = await fetch("/api/admin/upload", { method: "POST", body: upload });
      const uploadData = await uploadRes.json();
      if (uploadData?.data?.url) image_url = uploadData.data.url;
    }
    const res = await fetch("/api/gift-shop", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: editModal.id,
        name: String(form.get("name") ?? ""),
        description: String(form.get("description") ?? ""),
        image_url
      })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to update");
      return;
    }
    showAlert("success", "Item updated.");
    setEditModal(null);
    load();
  }

  async function toggleActive(item: GiftItem) {
    const res = await fetch("/api/gift-shop", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: item.id, active: !item.active })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to update");
      return;
    }
    showAlert("success", item.active ? "Item hidden." : "Item visible.");
    load();
  }

  async function onDelete(id: number) {
    if (!confirm("Delete this item?")) return;
    const res = await fetch("/api/gift-shop", {
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

  return (
    <>
      <div className="admin-page-header">
        <h2>Gift Shop Manager</h2>
        <button type="button" className="admin-btn admin-btn-primary" onClick={() => setAddOpen(true)}>
          Add Item
        </button>
      </div>

      {alert && <div className={`admin-alert admin-alert-${alert.type}`}>{alert.message}</div>}

      <h3 style={{ marginBottom: "1rem" }}>Items</h3>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(200px, 1fr))", gap: 16 }}>
        {items.map((item) => (
          <div key={item.id} className="admin-card" style={{ display: "flex", flexDirection: "column", gap: 8, opacity: item.active ? 1 : 0.75 }}>
            <img src={item.image_url} alt={item.name} style={{ width: "100%", aspectRatio: "1", objectFit: "cover", borderRadius: 8 }} />
            <div>
              <strong>{item.name}</strong>
              {item.description && <p style={{ margin: "4px 0 0", fontSize: "0.85rem", color: "#6c757d" }}>{item.description}</p>}
            </div>
            <div style={{ display: "flex", gap: 6, flexWrap: "wrap", marginTop: "auto" }}>
              <button type="button" className="admin-btn admin-btn-secondary admin-btn-sm" onClick={() => setEditModal(item)}>Edit</button>
              <button type="button" className="admin-btn admin-btn-sm" style={{ background: "#6c757d", color: "#fff" }} onClick={() => toggleActive(item)}>
                {item.active ? "Hide" : "Show"}
              </button>
              <button type="button" className="admin-btn admin-btn-danger admin-btn-sm" onClick={() => onDelete(item.id)}>Delete</button>
            </div>
          </div>
        ))}
      </div>
      {items.length === 0 && (
        <div className="admin-card">
          <p style={{ margin: 0, color: "#6c757d" }}>No items yet.</p>
        </div>
      )}

      {addOpen && (
        <div className="admin-modal-backdrop" onClick={() => setAddOpen(false)}>
          <div className="admin-modal" onClick={(e) => e.stopPropagation()}>
            <div className="admin-modal-header">
              <h3>Add Gift Item</h3>
              <button type="button" className="admin-modal-close" onClick={() => setAddOpen(false)} aria-label="Close">×</button>
            </div>
            <form onSubmit={create}>
              <div className="admin-modal-body">
                <div className="admin-form-group">
                  <label>Name *</label>
                  <input name="name" required placeholder="Name" />
                </div>
                <div className="admin-form-group">
                  <label>Description</label>
                  <textarea name="description" rows={2} placeholder="Description" />
                </div>
                <div className="admin-form-group">
                  <label>Image *</label>
                  <input name="file" type="file" accept="image/*" required />
                </div>
              </div>
              <div className="admin-modal-footer">
                <button type="button" className="admin-btn admin-btn-secondary" onClick={() => setAddOpen(false)}>Cancel</button>
                <button type="submit" className="admin-btn admin-btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {editModal && (
        <div className="admin-modal-backdrop" onClick={() => setEditModal(null)}>
          <div className="admin-modal" onClick={(e) => e.stopPropagation()}>
            <div className="admin-modal-header">
              <h3>Edit Gift Item</h3>
              <button type="button" className="admin-modal-close" onClick={() => setEditModal(null)} aria-label="Close">×</button>
            </div>
            <form onSubmit={updateItem}>
              <div className="admin-modal-body">
                <div className="admin-form-group">
                  <label>Name *</label>
                  <input name="name" defaultValue={editModal.name} required />
                </div>
                <div className="admin-form-group">
                  <label>Description</label>
                  <textarea name="description" rows={2} defaultValue={editModal.description ?? ""} />
                </div>
                <div className="admin-form-group">
                  <label>New image (optional)</label>
                  <input name="image" type="file" accept="image/*" />
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
