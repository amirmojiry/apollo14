import { useState } from "react";
import { MapContainer, TileLayer, Marker, useMapEvents } from "react-leaflet";
import { MapPin } from "lucide-react";
import L from "leaflet";

const markerIcon = new L.Icon({
  iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
  iconSize: [25, 41],
});

function LocationMarker({ onChange }) {
  const [position, setPosition] = useState(null);

  useMapEvents({
    click(e) {
      setPosition(e.latlng);
      onChange(e.latlng);
    },
  });

  return position ? <Marker position={position} icon={markerIcon} /> : null;
}

export default function LocationPicker({ onLocationSelect }) {
  return (
    <div className="h-64 w-full rounded-lg overflow-hidden">
      <MapContainer center={[0, 0]} zoom={2} className="h-full w-full">
        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <LocationMarker onChange={onLocationSelect} />
      </MapContainer>
    </div>
  );
}
