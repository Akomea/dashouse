export type Dictionary = {
  nav: {
    home: string;
    story: string;
    menu: string;
    reservations: string;
    giftShop: string;
    adopt: string;
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
    adoptCta: string;
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
  adopt: {
    title: string;
    subtitle: string;
    intro: string;
    download: string;
    submit: string;
    submitting: string;
    success: string;
    error: string;
    requiredNote: string;
    yes: string;
    no: string;
    notApplicable: string;
    notYet: string;
    sections: {
      personal: string;
      household: string;
      living: string;
      employment: string;
      experience: string;
      motivation: string;
      additional: string;
    };
    fields: {
      fullName: string;
      dateOfBirth: string;
      phone: string;
      email: string;
      address: string;
      cityPostal: string;
      householdSize: string;
      children: string;
      childrenAges: string;
      otherAnimals: string;
      otherAnimalsDetail: string;
      homeType: string;
      apartment: string;
      house: string;
      livingSpace: string;
      balcony: string;
      secured: string;
      renting: string;
      landlordPermission: string;
      employmentStatus: string;
      fullTime: string;
      partTime: string;
      selfEmployed: string;
      student: string;
      vocationalTraining: string;
      notEmployed: string;
      profession: string;
      hoursAway: string;
      lessThan4: string;
      hours4to6: string;
      hours6to8: string;
      moreThan8: string;
      hadCats: string;
      hadCatsDetail: string;
      specialNeeds: string;
      whyAdopt: string;
      whichCats: string;
      anythingElse: string;
      consent: string;
      placeDate: string;
      signature: string;
    };
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
    adopt: "Adoption",
    contact: "Contact",
    langLabel: "Language",
  },
  hero: {
    line1Lead: "Your cozy",
    line1Trail: "spot",
    line2Lead: "in the",
    line2Trail: "Sixth.",
    lead: "Cat café & café in Vienna 1060 – coffee in soft-pawed company.",
    seeMenu: "See Menu",
    reserve: "Reserve a Table",
  },
  story: {
    eyebrow: "Our Story",
    heading: "Coffee, Cocktails, and Adorable Cats",
    body: "Where great coffee, handcrafted cocktails, and the company of furry friends await you! With over 500 cats and dogs rescued and adopted, every sip you take and every moment you spend here helps us continue this incredible journey.",
    adoptCta: "Interested in adopting? Fill out our form",
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
  adopt: {
    title: "Cat Adoption Application",
    subtitle: "Give a cat a loving home",
    intro:
      "Thank you for your interest in giving a cat a loving home! Please fill out this application honestly and completely. This helps us make sure you and the cat are a good match.",
    download: "Download PDF form",
    submit: "Submit application",
    submitting: "Sending…",
    success: "Thank you! Your adoption application was sent. We’ll be in touch soon.",
    error: "Something went wrong sending your application. Please try again or email us.",
    requiredNote: "Fields marked with * are required.",
    yes: "Yes",
    no: "No",
    notApplicable: "Not applicable",
    notYet: "Not yet, but I will get it",
    sections: {
      personal: "1. Personal information",
      household: "2. Household",
      living: "3. Living situation",
      employment: "4. Employment and daily life",
      experience: "5. Experience",
      motivation: "6. Motivation",
      additional: "7. Additional information",
    },
    fields: {
      fullName: "Full name",
      dateOfBirth: "Date of birth",
      phone: "Phone number",
      email: "Email address",
      address: "Current address",
      cityPostal: "City, postal code",
      householdSize: "How many people live in your household?",
      children: "Are there children in your household?",
      childrenAges: "If yes, how old?",
      otherAnimals: "Are there other animals in your household?",
      otherAnimalsDetail: "If yes, which and how many?",
      homeType: "Type of home",
      apartment: "Apartment",
      house: "House",
      livingSpace: "Size of living space (approx. m²)",
      balcony: "Do you have a balcony / terrace?",
      secured: "Is it secured (e.g. with a cat net)?",
      renting: "Are you renting your home?",
      landlordPermission: "If yes, do you have your landlord’s permission to keep cats?",
      employmentStatus: "What is your employment status?",
      fullTime: "Full-time",
      partTime: "Part-time",
      selfEmployed: "Self-employed",
      student: "Student",
      vocationalTraining: "In vocational training",
      notEmployed: "Not employed",
      profession: "What is your profession?",
      hoursAway: "How many hours are you usually away from home?",
      lessThan4: "Less than 4 hours",
      hours4to6: "4–6 hours",
      hours6to8: "6–8 hours",
      moreThan8: "More than 8 hours",
      hadCats: "Have you had cats before?",
      hadCatsDetail: "If yes, for how long and what happened to them?",
      specialNeeds: "Do you have experience with special needs? (e.g. sick, shy, or senior cats)",
      whyAdopt: "Why do you want to adopt a cat?",
      whichCats: "Which cat(s) are you interested in?",
      anythingElse: "Is there anything else you would like to tell us?",
      consent:
        "I hereby confirm that all information is true and correct and I agree that my data may be stored and used as part of the adoption process.",
      placeDate: "Place, date",
      signature: "Signature (full name)",
    },
  },
  footer: {
    copyright: "Copyrights © 2026 All Rights Reserved by Dashouse Inc.",
  },
};
