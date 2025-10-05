import { useState } from "react";
import { Upload, X } from "lucide-react";

export default function UploadBox({ onUpload }) {
  const [file, setFile] = useState(null);
  const [preview, setPreview] = useState(null);

  const handleFileChange = (e) => {
    const selected = e.target.files[0];
    if (selected) {
      setFile(selected);
      setPreview(URL.createObjectURL(selected));
      onUpload && onUpload(selected);
    }
  };

  const removeFile = () => {
    setFile(null);
    setPreview(null);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    const dropped = e.dataTransfer.files[0];
    if (dropped) {
      setFile(dropped);
      setPreview(URL.createObjectURL(dropped));
      onUpload && onUpload(dropped);
    }
  };

  const handleDragOver = (e) => e.preventDefault();

  return (
    <div
      onDrop={handleDrop}
      onDragOver={handleDragOver}
      className="flex flex-col items-center justify-center border-2 border-dashed border-gray-500 rounded-2xl p-6 w-full max-w-md mx-auto text-center bg-gray-900/40 hover:bg-gray-800/60 transition-all"
    >
      {!file ? (
        <>
          <Upload className="w-10 h-10 mb-3 text-gray-300" />
          <p className="text-gray-300 mb-2">Drag & drop an image here</p>
          <label className="cursor-pointer text-blue-400 hover:underline">
            or click to browse
            <input
              type="file"
              accept="image/*"
              onChange={handleFileChange}
              className="hidden"
            />
          </label>
        </>
      ) : (
        <div className="relative">
          <img
            src={preview}
            alt="Preview"
            className="max-h-64 rounded-xl object-cover shadow-lg"
          />
          <button
            onClick={removeFile}
            className="absolute top-2 right-2 bg-black/60 p-1 rounded-full hover:bg-black/80"
          >
            <X className="text-white" size={20} />
          </button>
          <p className="mt-3 text-sm text-gray-400">{file.name}</p>
        </div>
      )}
    </div>
  );
}
