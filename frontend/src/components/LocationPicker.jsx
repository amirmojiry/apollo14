import { useEffect } from "react";
import {
  MapContainer,
  TileLayer,
  Marker,
  useMapEvents,
  useMap,
} from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { useSenderStore } from "../store/useSenderStore";

const markerIcon = new L.Icon({
  iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
  iconSize: [25, 41],
});

function RecenterMap({ position }) {
  const map = useMap();
  useEffect(() => {
    if (position) {
      map.setView(position, 13);
    }
  }, [position, map]);
  return null;
}

function LocationMarker() {
  const location = useSenderStore((s) => s.location);
  const setStoreLocation = useSenderStore((s) => s.setLocation);

  //clicks on the map
  useMapEvents({
    click(e) {
      const coords = { latitude: e.latlng.lat, longitude: e.latlng.lng };
      setStoreLocation(coords);
    },
  });

  if (!location) return null;

  const position = [location.latitude, location.longitude];

  return (
    <>
      <Marker position={position} icon={markerIcon} />
      <RecenterMap position={position} />
    </>
  );
}

export default function LocationPicker() {
  return (
    <div className="h-64 w-full rounded-lg overflow-hidden">
      <MapContainer center={[0, 0]} zoom={2} className="h-full w-full">
        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <LocationMarker />
      </MapContainer>
    </div>
  );
}
