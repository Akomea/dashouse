import { NextRequest, NextResponse } from "next/server";

const ajaxContent: Record<string, string> = {
  "portfolio-single-image":
    '<div class="portfolio-ajax-modal"><div class="ajax-modal-title"><h2>Single Item with Image</h2></div><p>Migrated to Next.js API content endpoint.</p></div>',
  "portfolio-single-thumbs":
    '<div class="portfolio-ajax-modal"><div class="ajax-modal-title"><h2>Single Item with Thumbs</h2></div><p>Migrated to Next.js API content endpoint.</p></div>',
  "portfolio-single-gallery":
    '<div class="portfolio-ajax-modal"><div class="ajax-modal-title"><h2>Single Item with Slider</h2></div><p>Migrated to Next.js API content endpoint.</p></div>',
  "portfolio-single-video":
    '<div class="portfolio-ajax-modal"><div class="ajax-modal-title"><h2>Single Item with Video</h2></div><p>Migrated to Next.js API content endpoint.</p></div>',
  "shop-item":
    '<div class="single-product shop-quick-view-ajax"><div class="ajax-modal-title"><h2>Shop Item</h2></div><p>Migrated to Next.js API content endpoint.</p></div>'
};

export async function GET(_req: NextRequest, { params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const html = ajaxContent[slug];
  if (!html) {
    return new NextResponse("Not found", { status: 404 });
  }
  return new NextResponse(html, {
    headers: { "Content-Type": "text/html; charset=utf-8" }
  });
}
