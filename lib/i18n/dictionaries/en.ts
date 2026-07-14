export type Dictionary = {
  nav: {
    home: string;
    story: string;
    menu: string;
    reservations: string;
    giftShop: string;
    contact: string;
    langLabel: string;
  };
  hero: {
    line1Lead: string;
    line1Trail: string;
    line2Lead: string;
    line2Trail: string;
    lead: string;
    seeMenu: string;
    reserve: string;
  };
  story: {
    eyebrow: string;
    heading: string;
    body: string;
  };
  menu: {
    cafeEyebrow: string;
    heading: string;
    download: string;
    loading: string;
    ourMenu: string;
    vegetarian: string;
    vegan: string;
    glutenFree: string;
  };
  reservations: {
    eyebrow: string;
    heading: string;
    body: string;
  };
  contact: {
    address: string;
    phone: string;
    email: string;
    time: string;
    closed: string;
    fallbackAddressLine1: string;
    fallbackAddressLine2: string;
    mapTitle: string;
  };
  days: {
    monday: string;
    tuesday: string;
    wednesday: string;
    thursday: string;
    friday: string;
    saturday: string;
    sunday: string;
  };
  daysAbbr: {
    monday: string;
    tuesday: string;
    wednesday: string;
    thursday: string;
    friday: string;
    saturday: string;
    sunday: string;
  };
  giftShop: {
    title: string;
    subtitle: string;
    loading: string;
    empty: string;
    noImage: string;
    aboutHeading: string;
    aboutBody: string;
    close: string;
    giftItemFallback: string;
  };
  footer: {
    copyright: string;
  };
};

export const en: Dictionary = {
  nav: {
    home: "Home",
    story: "Story",
    menu: "Menu",
    reservations: "Reservations",
    giftShop: "Gift Shop",
    contact: "Contact",
    langLabel: "Language",
  },
  hero: {
    line1Lead: "Purr-fect",
    line1Trail: "Brews",
    line2Lead: "&",
    line2Trail: "Bites!",
    lead: "Enjoy delicious food, cocktails, and specialty brews—all while making a difference. Come for the bites, stay for the purrs!",
    seeMenu: "See Menu",
    reserve: "Reserve a Table",
  },
  story: {
    eyebrow: "Our Story",
    heading: "Coffee, Cocktails, and Adorable Cats",
    body: "Where great coffee, handcrafted cocktails, and the company of furry friends await you! With over 500 cats and dogs rescued and adopted, every sip you take and every moment you spend here helps us continue this incredible journey.",
  },
  menu: {
    cafeEyebrow: "DASHOUSE Cafe Bar",
    heading: "Menu",
    download: "Download Full Menu",
    loading: "Loading our delicious menu...",
    ourMenu: "Our Menu",
    vegetarian: "Vegetarian",
    vegan: "Vegan",
    glutenFree: "Gluten Free",
  },
  reservations: {
    eyebrow: "Book Your Visit",
    heading: "Reservations",
    body: "Reserve your table online in just a few clicks. Choose your date, time, and party size — we look forward to welcoming you to Das House.",
  },
  contact: {
    address: "Address:",
    phone: "Phone Number:",
    email: "Email:",
    time: "Time:",
    closed: "Closed",
    fallbackAddressLine1: "Austria, Vienna",
    fallbackAddressLine2: "Gumpendorfer strasse 51",
    mapTitle: "Das House Map",
  },
  days: {
    monday: "Monday",
    tuesday: "Tuesday",
    wednesday: "Wednesday",
    thursday: "Thursday",
    friday: "Friday",
    saturday: "Saturday",
    sunday: "Sunday",
  },
  daysAbbr: {
    monday: "Mon",
    tuesday: "Tue",
    wednesday: "Wed",
    thursday: "Thu",
    friday: "Fri",
    saturday: "Sat",
    sunday: "Sun",
  },
  giftShop: {
    title: "Das House Gift Shop",
    subtitle: "Take a piece of Das House home with you",
    loading: "Loading our amazing merchandise...",
    empty: "No gift shop items available at the moment.",
    noImage: "No image",
    aboutHeading: "About Our Merchandise",
    aboutBody:
      "All our merchandise is carefully selected and designed to reflect the spirit of Das House. From cozy t-shirts to beautiful mugs and unique coasters, each item helps support our mission of caring for cats and creating a warm community space.",
    close: "Close",
    giftItemFallback: "Gift Item",
  },
  footer: {
    copyright: "Copyrights © 2026 All Rights Reserved by Dashouse Inc.",
  },
};
