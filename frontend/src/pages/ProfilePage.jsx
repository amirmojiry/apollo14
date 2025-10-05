import React from "react";

const ProfilePage = () => {
  const ranks = [
    { id: 1, name: "Alice", score: 95 },
    { id: 2, name: "Bob", score: 90 },
    { id: 3, name: "Charlie", score: 85 },
  ];

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
        <h1 className="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-100">
          Ranking
        </h1>
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-200 dark:bg-gray-700">
              <th className="p-2 text-left">Rank</th>
              <th className="p-2 text-left">Name</th>
              <th className="p-2 text-left">Score</th>
            </tr>
          </thead>
          <tbody>
            {ranks.map((person, index) => (
              <tr key={person.id} className="border-b border-gray-300 ">
                <td className="p-2">{index + 1}</td>
                <td className="p-2">{person.name}</td>
                <td className="p-2">{person.score}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ProfilePage;
