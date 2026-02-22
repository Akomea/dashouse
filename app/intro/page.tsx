import Link from "next/link";

export default function IntroPage() {
  return (
    <main>
      <h1>Intro</h1>
      <p className="muted">Migrated from static intro.html.</p>
      <div className="card">
        <p>This route replaces the original theme intro page.</p>
        <Link href="/">Back home</Link>
      </div>
    </main>
  );
}
