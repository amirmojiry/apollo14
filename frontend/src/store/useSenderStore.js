import { create } from "zustand";

export const useSenderStore = create((set, get) => ({
  image: null,
  location: null,
  barValue: 5,

  setImage: (image) => set({ image }),
  setLocation: (location) => set({ location }),
  setBarValue: (barValue) => set({ barValue }),

  sendData: async () => {
    const state = get();
    const payload = {
      image: state.image,
      barValue: state.barValue,
      latitude: state.location?.latitude,
      longitude: state.location?.longitude,
    };

    console.log(" Sending payload:", payload);
    try {
      const res = await fetch("http://localhost:4000/api/upload", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => null);
      console.log(" Backend response:", data);
    } catch (err) {
      console.error(" Error sending data:", err);
    }
  },
}));
