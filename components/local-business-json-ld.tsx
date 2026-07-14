/** Suggested Google Business Profile description (paste into GBP manually):
 * Das House ist ein Katzenkaffee & Café in Wien 1060 (Gumpendorf). Veganes Frühstück,
 * Kaffee, Cocktails und Samtpfoten — Gumpendorfer Straße 51.
 */
const SITE_URL = "https://dashouse.at";

export function LocalBusinessJsonLd() {
  const data = {
    "@context": "https://schema.org",
    "@type": ["CafeOrCoffeeShop", "Restaurant"],
    name: "Das House",
    description:
      "Dein gemütliches Revier im Sechsten. Katzenkaffee & Café in Wien 1060 – Kaffee genießen in samtiger Gesellschaft.",
    url: SITE_URL,
    telephone: "+43-677-634-23881",
    email: "info@dashouse.at",
    image: `${SITE_URL}/demos/burger/images/logo-hakane3.png`,
    address: {
      "@type": "PostalAddress",
      streetAddress: "Gumpendorfer Straße 51",
      addressLocality: "Wien",
      postalCode: "1060",
      addressCountry: "AT",
    },
    geo: {
      "@type": "GeoCoordinates",
      latitude: 48.1965,
      longitude: 16.3505,
    },
    servesCuisine: ["Café", "Vegan", "Breakfast", "Cocktails"],
    priceRange: "€€",
    openingHoursSpecification: [
      {
        "@type": "OpeningHoursSpecification",
        dayOfWeek: ["Tuesday", "Wednesday", "Thursday"],
        opens: "10:00",
        closes: "23:30",
      },
      {
        "@type": "OpeningHoursSpecification",
        dayOfWeek: ["Friday", "Saturday"],
        opens: "10:00",
        closes: "01:00",
      },
      {
        "@type": "OpeningHoursSpecification",
        dayOfWeek: "Sunday",
        opens: "10:00",
        closes: "19:00",
      },
    ],
  };

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
    />
  );
}
