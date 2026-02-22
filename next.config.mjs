/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "res.cloudinary.com" },
      { protocol: "https", hostname: "lvatvujwtyqwdsbqxjvm.supabase.co" }
    ]
  },
  async rewrites() {
    return [
      { source: "/favicon.ico", destination: "/demos/burger/images/logo-hakane3.png" },
      // Legacy PHP API URLs → Next.js API (avoids 500 from PHP/__dirname on Vercel)
      { source: "/admin/api/menu-items.php", destination: "/api/menu-items" },
      { source: "/admin/api/categories.php", destination: "/api/categories" },
      { source: "/admin/api/business-info.php", destination: "/api/business-info" },
    ];
  },
};

export default nextConfig;
