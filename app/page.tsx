"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

type Category = {
  id: number;
  name: string;
  description: string | null;
  image_url?: string | null;
  sort_order?: number;
};

type MenuItem = {
  id: number;
  name: string;
  description: string | null;
  price: string | number;
  category_id: number;
  category_name?: string | null;
  is_vegetarian?: boolean;
  is_vegan?: boolean;
  is_gluten_free?: boolean;
};

type BusinessInfo = {
  business_name?: string;
  phone?: string | null;
  email?: string | null;
  address?: string | null;
  description?: string | null;
  website?: string | null;
  monday_open?: string | null;
  monday_close?: string | null;
  tuesday_open?: string | null;
  tuesday_close?: string | null;
  wednesday_open?: string | null;
  wednesday_close?: string | null;
  thursday_open?: string | null;
  thursday_close?: string | null;
  friday_open?: string | null;
  friday_close?: string | null;
  saturday_open?: string | null;
  saturday_close?: string | null;
  sunday_open?: string | null;
  sunday_close?: string | null;
};

const DAYS = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as const;

/** Format "09:00" / "09:00:00" as "9:00am", "23:30" as "11:30pm". */
function formatTimeForDisplay(t: string | null | undefined): string {
  if (!t || !t.trim()) return "";
  const parts = t.trim().slice(0, 5).split(":");
  const h = parseInt(parts[0], 10);
  const m = parts[1] ? parseInt(parts[1], 10) : 0;
  if (h === 12) return `${12}:${m.toString().padStart(2, "0")}pm`;
  if (h === 0) return `12:${m.toString().padStart(2, "0")}am`;
  if (h > 12) return `${h - 12}:${m.toString().padStart(2, "0")}pm`;
  return `${h}:${m.toString().padStart(2, "0")}am`;
}

function formatDayName(day: string): string {
  return day.charAt(0).toUpperCase() + day.slice(1);
}

const DAY_ABBR: Record<string, string> = {
  monday: "Mon",
  tuesday: "Tue",
  wednesday: "Wed",
  thursday: "Thu",
  friday: "Fri",
  saturday: "Sat",
  sunday: "Sun",
};

type HoursRow = { type: "range"; days: readonly string[]; open: string; close: string } | { type: "closed"; days: readonly string[] };

/** Group consecutive days with same open/close into ranges; closed days as separate or grouped. */
function getHoursRows(business: BusinessInfo): HoursRow[] {
  const rows: HoursRow[] = [];
  let i = 0;
  while (i < DAYS.length) {
    const day = DAYS[i];
    const open = business[`${day}_open`]?.trim();
    const close = business[`${day}_close`]?.trim();
    const hasHours = !!(open && close);
    if (hasHours) {
      const group: string[] = [day];
      while (i + 1 < DAYS.length) {
        const next = DAYS[i + 1];
        const no = business[`${next}_open`]?.trim();
        const nc = business[`${next}_close`]?.trim();
        if (no && nc && no === open && nc === close) {
          group.push(next);
          i++;
        } else break;
      }
      rows.push({ type: "range", days: group, open, close });
    } else {
      const group: string[] = [day];
      while (i + 1 < DAYS.length) {
        const next = DAYS[i + 1];
        const no = business[`${next}_open`]?.trim();
        const nc = business[`${next}_close`]?.trim();
        if (!no && !nc) {
          group.push(next);
          i++;
        } else break;
      }
      rows.push({ type: "closed", days: group });
    }
    i++;
  }
  return rows;
}

function formatHoursRow(row: HoursRow): string {
  if (row.type === "range") {
    const start = DAY_ABBR[row.days[0]] ?? row.days[0];
    const end = row.days.length > 1 ? (DAY_ABBR[row.days[row.days.length - 1]] ?? row.days[row.days.length - 1]) : start;
    const range = row.days.length > 1 ? `${start}–${end}` : start;
    return `${range} ${formatTimeForDisplay(row.open)} – ${formatTimeForDisplay(row.close)}`;
  }
  const labels = row.days.map((d) => DAY_ABBR[d] ?? d);
  const range = labels.length > 1 ? `${labels[0]}–${labels[labels.length - 1]}` : labels[0];
  return `${range} Closed`;
}

const CATEGORY_ICONS: Record<string, string> = {
  "Breakfast & Waffles": "burger.svg",
  "Snacks & Meze": "snacks.svg",
  Beverages: "drinks.svg",
  "Cocktails & Spirits": "drinks.svg",
};

const CATEGORY_IMAGES: Record<string, string> = {
  "Breakfast & Waffles": "/demos/burger/images/others/burger.png",
  "Snacks & Meze": "/demos/burger/images/others/snacks.png",
  Beverages: "/demos/burger/images/others/beverage.png",
  "Cocktails & Spirits": "/demos/burger/images/others/beverage-1.png",
};

