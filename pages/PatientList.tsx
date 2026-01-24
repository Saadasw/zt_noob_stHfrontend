
import React from 'react';
import { Link } from 'react-router-dom';
import { Search, Plus, Filter, MoreVertical, FileText, Download } from 'lucide-react';
import { mockPatients } from '../mockData';
import { exportToCSV } from '../utils/csvExport';

const PatientList: React.FC = () => {
  const handleExport = () => {
    exportToCSV(mockPatients, 'Hospital_Patients');
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-bold text-gray-800">Patients Directory</h1>
          <p className="text-gray-600 text-sm">Manage and view all registered patients.</p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={handleExport}
            className="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50"
          >
            <Download size={16} /> Export CSV
          </button>
          <button className="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700 flex items-center gap-2">
            <Plus size={16} /> Add Patient
          </button>
        </div>
      </div>

      <div className="bg-white border border-gray-300 rounded overflow-hidden">
        <div className="p-3 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div className="relative flex-1 max-w-sm">
            <Search className="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400" size={16} />
            <input
              type="text"
              placeholder="Search by name, ID or phone..."
              className="border border-gray-300 rounded py-1.5 pl-8 pr-3 text-sm w-full focus:outline-none focus:border-blue-500"
            />
          </div>
          <button className="flex items-center gap-1 px-2 py-1.5 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
            <Filter size={16} /> Filter
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Name</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Patient ID</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Gender / Age</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Contact</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Blood Group</th>
                <th className="px-4 py-3 text-right text-xs font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {mockPatients.map((patient) => (
                <tr key={patient.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <img src={`https://picsum.photos/seed/${patient.id}/36/36`} className="w-9 h-9 rounded-full border border-gray-200" alt="" />
                      <div>
                        <Link to={`/patients/${patient.id}`} className="text-sm font-medium text-gray-800 hover:text-blue-600">{patient.user?.name}</Link>
                        <p className="text-xs text-gray-500">{patient.user?.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{patient.patientId}</span>
                  </td>
                  <td className="px-4 py-3">
                    <p className="text-sm text-gray-700">{patient.gender}, 38y</p>
                  </td>
                  <td className="px-4 py-3">
                    <p className="text-sm text-gray-700">{patient.user?.phone}</p>
                  </td>
                  <td className="px-4 py-3">
                    <span className="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">{patient.bloodGroup}</span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <Link to={`/patients/${patient.id}`} className="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded">
                        <FileText size={16} />
                      </Link>
                      <button className="p-1.5 text-gray-500 hover:text-gray-700 rounded">
                        <MoreVertical size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default PatientList;
