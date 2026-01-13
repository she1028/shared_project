// Wedding Packages
const weddingInclusions = [
    {
        id: "standard",
        offer: "CLASSIC OFFER",
        title: "Standard Package",
        price: "700 per guest",
        note: "Price may vary depending on menu selection, number of guests, service preferences, and location.",
        image: "images/packages/wedding/standard.jpg",
        menu: [
            "1 appetizer",
            "2 main courses",
            "1 desserts",
            "Steamed rice",
            "Iced tea",
            "Purified water"
        ],
        rentals: [
            "Chafing dishes",
            "Serving utensils",
            "Tables with basic skirting",
            "Dining tables and chairs",
            "Basic tablecloths and napkins",
            "Plates, cutlery, and glasses"
        ],
        decorations: [
            "Simple table styling",
            "Basic floral arrangement (artificial or minimal fresh flowers)",
            "Menu labels and table numbers"
        ],
        services: [
            "Uniformed service staff",
            "Setup and clean-up",
            "Food safety and sanitation compliance"
        ]
    },
    {
        id: "premium",
        offer: "SIGNATURE OFFER",
        title: "Premium Package",
        price: "1,200 per guest",
        note: "Premium inclusions with upgraded styling and menu options.",
        image: "images/packages/wedding/premium.jpg",
        menu: [
            "2 Appetizers",
            "3 Main dishes",
            "2 Desserts",
            "Steamed rice",
            "3 types of beverages",
            "Purified Water"
        ],
        rentals: [
            "Chafing dishes",
            "Serving utensils",
            "Menu labels and table numbers",
            "Themed buffet tables with elegant skirting",
            "Round tables and banquet chairs",
            "Tablecloths, napkins, and chair covers with sashes",
            "Complete tableware set (plates, cutlery, glasses)",
            "Cake and gift tables"
        ],
        decorations: [
            "Themed dessert table styling",
            "Table centerpieces",
            "Welcome signage",
            "Basic stage or backdrop styling",
            "Accent lighting (fairy lights or uplights)"
        ],
        services: [
            "Trained and uniformed service staff",
            "Event coordinator (basic)",
            "Setup and dismantling",
            "Food sanitation and quality control"
        ]
    },
    {
        id: "deluxe",
        offer: "PRESTIGE OFFER",
        title: "Deluxe Package",
        price: "1,800 per guest",
        note: "Deluxe inclusions with premium styling and menu options.",
        image: "images/packages/wedding/deluxe.jpg",
        menu: [
            "3 appetizer",
            "5 main courses",
            "4 desserts",
            "Steamed rice",
            "4 types of beverages",
            "Purified water"
        ],
        rentals: [
            "Chafing dishes",
            "Serving ware",
            "Elegant round tables and premium chairs (Chiavari/Tiffany)",
            "Luxury tablecloths, napkins, chargers",
            "Chair covers with customized sashes",
            "Complete premium tableware set",
            "Cake table, gift table, and registration table",
            "Stage and aisle setup"
        ],
        decorations: [
            "Themed food and dessert table styling",
            "Table centerpieces",
            "Welcome signage",
            "Elegant stage or backdrop styling",
            "Accent lighting (fairy lights or uplights)"
        ],
        services: [
            "Trained and uniformed service staff",
            "Event coordinator (dedicated)",
            "Setup and dismantling",
            "Food sanitation and quality control"
        ]
    }
];

