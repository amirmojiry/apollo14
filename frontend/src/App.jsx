import { Navigate, Route, Routes } from "react-router-dom";
import Navbar from "./components/Navbar";
import HomePage from "./pages/HomePage";
import ProfilePage from "./pages/ProfilePage";
import LoginPage from "./pages/LoginPage";
import { useAuthStore } from "./store/useAuthStore";

function App() {
  const { isAuthenticated } = useAuthStore();
  return (
    <div>
      {isAuthenticated && <Navbar />}

      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/" element={isAuthenticated ? <HomePage /> : <Navigate to="/login" replace />} />
        <Route path="/profile" element={isAuthenticated ? <ProfilePage /> : <Navigate to="/login" replace />} />
      </Routes>
    </div>
  );
}

export default App;
