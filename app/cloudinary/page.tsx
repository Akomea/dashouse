"use client";

import { CldImage } from "next-cloudinary";

export default function CloudinaryPage() {
  const cloudName = process.env.NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME;

  return (
    <main>
      <h1>Cloudinary Demo</h1>
      <p className="muted">
        `CldImage` applies automatic quality and format optimization by default.
      </p>
      <div className="card" style={{ maxWidth: 520 }}>
        {cloudName ? (
          <CldImage
            src="cld-sample-5"
            width={500}
            height={500}
            crop={{
              type: "auto",
              source: true
            }}
            alt="Cloudinary sample image"
          />
        ) : (
          <p className="muted">
            Set `NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME` in `.env.local` to render the Cloudinary image.
          </p>
        )}
      </div>
    </main>
  );
}
