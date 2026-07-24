import {
  ShoppingCart,
  Shirt,
  Heart,
  Hotel,
  Coffee,
  Dumbbell,
  Activity,
  Gamepad2,
  Store,
  Scissors,
  Car
} from "lucide-react";

export const facilities = [
  {
    id: "supermarket",
    name: "Supermarket",
    description: "Pusat belanja kebutuhan harian lengkap dan segar.",
    icon: ShoppingCart,
  },
  {
    id: "fashion",
    name: "Fashion",
    description: "Pakaian tren terbaru untuk seluruh anggota keluarga.",
    icon: Shirt,
  },
  {
    id: "moslem-house",
    name: "Moslem House",
    description: "Pusat busana muslimah dan perlengkapan ibadah.",
    icon: Heart,
  },
  {
    id: "hotel-syariah",
    name: "Hotel Syariah",
    description: "Penginapan nyaman, bersih, dan berprinsip syariah.",
    icon: Hotel,
  },
  {
    id: "jajanan-subuh",
    name: "Jajanan Subuh",
    description: "Pusat kuliner tradisional dan aneka kue subuh.",
    icon: Coffee,
  },
  {
    id: "fitness-center",
    name: "SHSC Fitness Center",
    description: "Pusat kebugaran dengan alat modern dan profesional.",
    icon: Dumbbell,
  },
  {
    id: "kids-arena",
    name: "Arena Bermain Anak",
    description: "Area bermain yang aman, nyaman, dan edukatif.",
    icon: Gamepad2,
  },
  {
    id: "tenant-kuliner",
    name: "Tenant Kuliner & Ritel",
    description: "Menampilkan brand populer seperti Tomoro, Miniso, dll.",
    icon: Store,
  },
  {
    id: "salon-muslimah",
    name: "Salon Muslimah",
    description: "Perawatan kecantikan dengan privasi khusus wanita.",
    icon: Scissors,
  },
  {
    id: "autocare",
    name: "Autocare",
    description: "Jasa cuci dan service kendaraan selagi Anda berbelanja.",
    icon: Car,
  },
];

export const branches = [
  {
    id: 1,
    name: "Toserba Selamat Pusat",
    address: "Jl. A. Yani No. 1, Pusat Kota",
    lat: -6.914744,
    lng: 107.609810,
    facilities: ["supermarket", "fashion", "moslem-house", "hotel-syariah", "fitness-center", "tenant-kuliner"],
    openHours: "08:00 - 22:00",
  },
  {
    id: 2,
    name: "Toserba Selamat Cabang Timur",
    address: "Jl. Timur Raya No. 45",
    lat: -6.920000,
    lng: 107.620000,
    facilities: ["supermarket", "fashion", "jajanan-subuh", "kids-arena", "autocare"],
    openHours: "07:00 - 21:00",
  },
  {
    id: 3,
    name: "Toserba Selamat Cabang Barat",
    address: "Jl. Barat Jaya No. 12",
    lat: -6.910000,
    lng: 107.600000,
    facilities: ["supermarket", "moslem-house", "salon-muslimah", "tenant-kuliner"],
    openHours: "08:00 - 21:30",
  },
  // Add more branches to reach 26 in the backend later
];

export const companyStats = [
  { label: "Cabang Tersebar", value: "26+" },
  { label: "Unit Bisnis Utama", value: "11+" },
  { label: "Karyawan", value: "1000+" },
  { label: "Pelanggan Setia", value: "5000+" },
];
