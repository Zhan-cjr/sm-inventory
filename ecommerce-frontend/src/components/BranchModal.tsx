import React, { useEffect, useState } from 'react';
import { useEcom, Branch } from '../context/EcomContext';
import { MapPin, Navigation, Loader2, X } from 'lucide-react';
import axios from 'axios';

export const BranchModal: React.FC = () => {
  const {
    isBranchModalOpen,
    setIsBranchModalOpen,
    selectedBranch,
    setSelectedBranch,
  } = useEcom();

  const [branches, setBranches] = useState<Branch[]>([]);
  const [loading, setLoading] = useState(false);
  const [geoLoading, setGeoLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isBranchModalOpen) return;

    const fetchBranches = async () => {
      setLoading(true);
      try {
        const res = await axios.get('/ecommerce/branches');
        setBranches(res.data);
      } catch (err) {
        console.error(err);
        setError('Gagal memuat daftar cabang.');
      } finally {
        setLoading(false);
      }
    };

    fetchBranches();
  }, [isBranchModalOpen]);

  if (!isBranchModalOpen) return null;

  const handleSelectBranch = (branch: Branch) => {
    setSelectedBranch(branch);
    setIsBranchModalOpen(false);
  };

  const handleAutoDetect = () => {
    if (!navigator.geolocation) {
      setError('Geolokasi tidak didukung oleh browser Anda.');
      return;
    }

    setGeoLoading(true);
    setError(null);

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const res = await axios.get('/ecommerce/nearest-branch', {
            params: {
              latitude: position.coords.latitude,
              longitude: position.coords.longitude,
            },
          });
          
          if (res.data.branch) {
            setSelectedBranch(res.data.branch);
            setIsBranchModalOpen(false);
          } else {
            setError('Gagal mendeteksi cabang terdekat.');
          }
        } catch (err) {
          console.error(err);
          setError('Gagal mendeteksi cabang terdekat dari server.');
        } finally {
          setGeoLoading(false);
        }
      },
      (err) => {
        console.error(err);
        setError('Gagal mendapatkan lokasi Anda. Pastikan akses lokasi diaktifkan.');
        setGeoLoading(false);
      }
    );
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4">
      <div className="bg-white w-full h-full sm:h-auto sm:max-h-[90vh] max-w-md rounded-none sm:rounded-2xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col">
        {/* Header */}
        <div className="p-5 border-b border-slate-100 flex justify-between items-center">
          <div>
            <h3 className="text-xl font-bold text-slate-900 flex items-center gap-2">
              <MapPin className="text-brand-red" size={22} />
              Pilih Cabang Toko
            </h3>
            <p className="text-xs text-slate-500 mt-1">Dapatkan harga & stok sesuai cabang terdekat Anda.</p>
          </div>
          <button 
            onClick={() => setIsBranchModalOpen(false)}
            className="p-1.5 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <X size={20} />
          </button>
        </div>

        {/* Content */}
        <div className="p-5 flex-grow overflow-y-auto flex flex-col gap-5">
          {error && (
            <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium border border-red-100">
              {error}
            </div>
          )}

          {/* Auto Detect Button */}
          <button
            onClick={handleAutoDetect}
            disabled={geoLoading}
            className="flex items-center justify-center gap-3 w-full bg-brand-blue text-white py-3.5 px-4 rounded-xl font-semibold hover:bg-brand-blue/90 disabled:opacity-75 transition-all shadow-md shadow-brand-blue/10"
          >
            {geoLoading ? (
              <Loader2 className="animate-spin" size={20} />
            ) : (
              <Navigation size={20} />
            )}
            {geoLoading ? 'Mendeteksi Lokasi...' : 'Cari Cabang Terdekat (GPS)'}
          </button>

          <div className="relative flex py-2 items-center">
            <div className="flex-grow border-t border-slate-100"></div>
            <span className="flex-shrink mx-4 text-xs font-semibold text-slate-400 uppercase tracking-widest">Atau Pilih Manual</span>
            <div className="flex-grow border-t border-slate-100"></div>
          </div>

          {/* Branch List */}
          {loading ? (
            <div className="flex justify-center items-center py-10">
              <Loader2 className="animate-spin text-brand-blue" size={36} />
            </div>
          ) : (
            <div className="flex flex-col gap-2.5">
              {branches.map((branch) => {
                const isSelected = selectedBranch?.id === branch.id;
                return (
                  <button
                    key={branch.id}
                    onClick={() => handleSelectBranch(branch)}
                    className={`flex items-start gap-4 p-4 rounded-xl text-left border transition-all duration-200 group ${
                      isSelected 
                        ? 'border-brand-green bg-brand-green/5 ring-2 ring-brand-green/10' 
                        : 'border-slate-100 hover:border-brand-blue hover:bg-slate-50'
                    }`}
                  >
                    <div className={`p-2 rounded-lg mt-0.5 transition-colors ${
                      isSelected ? 'bg-brand-green/10 text-brand-green' : 'bg-slate-100 text-slate-500 group-hover:bg-brand-blue/10 group-hover:text-brand-blue'
                    }`}>
                      <MapPin size={18} />
                    </div>
                    <div className="flex-grow">
                      <div className="flex justify-between items-center gap-2">
                        <span className="font-bold text-slate-800 text-sm group-hover:text-brand-blue transition-colors">
                          {branch.name}
                        </span>
                        <span className="text-[0.65rem] font-bold px-2 py-0.5 bg-slate-100 text-slate-600 rounded uppercase">
                          {branch.code}
                        </span>
                      </div>
                      <p className="text-xs text-slate-500 mt-1 leading-relaxed line-clamp-2">
                        {branch.address}
                      </p>
                    </div>
                  </button>
                );
              })}

              {branches.length === 0 && !loading && (
                <div className="text-center py-8 text-slate-400 text-sm">
                  Tidak ada cabang aktif.
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
