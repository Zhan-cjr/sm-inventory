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
    <div className="h-full w-full flex items-center justify-center bg-slate-100">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
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
          <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-6">Temukan Cabang <span className="text-emerald-600">Terdekat</span></h1>
          <p className="text-lg text-slate-600 mb-8">
            Kunjungi lokasi Toserba Selamat yang tersebar di berbagai wilayah untuk melayani kebutuhan keluarga Anda.
          </p>
          
          <div className="relative max-w-xl mx-auto">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
            <input 
              type="text"
              placeholder="Cari nama cabang atau wilayah..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-12 pr-4 py-4 rounded-2xl border border-slate-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-slate-700 bg-white"
            />
          </div>
        </motion.div>
      </div>

      {isLoading ? (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
        </div>
      ) : (
      <div className="flex-1 flex flex-col md:flex-row relative bg-white border-t border-slate-200">
        
        {/* Sidebar List */}
        <div className="w-full md:w-[400px] h-[50vh] md:h-[calc(100vh-220px)] overflow-y-auto bg-white border-r border-slate-200 z-10 p-5 space-y-5">
          {filteredBranches.map((branch) => (
            <div 
              key={branch.id}
              onClick={() => setSelectedBranch(branch)}
              className={`p-5 rounded-2xl border cursor-pointer transition-all ${
                selectedBranch?.id === branch.id 
                  ? 'border-emerald-500 bg-emerald-50/50 shadow-md ring-1 ring-emerald-500' 
                  : 'border-slate-200 hover:border-emerald-300 hover:shadow-md'
              }`}
            >
              <h3 className="font-bold text-lg text-slate-900 mb-2">{branch.name}</h3>
              
              <div className="flex items-start gap-3 text-slate-600 mb-3">
                <MapPin size={18} className="shrink-0 text-emerald-600 mt-0.5" />
                <p className="text-sm leading-relaxed">{branch.address}</p>
              </div>
              <div className="flex items-center gap-3 text-slate-600 mb-5">
                <Clock size={18} className="shrink-0 text-emerald-600" />
                <p className="text-sm font-medium">{branch.open_hours}</p>
              </div>
              
              <div className="pt-4 border-t border-slate-200">
                <p className="text-xs font-bold text-slate-400 mb-3 uppercase tracking-wider">Fasilitas Cabang:</p>
                <div className="flex flex-wrap gap-2">
                  {branch.facilities.map((facId: string) => {
                    const fac = facilities.find((f: any) => f.identifier === facId);
                    if (!fac) return null;
                    const Icon = IconMapper[fac.icon] || MapPin;
                    return (
                      <div key={fac.id} className="bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-lg flex items-center gap-2 text-slate-600" title={fac.name}>
                        <Icon size={14} className="text-emerald-600" />
                        <span className="text-xs font-medium">{fac.name}</span>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          ))}
          {filteredBranches.length === 0 && (
            <div className="text-center py-10">
              <div className="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Search className="text-slate-400" size={24} />
              </div>
              <p className="text-slate-500">Cabang tidak ditemukan.</p>
            </div>
          )}
        </div>

        {/* Map View */}
        <div className="flex-1 h-[50vh] md:h-[calc(100vh-220px)] bg-slate-100 relative z-0">
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
