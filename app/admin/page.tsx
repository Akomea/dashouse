"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

export default function AdminLoginPage() {
  const router = useRouter();
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);
    setError("");
    const form = new FormData(e.currentTarget);
    const username = String(form.get("username") ?? "");
    const password = String(form.get("password") ?? "");

    const res = await fetch("/api/admin/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password })
    });
    const data = await res.json();
    setLoading(false);
    if (!data.success) {
      setError(data.error ?? "Login failed");
      return;
    }
    router.push("/admin/dashboard");
  }

  return (
    <main style={{ maxWidth: 480 }}>
      <h1>Admin Login</h1>
      <p className="muted">Simple env-based login migration from PHP admin auth.</p>
      <form className="card" onSubmit={onSubmit}>
        <label>
          Username
          <input name="username" type="text" required />
        </label>
        <label>
          Password
          <input name="password" type="password" required />
        </label>
        <button type="submit" disabled={loading}>
          {loading ? "Logging in..." : "Login"}
        </button>
        {error ? <p style={{ color: "#ff7f7f" }}>{error}</p> : null}
      </form>
    </main>
  );
}
