"use client";

import { useEffect, useState } from "react";

type Application = {
  id: number;
  full_name: string;
  email: string;
  phone: string;
  locale: string;
  status: string;
  created_at: string;
  payload: Record<string, unknown>;
};

export default function AdminAdoptionsPage() {
  const [items, setItems] = useState<Application[]>([]);
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState<Application | null>(null);
  const [error, setError] = useState("");

  const load = () => {
    setLoading(true);
    fetch("/api/adoption-applications")
      .then((r) => r.json())
      .then((d) => {
        if (d.success === false) {
          setError(d.error || "Failed to load");
          setItems([]);
          return;
        }
        setItems(d.data ?? []);
        setError("");
      })
      .catch(() => setError("Failed to load"))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const setStatus = async (id: number, status: string) => {
    const res = await fetch("/api/adoption-applications", {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, status }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      setError(data.error || "Update failed");
      return;
    }
    load();
    if (selected?.id === id) {
      setSelected((prev) => (prev ? { ...prev, status } : prev));
    }
  };

  return (
    <>
      <div className="admin-page-header">
        <h2>Adoption Applications</h2>
        <p className="text-muted mb-0">Submitted from the public /adopt form</p>
      </div>

      {error ? <div className="alert alert-danger">{error}</div> : null}
      {loading ? <p>Loading…</p> : null}

      {!loading && items.length === 0 ? (
        <p>No applications yet.</p>
      ) : null}

      {!loading && items.length > 0 ? (
        <div className="table-responsive">
          <table className="table table-striped align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id}>
                  <td>{new Date(item.created_at).toLocaleString()}</td>
                  <td>{item.full_name}</td>
                  <td>
                    <a href={`mailto:${item.email}`}>{item.email}</a>
                  </td>
                  <td>
                    <a href={`tel:${item.phone}`}>{item.phone}</a>
                  </td>
                  <td>
                    <select
                      className="form-select form-select-sm"
                      value={item.status}
                      onChange={(e) => setStatus(item.id, e.target.value)}
                    >
                      <option value="new">new</option>
                      <option value="reviewed">reviewed</option>
                      <option value="accepted">accepted</option>
                      <option value="declined">declined</option>
                    </select>
                  </td>
                  <td>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setSelected(item)}>
                      View
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}

      {selected ? (
        <div
          className="modal d-block"
          style={{ background: "rgba(0,0,0,0.45)" }}
          onClick={() => setSelected(null)}
          role="presentation"
        >
          <div
            className="modal-dialog modal-lg modal-dialog-scrollable"
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
          >
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">
                  #{selected.id} — {selected.full_name}
                </h5>
                <button type="button" className="btn-close" aria-label="Close" onClick={() => setSelected(null)} />
              </div>
              <div className="modal-body">
                <pre
                  style={{
                    whiteSpace: "pre-wrap",
                    wordBreak: "break-word",
                    fontSize: 13,
                    background: "#f8f9fa",
                    padding: 12,
                    borderRadius: 8,
                  }}
                >
                  {JSON.stringify(selected.payload, null, 2)}
                </pre>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}
