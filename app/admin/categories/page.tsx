"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

type Category = { id: number; name: string; description: string | null; image_url: string | null; is_active: boolean; sort_order?: number };

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [alert, setAlert] = useState<{ type: "success" | "danger"; message: string } | null>(null);
  const [editModal, setEditModal] = useState<Category | null>(null);
  const [addOpen, setAddOpen] = useState(false);

  const load = useCallback(async () => {
    const res = await fetch("/api/categories?is_active=1");
    const data = await res.json();
    setCategories(data?.data ?? []);
  }, []);

  useEffect(() => {
    load().catch(() => setAlert({ type: "danger", message: "Failed to load categories" }));
  }, [load]);

  function showAlert(type: "success" | "danger", message: string) {
    setAlert({ type, message });
    setTimeout(() => setAlert(null), 5000);
  }

  async function create(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);
    const res = await fetch("/api/categories", { method: "POST", body: formData });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to add category");
      return;
    }
    showAlert("success", "Category added.");
    form.reset();
    setAddOpen(false);
    load();
  }

  async function updateCategory(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!editModal) return;
    const form = e.currentTarget;
    const formData = new FormData(form);
    let imageUrl = editModal.image_url ?? "";
    const newImage = formData.get("image") as File | null;
    if (newImage && newImage.size > 0) {
      const uploadForm = new FormData();
      uploadForm.set("file", newImage);
      uploadForm.set("folder", "das-house/categories");
      const upRes = await fetch("/api/admin/upload", { method: "POST", body: uploadForm });
      const upData = await upRes.json();
      if (upData?.data?.url) imageUrl = upData.data.url;
    }
    const res = await fetch("/api/categories", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: editModal.id,
        name: String(formData.get("name") ?? ""),
        description: String(formData.get("description") ?? "") || null,
        sort_order: Number(formData.get("sort_order") ?? 0),
        image_url: imageUrl || undefined
      })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to update");
      return;
    }
    showAlert("success", "Category updated.");
    setEditModal(null);
    load();
  }

  async function remove(id: number) {
    if (!confirm("Delete this category?")) return;
    const res = await fetch("/api/categories", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to delete");
      return;
    }
    showAlert("success", data.message ?? "Category deleted.");
    load();
  }

  return (
    <>
      <div className="admin-page-header">
        <h2>Category Manager</h2>
        <button type="button" className="admin-btn admin-btn-primary" onClick={() => setAddOpen(true)}>
          Add Category
        </button>
      </div>

      {alert && <div className={`admin-alert admin-alert-${alert.type}`}>{alert.message}</div>}

      {categories.length === 0 && (
        <div className="admin-card">
          <p style={{ margin: 0, color: "#6c757d" }}>No categories yet. Add one above.</p>
        </div>
      )}

      <div style={{ display: "flex", flexDirection: "column", gap: 12 }}>
        {categories.map((cat) => (
          <div key={cat.id} className="admin-card" style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 12 }}>
            <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
              {cat.image_url ? (
                <img src={cat.image_url} alt={cat.name} style={{ width: 56, height: 56, objectFit: "cover", borderRadius: 8 }} />
              ) : (
                <div style={{ width: 56, height: 56, background: "#e9ecef", borderRadius: 8, display: "flex", alignItems: "center", justifyContent: "center", color: "#6c757d" }}>-</div>
              )}
              <div>
                <strong>{cat.name}</strong>
                {cat.description && <p style={{ margin: "4px 0 0", fontSize: "0.9rem", color: "#6c757d" }}>{cat.description}</p>}
              </div>
            </div>
            <div style={{ display: "flex", gap: 8 }}>
              <button type="button" className="admin-btn admin-btn-secondary admin-btn-sm" onClick={() => setEditModal(cat)}>Edit</button>
              <button type="button" className="admin-btn admin-btn-danger admin-btn-sm" onClick={() => remove(cat.id)}>Delete</button>
            </div>
          </div>
        ))}
      </div>

      {addOpen && (
        <div className="admin-modal-backdrop" onClick={() => setAddOpen(false)}>
          <div className="admin-modal" onClick={(e) => e.stopPropagation()}>
            <div className="admin-modal-header">
              <h3>Add Category</h3>
              <button type="button" className="admin-modal-close" onClick={() => setAddOpen(false)} aria-label="Close">×</button>
            </div>
            <form onSubmit={create}>
              <div className="admin-modal-body">
                <div className="admin-form-group">
                  <label>Name *</label>
                  <input name="name" required placeholder="Category name" />
                </div>
                <div className="admin-form-group">
                  <label>Description</label>
                  <textarea name="description" rows={2} placeholder="Optional" />
                </div>
                <div className="admin-form-group">
                  <label>Image</label>
                  <input name="image" type="file" accept="image/*" />
                </div>
                <div className="admin-form-group">
                  <label>Sort order</label>
                  <input name="sort_order" type="number" min="0" defaultValue={0} />
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
              <h3>Edit Category</h3>
              <button type="button" className="admin-modal-close" onClick={() => setEditModal(null)} aria-label="Close">×</button>
            </div>
            <form onSubmit={updateCategory}>
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
                  {editModal.image_url && <p style={{ marginTop: 6, fontSize: "0.85rem", color: "#6c757d" }}>Current image is set. Choose a file to replace.</p>}
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
