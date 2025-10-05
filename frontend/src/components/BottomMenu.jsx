import { Home, Camera, MapPin, Upload } from "lucide-react";

export default function BottomMenu({ onSelect }) {
  const menuItems = [
    { icon: <Home />, name: "home" },
    { icon: <Camera />, name: "camera" },
    { icon: <Upload />, name: "upload" },
    { icon: <MapPin />, name: "location" },
  ];

  return (
    <div className="fixed bottom-0 left-0 w-full bg-gray-900 text-white flex justify-around py-2">
      {menuItems.map((item) => (
        <button
          key={item.name}
          onClick={() => onSelect(item.name)}
          className="flex flex-col items-center text-sm"
        >
          {item.icon}
          <span>{item.name}</span>
        </button>
      ))}
    </div>
  );
}
