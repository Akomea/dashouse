"use client";

import { useEffect, useState } from "react";
import { useT } from "@/lib/i18n/language-context";
import "./gift-shop.css";

type GiftItem = {
  id: number;
  name: string;
  description: string | null;
  image_url: string;
  active: boolean;
  sort_order: number;
};

export default function GiftShopPage() {
  const t = useT();
  const [items, setItems] = useState<GiftItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState<GiftItem | null>(null);
  const [modalOpen, setModalOpen] = useState(false);

  useEffect(() => {
    fetch("/api/gift-shop?is_active=1")
      .then((r) => r.json())
      .then((d) => setItems(d.data ?? []))
      .catch(() => setItems([]))
      .finally(() => setLoading(false));
  }, []);

  const openModal = (item: GiftItem) => {
    setSelected(item);
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
    setSelected(null);
  };

  return (
    <div className="gift-shop-page">
      <section id="content">
        <div className="content-wrap" style={{ paddingTop: 0 }}>
          <div className="gift-shop-header">
            <div className="container">
              <h1 style={{ color: "white !important" }}>{t.giftShop.title}</h1>
              <p>{t.giftShop.subtitle}</p>
            </div>
          </div>

          <div className="section py-5" style={{ backgroundColor: "#f8f9fa" }}>
            <div className="container">
              {loading && (
                <div id="gift-shop-loading">
                  <p>{t.giftShop.loading}</p>
                </div>
              )}

              {!loading && items.length > 0 && (
                <div id="gift-shop-gallery" className="row g-4">
                  {items.map((item) => (
                    <div key={item.id} className="col-md-6 col-lg-4">
                      <div
                        className="gift-item-card"
                        onClick={() => openModal(item)}
                        onKeyDown={(e) => e.key === "Enter" && openModal(item)}
                        role="button"
                        tabIndex={0}
                      >
                        <div className="gift-image-container">
                          {item.image_url ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                              src={item.image_url}
                              alt={item.name}
                              className="gift-image"
                            />
                          ) : (
                            <div className="gift-image" style={{ background: "#eee", display: "flex", alignItems: "center", justifyContent: "center" }}>
                              <span className="text-muted">{t.giftShop.noImage}</span>
                            </div>
                          )}
                          <div className="gift-overlay">
                            <i className="icon-search" />
                          </div>
                        </div>
                        <div className="gift-content">
                          <h3 className="gift-title">{item.name}</h3>
                          <p className="gift-description">{item.description ?? ""}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {!loading && items.length === 0 && (
                <div className="text-center py-5 text-muted">
                  <p>{t.giftShop.empty}</p>
                </div>
              )}

              <div className="row justify-content-center mt-5">
                <div className="col-lg-8 text-center">
                  <div className="card border-0 shadow-sm">
                    <div className="card-body p-4">
                      <i className="icon-info-circle" style={{ fontSize: "2rem", marginBottom: 12 }} />
                      <h4 className="mb-3">{t.giftShop.aboutHeading}</h4>
                      <p className="text-muted mb-0">{t.giftShop.aboutBody}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Modal - Bootstrap-style markup for theme compatibility */}
      <div
        className={`modal fade ${modalOpen ? "show" : ""}`}
        id="giftModal"
        tabIndex={-1}
        aria-labelledby="giftModalLabel"
        aria-hidden={!modalOpen}
        style={{
          display: modalOpen ? "block" : "none",
          position: "fixed",
          inset: 0,
          zIndex: 1050,
          overflow: "auto",
          background: "rgba(0,0,0,0.5)",
        }}
        onClick={(e) => e.target === e.currentTarget && closeModal()}
      >
        <div className="modal-dialog modal-lg modal-dialog-centered" onClick={(e) => e.stopPropagation()}>
          <div className="modal-content" style={{ borderRadius: 15, overflow: "hidden" }}>
            <div className="modal-header border-0">
              <h5 className="modal-title" id="giftModalTitle">
                {selected?.name ?? t.giftShop.giftItemFallback}
              </h5>
              <button
                type="button"
                className="btn-close"
                aria-label={t.giftShop.close}
                onClick={closeModal}
              />
            </div>
            <div className="modal-body text-center">
              {selected?.image_url && (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  id="giftModalImage"
                  src={selected.image_url}
                  alt={selected.name}
                  className="img-fluid mb-3"
                  style={{ borderRadius: 10 }}
                />
              )}
              <p id="giftModalDescription" className="text-muted">
                {selected?.description ?? ""}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