// Children's Party Packages
const childrensPartyInclusions = [
  {
    id: "little-star",
    offer: "CLASSIC OFFER",
    title: "Little Star Package",
    price: "500 per guest",
    note: "Perfect for small children’s parties. Price may vary depending on menu selection, number of guests, and decorations.",
    image: "images/packages/children/little-star.jpg",
    menu: [
      "2 Appetizers",
      "3 Main courses",
      "1 Desserts",
      "Steamed rice or pasta",
      "Juice",
      "Purified water"
    ],
    rentals: [
      "Children’s tables and chairs",
      "Colorful tablecloths and napkins",
      "Kid-friendly plates, cups, and cutlery",
      "Serving utensils",
      "Chafing dishes"
    ],
    decorations: [
      "Simple balloon decorations",
      "Themed table centerpieces",
      "Party banners",
      "Food labels"
    ],
    services: [
      "Uniformed service staff",
      "Setup and cleanup",
      "Food safety and sanitation"
    ]
  },
  {
    id: "super-star",
    offer: "SIGNATURE OFFER",
    title: "Super Star Package",
    price: "750 per guest",
    note: "Ideal for medium-sized children’s parties with added decorations and activities.",
    image: "images/packages/children/super-star.jpg",
    menu: [
      "3 Appetizers",
      "4 Main courses",
      "2 Desserts",
      "Steamed rice or pasta",
      "Juice and soft drinks",
      "Purified water"
    ],
    rentals: [
      "Children’s tables and chairs",
      "Premium tablecloths and napkins",
      "Complete dining ware",
      "Serving utensils",
      "Chafing dishes",
      "Cake table and gift table"
    ],
    decorations: [
      "Balloon arch",
      "Themed backdrop",
      "Table centerpieces",
      "Welcome signage",
      "Party props",
      "Food labels",
      "Cake table styling"
    ],
    services: [
      "Party host (basic)",
      "Simple party games",
      "Uniformed service staff",
      "Setup and cleanup",
      "Basic party host"
    ]
  },
  {
    id: "mega-star",
    offer: "PRESTIGE OFFER",
    title: "Mega Star Package",
    price: "850 per guest",
    note: "Best for large and grand children’s parties with full decorations and entertainment.",
    image: "images/packages/children/mega-star.jpg",
    menu: [
      "4 Appetizers",
      "5 Main courses",
      "4 Desserts",
      "Steamed rice or pasta",
      "Juice, soft drinks, and Lemonade",
      "Purified water"
    ],
    rentals: [
      "Children’s tables and chairs",
      "Tablecloths, chair covers, and runners",
      "Premium table setup",
      "Complete dining ware",
      "Cake, gift, and activity tables",
      "Stage or activity area setup",
      "Serving utensils",
      "Chafing dishes"
    ],
    decorations: [
      "Grand themed balloon setup",
      "Themed backdrop with name and age celebrant",
      "Ceiling balloons or streamers",
      "Table centerpieces",
      "Entrance decor",
      "Party props",
      "Food labels",
      "Cake table styling"
    ],
    services: [
      "Full catering and event team",
      "Party coordinator",
      "Setup, styling, and dismantling",
      "Strict food safety and sanitation"
    ]
  }
];

// Corporate Packages
const corporateInclusions = [
  {
    id: "essential",
    offer: "CLASSIC OFFER",
    title: "Essential Package",
    price: "600 per guest",
    note: "Perfect for small corporate gatherings. Price may vary depending on menu selection, number of attendees, and styling.",
    image: "images/packages/corporate/essential.jpg",
    menu: [
      "1 Appetizer",
      "2 Main courses",
      "2 Dessert",
      "Steamed rice or pasta",
      "1 type of beverage",
      "Purified water"
    ],
    rentals: [
      "Tables and chairs for attendees",
      "Basic tablecloths and napkins",
      "Serving utensils",
      "Chafing dishes",
      "Plates, cutlery, and glasses"
    ],
    decorations: [
      "Minimalist table styling",
      "Minimal floral arrangements",
      "Menu labels and table numbers",
      "Corporate signage"
    ],
    services: [
      "Uniformed service staff",
      "Setup and cleanup",
      "Food safety and sanitation"
    ]
  },
  {
    id: "professional",
    offer: "SIGNATURE OFFER",
    title: "Professional Package",
    price: "800 per guest",
    note: "Ideal for medium-sized corporate events with enhanced styling and catering options.",
    image: "images/packages/corporate/professional.jpg",
    menu: [
      "2 Appetizers",
      "3 Main courses",
      "3 Desserts",
      "Steamed rice or pasta",
      "2 types of beverages",
      "Purified water"
    ],
    rentals: [
      "Tables and chairs for attendees",
      "Premium tablecloths and napkins",
      "Serving utensils",
      "Chafing dishes",
      "Complete dining ware",
      "Dessert table styling"
    ],
    decorations: [
      "Table centerpieces",
      "Themed signage or branding",
      "Menu labels",
      "Stage or podium setup",
      "Lighting accents"
    ],
    services: [
      "Professional service staff",
      "Event coordinator (basic)",
      "Setup and dismantling",
      "Food safety and sanitation"
    ]
  },
  {
    id: "executive",
    offer: "PRESTIGE OFFER",
    title: "Executive Package",
    price: "1,200 per guest",
    note: "Best for large corporate events with full-service catering, premium styling, and executive-level support.",
    image: "images/packages/corporate/executive.jpg",
    menu: [
      "4 Appetizers",
      "5 Main courses",
      "4 Desserts",
      "Steamed rice or pasta",
      "4 types of beverages",
      "Purified water"
    ],
    rentals: [
      "Premium tables and chairs",
      "Luxury tablecloths, napkins, and runners",
      "Serving utensils",
      "Chafing dishes",
      "Complete premium dining ware",
      "Presentation tables",
      "Podium or stage setup"
    ],
    decorations: [
      "Elegant table styling",
      "Corporate branding/signage",
      "Centerpieces and floral accents",
      "Stage, podium, or presentation area",
      "Lighting accents for the venue"
    ],
    services: [
      "Full catering and service team",
      "Event coordinator (dedicated)",
      "Setup, styling, and dismantling",
      "Professional service staff",
      "Food safety and sanitation"
    ]
  }
];

