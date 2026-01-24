
import React from 'react';
import { Link } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area } from 'recharts';
import { Users, Calendar, Pill, TrendingUp, AlertCircle, Clock } from 'lucide-react';

const data = [
  { name: 'Mon', patients: 40, revenue: 2400 },
  { name: 'Tue', patients: 30, revenue: 1398 },
  { name: 'Wed', patients: 20, revenue: 9800 },
  { name: 'Thu', patients: 27, revenue: 3908 },
  { name: 'Fri', patients: 18, revenue: 4800 },
  { name: 'Sat', patients: 23, revenue: 3800 },
  { name: 'Sun', patients: 34, revenue: 4300 },
];

const StatCard = ({ title, value, icon: Icon, trend, color }: { title: string, value: string, icon: any, trend: string, color: string }) => (
  <div className="bg-white p-4 border border-gray-300 rounded">
    <div className="flex justify-between items-start mb-3">
      <div className={`p-2 rounded ${color} text-white`}>
        <Icon size={20} />
      </div>
      <span className="text-green-600 text-sm flex items-center gap-1">
        <TrendingUp size={14} /> {trend}
      </span>
    </div>
    <p className="text-gray-600 text-sm">{title}</p>
    <h3 className="text-xl font-bold text-gray-800 mt-1">{value}</h3>
  </div>
);

const Dashboard: React.FC = () => {
  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Hospital Overview</h1>
          <p className="text-gray-600 text-sm">Welcome to St. George Hospital Management System</p>
        </div>
        <div className="flex items-center gap-2">
          <select className="bg-white border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
            <option>Last 7 Days</option>
            <option>Last 30 Days</option>
            <option>This Year</option>
          </select>
          <button className="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700">
            Download Report
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard title="Total Patients" value="1,284" icon={Users} trend="+12%" color="bg-blue-500" />
        <StatCard title="Appointments" value="84" icon={Calendar} trend="+5%" color="bg-green-500" />
        <StatCard title="Medicine Stock" value="452" icon={Pill} trend="-2%" color="bg-yellow-500" />
        <StatCard title="Avg. Waiting Time" value="18m" icon={Clock} trend="-4%" color="bg-red-500" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="bg-white p-4 border border-gray-300 rounded">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Patient Admissions</h3>
          <div className="h-[250px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fill: '#6b7280', fontSize: 12 }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fill: '#6b7280', fontSize: 12 }} />
                <Tooltip />
                <Bar dataKey="patients" fill="#3b82f6" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-white p-4 border border-gray-300 rounded">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Revenue Analysis ($)</h3>
          <div className="h-[250px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={data}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fill: '#6b7280', fontSize: 12 }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fill: '#6b7280', fontSize: 12 }} />
                <Tooltip />
                <Area type="monotone" dataKey="revenue" stroke="#10b981" fill="#d1fae5" strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2 bg-white border border-gray-300 rounded overflow-hidden">
          <div className="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 className="text-lg font-bold text-gray-800">Recent Appointments</h3>
            <Link to="/appointments" className="text-blue-600 text-sm hover:underline">View All</Link>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Patient</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Doctor</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Time</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {[1, 2, 3].map((i) => (
                  <tr key={i} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <img src={`https://picsum.photos/seed/${i + 10}/28/28`} className="w-7 h-7 rounded-full" alt="" />
                        <div>
                          <p className="text-sm font-medium text-gray-800">John Doe</p>
                          <p className="text-xs text-gray-500">ID: PAT-00{i}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <p className="text-sm text-gray-700">Dr. James Wilson</p>
                    </td>
                    <td className="px-4 py-3">
                      <p className="text-sm text-gray-700">10:30 AM</p>
                    </td>
                    <td className="px-4 py-3">
                      <span className="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Confirmed</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div className="bg-white p-4 border border-gray-300 rounded">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Inventory Alerts</h3>
          <div className="space-y-3">
            {[1, 2].map((i) => (
              <div key={i} className="flex gap-3 p-3 rounded bg-red-50 border border-red-200">
                <div className="p-1.5 bg-red-500 text-white rounded h-fit">
                  <AlertCircle size={16} />
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-800">Critical Stock: Paracetamol</p>
                  <p className="text-xs text-gray-600 mt-1">Only 12 units remaining.</p>
                  <button className="text-red-600 text-xs font-medium mt-1 hover:underline">Reorder Now</button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
