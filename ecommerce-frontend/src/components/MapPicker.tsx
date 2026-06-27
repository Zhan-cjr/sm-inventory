import React, { useState, useEffect, useRef } from 'react';
import { MapContainer, TileLayer, Marker, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPin, Crosshair, Loader2 } from 'lucide-react';

// Fix for default marker icon in react-leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

interface MapPickerProps {
  initialLat?: number | null;
  initialLng?: number | null;
  onLocationSelect: (lat: number, lng: number) => void;
  className?: string;
}

const DEFAULT_CENTER = [-6.2088, 106.8456]; // Jakarta Default

const LocationMarker = ({ position, setPosition, onLocationSelect }: any) => {
  const map = useMapEvents({
    click(e: L.LeafletMouseEvent) {
      setPosition(e.latlng);
      onLocationSelect(e.latlng.lat, e.latlng.lng);
      map.flyTo(e.latlng, map.getZoom());
    },
  });

  return position === null ? null : (
    <Marker position={position}></Marker>
  );
};

const MapPicker: React.FC<MapPickerProps> = ({
  initialLat,
  initialLng,
  onLocationSelect,
  className = "h-[300px] w-full rounded-xl overflow-hidden border border-slate-200"
}) => {
  const [position, setPosition] = useState<L.LatLng | null>(
    initialLat && initialLng ? new L.LatLng(initialLat, initialLng) : null
  );
  const [isGettingLocation, setIsGettingLocation] = useState(false);
  const mapRef = useRef<L.Map | null>(null);

  useEffect(() => {
    if (initialLat && initialLng && mapRef.current) {
      const newPos = new L.LatLng(initialLat, initialLng);
      setPosition(newPos);
      mapRef.current.flyTo(newPos, 15);
    }
  }, [initialLat, initialLng]);

  const getCurrentLocation = () => {
    setIsGettingLocation(true);
    if (!navigator.geolocation) {
      alert('Geolocation tidak didukung oleh browser Anda.');
      setIsGettingLocation(false);
      return;
    }
    
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        const newPos = new L.LatLng(latitude, longitude);
        setPosition(newPos);
        onLocationSelect(latitude, longitude);
        if (mapRef.current) {
          mapRef.current.flyTo(newPos, 16);
        }
        setIsGettingLocation(false);
      },
      () => {
        alert('Gagal mendapatkan lokasi. Pastikan izin lokasi (GPS) diberikan.');
        setIsGettingLocation(false);
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  };

  return (
    <div className="space-y-2">
      <div className="flex justify-between items-center mb-1">
        <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
          Titik Lokasi Peta (GPS)
        </label>
        <button
          type="button"
          onClick={getCurrentLocation}
          disabled={isGettingLocation}
          className="flex items-center gap-1 text-[10px] font-bold text-brand-blue hover:bg-brand-blue/10 px-2 py-1 rounded transition-colors"
        >
          {isGettingLocation ? <Loader2 size={12} className="animate-spin" /> : <Crosshair size={12} />}
          Gunakan Lokasi Saat Ini
        </button>
      </div>
      
      <div className={`relative z-0 ${className}`}>
        <MapContainer
          center={position || (DEFAULT_CENTER as L.LatLngExpression)}
          zoom={position ? 15 : 12}
          scrollWheelZoom={true}
          style={{ height: '100%', width: '100%' }}
          ref={mapRef}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <LocationMarker position={position} setPosition={setPosition} onLocationSelect={onLocationSelect} />
        </MapContainer>
        
        {!position && (
          <div className="absolute inset-0 z-[400] pointer-events-none flex items-center justify-center bg-black/5">
            <div className="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full shadow-lg border border-slate-200 flex items-center gap-2">
              <MapPin size={16} className="text-brand-blue animate-bounce" />
              <span className="text-xs font-bold text-slate-700">Klik pada peta untuk menandai lokasi</span>
            </div>
          </div>
        )}
      </div>
      
      {position && (
        <p className="text-[10px] text-slate-500 text-right mt-1">
          Koordinat: {position.lat.toFixed(6)}, {position.lng.toFixed(6)}
        </p>
      )}
    </div>
  );
};

export default MapPicker;
