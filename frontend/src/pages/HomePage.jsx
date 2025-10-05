import { useRef, useState, useEffect } from "react";
import { Camera, Upload, X, MapPin } from "lucide-react";
import {
  MapContainer,
  TileLayer,
  Marker,
  useMap,
  useMapEvents,
} from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

import { useSenderStore } from "../store/useSenderStore";

const markerIcon = new L.Icon({
  iconUrl: "https://unpkg.com/leaflet@1.7/dist/images/marker-icon.png",
  iconSize: [25, 41],
  iconAnchor: [12, 41],
});

function LocationPicker({ onSelect }) {
  useMapEvents({
    click(e) {
      onSelect({
        latitude: e.latlng.lat,
        longitude: e.latlng.lng,
      });
    },
  });
  return null;
}

function RecenterMap({ coords }) {
  const map = useMap();
  useEffect(() => {
    if (coords) {
      map.setView([coords.latitude, coords.longitude], 13);
    }
  }, [coords, map]);
  return null;
}

export default function CameraBox() {
  const videoRef = useRef(null);
  const [photo, setPhoto] = useState(null);
  const [stream, setStream] = useState(null);
  const [cameraOn, setCameraOn] = useState(false);
  const [location, setLocation] = useState(null);
  const [mapCenter, setMapCenter] = useState({ lat: 20, lng: 0 }); // Global view
  const [barValue, setBarValue] = useState(5); // 👈 vertical slider state

  const startCamera = async () => {
    try {
      const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: true,
      });
      if (videoRef.current) videoRef.current.srcObject = mediaStream;
      setStream(mediaStream);
      setCameraOn(true);
    } catch (err) {
      console.error("Camera access denied:", err);
    }
  };

  const stopCamera = () => {
    if (stream) stream.getTracks().forEach((track) => track.stop());
    setStream(null);
    setCameraOn(false);
  };

  const takePhoto = () => {
    if (!videoRef.current) return;
    const canvas = document.createElement("canvas");
    canvas.width = videoRef.current.videoWidth;
    canvas.height = videoRef.current.videoHeight;
    canvas.getContext("2d").drawImage(videoRef.current, 0, 0);
    setPhoto(canvas.toDataURL("image/png"));
    stopCamera();
  };

  const retakePhoto = () => {
    setPhoto(null);
    startCamera();
  };

  const handleUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onloadend = () => setPhoto(reader.result);
    reader.readAsDataURL(file);
  };

  const getLocation = () => {
    if (!navigator.geolocation) {
      alert("Geolocation is not supported by your browser.");
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const coords = {
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
        };
        setLocation(coords);
        setMapCenter({ lat: coords.latitude, lng: coords.longitude });
      },
      (err) => {
        console.error("Error getting location:", err);
        alert("Failed to get location. Please allow location access.");
      }
    );
  };

  const finalize = () => {
    if (!photo) return;
    const data = { image: photo, location, barValue };
    console.log("✅ Finalized data:", data);
    alert("Image, location, and slider value finalized!");
  };

  return (
    <div className="relative flex flex-col items-center w-full max-w-md mx-auto">
      <div className="flex flex-col items-center w-full">
        {!photo ? (
          <>
            <video
              ref={videoRef}
              autoPlay
              playsInline
              muted
              className="w-full h-60 object-cover rounded-md mt-10"
            />

            {!cameraOn ? (
              <div className="flex flex-wrap gap-2 mt-2 justify-center">
                <button
                  onClick={startCamera}
                  className="px-4 py-2 bg-primary rounded-md text-primary-content flex items-center gap-1"
                >
                  <Camera size={18} /> Start Camera
                </button>

                <label className="px-4 py-2 bg-green-600 text-white rounded cursor-pointer flex items-center gap-1">
                  <Upload size={18} /> Upload
                  <input
                    type="file"
                    accept="image/*"
                    onChange={handleUpload}
                    className="hidden"
                  />
                </label>

                <button
                  onClick={getLocation}
                  className="px-4 py-2 bg-blue-500 text-white rounded flex items-center gap-1"
                >
                  <MapPin size={18} /> Get Location
                </button>
              </div>
            ) : (
              <div className="flex gap-2 mt-2">
                <button
                  onClick={takePhoto}
                  className="px-4 py-2 text-primary rounded border"
                >
                  <Camera />
                </button>
                <button
                  onClick={stopCamera}
                  className="px-4 py-2 text-red-600 rounded border"
                >
                  <X />
                </button>
              </div>
            )}

            {/* Full-box Map */}
            <div
              className="mt-4 w-full rounded overflow-hidden border"
              style={{ height: 300 }}
            >
              <MapContainer
                center={mapCenter}
                zoom={location ? 13 : 2}
                worldCopyJump={true}
                style={{ width: "100%", height: "100%" }}
              >
                <TileLayer
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                  attribution="© OpenStreetMap contributors"
                />
                <LocationPicker onSelect={(coords) => setLocation(coords)} />
                {location && (
                  <>
                    <Marker
                      position={[location.latitude, location.longitude]}
                      icon={markerIcon}
                    />
                    <RecenterMap coords={location} />
                  </>
                )}
              </MapContainer>
            </div>

            {location && (
              <p className="text-sm text-gray-500 mt-2">
                📍 Lat: {location.latitude.toFixed(5)}, Lng:{" "}
                {location.longitude.toFixed(5)}
              </p>
            )}
          </>
        ) : (
          <>
            <img
              src={photo}
              alt="Captured"
              className="w-80 h-60 object-cover rounded mt-10"
            />
            {location && (
              <p className="text-sm text-gray-500 mt-2">
                📍 Lat: {location.latitude.toFixed(2)}, Lng:{" "}
                {location.longitude.toFixed(2)}
              </p>
            )}
            <div className="flex gap-2 mt-2">
              <button
                onClick={retakePhoto}
                className="px-4 py-2 bg-yellow-600 text-white rounded"
              >
                Retake
              </button>
              <button
                onClick={finalize}
                className="px-4 py-2 bg-blue-600 text-white rounded flex items-center gap-1"
              >
                <Upload size={18} /> Finalize
              </button>
            </div>
          </>
        )}
      </div>

      {/* 👇 Vertical slider always visible */}
      <div className="absolute right-[-150px] top-1/2 -translate-y-1/2 flex flex-col items-center">
        <input
          type="range"
          min="0"
          max="10"
          value={barValue}
          onChange={(e) => setBarValue(Number(e.target.value))}
          className="h-40 w-2 accent-secondary"
          style={{
            writingMode: "bt-lr", // Firefox
            WebkitAppearance: "slider-vertical", // Chrome/Safari
          }}
        />
        <span className="mt-2 text-sm text-gray-600">{barValue}</span>
      </div>
    </div>
  );
}
