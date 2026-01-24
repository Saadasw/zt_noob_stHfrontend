
import React, { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  ArrowLeft,
  Calendar,
  FileText,
  Activity,
  Thermometer,
  Droplet,
  Scale,
  Plus,
  BrainCircuit,
  AlertTriangle,
  Loader2,
  CheckCircle
} from 'lucide-react';
import { mockPatients, mockRecords } from '../mockData';
import { analyzeMedicalRecord, AnalysisResult } from '../geminiService';

const PatientDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const patient = mockPatients.find(p => p.id === id);
  const [analyzing, setAnalyzing] = useState(false);
  const [analysisResult, setAnalysisResult] = useState<AnalysisResult | null>(null);

  if (!patient) return <div>Patient not found</div>;

  const handleAiAnalysis = async () => {
    setAnalyzing(true);
    const result = await analyzeMedicalRecord(
      mockRecords[0].symptoms,
      patient.medicalHistory || "None provided"
    );
    setAnalysisResult(result);
    setAnalyzing(false);
  };

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      <div className="flex items-center gap-3">
        <Link to="/patients" className="p-2 hover:bg-gray-100 rounded border border-gray-300 text-gray-600">
          <ArrowLeft size={18} />
        </Link>
        <h1 className="text-xl font-bold text-gray-800">Patient Profile</h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Profile Info Sidebar */}
        <div className="space-y-4">
          <div className="bg-white p-6 border border-gray-300 rounded text-center">
            <img
              src={`https://picsum.photos/seed/${patient.id}/100/100`}
              className="w-20 h-20 rounded-full mx-auto border-2 border-gray-300 mb-3"
              alt=""
            />
            <h2 className="text-lg font-bold text-gray-800">{patient.user?.name}</h2>
            <p className="text-sm text-gray-500">{patient.patientId}</p>
            <div className="flex items-center justify-center gap-2 mt-3">
              <span className="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">{patient.gender}</span>
              <span className="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded">{patient.bloodGroup}</span>
            </div>
            <div className="mt-6 pt-4 border-t border-gray-200 text-left space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Date of Birth</span>
                <span className="text-gray-800 font-medium">{patient.dateOfBirth}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Contact</span>
                <span className="text-gray-800 font-medium">{patient.user?.phone}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Last Visit</span>
                <span className="text-gray-800 font-medium">Oct 12, 2023</span>
              </div>
            </div>
          </div>

          <div className="bg-yellow-50 p-4 border border-yellow-200 rounded">
            <div className="flex items-center gap-2 text-yellow-700 font-medium mb-2 text-sm">
              <AlertTriangle size={16} />
              Allergies & Risks
            </div>
            <p className="text-sm text-yellow-800">
              {patient.allergies || "No allergies reported"}
            </p>
          </div>
        </div>

        {/* Main Content Area */}
        <div className="lg:col-span-2 space-y-4">
          {/* Stats Bar */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div className="bg-white p-3 border border-gray-300 rounded text-center">
              <Thermometer className="mx-auto text-red-500 mb-1" size={18} />
              <p className="text-xs text-gray-500">Temp</p>
              <p className="text-lg font-bold text-gray-800">98.6°F</p>
            </div>
            <div className="bg-white p-3 border border-gray-300 rounded text-center">
              <Droplet className="mx-auto text-blue-500 mb-1" size={18} />
              <p className="text-xs text-gray-500">BP</p>
              <p className="text-lg font-bold text-gray-800">120/80</p>
            </div>
            <div className="bg-white p-3 border border-gray-300 rounded text-center">
              <Activity className="mx-auto text-green-500 mb-1" size={18} />
              <p className="text-xs text-gray-500">Heart Rate</p>
              <p className="text-lg font-bold text-gray-800">72 bpm</p>
            </div>
            <div className="bg-white p-3 border border-gray-300 rounded text-center">
              <Scale className="mx-auto text-yellow-500 mb-1" size={18} />
              <p className="text-xs text-gray-500">Weight</p>
              <p className="text-lg font-bold text-gray-800">165 lbs</p>
            </div>
          </div>

          {/* AI Medical Assistant */}
          <div className="bg-white p-4 border border-gray-300 rounded">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-2">
                <div className="p-2 bg-blue-600 text-white rounded">
                  <BrainCircuit size={18} />
                </div>
                <div>
                  <h3 className="text-md font-bold text-gray-800">Medical AI Assistant</h3>
                  <p className="text-xs text-gray-500">Clinical analysis helper</p>
                </div>
              </div>
              {!analysisResult && (
                <button
                  onClick={handleAiAnalysis}
                  disabled={analyzing}
                  className="flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                >
                  {analyzing ? <Loader2 className="animate-spin" size={14} /> : <Activity size={14} />}
                  {analyzing ? 'Analyzing...' : 'Analyze'}
                </button>
              )}
            </div>

            {analysisResult ? (
              <div className="space-y-3">
                <div className={`inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium ${analysisResult.riskLevel === 'HIGH' ? 'bg-red-100 text-red-700' :
                    analysisResult.riskLevel === 'MEDIUM' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'
                  }`}>
                  <AlertTriangle size={12} />
                  Risk Level: {analysisResult.riskLevel}
                </div>
                <p className="text-gray-600 text-sm bg-gray-50 p-3 rounded border border-gray-200">
                  "{analysisResult.summary}"
                </p>
                <div>
                  <p className="text-xs font-semibold text-gray-500 mb-2">Recommendations</p>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                    {analysisResult.suggestions.map((s, i) => (
                      <div key={i} className="flex items-start gap-2 p-2 bg-blue-50 rounded border border-blue-100">
                        <CheckCircle className="text-blue-500 shrink-0 mt-0.5" size={14} />
                        <p className="text-xs text-gray-700">{s}</p>
                      </div>
                    ))}
                  </div>
                </div>
                <button onClick={() => setAnalysisResult(null)} className="text-xs text-blue-600 hover:underline">Re-analyze</button>
              </div>
            ) : (
              <div className="text-center py-6">
                <p className="text-gray-500 text-sm">Click "Analyze" to get AI insights on this patient.</p>
              </div>
            )}
          </div>

          {/* Medical Records */}
          <div className="bg-white border border-gray-300 rounded">
            <div className="p-4 border-b border-gray-200 flex justify-between items-center">
              <h3 className="text-md font-bold text-gray-800">Medical History</h3>
              <button className="text-blue-600 text-sm flex items-center gap-1 hover:underline">
                <Plus size={14} /> New Record
              </button>
            </div>
            <div className="p-4 space-y-4">
              {mockRecords.map((record) => (
                <div key={record.id} className="relative pl-6 pb-4 border-l-2 border-gray-200 last:pb-0">
                  <div className="absolute left-[-5px] top-0 w-2 h-2 rounded-full bg-blue-600"></div>
                  <div className="flex flex-col md:flex-row md:items-center justify-between gap-1 mb-2">
                    <span className="text-xs font-medium text-gray-500">
                      {new Date(record.createdAt).toLocaleDateString()}
                    </span>
                    <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                      {record.recordNo}
                    </span>
                  </div>
                  <div className="p-3 rounded border border-gray-200 bg-gray-50">
                    <h4 className="font-medium text-gray-800 mb-2">{record.chiefComplaint}</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                      <div>
                        <p className="text-xs font-medium text-gray-500 mb-1">Diagnosis</p>
                        <p className="text-sm text-gray-700">{record.diagnosis}</p>
                      </div>
                      <div>
                        <p className="text-xs font-medium text-gray-500 mb-1">Doctor</p>
                        <p className="text-sm text-gray-700">Dr. James Wilson</p>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PatientDetail;