// Debut Packages
const debutInclusions = [
  {
    id: "Grace",
    offer: "CLASSIC OFFER",
    title: "Grace Package",
    price: "700 per guest",
    note: "Perfect for intimate 18th birthday celebrations of 30–40 guests. Price may vary depending on menu selection, number of attendees, and styling preferences.",
    image: "images/packages/debut/grace.jpg",
    menu: [
      "1 Appetizer",
      "2 Main courses",
      "2 Dessert",
      "Steamed rice or pasta",
      "1 type of beverage",
      "Purified water"
    ],
    rentals: [
      "Tables and chairs for attendees",
      "Basic tablecloths and napkins",
      "Serving utensils",
      "Chafing dishes",
      "Plates, cutlery, and glasses"
    ],
    decorations: [
      "Minimalist table styling",
      "Simple floral arrangements",
      "Menu labels",
      "Cake table styling",
      "Welcome signage",
      "Birthday signage or banners"
    ],
    services: [
      "Uniformed service staff",
      "Setup and cleanup",
      "Food safety and sanitation"
    ]
  },
  {
    id: "elegance",
    offer: "SIGNATURE OFFER",
    title: "Elegance Package",
    price: "900 per guest",
    note: "Ideal for medium-sized debut celebrations of 50–70 guests with enhanced styling, curated menus, and coordinated event setup.",
    image: "images/packages/debut/elegance.jpg",
    menu: [
      "2 Appetizers",
      "3 Main courses",
      "2 Desserts",
      "Steamed rice or pasta",
      "2 types of beverages",
      "Purified water"
    ],
    rentals: [
      "Tables and chairs for attendees",
      "Premium tablecloths, napkins, and runners",
      "Serving utensils",
      "Chafing dishes",
      "Complete dining ware",
      "Dessert table setup",
      "Cake and gift tables",
      "Menu labels and table numbers"
    ],
    decorations: [
      "Elegant table styling",
      "Themed birthday signage",
      "Table centerpieces",
      "Backdrop or stage styling",
      "Lighting accents"
    ],
    services: [
      "Professional service staff",
      "Event coordinator (basic)",
      "Setup and dismantling",
      "Food safety and sanitation"
    ]
  },
  {
    id: "radiance",
    offer: "SIGNATURE OFFER",
    title: "Radiance Package",
    price: "1100 per guest",
    note: "Best for grand debut celebrations of 80–120 guests with full-service catering, premium styling, entertainment, and dedicated staff.",
    image: "images/packages/debut/radiance.jpg",
    menu: [
      "3 Appetizers",
      "5 Main courses",
      "4 Desserts",
      "Steamed rice or pasta",
      "4 types of beverages",
      "Purified water"
    ],
    rentals: [
      "Premium tables and chairs",
      "Chair covers with customized sashes",
      "Luxury tablecloths, napkins, and runners",
      "Serving utensils",
      "Chafing dishes",
      "Complete premium dining ware",
      "Presentation tables, cake and gift tables",
      "Stage and walkway setup"
    ],
    decorations: [
      "Elegant table styling",
      "Birthday signage and themed decor",
      "Table centerpieces",
      "Fresh floral arrangements",
      "Stage or backdrop styling",
      "Ceiling or wall draping",
      "Professional lighting setup",
      "Entrance decorations"
    ],
    services: [
      "Full catering and service team",
      "Dedicated event coordinator",
      "Setup, styling, and dismantling",
      "Professional service staff",
      "Entertainment coordination",
      "Food safety and sanitation"
    ]
  }
];

