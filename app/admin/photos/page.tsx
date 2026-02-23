"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

type Photo = { id: number; title: string | null; description: string | null; image_url: string; category_id: number | null; is_active?: boolean };
type Category = { id: number; name: string };

export default function AdminPhotosPage() {
  const [photos, setPhotos] = useState<Photo[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [alert, setAlert] = useState<{ type: "success" | "danger"; message: string } | null>(null);

  const load = useCallback(async () => {
    const [photoRes, categoryRes] = await Promise.all([
      fetch("/api/photos"),
      fetch("/api/categories?is_active=1")
    ]);
    const photoData = await photoRes.json();
    const categoryData = await categoryRes.json();
    setPhotos(photoData?.data ?? []);
    setCategories(categoryData?.data ?? []);
  }, []);

  useEffect(() => {
    load().catch(() => setAlert({ type: "danger", message: "Failed to load" }));
  }, [load]);

  function showAlert(type: "success" | "danger", message: string) {
    setAlert({ type, message });
    setTimeout(() => setAlert(null), 5000);
  }

  async function onCreate(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    const file = form.get("file");
    if (!(file instanceof File) || file.size <= 0) return;
    const upload = new FormData();
    upload.set("file", file);
    upload.set("folder", "das-house/photos");
    const uploadRes = await fetch("/api/admin/upload", { method: "POST", body: upload });
    const uploadData = await uploadRes.json();
    if (!uploadData?.data?.url) {
      showAlert("danger", uploadData?.error ?? "Upload failed");
      return;
    }
    const res = await fetch("/api/photos", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        title: String(form.get("title") ?? ""),
        description: String(form.get("description") ?? ""),
        category_id: Number(form.get("category_id") ?? 0) || null,
        image_url: uploadData.data.url,
        external_id: uploadData.data.public_id ?? null,
        original_name: uploadData.data.original_name ?? null
      })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to save photo");
      return;
    }
    showAlert("success", "Photo uploaded.");
    e.currentTarget.reset();
    load();
  }

  async function setCategoryImage(photo: Photo, categoryId: number) {
    const res = await fetch("/api/categories", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: categoryId, image_url: photo.image_url })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to set category image");
      return;
    }
    showAlert("success", "Category image set.");
    load();
  }

  async function toggleActive(photo: Photo) {
    const res = await fetch("/api/photos", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: photo.id, is_active: !photo.is_active })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to update");
      return;
    }
    showAlert("success", photo.is_active ? "Photo hidden." : "Photo visible.");
    load();
  }

  async function onDelete(id: number) {
    if (!confirm("Delete this photo?")) return;
    const res = await fetch("/api/photos", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (!data.success) {
      showAlert("danger", data.error ?? "Failed to delete");
      return;
    }
    showAlert("success", "Photo deleted.");
    load();
  }

  return (
    <>
      <div className="admin-page-header">
        <h2>Photo Manager</h2>
      </div>

      {alert && <div className={`admin-alert admin-alert-${alert.type}`}>{alert.message}</div>}

      <div className="admin-card">
        <h3 style={{ margin: "0 0 1rem" }}>Upload Photo</h3>
        <form onSubmit={onCreate} style={{ display: "flex", flexWrap: "wrap", gap: 12, alignItems: "flex-end" }}>
          <div className="admin-form-group" style={{ marginBottom: 0, minWidth: 140 }}>
            <label>Title</label>
            <input name="title" placeholder="Title" />
          </div>
          <div className="admin-form-group" style={{ marginBottom: 0, minWidth: 140 }}>
            <label>Description</label>
            <input name="description" placeholder="Description" />
          </div>
          <div className="admin-form-group" style={{ marginBottom: 0, minWidth: 160 }}>
            <label>Category</label>
            <select name="category_id">
              <option value="">Optional</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>
          <div className="admin-form-group" style={{ marginBottom: 0 }}>
            <label>Image *</label>
            <input name="file" type="file" accept="image/*" required />
          </div>
          <button type="submit" className="admin-btn admin-btn-primary">Upload</button>
        </form>
      </div>

      <h3 style={{ marginBottom: "1rem" }}>Photos</h3>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(220px, 1fr))", gap: 16 }}>
        {photos.map((photo) => (
          <div
            key={photo.id}
            className="admin-card"
            style={{
              opacity: photo.is_active === false ? 0.7 : 1,
              display: "flex",
              flexDirection: "column",
              gap: 8
            }}
          >
            <img
              src={photo.image_url}
              alt={photo.title ?? "Photo"}
              style={{ width: "100%", aspectRatio: "1", objectFit: "cover", borderRadius: 8 }}
            />
            <div>
              <strong>{photo.title || "Untitled"}</strong>
              {photo.description && <p style={{ margin: "4px 0 0", fontSize: "0.85rem", color: "#6c757d" }}>{photo.description}</p>}
            </div>
            <div className="admin-form-group" style={{ marginBottom: 0 }}>
              <label style={{ fontSize: "0.8rem" }}>Set as category image</label>
              <select
                onChange={(e) => {
                  const v = e.target.value;
                  if (v) setCategoryImage(photo, Number(v));
                }}
                defaultValue=""
              >
                <option value="">-</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>
              <button type="button" className="admin-btn admin-btn-sm" style={{ background: "#6c757d", color: "#fff" }} onClick={() => toggleActive(photo)}>
                {photo.is_active === false ? "Show" : "Hide"}
              </button>
              <button type="button" className="admin-btn admin-btn-danger admin-btn-sm" onClick={() => onDelete(photo.id)}>Delete</button>
            </div>
          </div>
        ))}
      </div>
      {photos.length === 0 && (
        <div className="admin-card">
          <p style={{ margin: 0, color: "#6c757d" }}>No photos yet. Upload one above.</p>
        </div>
      )}
    </>
  );
}
