import type { Metadata } from "next";
import "./globals.css";
import { SiteLayoutWrapper } from "@/components/site-layout-wrapper";
import { LocalBusinessJsonLd } from "@/components/local-business-json-ld";

const SITE_URL = "https://dashouse.at";
const TITLE = "Das House | Dein gemütliches Revier im Sechsten";
const DESCRIPTION =
  "Katzenkaffee & Café in Wien 1060 – Kaffee genießen in samtiger Gesellschaft. Das House, Gumpendorfer Straße 51.";

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: TITLE,
  description: DESCRIPTION,
  icons: { icon: "/demos/burger/images/logo-hakane3.png" },
  alternates: {
    canonical: "/",
  },
  openGraph: {
    title: TITLE,
    description: DESCRIPTION,
    url: SITE_URL,
    siteName: "Das House",
    locale: "de_AT",
    type: "website",
  },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="de" dir="ltr">
      <head>
        <link
          href="https://fonts.googleapis.com/css?family=Dosis:400,500,600,700|Open+Sans:400,600,700|Dancing+Script&display=swap"
          rel="stylesheet"
          type="text/css"
        />
        <link rel="stylesheet" href="/css/bootstrap.css" type="text/css" />
        <link rel="stylesheet" href="/style.css" type="text/css" />
        <link rel="stylesheet" href="/css/dark.css" type="text/css" />
        <link rel="stylesheet" href="/css/font-icons.css" type="text/css" />
        <link rel="stylesheet" href="/css/animate.css" type="text/css" />
        <link rel="stylesheet" href="/css/magnific-popup.css" type="text/css" />
        <link rel="stylesheet" href="/css/custom.css" type="text/css" />
        <link rel="stylesheet" href="/css/colors.css" type="text/css" />
        <link rel="stylesheet" href="/demos/burger/css/fonts.css" type="text/css" />
        <link rel="stylesheet" href="/demos/burger/burger.css" type="text/css" />
        <link rel="stylesheet" href="/css/das-house-menu.css" type="text/css" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <LocalBusinessJsonLd />
      </head>
      <body className="stretched">
        <div className="body-overlay" />
        <div id="wrapper" className="clearfix">
          <SiteLayoutWrapper>{children}</SiteLayoutWrapper>
        </div>
      </body>
    </html>
  );
}
