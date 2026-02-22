/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "res.cloudinary.com" },
      { protocol: "https", hostname: "lvatvujwtyqwdsbqxjvm.supabase.co" }
    ]
  },
  // Avoid 404 for browser favicon request (no public/favicon.ico)
  async rewrites() {
    return [
      { source: "/favicon.ico", destination: "/demos/burger/images/logo-hakane3.png" },
    ];
  },
};

export default nextConfig;
