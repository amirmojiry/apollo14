import React, { useState } from "react";
import { useAuthStore } from "../store/useAuthStore";
import { useNavigate } from "react-router-dom";

export default function LoginPage() {
  const navigate = useNavigate();
  const { signIn } = useAuthStore();
  const [email, setEmail] = useState("test@test.com");
  const [password, setPassword] = useState("password");
  const [error, setError] = useState("");

  const handleSubmit = (e) => {
    e.preventDefault();
    const res = signIn({ email, password });
    if (res.success) {
      navigate("/");
    } else {
      setError(res.message || "Login failed");
    }
  };

  return (
    <main className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
      <div className="w-full max-w-sm bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h1 className="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-1">Sign in</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">Use demo credentials below.</p>
        {error && (
          <div className="mb-3 text-sm text-red-600">{error}</div>
        )}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm text-gray-600 dark:text-gray-300 mb-1">Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full border rounded-md px-3 py-2 bg-transparent"
              placeholder="test@test.com"
            />
          </div>
          <div>
            <label className="block text-sm text-gray-600 dark:text-gray-300 mb-1">Password</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full border rounded-md px-3 py-2 bg-transparent"
              placeholder="password"
            />
          </div>
          <button type="submit" className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-md">Sign in</button>
          <p className="text-xs text-gray-500 dark:text-gray-400 text-center">
            Hint: Use <span className="font-mono">test@test.com</span> / <span className="font-mono">password</span>
          </p>
        </form>
      </div>
    </main>
  );
}


