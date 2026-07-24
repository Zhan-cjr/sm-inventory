"use client";

import { useState, useMemo } from "react";
import { motion } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { Search, MapPin, Clock, Navigation, CheckCircle2, Building2 } from "lucide-react";
import dynamic from "next/dynamic";

const DynamicMap = dynamic(() => import("@/components/MapComponent"), {
  ssr: false,
  loading: () => (
    <div className="h-full w-full flex items-center justify-center bg-slate-50">
      <div className="animate-spin rounded-full h-12 w-12 border-4 border-primary border-t-transparent shadow-md" />
    </div>
  ),
});

const cityChips = ["Semua", "Cianjur", "Sukabumi", "Bandung", "Garut", "Bogor"];

export default function LocationsPage() {
  const { branches, facilities, isLoading } = useCompanyProfile();
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCity, setSelectedCity] = useState("Semua");
  const [selectedBranch, setSelectedBranch] = useState<any>(null);

  const filteredBranches = useMemo(() => {
    if (!branches) return [];
    return branches.filter((branch) => {
      const matchSearch =
        branch.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        branch.address.toLowerCase().includes(searchQuery.toLowerCase());
      
      const matchCity =
        selectedCity === "Semua" ||
        branch.address.toLowerCase().includes(selectedCity.toLowerCase()) ||
        branch.name.toLowerCase().includes(selectedCity.toLowerCase());

      return matchSearch && matchCity;
    });
  }, [searchQuery, selectedCity, branches]);

  return (
    <div className="bg-slate-50 min-h-screen pt-28 flex flex-col">
      
      {/* Page Header */}
      <div className="container mx-auto px-4 md:px-8 mb-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-center max-w-3xl mx-auto space-y-4"
        >
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest">
            <Building2 size={14} /> Jaringan Store Official
          </span>
          <h1 className="text-4xl md:text-5xl font-black text-slate-900 tracking-tight">
            Peta Cabang <span className="text-primary">Toserba Selamat</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg">
            Temukan 26+ cabang store dan pusat fasilitas keluarga terdekat di kota Anda.
          </p>

          {/* Search Bar & City Chips */}
          <div className="space-y-4 max-w-2xl mx-auto pt-2">
            <div className="relative">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
              <input
                type="text"
                placeholder="Cari nama cabang, jalan, atau kota..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-12 pr-4 py-4 rounded-2xl border border-slate-200 shadow-md focus:ring-2 focus:ring-primary focus:border-primary outline-none text-slate-900 font-medium bg-white text-sm"
              />
            </div>

            <div className="flex flex-wrap items-center justify-center gap-2">
              {cityChips.map((city) => (
                <button
                  key={city}
                  onClick={() => setSelectedCity(city)}
                  className={`px-4 py-1.5 rounded-full text-xs font-bold transition-all ${
                    selectedCity === city
                      ? "bg-primary text-white shadow-md shadow-primary/20"
                      : "bg-white text-slate-600 hover:bg-slate-100 border border-slate-200"
                  }`}
                >
                  {city}
                </button>
              ))}
            </div>
          </div>
        </motion.div>
      </div>

      {isLoading ? (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-4 border-primary border-t-transparent"></div>
        </div>
      ) : (
        <div className="flex-1 flex flex-col md:flex-row relative bg-white border-t border-slate-200/80">
          
          {/* Branch List Sidebar */}
          <div className="w-full md:w-[440px] h-[calc(100vh-280px)] overflow-y-auto bg-slate-50/50 border-r border-slate-200/80 p-5 space-y-4 custom-scrollbar">
            {filteredBranches.map((branch) => {
              const isSelected = selectedBranch?.id === branch.id;
              return (
                <div
                  key={branch.id}
                  onClick={() => setSelectedBranch(branch)}
                  className={`p-5 rounded-3xl border cursor-pointer transition-all flex flex-col space-y-3 ${
                    isSelected
                      ? "border-primary bg-white shadow-xl ring-2 ring-primary/20"
                      : "border-slate-200 bg-white hover:border-primary/40 hover:shadow-md"
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <h3 className="font-extrabold text-lg text-slate-900">{branch.name}</h3>
                    <span className="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider flex items-center gap-1">
                      <CheckCircle2 size={12} /> BUKA HARIAN
                    </span>
                  </div>

                  <div className="flex items-start gap-2.5 text-slate-600 text-xs font-medium">
                    <MapPin size={16} className="shrink-0 text-primary mt-0.5" />
                    <p className="leading-relaxed">{branch.address}</p>
                  </div>

                  <div className="flex items-center gap-2.5 text-slate-600 text-xs font-medium">
                    <Clock size={16} className="shrink-0 text-secondary" />
                    <p>{branch.open_hours || "08.00 - 21.30 WIB"}</p>
                  </div>

                  {/* Branch Facilities */}
                  {branch.facilities && branch.facilities.length > 0 && (
                    <div className="pt-3 border-t border-slate-100">
                      <p className="text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">FASILITAS STORE:</p>
                      <div className="flex flex-wrap gap-1.5">
                        {branch.facilities.map((facId: string) => {
                          const fac = facilities.find((f: any) => f.identifier === facId);
                          if (!fac) return null;
                          const Icon = IconMapper[fac.icon] || MapPin;
                          return (
                            <div key={fac.id} className="bg-slate-100 px-2 py-1 rounded-md flex items-center gap-1.5 text-slate-700 text-[11px] font-medium" title={fac.name}>
                              <Icon size={12} className="text-primary" />
                              <span>{fac.name}</span>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  )}

                  {/* Google Maps Button */}
                  <div className="pt-2">
                    <a
                      href={`https://maps.google.com/?q=${branch.lat},${branch.lng}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      onClick={(e) => e.stopPropagation()}
                      className="w-full py-2.5 bg-primary hover:bg-primary-light text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all shadow-md shadow-primary/20"
                    >
                      <Navigation size={15} /> Navigasi Google Maps
                    </a>
                  </div>
                </div>
              );
            })}

            {filteredBranches.length === 0 && (
              <div className="text-center py-12 text-slate-400">
                <p className="font-bold text-sm">Tidak ada cabang untuk &quot;{searchQuery || selectedCity}&quot;</p>
              </div>
            )}
          </div>

          {/* Interactive Map Component */}
          <div className="hidden md:block flex-1 h-[calc(100vh-280px)] bg-slate-100 relative z-0">
            <DynamicMap
              branches={filteredBranches}
              selectedBranch={selectedBranch}
              onSelectBranch={setSelectedBranch}
            />
          </div>

        </div>
      )}
    </div>
  );
}
