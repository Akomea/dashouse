"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { useLanguage } from "@/lib/i18n/language-context";
import type { Locale } from "@/lib/i18n/locales";

const LOGO_SRC = "/demos/burger/images/logo-hakane3.png";
const LOGO_FALLBACK = "/demos/burger/images/das-logo.svg";
const FALLBACK_PHONE = "+4367763423881";
const STICKY_SCROLL_THRESHOLD = 50;

export function SiteHeader({ phone }: { phone?: string | null }) {
  const pathname = usePathname();
  const isHome = pathname === "/";
  const [logoSrc, setLogoSrc] = useState(LOGO_SRC);
  const [isSticky, setIsSticky] = useState(false);
  const { locale, setLocale, t } = useLanguage();

  useEffect(() => {
    const header = document.getElementById("header");
    if (!header) return;
    const updateSticky = () => {
      const scrolled = window.scrollY > STICKY_SCROLL_THRESHOLD;
      const shouldBeSticky = isHome ? scrolled : true;
      setIsSticky(shouldBeSticky);
      if (shouldBeSticky) {
        header.classList.add("sticky-header");
      } else {
        header.classList.remove("sticky-header");
      }
    };
    updateSticky();
    window.addEventListener("scroll", updateSticky, { passive: true });
    return () => window.removeEventListener("scroll", updateSticky);
  }, [isHome]);

  const toggleMenu = useCallback(() => {
    document.body.classList.toggle("primary-menu-open");
    const menu = document.querySelector(".primary-menu .menu-container");
    if (menu) menu.classList.toggle("d-block");
  }, []);

  const closeMenu = useCallback(() => {
    document.body.classList.remove("primary-menu-open");
    const menu = document.querySelector(".primary-menu .menu-container");
    if (menu) menu.classList.remove("d-block");
  }, []);

  useEffect(() => {
    const trigger = document.getElementById("primary-menu-trigger");
    const menu = document.querySelector(".primary-menu .menu-container");
    if (!trigger || !menu) return;
    const handler = (e: Event) => {
      e.preventDefault();
      toggleMenu();
    };
    trigger.addEventListener("click", handler);
    return () => trigger.removeEventListener("click", handler);
  }, [toggleMenu]);

  // Close menu when route changes (e.g. after client nav)
  useEffect(() => {
    document.body.classList.remove("primary-menu-open");
    const menu = document.querySelector(".primary-menu .menu-container");
    if (menu) menu.classList.remove("d-block");
  }, [pathname]);

  const tel = phone?.replace(/\D/g, "") || FALLBACK_PHONE;
  const telHref = `tel:${tel}`;
  const headerBackground = isHome && !isSticky ? "transparent" : "#101010";

  const pickLocale = (next: Locale) => {
    setLocale(next);
    closeMenu();
  };

  return (
    <header
      id="header"
      className={`${isHome ? "transparent-header dark" : "dark"} header-size-md`}
      data-sticky-class="dark-color"
      data-sticky-shrink-offset="0"
    >
      <div id="header-wrap" style={{ backgroundColor: headerBackground, transition: "background-color 220ms ease" }}>
        <div className="container">
          <div className="header-row">
            <div id="logo">
              <Link href="/" className="standard-logo" data-dark-logo={logoSrc} data-sticky-logo={logoSrc}>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={logoSrc}
                  alt="Das House Logo"
                  onError={() => setLogoSrc(LOGO_FALLBACK)}
                />
              </Link>
              <Link href="/" className="retina-logo" data-dark-logo={logoSrc} data-sticky-logo={logoSrc}>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={logoSrc}
                  alt="Das House Logo"
                  onError={() => setLogoSrc(LOGO_FALLBACK)}
                />
              </Link>
            </div>

            <div id="primary-menu-trigger" aria-label="Toggle menu">
              <svg className="svg-trigger" viewBox="0 0 100 100">
                <path d="m 30,33 h 40 c 3.722839,0 7.5,3.126468 7.5,8.578427 0,5.451959 -2.727029,8.421573 -7.5,8.421573 h -20" />
                <path d="m 30,50 h 40" />
                <path d="m 70,67 h -40 c 0,0 -7.5,-0.802118 -7.5,-8.365747 0,-7.563629 7.5,-8.634253 7.5,-8.634253 h 20" />
              </svg>
            </div>

            <nav className="primary-menu mobile-menu-off-canvas">
              <ul
                className={`menu-container ${isHome ? "one-page-menu" : ""}`}
                data-easing="easeInOutExpo"
                data-speed="1250"
                data-offset="60"
              >
                {isHome ? (
                  <>
                    <li className="current menu-item">
                      <a className="menu-link" href="#slider" onClick={closeMenu}>
                        <div>{t.nav.home}</div>
                      </a>
                    </li>
                    <li className="menu-item">
                      <a className="menu-link" href="#story" onClick={closeMenu}>
                        <div>{t.nav.story}</div>
                      </a>
                    </li>
                    <li className="menu-item">
                      <a className="menu-link" href="#menu" onClick={closeMenu}>
                        <div>{t.nav.menu}</div>
                      </a>
                    </li>
                    <li className="menu-item">
                      <a className="menu-link" href="#reservations" onClick={closeMenu}>
                        <div>{t.nav.reservations}</div>
                      </a>
                    </li>
                    <li className="menu-item">
                      <Link href="/gift-shop" className="menu-link">
                        <div>{t.nav.giftShop}</div>
                      </Link>
                    </li>
                    <li className="menu-item">
                      <Link href="/adopt" className="menu-link">
                        <div>{t.nav.adopt}</div>
                      </Link>
                    </li>
                    <li className="menu-item">
                      <a className="menu-link" href="#contact" data-offset="20" onClick={closeMenu}>
                        <div>{t.nav.contact}</div>
                      </a>
                    </li>
                  </>
                ) : (
                  <>
                    <li className="menu-item">
                      <Link href="/" className="menu-link">
                        <div>{t.nav.home}</div>
                      </Link>
                    </li>
                    <li className="menu-item">
                      <Link href="/#story" className="menu-link">
                        <div>{t.nav.story}</div>
                      </Link>
                    </li>
                    <li className="menu-item">
                      <Link href="/#menu" className="menu-link">
                        <div>{t.nav.menu}</div>
                      </Link>
                    </li>
                    <li className="menu-item">
                      <Link href="/#reservations" className="menu-link">
                        <div>{t.nav.reservations}</div>
                      </Link>
                    </li>
                    <li className={`menu-item ${pathname === "/gift-shop" ? "current" : ""}`}>
                      <Link href="/gift-shop" className="menu-link">
                        <div>{t.nav.giftShop}</div>
                      </Link>
                    </li>
                    <li className={`menu-item ${pathname === "/adopt" ? "current" : ""}`}>
                      <Link href="/adopt" className="menu-link">
                        <div>{t.nav.adopt}</div>
                      </Link>
                    </li>
                    <li className="menu-item">
                      <Link href="/#contact" className="menu-link">
                        <div>{t.nav.contact}</div>
                      </Link>
                    </li>
                  </>
                )}
                <li className="menu-item lang-toggle-item">
                  <div className="menu-link lang-toggle" role="group" aria-label={t.nav.langLabel}>
                    <button
                      type="button"
                      className={`lang-toggle-btn${locale === "de" ? " is-active" : ""}`}
                      aria-pressed={locale === "de"}
                      onClick={() => pickLocale("de")}
                    >
                      DE
                    </button>
                    <span className="lang-toggle-sep" aria-hidden="true">
                      |
                    </span>
                    <button
                      type="button"
                      className={`lang-toggle-btn${locale === "en" ? " is-active" : ""}`}
                      aria-pressed={locale === "en"}
                      onClick={() => pickLocale("en")}
                    >
                      EN
                    </button>
                  </div>
                </li>
                <li className="noborder menu-item">
                  <a className="menu-link" href={telHref}>
                    <div>{phone || "+43 677 634 238 81"}</div>
                  </a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
      <div className="header-wrap-clone" />
    </header>
  );
}