// Build normalized fallbacks (ids to lowercase) so we can gracefully fall back if the API is unavailable
const fallbackInclusionMap = {
  corporate: corporateInclusions.map(p => ({ ...p, id: (p.id || "").toLowerCase() })),
  wedding: weddingInclusions.map(p => ({ ...p, id: (p.id || "").toLowerCase() })),
  children: childrensPartyInclusions.map(p => ({ ...p, id: (p.id || "").toLowerCase() })),
  debut: debutInclusions.map(p => ({ ...p, id: (p.id || "").toLowerCase() }))
};

const inclusionMap = {};
const inclusionFetches = {};

function normalizeCategory(value) {
  const normalized = (value || "").toString().trim().toLowerCase();
  return ["wedding", "children", "debut", "corporate"].includes(normalized) ? normalized : "";
}

function normalizeItems(list) {
  const arr = Array.isArray(list) ? list : [];
  return arr.map(entry => {
    if (typeof entry === "string") return entry;
    if (entry && typeof entry === "object") {
      const text = entry.text || entry.item_text || "";
      const qtyRaw = entry.quantity ?? entry.qty ?? entry.item_order;
      const qty = Number.isFinite(qtyRaw) ? qtyRaw : parseInt(qtyRaw, 10);
      if (qty && text) return `${qty} x ${text}`;
      return text || "";
    }
    return "";
  }).filter(Boolean);
}

function normalizeInclusion(item) {
  if (!item || typeof item !== "object") return null;

  const normalizedId = (item.slug || item.id || "").toString().toLowerCase();

  return {
    id: normalizedId,
    slug: normalizedId,
    offer: item.offer || item.packageTitle || "",
    title: item.title || item.packageName || "",
    price: item.price || item.price_label || item.priceLabel || "",
    note: item.note || "",
    image: item.image || item.image_path || item.imagePath || "",
    menu: normalizeItems(item.menu),
    rentals: normalizeItems(item.rentals),
    decorations: normalizeItems(item.decorations),
    services: normalizeItems(item.services)
  };
}

async function loadPackageInclusions(category) {
  const cat = normalizeCategory(category);
  if (!cat) return [];

  if (Array.isArray(inclusionMap[cat]) && inclusionMap[cat].length) {
    return inclusionMap[cat];
  }

  if (!inclusionFetches[cat]) {
    inclusionFetches[cat] = fetch(`api/get_package_inclusions.php?category=${encodeURIComponent(cat)}`)
      .then(async (res) => {
        if (!res.ok) {
          throw new Error(`Failed to load inclusions (${res.status})`);
        }
        const data = await res.json();
        const normalized = Array.isArray(data)
          ? data.map(normalizeInclusion).filter(Boolean)
          : [];

        inclusionMap[cat] = normalized.length ? normalized : (fallbackInclusionMap[cat] || []);
        return inclusionMap[cat];
      })
      .catch((err) => {
        console.error("Inclusions fetch failed, using fallback.", err);
        inclusionMap[cat] = fallbackInclusionMap[cat] || [];
        return inclusionMap[cat];
      })
      .finally(() => {
        delete inclusionFetches[cat];
      });
  }

  return inclusionFetches[cat];
}

// Expose helpers globally for modal.js usage
window.loadPackageInclusions = loadPackageInclusions;
window.normalizeInclusion = normalizeInclusion;
window.normalizeCategory = normalizeCategory;
window.inclusionMap = inclusionMap;

// Kick off preload for the active page when available
if (typeof ACTIVE_PACKAGE_TYPE !== "undefined") {
  loadPackageInclusions(ACTIVE_PACKAGE_TYPE);
}