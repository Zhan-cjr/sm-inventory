"use client";

import { useState, useMemo } from "react";
import { motion } from "framer-motion";
import { useCompanyProfile } from "@/lib/hooks";
import { IconMapper } from "@/lib/icon-mapper";
import { Search, MapPin, Clock } from "lucide-react";
import dynamic from "next/dynamic";

// Dynamically import MapComponent to prevent SSR issues with Leaflet's window dependency
const DynamicMap = dynamic(() => import("@/components/MapComponent"), {
  ssr: false,
  loading: () => (
    <div className="h-full w-full flex items-center justify-center bg-slate-50">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
    </div>
  ),
});

export default function LocationsPage() {
  const { branches, facilities, isLoading } = useCompanyProfile();
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedBranch, setSelectedBranch] = useState<any>(null);

  const filteredBranches = useMemo(() => {
    if (!branches) return [];
    return branches.filter((branch) => 
      branch.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
      branch.address.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [searchQuery, branches]);

  return (
    <div className="pt-28 pb-0 h-screen flex flex-col">
      <div className="container mx-auto px-4 mb-8">
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-center max-w-3xl mx-auto"
        >
          <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-6">Temukan Cabang <span className="text-primary">Terdekat</span></h1>
          <p className="text-lg text-slate-600 font-medium mb-8">
            Kunjungi lokasi Toserba Selamat yang tersebar di berbagai wilayah untuk melayani kebutuhan keluarga Anda.
          </p>
          
          <div className="relative max-w-xl mx-auto">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
            <input 
              type="text"
              placeholder="Cari nama cabang atau wilayah..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-12 pr-4 py-4 rounded-2xl border border-slate-200 shadow-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none text-slate-900 font-medium bg-white"
            />
          </div>
        </motion.div>
      </div>

      {isLoading ? (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
      ) : (
      <div className="flex-1 flex flex-col md:flex-row relative bg-white border-t border-slate-200">
        
        {/* Sidebar List */}
        <div className="w-full md:w-[400px] h-[calc(100vh-220px)] overflow-y-auto bg-white border-r border-slate-200 z-10 p-5 space-y-5 custom-scrollbar">
          {filteredBranches.map((branch) => (
            <div 
              key={branch.id}
              onClick={() => setSelectedBranch(branch)}
              className={`p-5 rounded-2xl border cursor-pointer transition-all flex flex-col ${
                selectedBranch?.id === branch.id 
                  ? 'border-primary bg-primary/5 shadow-lg ring-1 ring-primary' 
                  : 'border-slate-200 bg-white hover:border-primary/30 hover:shadow-md'
              }`}
            >
              <h3 className="font-bold text-lg text-slate-900 mb-3">{branch.name}</h3>
              
              <div className="flex items-start gap-3 text-slate-600 font-medium mb-3">
                <MapPin size={18} className="shrink-0 text-primary mt-0.5" />
                <p className="text-sm leading-relaxed">{branch.address}</p>
              </div>
              <div className="flex items-center gap-3 text-slate-600 font-medium mb-5">
                <Clock size={18} className="shrink-0 text-primary" />
                <p className="text-sm">{branch.open_hours}</p>
              </div>
              
              <div className="pt-4 border-t border-slate-100 mb-4">
                <p className="text-xs font-bold text-slate-400 mb-3 uppercase tracking-wider">Fasilitas Cabang:</p>
                <div className="flex flex-wrap gap-2">
                  {branch.facilities.map((facId: string) => {
                    const fac = facilities.find((f: any) => f.identifier === facId);
                    if (!fac) return null;
                    const Icon = IconMapper[fac.icon] || MapPin;
                    return (
                      <div key={fac.id} className="bg-white border border-slate-200 px-2.5 py-1.5 rounded-lg flex items-center gap-2 text-slate-600 shadow-sm" title={fac.name}>
                        <Icon size={14} className="text-primary" />
                        <span className="text-xs font-medium">{fac.name}</span>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="mt-auto pt-2">
                <a 
                  href={`https://maps.google.com/?q=${branch.lat},${branch.lng}`} 
                  target="_blank" 
                  rel="noopener noreferrer"
                  onClick={(e) => e.stopPropagation()}
                  className="w-full py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-xl flex items-center justify-center gap-2 transition-colors shadow-sm"
                >
                  <MapPin size={16} /> Buka di Google Maps
                </a>
              </div>
            </div>
          ))}
          {filteredBranches.length === 0 && (
            <div className="text-center py-10">
               <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <Search className="text-slate-300" size={24} />
              </div>
              <p className="text-slate-500 font-medium">Cabang tidak ditemukan.</p>
              <p className="text-slate-400 text-sm mt-1">Coba gunakan kata kunci lain.</p>
            </div>
          )}
        </div>

        {/* Map View - Hidden on mobile, shown on desktop */}
        <div className="hidden md:block flex-1 h-[calc(100vh-220px)] bg-slate-100 relative z-0">
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
