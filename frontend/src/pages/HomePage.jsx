import React from "react";
import CameraBox from "../components/CameraBox";

export default function Home() {
  return (
    <main className="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col items-center justify-start p-4">
      {/* Header / Title */}
      <header className="w-full max-w-md text-center my-6">
        <h1 className="text-2xl font-bold text-gray-800 dark:text-gray-100">
          Camera Capture & Location
        </h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Take or upload a photo, select your location, and finalize your data.
        </p>
      </header>

      {/* Main content */}
      <section className="w-full max-w-md">
        <CameraBox />
      </section>

      {/* Footer (optional) */}
      <footer className="mt-10 text-xs text-gray-400">
        © {new Date().getFullYear()} YourApp — All rights reserved
      </footer>
    </main>
  );
}
