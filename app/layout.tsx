import type { Metadata } from "next";
import "./globals.css";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { GotoTop } from "@/components/goto-top";

export const metadata: Metadata = {
  title: "Das House | Restaurant & Café",
  description: "Das House - Purr-fect Brews & Bites! Coffee, cocktails, and adorable cats.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" dir="ltr">
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
      </head>
      <body className="stretched">
        <div className="body-overlay" />
        <div id="wrapper" className="clearfix">
          <SiteHeader />
          {children}
          <SiteFooter />
          <GotoTop />
        </div>
      </body>
    </html>
  );
}
