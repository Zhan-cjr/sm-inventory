import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { MapPin, Plus, Loader2, Search } from 'lucide-react';
import { useEcom } from '../context/EcomContext';
import MapPicker from './MapPicker';

const AddressBookTab = () => {
  const { member } = useEcom();
  const [addresses, setAddresses] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isAdding, setIsAdding] = useState(false);
  
  // Form State
  const [label, setLabel] = useState('Rumah');
  const [recipientName, setRecipientName] = useState(member?.name || '');
  const [recipientPhone, setRecipientPhone] = useState(member?.phone || '');
  const [fullAddress, setFullAddress] = useState('');
  
  // Biteship Area Search
  const [areaQuery, setAreaQuery] = useState('');
  const [areaResults, setAreaResults] = useState<any[]>([]);
  const [isSearchingArea, setIsSearchingArea] = useState(false);
  const [selectedArea, setSelectedArea] = useState<any | null>(null);

  // Geolocation
  const [latitude, setLatitude] = useState<number | null>(null);
  const [longitude, setLongitude] = useState<number | null>(null);

  useEffect(() => {
    if (member) {
      fetchAddresses();
    }
  }, [member]);

  const fetchAddresses = async () => {
    setIsLoading(true);
    try {
      const res = await axios.get('/ecommerce/customers/addresses', {
        headers: { 'X-Member-ID': member?.id }
      });
      setAddresses(res.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  const geocodeAddress = async (searchQuery: string) => {
    try {
      const res = await axios.get('https://nominatim.openstreetmap.org/search', {
        params: {
          q: searchQuery,
          format: 'json',
          limit: 1
        }
      });
      if (res.data && res.data.length > 0) {
        setLatitude(parseFloat(res.data[0].lat));
        setLongitude(parseFloat(res.data[0].lon));
      }
    } catch (err) {
      console.error('Geocoding failed:', err);
    }
  };

  const searchArea = async (query: string) => {
    setAreaQuery(query);
    if (query.length < 3) {
      setAreaResults([]);
      return;
    }
    
    setIsSearchingArea(true);
    try {
      const res = await axios.get('/ecommerce/areas/search', {
        params: { query }
      });
      setAreaResults(res.data.areas || []);
    } catch (err) {
      console.error(err);
    } finally {
      setIsSearchingArea(false);
    }
  };

  const handleSaveAddress = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedArea) {
      alert('Pilih kecamatan/kelurahan dari daftar yang muncul!');
      return;
    }
    
    // Kurir instan mewajibkan koordinat
    if (!latitude || !longitude) {
      const confirmProceed = window.confirm('Anda belum menentukan Koordinat Peta (GPS). Tanpa koordinat, Anda TIDAK BISA menggunakan kurir Instan (Gojek/Grab). Lanjutkan menyimpan tanpa koordinat?');
      if (!confirmProceed) return;
    }

    setIsLoading(true);
    try {
      await axios.post('/ecommerce/customers/addresses', {
        label,
        recipient_name: recipientName,
        recipient_phone: recipientPhone,
        full_address: fullAddress,
        biteship_area_id: selectedArea.id,
        latitude,
        longitude
      }, {
        headers: { 'X-Member-ID': member?.id }
      });
      
      setIsAdding(false);
      setLabel('Rumah');
      setFullAddress('');
      setAreaQuery('');
      setSelectedArea(null);
      setLatitude(null);
      setLongitude(null);
      fetchAddresses();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal menyimpan alamat');
    } finally {
      setIsLoading(false);
    }
  };

  const handleSetPrimary = async (id: string) => {
    setIsLoading(true);
    try {
      await axios.put(`/ecommerce/customers/addresses/${id}/set-primary`, {}, {
        headers: { 'X-Member-ID': member?.id }
      });
      fetchAddresses();
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm('Hapus alamat ini?')) return;
    setIsLoading(true);
    try {
      await axios.delete(`/ecommerce/customers/addresses/${id}`, {
        headers: { 'X-Member-ID': member?.id }
      });
      fetchAddresses();
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  if (isAdding) {
    return (
      <div className="space-y-4 animate-in fade-in">
        <div className="flex justify-between items-center mb-2">
          <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Tambah Alamat Baru</span>
          <button onClick={() => setIsAdding(false)} className="text-xs font-bold text-slate-400 hover:text-slate-600">Batal</button>
        </div>
        
        <form onSubmit={handleSaveAddress} className="space-y-3 bg-slate-50 border border-slate-100 p-4 rounded-xl">
          <div>
            <label className="block text-[10px] font-bold text-slate-500 mb-1">LABEL ALAMAT</label>
            <input
              type="text" required value={label} onChange={e => setLabel(e.target.value)}
              placeholder="Contoh: Rumah, Kantor"
              className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm"
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-[10px] font-bold text-slate-500 mb-1">NAMA PENERIMA</label>
              <input
                type="text" required value={recipientName} onChange={e => setRecipientName(e.target.value)}
                className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm"
              />
            </div>
            <div>
              <label className="block text-[10px] font-bold text-slate-500 mb-1">NO. TELEPON</label>
              <input
                type="tel" required value={recipientPhone} onChange={e => setRecipientPhone(e.target.value)}
                className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm"
              />
            </div>
          </div>
          
          <div className="relative">
            <label className="block text-[10px] font-bold text-slate-500 mb-1">KECAMATAN / KELURAHAN</label>
            <div className="relative">
              <input
                type="text" required
                value={selectedArea ? selectedArea.name : areaQuery}
                onChange={e => {
                  setSelectedArea(null);
                  searchArea(e.target.value);
                }}
                placeholder="Ketik nama kecamatan..."
                className="w-full px-3 py-2 pl-8 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue/20"
              />
              <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
            </div>
            
            {/* Autocomplete Dropdown */}
            {isSearchingArea && (
              <div className="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg p-2 text-xs text-center text-slate-500">
                Mencari...
              </div>
            )}
            {!selectedArea && areaResults.length > 0 && (
              <div className="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                {areaResults.map((area: any) => (
                  <button
                    key={area.id} type="button"
                    onClick={() => { 
                      setSelectedArea(area); 
                      setAreaResults([]); 
                      geocodeAddress(`${area.name}, ${area.administrative_division_level_2_name}, Indonesia`);
                    }}
                    className="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 border-b border-slate-50 last:border-0"
                  >
                    <span className="font-bold text-slate-700 block">{area.name}</span>
                    <span className="text-[10px] text-slate-400">{area.administrative_division_level_2_name}, {area.administrative_division_level_1_name}</span>
                  </button>
                ))}
              </div>
            )}
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-500 mb-1">DETAIL JALAN (NO. RUMAH, RT/RW)</label>
            <textarea
              required value={fullAddress} onChange={e => setFullAddress(e.target.value)}
              rows={2} placeholder="Nama jalan, nomor rumah, detail blok..."
              className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm resize-none"
            />
          </div>

          <div className="bg-slate-50 p-3 rounded-xl border border-slate-200">
            <MapPicker 
              initialLat={latitude} 
              initialLng={longitude} 
              onLocationSelect={(lat, lng) => {
                setLatitude(lat);
                setLongitude(lng);
              }}
            />
          </div>

          <button
            type="submit" disabled={isLoading}
            className="w-full py-2.5 bg-brand-blue text-white font-bold rounded-lg text-sm mt-2 flex justify-center items-center gap-2 hover:bg-brand-blue/90"
          >
            {isLoading ? <Loader2 size={16} className="animate-spin" /> : 'Simpan Alamat'}
          </button>
        </form>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {isLoading ? (
        <div className="flex justify-center py-8"><Loader2 className="animate-spin text-brand-blue" /></div>
      ) : addresses.length === 0 ? (
        <div className="text-center py-8 text-slate-400 space-y-2">
          <MapPin size={32} className="mx-auto text-slate-300" />
          <p className="text-xs font-semibold">Belum ada alamat tersimpan</p>
        </div>
      ) : (
        <div className="space-y-2 max-h-[40vh] overflow-y-auto pr-1">
          {addresses.map((addr: any) => (
            <div key={addr.id} className={`p-3 border rounded-xl flex gap-3 ${addr.is_primary ? 'border-brand-blue bg-blue-50/30' : 'border-slate-200 bg-white'}`}>
              <MapPin className={addr.is_primary ? 'text-brand-blue' : 'text-slate-400'} size={20} />
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-xs font-bold text-slate-800">{addr.label}</span>
                  {addr.is_primary && (
                    <span className="text-[9px] bg-brand-blue text-white px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Utama</span>
                  )}
                </div>
                <p className="text-xs font-semibold text-slate-700">{addr.recipient_name} | {addr.recipient_phone}</p>
                <p className="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{addr.full_address}</p>
                
                <div className="flex gap-3 mt-2">
                  {!addr.is_primary && (
                    <button onClick={() => handleSetPrimary(addr.id)} className="text-[10px] font-bold text-brand-blue hover:underline">Jadikan Utama</button>
                  )}
                  <button onClick={() => handleDelete(addr.id)} className="text-[10px] font-bold text-red-500 hover:underline">Hapus</button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {!isAdding && (
        <button
          onClick={() => setIsAdding(true)}
          className="w-full py-2.5 border-2 border-dashed border-slate-300 text-slate-500 font-bold rounded-xl text-xs hover:border-brand-blue hover:text-brand-blue transition-all flex items-center justify-center gap-1"
        >
          <Plus size={14} /> Tambah Alamat Baru
        </button>
      )}
    </div>
  );
};

export default AddressBookTab;
