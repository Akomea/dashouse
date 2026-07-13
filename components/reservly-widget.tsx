"use client";

import Script from "next/script";

export function ReservlyWidget() {
  return (
    <>
      <link rel="stylesheet" href="https://app.reservly.at/embed.css" />
      <div className="reservly-wrapper" style={{ borderRadius: 20 }}>
        <div id="reservly-banner">
          <div id="reservly-link-line" className="reservly-bgtext">
            <a href="https://reservly.at" target="_blank" rel="noopener" className="reservly-bgtext">
              Reservly
            </a>
            <a
              href="https://reservly.at/datenschutz/b2c/?r-slug=das-house-cafe-bar"
              target="_blank"
              rel="noopener noreferrer"
              className="reservly-bgtext"
            >
              Datenschutz
            </a>
          </div>
        </div>
        <iframe
          id="reservly-iframe"
          src="https://app.reservly.at/iframe/das-house-cafe-bar/?theme=default&primaryColor=F0B24E&backgroundColor=000000&font=inter&tv=2026-06-02T15%3A11%3A35.603%2B00%3A00"
          title="Reservly Reservierung"
          loading="lazy"
          frameBorder={0}
          scrolling="auto"
        />
      </div>
      <Script src="https://app.reservly.at/embed.js" strategy="lazyOnload" />
    </>
  );
}
