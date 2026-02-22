"use client";

import { useEffect, useState } from "react";

export function GotoTop() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const onScroll = () => setVisible(window.scrollY > 400);
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  const scrollToTop = () => window.scrollTo({ top: 0, behavior: "smooth" });

  return (
    <div
      id="gotoTop"
      className="icon-angle-up"
      role="button"
      tabIndex={0}
      onClick={scrollToTop}
      onKeyDown={(e) => e.key === "Enter" && scrollToTop()}
      aria-label="Go to top"
      style={{ display: visible ? undefined : "none" }}
    />
  );
}
