import { useRef, useState } from "react";
import { Camera, X } from "lucide-react";

export default function CameraBox({ onCapture }) {
  const videoRef = useRef(null);
  const [photo, setPhoto] = useState(null);

  const startCamera = async () => {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    videoRef.current.srcObject = stream;
  };

  const takePhoto = () => {
    const canvas = document.createElement("canvas");
    canvas.width = videoRef.current.videoWidth;
    canvas.height = videoRef.current.videoHeight;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(videoRef.current, 0, 0);
    const dataUrl = canvas.toDataURL("image/png");
    setPhoto(dataUrl);
    onCapture(dataUrl);
  };

  return (
    <div className="flex flex-col items-center gap-2">
      {!photo ? (
        <>
          <video ref={videoRef} autoPlay className="rounded-lg" />
          <button
            onClick={takePhoto}
            className="bg-blue-500 text-white px-4 py-1 rounded-lg"
          >
            📸 Capture
          </button>
          <button
            onClick={startCamera}
            className="bg-gray-600 text-white px-4 py-1 rounded-lg"
          >
            🎥 Start
          </button>
        </>
      ) : (
        <div className="relative">
          <img src={photo} alt="Captured" className="rounded-lg" />
          <button
            onClick={() => setPhoto(null)}
            className="absolute top-1 right-1 bg-black/50 p-1 rounded-full"
          >
            <X className="text-white" size={20} />
          </button>
        </div>
      )}
    </div>
  );
}
