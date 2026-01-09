const weddingPackages = [
    {
        "id": "standard",
        "packageTitle": "CLASSIC OFFER",
        "packageName": "Standard Package",
        "description": "The Standard Package is ideal for intimate gatherings of 30–40 guests, offering full-service catering with reception setup and basic styling. It includes essential tables, chairs, dining ware, and serving utensils, along with a curated buffet menu featuring appetizers, main courses, and dessert—perfect for simple yet memorable celebrations.",
        "note": "Price may vary depending on menu selection, number of guests, service preferences, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 25,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/standard.jpg",
        "maxImageHeight": "800px"
    },
    {
        "id": "premium",
        "packageTitle": "SIGNATURE OFFER",
        "packageName": "Premium Package",
        "description": "The Premium Package is perfect for medium-sized gatherings of 50–70 guests. It includes premium full-service catering, reception styling, and additional menu options, with elegant table settings and upgraded service staff.",
        "note": "Price may vary depending on menu selection, number of guests, service preferences, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 45,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/premium.jpg",
        "maxImageHeight": "500px",
        "backgroundColor": "#D1B68B"
    },
    {
        "id": "deluxe",
        "packageTitle": "PRESTIGE OFFER",
        "packageName": "Deluxe Package",
        "description": "The Deluxe Package is designed for large gatherings of 80–120 guests, featuring full-service catering, luxurious reception styling, and a wide selection of gourmet dishes. Includes top-quality tables, chairs, dining ware, and staff to ensure a memorable event.",
        "note": "Price may vary depending on menu selection, number of guests, service preferences, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 65,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/deluxe.jpg",
        "maxImageHeight": "800px"
    }
];

const childrenPartyPackages = [
    {
        "id": "little-star",
        "packageTitle": "CLASSIC OFFER",
        "packageName": "Little Star Package",
        "description": "The Little Star Package is perfect for small children’s parties of 20–30 kids. It includes fun-themed setup, kid-friendly tables and chairs, complete dining ware, and a colorful buffet featuring favorite party snacks and meals—ideal for birthdays and playdates.",
        "note": "Price may vary depending on menu selection, number of kids, theme customization, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 18,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/little-star.jpg",
        "maxImageHeight": "500px"
    },
    {
        "id": "super-star",
        "packageTitle": "SIGNATURE OFFER",
        "packageName": "Super Star Package",
        "description": "The Super Star Package is designed for medium-sized children’s parties of 30–50 kids. It includes upgraded themed styling, interactive food stations, party favors, and a wider selection of kid-approved dishes—perfect for school celebrations and milestone birthdays.",
        "note": "Price may vary depending on menu selection, number of kids, theme customization, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 28,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/super-star.jpg",
        "maxImageHeight": "800px",
        "backgroundColor": "#F5D1E7",
        "buttoncolor": "#830050"
    },
    {
        "id": "mega-star",
        "packageTitle": "PRESTIGE OFFER",
        "packageName": "Mega Star Package",
        "description": "The Mega Star Package is ideal for large children’s parties of 50–80 kids. It features full themed venue styling, premium kids’ buffet selections, dessert stations, party games setup, and dedicated service staff to ensure a fun-filled and stress-free celebration.",
        "note": "Price may vary depending on menu selection, number of kids, theme customization, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 38,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/mega-star.jpg",
        "maxImageHeight": "500px"
    }
];

const debutPackages = [
    {
        "id": "Grace",
        "packageTitle": "CLASSIC OFFER",
        "packageName": "Grace Package",
        "description": "The Grace Package is perfect for intimate 18th birthday celebrations with 30–40 guests. It includes elegant venue setup, basic styling, complete tables and chairs, dining ware, and a curated buffet menu—ideal for a simple yet meaningful debut celebration.",
        "note": "Price may vary depending on menu selection, number of guests, styling preferences, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 32,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/grace.jpg",
        "maxImageHeight": "500px"
    },
    {
        "id": "elegance",
        "packageTitle": "SIGNATURE OFFER",
        "packageName": "Elegance Package",
        "description": "The Elegance Package is designed for medium-sized debut celebrations of 50–70 guests. It features enhanced venue styling, elegant table settings, themed décor, and an upgraded buffet selection—perfect for a stylish and memorable 18th birthday.",
        "note": "Price may vary depending on menu selection, number of guests, styling theme, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 48,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/elegance.jpg",
        "maxImageHeight": "800px",
        "backgroundColor": "#C6EBFF",
        "buttoncolor": "#353269"
    },
    {
        "id": "Radiance",
        "packageTitle": "PRESTIGE OFFER",
        "packageName": "Radiance Package",
        "description": "The Radiance Package is ideal for grand debut celebrations with 80–120 guests. It includes luxurious venue styling, premium buffet selections, dessert stations, coordinated program setup, and dedicated service staff to ensure a flawless and elegant debut event.",
        "note": "Price may vary depending on menu selection, number of guests, styling theme, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 65,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/radiance.jpg",
        "maxImageHeight": "500px"
    }
];

const corporatePackages = [
    {
        "id": "essential",
        "packageTitle": "CLASSIC OFFER",
        "packageName": "Essential Package",
        "description": "The Essential Package is perfect for small corporate gatherings of 20–40 attendees. It includes professional venue setup, basic styling, tables and chairs, complete dining ware, and a curated buffet menu—ideal for meetings, team lunches, or small company events.",
        "note": "Price may vary depending on menu selection, number of attendees, styling preferences, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 35,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/essential.jpg",
        "maxImageHeight": "500px"
    },
    {
        "id": "professional",
        "packageTitle": "SIGNATURE OFFER",
        "packageName": "Professional Package",
        "description": "The Professional Package is designed for medium-sized corporate events of 50–80 attendees. It features enhanced venue styling, elegant table arrangements, corporate branding décor, and an upgraded buffet selection—perfect for company celebrations, seminars, or client meetings.",
        "note": "Price may vary depending on menu selection, number of attendees, styling theme, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 55,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/professional.jpg",
        "maxImageHeight": "800px",
        "backgroundColor": "#F2B8A7",
        "buttoncolor": "#BA2A00"
    },
    {
        "id": "executive",
        "packageTitle": "PRESTIGE OFFER",
        "packageName": "Executive Package",
        "description": "The Executive Package is ideal for large corporate events of 100–150 attendees. It includes premium venue styling, top-tier buffet selections, dessert stations, AV setup, and dedicated service staff to ensure a professional and seamless corporate experience.",
        "note": "Price may vary depending on menu selection, number of attendees, styling theme, and location.",
        "buttonText": "Full Inclusion",
        "startsAt": 75,
        "currency": "$",
        "bookLink": "event_form.php",
        "image": "images/packages/executive.jpg",
        "maxImageHeight": "500px"
    }
];

