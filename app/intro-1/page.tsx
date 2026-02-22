import Link from "next/link";

export default function IntroOnePage() {
  return (
    <main>
      <h1>Intro 1</h1>
      <p className="muted">Migrated from static intro-1.html.</p>
      <div className="card">
        <p>Use this page to port the remaining template-specific content incrementally.</p>
        <Link href="/">Back home</Link>
      </div>
    </main>
  );
}
