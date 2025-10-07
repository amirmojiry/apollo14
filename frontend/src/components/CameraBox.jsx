import { useRef, useState } from "react";
import { Camera, Upload, X, MapPin } from "lucide-react";
import { Button } from "../components/ui/button";
import LocationPicker from "./LocationPicker";
import { useSenderStore } from "../store/useSenderStore";

function CameraActions({
  cameraOn,
  startCamera,
  stopCamera,
  takePhoto,
  handleUpload,
}) {
  return (
    <div className="flex flex-wrap justify-center gap-3 mt-3 w-full">
      {!cameraOn ? (
        <>
          <Button onClick={startCamera} className="flex gap-2">
            <Camera size={18} /> Start Camera
          </Button>

          <label className="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white rounded-md px-4 py-2 cursor-pointer">
            <Upload size={18} /> Upload
            <input
              type="file"
              accept="image/*"
              onChange={handleUpload}
              className="hidden"
            />
          </label>
        </>
      ) : (
        <>
          <Button
            variant="outline"
            onClick={takePhoto}
            className="text-primary flex gap-2"
          >
            <Camera /> Capture
          </Button>
          <Button
            variant="outline"
            onClick={stopCamera}
            className="text-red-600 flex gap-2"
          >
            <X /> Stop
          </Button>
        </>
      )}
    </div>
  );
}

export default function CameraBox() {
  const videoRef = useRef(null);
  const [photo, setPhoto] = useState(null);
  const [stream, setStream] = useState(null);
  const [cameraOn, setCameraOn] = useState(false);
  const [showResult, setShowResult] = useState(false);
  const [realValue, setRealValue] = useState(null);

  const { setImage, barValue, setBarValue, location, setLocation, sendData } =
    useSenderStore();

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
    const photoData = canvas.toDataURL("image/png");
    setPhoto(photoData);
    setImage(photoData); 
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
    reader.onloadend = () => {
      setPhoto(reader.result);
      setImage(reader.result); 
    };
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
      },
      (err) => {
        console.error("Error getting location:", err);
        alert("Failed to get location. Please allow location access.");
      }
    );
  };

  const finalize = () => {
    if (!photo || !location) {
      alert("Please capture/upload a photo AND select your location first.");
      return;
    }
    const data = { image: photo, location, barValue };
    console.log("Finalized data (local):", data);
    sendData(); 
    // Demo: simulate real air pollution measurement (e.g., PM2.5 ug/m3)
    const simulatedReal = Math.round(10 + Math.random() * 90); // 10..100
    setRealValue(simulatedReal);
    setShowResult(true);
  };

  const canFinalize = photo && location;

  return (
    <div className="flex flex-col items-center w-full max-w-md mx-auto p-4 space-y-4">
      {!photo ? (
        <>
          <video
            ref={videoRef}
            autoPlay
            playsInline
            muted
            className="w-full h-60 object-cover rounded-xl border shadow-md"
          />
          <CameraActions
            cameraOn={cameraOn}
            startCamera={startCamera}
            stopCamera={stopCamera}
            takePhoto={takePhoto}
            handleUpload={handleUpload}
          />
        </>
      ) : (
        <>
          <img
            src={photo}
            alt="Captured"
            className="w-full max-w-sm h-60 object-cover rounded-xl border shadow-md"
          />
          <div className="flex flex-wrap justify-center gap-3 mt-3 w-full">
            <Button
              onClick={retakePhoto}
              className="bg-yellow-500 hover:bg-yellow-600 text-white"
            >
              Retake
            </Button>
          </div>
        </>
      )}

      {/* ✅ Map click picker */}
      <LocationPicker />

      {/* ✅ Get current device location button */}
      <Button onClick={getLocation} className="bg-blue-600 text-white mt-2">
        <MapPin size={18} /> Get My Location
      </Button>

      {location && (
        <p className="text-sm text-gray-500 mt-2">
          📍 Lat: {location.latitude.toFixed(5)}, Lng:{" "}
          {location.longitude.toFixed(5)}
        </p>
      )}

      {/* Slider */}
      <div className="flex flex-col items-center w-full mt-4">
        <label className="text-sm text-gray-600 mb-2">
          Adjustment Value: <span className="font-semibold">{barValue}</span>
        </label>
        <input
          type="range"
          min="0"
          max="10"
          value={barValue}
          onChange={(e) => setBarValue(Number(e.target.value))}
          className="w-full accent-secondary"
        />
      </div>

      {/* Finalize */}
      <div className="w-full flex justify-center mt-4">
        <Button
          onClick={finalize}
          disabled={!canFinalize}
          className={`w-full py-3 font-semibold flex gap-2 justify-center ${
            canFinalize
              ? "bg-blue-600 hover:bg-blue-700 text-white"
              : "bg-gray-300 text-gray-500 cursor-not-allowed"
          }`}
        >
          <Upload size={18} /> Finalize
        </Button>
      </div>

      {/* Result card (demo) */}
      {showResult && realValue !== null && (
        <div className="w-full mt-4 bg-white dark:bg-gray-800 rounded-xl shadow p-4">
          <h3 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Estimated vs Real Air Pollution</h3>
          <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Demo result based on your photo, location and adjustment.
          </p>

          <div className="grid grid-cols-2 gap-4">
            <div className="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30">
              <div className="text-xs text-gray-500 dark:text-gray-400">Your Guess</div>
              <div className="text-2xl font-bold text-blue-700 dark:text-blue-300">{barValue}</div>
              <div className="text-xs text-gray-500">PM2.5 (µg/m³)</div>
            </div>
            <div className="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
              <div className="text-xs text-gray-500 dark:text-gray-400">Real Measurement</div>
              <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{realValue}</div>
              <div className="text-xs text-gray-500">PM2.5 (µg/m³)</div>
            </div>
          </div>

          {(() => {
            const diff = Math.abs((realValue ?? 0) - (barValue ?? 0));
            const accuracy = Math.max(0, 100 - Math.min(100, Math.round((diff / Math.max(1, realValue)) * 100)));
            const color = accuracy > 75 ? "bg-emerald-500" : accuracy > 50 ? "bg-yellow-500" : "bg-red-500";
            return (
              <div className="mt-4">
                <div className="flex justify-between text-xs text-gray-500 mb-1">
                  <span>Accuracy</span>
                  <span>{accuracy}%</span>
                </div>
                <div className="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div className={`${color} h-3`} style={{ width: `${accuracy}%` }} />
                </div>
                <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                  {accuracy > 75 ? "Great! Your guess is very close." : accuracy > 50 ? "Nice try! You're in the ballpark." : "Interesting! Real value was quite different this time."}
                </div>
              </div>
            );
          })()}

          <div className="mt-4 text-xs text-gray-500">
            Lat: {location?.latitude?.toFixed?.(5)} · Lng: {location?.longitude?.toFixed?.(5)}
          </div>
        </div>
      )}
    </div>
  );
}
