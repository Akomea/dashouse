"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

type Setting = {
  id: number;
  setting_key: string;
  setting_value: string | null;
  setting_type: string | null;
  description: string | null;
};

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState<Setting[]>([]);
  const [message, setMessage] = useState<{ type: "success" | "danger"; text: string } | null>(null);
  const [uploadingPdf, setUploadingPdf] = useState(false);

  const load = useCallback(async () => {
    const res = await fetch("/api/settings");
    const data = await res.json();
    setSettings(data.data ?? []);
  }, []);

  useEffect(() => {
    load().catch(() => undefined);
  }, [load]);

  async function onSave(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    const res = await fetch("/api/settings", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        setting_key: form.get("setting_key"),
        setting_value: form.get("setting_value"),
        setting_type: form.get("setting_type"),
        description: form.get("description")
      })
    });
    const data = await res.json();
    if (!data.success) {
      setMessage({ type: "danger", text: data.error ?? "Failed to save setting." });
      return;
    }
    e.currentTarget.reset();
    setMessage({ type: "success", text: "Setting saved." });
    load();
  }

  async function saveSetting(
    setting_key: string,
    setting_value: string,
    description: string
  ) {
    const res = await fetch("/api/settings", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        setting_key,
        setting_value,
        setting_type: "text",
        description
      })
    });
    return res.json();
  }

  const menuPdfUrl =
    settings.find((setting) => setting.setting_key === "menu_pdf_url")?.setting_value ?? "";
  const menuPdfPublicId =
    settings.find((setting) => setting.setting_key === "menu_pdf_public_id")?.setting_value ?? "";

  async function onUploadMenuPdf(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);
    const file = formData.get("menu_pdf_file");
    if (!(file instanceof File) || file.size <= 0) {
      setMessage({ type: "danger", text: "Please choose a PDF file to upload." });
      return;
    }

    setUploadingPdf(true);
    setMessage(null);
    try {
      const uploadForm = new FormData();
      uploadForm.set("file", file);
      uploadForm.set("folder", "das-house/menu-pdf");
      const uploadRes = await fetch("/api/admin/upload", {
        method: "POST",
        body: uploadForm
      });
      const uploadData = await uploadRes.json();
      if (!uploadData.success) {
        setMessage({ type: "danger", text: uploadData.error ?? "Failed to upload PDF." });
        return;
      }

      const newUrl = String(uploadData?.data?.url ?? "");
      const newPublicId = String(uploadData?.data?.public_id ?? "");
      if (!newUrl || !newPublicId) {
        setMessage({ type: "danger", text: "Upload succeeded but no file details were returned." });
        return;
      }

      const [urlSave, publicIdSave] = await Promise.all([
        saveSetting("menu_pdf_url", newUrl, "Cloudinary URL for the current downloadable menu PDF"),
        saveSetting("menu_pdf_public_id", newPublicId, "Cloudinary public ID for current menu PDF")
      ]);
      if (!urlSave.success || !publicIdSave.success) {
        setMessage({ type: "danger", text: "PDF uploaded but failed to save settings." });
        return;
      }

      if (menuPdfPublicId && menuPdfPublicId !== newPublicId) {
        await fetch("/api/admin/upload/delete", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ public_id: menuPdfPublicId })
        }).catch(() => undefined);
      }

      form.reset();
      setMessage({ type: "success", text: "Menu PDF replaced successfully." });
      load();
    } catch (error) {
      setMessage({
        type: "danger",
        text: `Failed to upload menu PDF: ${(error as Error).message}`
      });
    } finally {
      setUploadingPdf(false);
    }
  }

  return (
    <>
      <div className="admin-page-header">
        <h2>Settings</h2>
      </div>

      <div className="admin-card">
        <h3 style={{ margin: "0 0 0.5rem" }}>Admin Panel</h3>
        <p style={{ margin: 0, fontSize: "0.9rem", color: "#6c757d" }}>
          Login uses environment variables <code>ADMIN_USER</code> and <code>ADMIN_PASSWORD</code>. To change the password, update these in your environment (e.g. <code>.env</code>) and redeploy.
        </p>
      </div>

      <div className="admin-card">
        <h3 style={{ margin: "0 0 1rem" }}>Add or update setting</h3>
        <form onSubmit={onSave}>
          <div className="admin-form-group">
            <label>Key *</label>
            <input name="setting_key" placeholder="setting_key" required />
          </div>
          <div className="admin-form-group">
            <label>Value</label>
            <input name="setting_value" placeholder="setting_value" />
          </div>
          <div className="admin-form-group">
            <label>Type</label>
            <input name="setting_type" placeholder="text or boolean" defaultValue="text" />
          </div>
          <div className="admin-form-group">
            <label>Description</label>
            <textarea name="description" placeholder="description" rows={2} />
          </div>
          <button type="submit" className="admin-btn admin-btn-primary">Save setting</button>
        </form>
      </div>

      <div className="admin-card">
        <h3 style={{ margin: "0 0 0.75rem" }}>Menu PDF</h3>
        <p style={{ marginTop: 0, fontSize: "0.9rem", color: "#6c757d" }}>
          Upload a new PDF to replace the current downloadable menu.
        </p>
        {menuPdfUrl ? (
          <p style={{ marginTop: 0 }}>
            Current file:{" "}
            <a href={menuPdfUrl} target="_blank" rel="noopener noreferrer">
              Open current menu PDF
            </a>
          </p>
        ) : (
          <p style={{ marginTop: 0, color: "#6c757d" }}>No uploaded menu PDF yet.</p>
        )}
        <form onSubmit={onUploadMenuPdf}>
          <div className="admin-form-group">
            <label>PDF file *</label>
            <input name="menu_pdf_file" type="file" accept="application/pdf" required />
          </div>
          <button type="submit" className="admin-btn admin-btn-primary" disabled={uploadingPdf}>
            {uploadingPdf ? "Uploading..." : "Upload and Replace Menu PDF"}
          </button>
        </form>
      </div>

      {message && (
        <div className={`admin-alert admin-alert-${message.type}`}>
          {message.text}
        </div>
      )}

      <div className="admin-card">
        <h3 style={{ margin: "0 0 1rem" }}>Current settings</h3>
        {settings.length === 0 ? (
          <p style={{ margin: 0, color: "#6c757d" }}>No settings yet.</p>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
            {settings.map((setting) => (
              <div key={setting.id}>
                <strong>{setting.setting_key}</strong>: {setting.setting_value ?? ""}
              </div>
            ))}
          </div>
        )}
      </div>
    </>
  );
}
