import React, { useMemo } from "react";
import { useAuthStore } from "../store/useAuthStore";

const ProfilePage = () => {
  // Demo data extended with guessesCount
  const ranks = [
    { id: 1, name: "Alice", score: 95, guessesCount: 12 },
    { id: 2, name: "Bob", score: 90, guessesCount: 9 },
    { id: 3, name: "Charlie", score: 85, guessesCount: 6 },
    { id: 4, name: "Demo User", score: 72, guessesCount: 7 },
  ];

  const { user } = useAuthStore();
  const currentUserName = user?.name || "Demo User";
  const sorted = useMemo(() => [...ranks].sort((a, b) => b.score - a.score), [ranks]);
  const myIndex = sorted.findIndex((u) => u.name === currentUserName);
  const myRank = myIndex >= 0 ? myIndex + 1 : null;
  const myUser = myIndex >= 0 ? sorted[myIndex] : null;

  return (
    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 h-screen p-6 bg-gray-50 dark:bg-gray-900">
      {/* Profile card */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col items-center md:col-span-1">
        <h1 className="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
          Profile
        </h1>
        <img
          src="p1.jpg"
          alt="User profile"
          className="h-32 w-32 rounded-full object-cover mb-4"
        />
        <p className="text-primary font-semibold">John Doe</p>
        <p className="text-base-content  text-sm">Software Engineer</p>
      </div>

      {/* Ranking table */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 overflow-auto md:col-span-3">
        <h1 className="text-2xl font-bold mb-2 text-gray-800 dark:text-gray-100">Ranking</h1>
        {myRank && myUser && (
          <div className="mb-4 p-3 rounded-md bg-blue-50 dark:bg-blue-900/30 text-sm text-blue-800 dark:text-blue-200">
            You are currently <span className="font-semibold">#{myRank}</span> with a score of <span className="font-semibold">{myUser.score}</span> based on <span className="font-semibold">{myUser.guessesCount}</span> guesses. Keep going!
          </div>
        )}
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-200 dark:bg-gray-700">
              <th className="p-2 text-left">Rank</th>
              <th className="p-2 text-left">Name</th>
              <th className="p-2 text-left">Score</th>
              <th className="p-2 text-left">Guesses</th>
            </tr>
          </thead>
          <tbody>
            {sorted.map((person, index) => (
              <tr key={person.id} className={`border-b border-gray-300 ${person.name === currentUserName ? "bg-yellow-50 dark:bg-yellow-900/20" : ""}`}>
                <td className="p-2">{index + 1}</td>
                <td className="p-2">{person.name}</td>
                <td className="p-2">{person.score}</td>
                <td className="p-2">{person.guessesCount}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ProfilePage;
