import { use, useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";

export default function Navbar() {
  const [isOpen, setIsOpen] = useState(false);
  const menuRef = useRef();

  useEffect(() => {
    function handleClickOutside(event) {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  });

  return (
    <nav ref={menuRef} className="bg-base-200 shadow-md text-base-content">
      <div className=" mx-auto px-16 sm:px-10 lg:px-20">
        <div className="flex justify-between h-16 items-center">
          {/* Logo + Tagline */}
          <div className="flex flex-col">
            <span className="text-xl font-bold text-primary">
              Discover Air Pollution
            </span>
            <span className="text-sm text-gray-500">
              Be part of our mission to gather data
            </span>
          </div>

          {/* Desktop Menu */}
          <div className="hidden md:flex space-x-6">
            <Link
              to={"/"}
              className="text-primary hover:text-primary/80 font-medium"
            >
              Home
            </Link>
             <Link
              to={"/profile"}
              className="text-primary hover:text-primary/80 font-medium"
            >
              Profile
            </Link>
          </div>
          <div className="hidden md:flex space-x-6">
           
          </div>

          {/* Mobile Hamburger */}
          <div className="md:hidden">
            <button
              onClick={() => setIsOpen(!isOpen)}
              className="text-primary hover:text-primary/80 font-medium inline-flex items-center justify-center p-2 rounded-md focus:outline-none"
            >
              {/* Hamburger icon */}
              <svg
                className="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                {isOpen ? (
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                ) : (
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                )}
              </svg>
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Dropdown */}
      {isOpen && (
        <div className="md:hidden px-4 pb-4">
          <Link
            to="/"
            onClick={() => setIsOpen(false)}
            className="block py-2 text-primary
            hover:text-primary/80 font-medium  text-center"
          >
            Home
          </Link>
          <Link
            to="/profile"
            onClick={() => setIsOpen(false)}
            className="block py-2 text-primary
            hover:text-primary/80 font-medium  text-center"
          >
            profile
          </Link>
        </div>
      )}
    </nav>
  );
}
