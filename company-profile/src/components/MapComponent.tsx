"use client";

import { useEffect } from "react";
import { MapContainer, TileLayer, Marker, Popup, useMap } from "react-leaflet";
import "leaflet/dist/leaflet.css";
import L from "leaflet";

// Fix leaflet icon paths in Next.js
const icon = L.icon({
  iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
  iconRetinaUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
  shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

// Component to dynamically change view when a branch is selected
function ChangeView({ center, zoom }: { center: [number, number]; zoom: number }) {
  const map = useMap();
  useEffect(() => {
    map.flyTo(center, zoom, { duration: 1.5 });
  }, [center, zoom, map]);
  return null;
}

export default function MapComponent({ 
  branches, 
  selectedBranch, 
  onSelectBranch 
}: { 
  branches: any[], 
  selectedBranch: any,
  onSelectBranch: (branch: any) => void
}) {
  const defaultCenter: [number, number] = [-6.914744, 107.609810]; // Bandung
  const center: [number, number] = selectedBranch 
    ? [Number(selectedBranch.lat), Number(selectedBranch.lng)] 
    : (branches.length > 0 ? [Number(branches[0].lat), Number(branches[0].lng)] : defaultCenter);

  return (
    <div className="h-full w-full relative z-0">
      <MapContainer 
        center={center} 
        zoom={selectedBranch ? 16 : 10} 
        style={{ height: "100%", width: "100%", zIndex: 0 }}
        scrollWheelZoom={true}
      >
        <ChangeView center={center} zoom={selectedBranch ? 16 : 10} />
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        
        {branches.map((branch) => (
          <Marker 
            key={branch.id} 
            position={[Number(branch.lat), Number(branch.lng)]}
            icon={icon}
            eventHandlers={{
              click: () => onSelectBranch(branch)
            }}
          >
            <Popup>
              <div className="text-center px-1">
                <h3 className="font-bold text-slate-800 text-[14px]">{branch.name}</h3>
                <p className="text-xs text-slate-500 mt-1">{branch.address}</p>
              </div>
            </Popup>
          </Marker>
        ))}
      </MapContainer>
    </div>
  );
}
