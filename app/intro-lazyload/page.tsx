import Link from "next/link";

export default function IntroLazyloadPage() {
  return (
    <main>
      <h1>Intro Lazyload</h1>
      <p className="muted">Migrated from static intro-lazyload.html.</p>
      <div className="card">
        <p>Next.js route placeholder with SSR/streaming support available for heavy sections.</p>
        <Link href="/">Back home</Link>
      </div>
    </main>
  );
}
