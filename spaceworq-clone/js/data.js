// SpaceWorq Data Module
const SpaceWorqData = {
  cities: [
    { id: 'bengaluru', name: 'Bengaluru', spaces: '18 Spaces', image: 'assets/images/cities/bangalore.jpg' },
    { id: 'chennai', name: 'Chennai', spaces: '12 Spaces', image: 'assets/images/cities/chennai.jpg' },
    { id: 'mumbai', name: 'Mumbai', spaces: '15 Spaces', image: 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=800&q=80' },
    { id: 'delhi', name: 'Delhi NCR', spaces: '14 Spaces', image: 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=800&q=80' },
    { id: 'hyderabad', name: 'Hyderabad', spaces: '11 Spaces', image: 'assets/images/cities/hyderabad.jpg' },
    { id: 'pune', name: 'Pune', spaces: '9 Spaces', image: 'https://images.unsplash.com/photo-1595658658481-d53d3f999875?auto=format&fit=crop&w=800&q=80' }
  ],

  categories: [
    {
      id: 'DAY_PASS',
      title: 'Day Pass',
      subtitle: 'Flexible coworking for individuals',
      bullets: [
        'Desks-by-the-day in coworking spaces',
        'Starting at competitive daily pricing',
        'Ideal for individual or small teams'
      ],
      cta: 'Explore Day Pass',
      image: 'assets/images/space1.png',
      badge: 'On-Demand'
    },
    {
      id: 'LONG_TERM_LEASING',
      title: 'Long-term Leasing',
      subtitle: 'Stable setups for growing teams',
      bullets: [
        'Dedicated desks and private offices',
        'Monthly and long-term pricing options',
        'Best for scaling operations'
      ],
      cta: 'Explore Long-term Leasing',
      image: 'assets/images/space2.png',
      badge: 'Popular'
    },
    {
      id: 'MEETINGS_EVENTS',
      title: 'Meetings & Events',
      subtitle: 'Professional rooms for collaboration',
      bullets: [
        'Meeting rooms, training rooms, and event spaces',
        'Hourly and block-based booking options',
        'Ideal for client meetings and workshops'
      ],
      cta: 'Explore Meetings & Events',
      image: 'assets/images/space3.png',
      badge: 'Hourly'
    },
    {
      id: 'VIRTUAL_OFFICE',
      title: 'Virtual Office',
      subtitle: 'Business presence without full office lease',
      bullets: [
        'Premium business address in key cities',
        'GST and registration-ready options',
        'Mail handling and compliance support'
      ],
      cta: 'Explore Virtual Office',
      image: 'assets/images/space1.png',
      badge: 'Compliance'
    }
  ],

  howItWorks: [
    {
      number: '01',
      title: 'Search',
      description: 'Browse spaces by city, locality, and workspace type that fits your team.',
      icon: 'search'
    },
    {
      number: '02',
      title: 'Compare',
      description: 'Check amenities, pricing, reviews, and availability side by side.',
      icon: 'compass'
    },
    {
      number: '03',
      title: 'Book',
      description: 'Reserve instantly or schedule a visit with secure, verified bookings.',
      icon: 'shield'
    }
  ],

  benefits: [
    { text: 'Zero brokerage fee - Direct access to best properties', icon: 'check-circle' },
    { text: 'Quick turnaround - Swift and effective solutions', icon: 'check-circle' },
    { text: 'Largest network - 30+ cities with 5,000+ properties', icon: 'check-circle' },
    { text: 'Expert support - One-on-one guidance throughout', icon: 'check-circle' }
  ],

  workspaces: [
    {
      id: 'sp-1',
      name: 'BHIVE Workspace - HSR Layout',
      city: 'Bengaluru',
      locality: 'HSR Layout, Sector 6',
      nearBy: 'Near Silk Board Junction',
      category: 'LONG_TERM_LEASING',
      typeLabel: 'Coworking & Dedicated Desks',
      rating: 4.9,
      reviews: 142,
      availability: 'available',
      priceDay: 499,
      priceMonth: 7999,
      priceMeetingHour: 800,
      amenities: ['High-Speed WiFi', 'Power Backup', 'Cafeteria', 'Meeting Rooms', '24/7 Access', 'Metro Nearby'],
      images: ['assets/images/space1.png', 'assets/images/space2.png'],
      capacity: '1 - 50 Seats',
      badge: 'Verified & Instant Book'
    },
    {
      id: 'sp-2',
      name: 'WeWork Galaxy - Residency Road',
      city: 'Bengaluru',
      locality: 'Residency Road, Ashok Nagar',
      nearBy: 'Near MG Road Metro Station',
      category: 'LONG_TERM_LEASING',
      typeLabel: 'Private Suites & Managed Office',
      rating: 4.9,
      reviews: 218,
      availability: 'filling',
      priceDay: 699,
      priceMonth: 12500,
      priceMeetingHour: 1500,
      amenities: ['High-Speed WiFi', 'Ergonomic Chairs', 'Power Backup', 'Cafeteria', 'Meeting Rooms', 'Parking', 'Receptionist'],
      images: ['assets/images/space2.png', 'assets/images/space3.png'],
      capacity: '2 - 120 Seats',
      badge: 'Premium'
    },
    {
      id: 'sp-3',
      name: 'Awfis - One BKC',
      city: 'Mumbai',
      locality: 'Bandra Kurla Complex, Bandra East',
      nearBy: 'Near MCA Club BKC',
      category: 'LONG_TERM_LEASING',
      typeLabel: 'Enterprise Managed Office',
      rating: 4.8,
      reviews: 96,
      availability: 'available',
      priceDay: 649,
      priceMonth: 11000,
      priceMeetingHour: 1200,
      amenities: ['High-Speed WiFi', 'Power Backup', 'Cafeteria', 'Meeting Rooms', '24/7 Access', 'Parking'],
      images: ['assets/images/space3.png', 'assets/images/space1.png'],
      capacity: '1 - 80 Seats',
      badge: 'Top Rated'
    },
    {
      id: 'sp-4',
      name: 'Innov8 - Connaught Place',
      city: 'Delhi NCR',
      locality: 'Regal Building, Connaught Place',
      nearBy: 'Near Rajiv Chowk Metro Gate 2',
      category: 'DAY_PASS',
      typeLabel: 'Day Pass & Flexi Desks',
      rating: 4.7,
      reviews: 135,
      availability: 'available',
      priceDay: 449,
      priceMonth: 8500,
      priceMeetingHour: 950,
      amenities: ['High-Speed WiFi', 'Ergonomic Chairs', 'Power Backup', 'Cafeteria', 'Metro Nearby'],
      images: ['assets/images/space1.png', 'assets/images/space3.png'],
      capacity: '1 - 30 Seats',
      badge: 'Instant Day Pass'
    },
    {
      id: 'sp-5',
      name: 'Smartworks - Guindy Olympia',
      city: 'Chennai',
      locality: 'Olympia Technology Park, Guindy',
      nearBy: 'Near Guindy Metro Station',
      category: 'LONG_TERM_LEASING',
      typeLabel: 'Enterprise Coworking',
      rating: 4.8,
      reviews: 84,
      availability: 'available',
      priceDay: 499,
      priceMonth: 7500,
      priceMeetingHour: 850,
      amenities: ['High-Speed WiFi', 'Power Backup', 'Cafeteria', 'Meeting Rooms', 'Parking', 'Metro Nearby'],
      images: ['assets/images/space2.png', 'assets/images/space1.png'],
      capacity: '5 - 150 Seats',
      badge: 'Verified'
    },
    {
      id: 'sp-6',
      name: 'Executive Boardroom - Cyber City',
      city: 'Delhi NCR',
      locality: 'DLF Cyber City, Building 10',
      nearBy: 'Near Cyber City Rapid Metro',
      category: 'MEETINGS_EVENTS',
      typeLabel: '16-Seater Boardroom & AV Suite',
      rating: 5.0,
      reviews: 48,
      availability: 'available',
      priceDay: 2800,
      priceMonth: 25000,
      priceMeetingHour: 1800,
      amenities: ['High-Speed WiFi', 'Smart TV & Projector', 'Video Conferencing', 'Whiteboard', 'Catering Available'],
      images: ['assets/images/space3.png', 'assets/images/space2.png'],
      capacity: 'Up to 18 People',
      badge: 'High-Tech AV'
    },
    {
      id: 'sp-7',
      name: 'SpaceWorq Prime Virtual Office',
      city: 'Bengaluru',
      locality: 'Indiranagar 100ft Road',
      nearBy: 'Prime Commercial District',
      category: 'VIRTUAL_OFFICE',
      typeLabel: 'GST Business Registration & Mailroom',
      rating: 4.9,
      reviews: 160,
      availability: 'available',
      priceDay: 0,
      priceMonth: 1499,
      priceMeetingHour: 600,
      amenities: ['GST Documentation', 'Mail & Parcel Handling', 'Director KYC Assistance', 'Meeting Credits Included'],
      images: ['assets/images/space1.png', 'assets/images/space2.png'],
      capacity: 'Corporate Address',
      badge: 'ROC & GST Ready'
    },
    {
      id: 'sp-8',
      name: 'IndiQube Pearl - Hitech City',
      city: 'Hyderabad',
      locality: 'HITEC City, Madhapur',
      nearBy: 'Near Cyber Towers',
      category: 'LONG_TERM_LEASING',
      typeLabel: 'Dedicated Office & Team Pods',
      rating: 4.8,
      reviews: 92,
      availability: 'filling',
      priceDay: 549,
      priceMonth: 8200,
      priceMeetingHour: 900,
      amenities: ['High-Speed WiFi', 'Ergonomic Chairs', 'Power Backup', 'Cafeteria', 'Meeting Rooms', '24/7 Access'],
      images: ['assets/images/space2.png', 'assets/images/space3.png'],
      capacity: '4 - 80 Seats',
      badge: 'Filling Fast'
    }
  ],

  amenitiesList: [
    'High-Speed WiFi',
    'Ergonomic Chairs',
    'Power Backup',
    'Cafeteria',
    'Meeting Rooms',
    '24/7 Access',
    'Parking',
    'Metro Nearby',
    'Receptionist'
  ]
};
