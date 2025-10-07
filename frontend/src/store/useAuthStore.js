import { create } from "zustand";

export const useAuthStore = create((set) => ({
  isAuthenticated: false,
  user: null,
  signIn: ({ email, password }) => {
    const demoEmail = "test@test.com";
    const demoPassword = "password";
    if (email === demoEmail && password === demoPassword) {
      const demoUser = { email: demoEmail, name: "Demo User" };
      set({ isAuthenticated: true, user: demoUser });
      return { success: true };
    }
    return { success: false, message: "Invalid credentials. Use test@test.com / password" };
  },
  signOut: () => set({ isAuthenticated: false, user: null }),
}));