const CATEGORY_IMAGE_SVG_FALLBACKS: Record<string, string> = {
  "Breakfast & Waffles": "/demos/burger/images/svg/burger.svg",
  "Snacks & Meze": "/demos/burger/images/svg/snacks.svg",
  Beverages: "/demos/burger/images/svg/drinks.svg",
  "Cocktails & Spirits": "/demos/burger/images/svg/drinks.svg",
};

function groupByCategory(items: MenuItem[], categories: Category[]): Record<string, MenuItem[]> {
  const map: Record<string, MenuItem[]> = {};
  for (const item of items) {
    const cat = categories.find((c) => c.id === item.category_id);
    const name = cat?.name ?? "Other";
    if (!map[name]) map[name] = [];
    map[name].push(item);
  }
  return map;
}

function getCategoryImage(cat: Category, name: string): string {
  if (cat?.image_url && (cat.image_url.startsWith("http") || cat.image_url.startsWith("/")))
    return cat.image_url;
  return CATEGORY_IMAGES[name] ?? CATEGORY_IMAGE_SVG_FALLBACKS[name] ?? "/demos/burger/images/svg/burger.svg";
}

export default function HomePage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [menuItems, setMenuItems] = useState<MenuItem[]>([]);
  const [business, setBusiness] = useState<BusinessInfo | null>(null);
  const [menuLoading, setMenuLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      fetch("/api/categories?is_active=1").then((r) => r.json()),
      fetch("/api/menu-items?is_active=1").then((r) => r.json()),
      fetch("/api/business-info").then((r) => r.json()),
    ])
      .then(([catRes, menuRes, bizRes]) => {
        setCategories((catRes?.data ?? []).slice(0, 20));
        setMenuItems(menuRes?.data ?? []);
        setBusiness(bizRes?.data ?? null);
      })
      .catch(() => undefined)
      .finally(() => setMenuLoading(false));
  }, []);

  const grouped = groupByCategory(menuItems, categories);
  const categoryNames = Object.keys(grouped).sort((a, b) => {
    const ca = categories.find((c) => c.name === a);
    const cb = categories.find((c) => c.name === b);
    const oa = ca?.sort_order ?? 0;
    const ob = cb?.sort_order ?? 0;
    return oa - ob || a.localeCompare(b);
  });

  const phone = business?.phone ?? "+43 677 634 238 81";
  const bookTel = phone.replace(/\D/g, "");

  return (
    <>
      {/* Hero - same structure as index.html #slider; animated so .slide-img::after doesn't cover background */}
      <section
        id="slider"
        className="slider-element min-vh-100 page-section slide-img img-to-right animated include-header"
        data-animate="img-to-right"
        style={{
          backgroundImage: "url('/demos/burger/images/cat-hero0.png')",
          backgroundPosition: "center center",
          backgroundRepeat: "no-repeat",
          backgroundSize: "cover",
        }}
      >
        <div className="slider-inner">
          <div className="vertical-middle">
            <div className="container dark">
              <div className="row justify-content-center">
                <div
                  className="col-lg-8 col-md-10 dotted-bg parallax text-center"
                  data-start="top: 0px; opacity: 1"
                  data-400="top: 50px; opacity: 0.3"
                  style={{ position: "relative", zIndex: 10 }}
                >
                  <div className="emphasis-title animated fadeInUp" data-animate="fadeInUp" style={{ color: "#fff", opacity: 1 }}>
                    <h1
                      className="font-border not-dark"
                      style={{ color: "#fff", textShadow: "5px 1px 2px rgba(0,0,0,0.8)", paddingTop: "1.5rem", fontSize: "clamp(2.4rem, 6vw, 5.5rem)", lineHeight: 1.05 }}
                    >
                      <span style={{ display: "block", whiteSpace: "nowrap" }}>
                        <span style={{ color: "#f5d742" }}>Purr-fect</span>{" "}
                        <span style={{ color: "#fff" }}>Brews</span>
                      </span>
                      <span style={{ display: "block", whiteSpace: "nowrap" }}>
                        <span style={{ color: "#fff" }}>&</span>{" "}
                        <span style={{ color: "#f5d742" }}>Bites!</span>
                      </span>
                    </h1>
                  </div>
                  <p className="lead animated fadeInUp" data-animate="fadeInUp" data-delay="100" style={{ color: "#fff", opacity: 1 }}>
                    Enjoy delicious food, cocktails, and specialty brews—all while making a difference. Come for the
                    bites, stay for the purrs!
                  </p>
                  <div className="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <a
                      href="#menu"
                      className="button button-large button-rounded px-4 button-border button-light button-white fw-semibold"
                      data-animate="fadeInUp"
                      data-delay="200"
                      data-scrollto="#menu"
                      data-easing="easeInOutExpo"
                      data-speed="1250"
                      onClick={(e) => {
                        e.preventDefault();
                        document.getElementById("menu")?.scrollIntoView({ behavior: "smooth" });
                      }}
                      style={{ opacity: 1 }}
                    >
                      See Menu
                    </a>
                    <a
                      href={`tel:${bookTel}`}
                      className="button button-large button-rounded px-4 button-reveal d-inline-flex tright fw-semibold"
                      data-animate="fadeInUp"
                      data-delay="300"
                      style={{ backgroundColor: "#f70000", color: "#fff", opacity: 1 }}
                    >
                      <i className="icon-line-arrow-right" />
                      <span>Book a Table</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Content - same structure as index.html #content */}
      <section id="content" className="dark-color">
        <div
          style={{
            position: "absolute",
            top: "-13px",
            left: 0,
            width: "100%",
            height: "30px",
            background: "url('/demos/burger/images/svg/brush.svg') no-repeat top center / 110% auto",
            zIndex: 2,
          }}
        />
        <div className="content-wrap p-0">
          {/* Story */}
          <div id="story" className="page-section">
            <div className="section my-0 dark-color">
              <div className="container dark">
                <div className="center bottommargin mx-auto" style={{ maxWidth: 700 }}>
                  <div className="before-heading font-primary color">Our Story</div>
                  <h1 className="font-secondary display-4 fw-bold">Coffee, Cocktails, and Adorable Cats</h1>
                  <p className="lead">
                    Where great coffee, handcrafted cocktails, and the company of furry friends await you! With over 500
                    cats and dogs rescued and adopted, every sip you take and every moment you spend here helps us
                    continue this incredible journey.
                  </p>
                  <div className="mx-auto my-5" style={{ maxWidth: 660 }}>
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src="/demos/burger/images/others/signboard2.png"
                      className="card-img"
                      alt="Das House"
                    />
                  </div>
                </div>
              </div>
            </div>
            <div className="clear" />
            <div className="section dark-color m-0 p-0">
              <div className="container dark">
                <div className="center bottommargin-lg">
                  <div className="before-heading font-secondary color">DASHOUSE Cafe Bar</div>
                  <h1 className="font-border display-4 ls1 fw-bold">Menu</h1>
                  <a
                    href="/demos/burger/images/others/menu.pdf"
                    download
                    data-easing="easeInOutExpo"
                    className="button button-large button-rounded px-4 button-border button-light button-white fw-semibold"
                  >
                    Download Full Menu
                  </a>
                </div>
                <div className="clear" />
              </div>
            </div>
          </div>

          <div className="clear" />

          {/* Menu */}
          <div id="menu" className="page-section">
            {menuLoading && (
              <div id="menu-loading" className="container dark py-5">
                Loading our delicious menu...
              </div>
            )}
            <div id="menu-error" className="container" style={{ display: "none" }} />
            <div id="dynamic-menu-container">
              {!menuLoading &&
                categoryNames.map((categoryName, index) => {
                  const items = grouped[categoryName] ?? [];
                  const category = categories.find((c) => c.name === categoryName);
                  const icon = CATEGORY_ICONS[categoryName] ?? "burger.svg";
                  const imageSrc = getCategoryImage(category ?? { id: 0, name: categoryName, description: null }, categoryName);
                  const isEven = index % 2 === 0;
                  const menuOrder = isEven ? "order-2 order-md-1" : "order-2 order-md-2";
                  const imageOrder = isEven ? "order-1 order-md-2" : "order-1 order-md-1";
                  return (
                    <div
                      key={categoryName}
                      className="section mb-0"
                      style={{
                        background:
                          "linear-gradient(to bottom, #101010, transparent, #101010), url('/demos/burger/images/others/section-2.jpg') no-repeat center top / cover",
                      }}
                    >
                      <div className="container">
                        <div className="row align-items-center">
                          <div className={`col-md-5 dark ${menuOrder}`}>
                            <div className="bottommargin">
                              <div className="before-heading font-secondary color mb-2">Our Menu</div>
                              <div className="d-flex align-items-center dotted-bg">
                                {/* eslint-disable-next-line @next/next/no-img-element */}
                                <img
                                  src={`/demos/burger/images/svg/${icon}`}
                                  alt=""
                                  width={60}
                                />
                                <h1 className="font-border display-4 ls1 fw-bold mb-0 ms-3">{categoryName}</h1>
                              </div>
                            </div>
                            <div className="clear" />
                            <ul className="list-unstyled m-0">
                              {items.map((item) => (
                                <li key={item.id} className="py-3">
                                  <div className="d-flex align-items-baseline justify-content-between gap-3">
                                    <div className="h5 mb-0 color">{item.name}</div>
                                    <div className="h5 mb-0 text-white">
                                      €{typeof item.price === "number" ? item.price.toFixed(2) : Number(item.price).toFixed(2)}
                                    </div>
                                  </div>
                                  {item.description && <p className="mb-1 text-white-50">{item.description}</p>}
                                  {(item.is_vegetarian || item.is_vegan || item.is_gluten_free) && (
                                    <div className="d-flex gap-2 flex-wrap mt-1">
                                      {item.is_vegetarian && <span className="badge bg-success">Vegetarian</span>}
                                      {item.is_vegan && <span className="badge bg-primary">Vegan</span>}
                                      {item.is_gluten_free && <span className="badge bg-warning">Gluten Free</span>}
                                    </div>
                                  )}
                                </li>
                              ))}
                            </ul>
                          </div>
                          <div className={`col-md-6 text-center ${imageOrder}`}>
                            {/* eslint-disable-next-line @next/next/no-img-element */}
                            <img
                              src={imageSrc}
                              alt={categoryName}
                              className="img-fluid"
                              style={{ maxWidth: 320, height: "auto", borderRadius: 8 }}
                              onError={(e) => {
                                const el = e.target as HTMLImageElement;
                                el.src = CATEGORY_IMAGE_SVG_FALLBACKS[categoryName] ?? "/demos/burger/images/svg/burger.svg";
                              }}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
            </div>
          </div>
          {/* Contact - same structure as index.html #contact */}
          <div
            id="contact"
            className="section page-section dark m-0 pb-0 pb-md-5 slide-img img-to-left animated"
            data-animate="img-to-left"
            style={{ background: "#101010 url('/demos/burger/images/icon-bg-white.png') repeat center center" }}
          >
            <div className="container pt-3 pb-4">
              <div className="row">
                <div className="col-sm-5" style={{ lineHeight: 1.7, zIndex: 1 }}>
                  <address className="d-block mb-5">
                    <div className="font-secondary h5 mb-2 color">Address:</div>
                    <span id="business-address" className="h6 text-white ls1 fw-normal font-primary">
                      {business?.address
                        ? business.address.split("\n").map((line, i) => (
                            <span key={i}>
                              {line}
                              <br />
                            </span>
                          ))
                        : (
                            <>
                              Austria, Vienna
                              <br />
                              Gumpendorfer strasse 51
                              <br />
                            </>
                          )}
                    </span>
                  </address>
                  <div className="font-secondary h5 mb-2 color">Phone Number:</div>
                  <a
                    href={`tel:${bookTel}`}
                    className="d-block h6 text-white ls1 fw-normal font-primary mb-5"
                    id="business-phone"
                  >
                    {phone}
                  </a>
                  <div className="font-secondary h5 mb-2 color">Email:</div>
                  <a
                    href={business?.email ? `mailto:${business.email}` : "mailto:info@dashouse.at?Subject=Hello%20again"}
                    className="d-block h6 text-white ls1 fw-normal font-primary mb-5"
                    id="business-email"
                  >
                    {business?.email ?? "info@dashouse.at"}
                  </a>
                  <div className="font-secondary h5 mb-2 color">Time:</div>
                  <div id="business-hours">
                    {business
                      ? getHoursRows(business).map((row, idx) => (
                          <div key={idx} className="h6 text-white ls1 fw-normal font-primary">
                            {formatHoursRow(row)}
                          </div>
                        ))
                      : (
                        <>
                          <div className="h6 text-white ls1 fw-normal font-primary">Tue–Thu 10:00am – 11:30pm</div>
                          <div className="h6 text-white ls1 fw-normal font-primary">Fri–Sat 10:00am – 1:00am</div>
                          <div className="h6 text-white ls1 fw-normal font-primary">Sunday 10:00am – 7:00pm</div>
                          <div className="h6 text-white ls1 fw-normal font-primary">Monday Closed</div>
                        </>
                      )}
                  </div>
                </div>
              </div>
            </div>
            <div
              id="map"
              className="gmap mt-5 mt-md-0"
              style={{ minHeight: 300, background: "#1a1a1a" }}
              data-address="Gumpendorfer strasse 51, Austria"
            >
              <iframe
                title="Das House Map"
                src="https://www.google.com/maps?q=Gumpendorfer+Strasse+51,+Vienna,+Austria&output=embed"
                width="100%"
                height="100%"
                style={{ border: 0, minHeight: 300 }}
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
              />
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
