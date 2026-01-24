
import React, { useState, useEffect } from 'react';
import { Mail, Lock, LogIn, AlertCircle, Loader2, ShieldCheck, UserPlus, CheckCircle2 } from 'lucide-react';
import { db } from '../utils/storage';

interface LoginProps {
  onLoginSuccess: () => void;
}

const Login: React.FC<LoginProps> = ({ onLoginSuccess }) => {
  const [activeTab, setActiveTab] = useState<'login' | 'register'>('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [otp, setOtp] = useState('');
  const [isOtpSent, setIsOtpSent] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    db.init();
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    await new Promise(resolve => setTimeout(resolve, 800));

    const user = db.login(email, password);
    if (user) {
      onLoginSuccess();
    } else {
      setError("Invalid credentials. Use password '123' for any staff or existing user.");
      setIsLoading(false);
    }
  };

  const handleSendOTP = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) return;
    setIsLoading(true);
    setError(null);

    await new Promise(resolve => setTimeout(resolve, 1000));
    db.registerPatient(email);
    setIsOtpSent(true);
    setIsLoading(false);
  };

  const handleVerifyOTP = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    await new Promise(resolve => setTimeout(resolve, 1000));
    const user = db.verifyOTP(email, otp);
    if (user) {
      onLoginSuccess();
    } else {
      setError("Invalid code. For testing, please use 001122.");
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md bg-white border border-gray-300 rounded p-6">
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold text-gray-800">St. George Hospital</h1>
          <p className="text-gray-600 text-sm">Hospital Management System</p>
        </div>

        {/* Tab buttons */}
        <div className="flex mb-4 border-b border-gray-300">
          <button
            onClick={() => { setActiveTab('login'); setError(null); setIsOtpSent(false); }}
            className={`flex-1 py-2 text-sm font-medium ${activeTab === 'login' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'}`}
          >
            Login
          </button>
          <button
            onClick={() => { setActiveTab('register'); setError(null); setIsOtpSent(false); }}
            className={`flex-1 py-2 text-sm font-medium ${activeTab === 'register' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'}`}
          >
            Patient Signup
          </button>
        </div>

        <h2 className="text-lg font-semibold text-gray-800 mb-1">
          {activeTab === 'login' ? 'Welcome Back' : 'Create Account'}
        </h2>
        <p className="text-gray-600 text-sm mb-4">
          {activeTab === 'login' ? 'Sign in to access the portal.' : 'Only an email is required to register.'}
        </p>

        {error && (
          <div className="bg-red-100 border border-red-300 p-3 rounded flex items-start gap-2 mb-4">
            <AlertCircle className="text-red-500 shrink-0 mt-0.5" size={16} />
            <p className="text-sm text-red-700">{error}</p>
          </div>
        )}

        {activeTab === 'login' ? (
          <form onSubmit={handleLogin} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input
                  type="email" required value={email} onChange={(e) => setEmail(e.target.value)}
                  placeholder="name@example.com"
                  className="w-full border border-gray-300 rounded py-2 pl-10 pr-3 text-sm focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input
                  type="password" required value={password} onChange={(e) => setPassword(e.target.value)}
                  placeholder="Enter password"
                  className="w-full border border-gray-300 rounded py-2 pl-10 pr-3 text-sm focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <button
              type="submit" disabled={isLoading}
              className="w-full bg-blue-600 text-white rounded py-2 font-medium hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-2"
            >
              {isLoading ? <Loader2 className="animate-spin" size={18} /> : <LogIn size={18} />}
              Sign In
            </button>
          </form>
        ) : (
          <div className="space-y-4">
            {!isOtpSent ? (
              <form onSubmit={handleSendOTP} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                    <input
                      type="email" required value={email} onChange={(e) => setEmail(e.target.value)}
                      placeholder="your.email@gmail.com"
                      className="w-full border border-gray-300 rounded py-2 pl-10 pr-3 text-sm focus:outline-none focus:border-blue-500"
                    />
                  </div>
                </div>
                <button
                  type="submit" disabled={isLoading}
                  className="w-full bg-blue-600 text-white rounded py-2 font-medium hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {isLoading ? <Loader2 className="animate-spin" size={18} /> : <UserPlus size={18} />}
                  Send OTP Code
                </button>
              </form>
            ) : (
              <form onSubmit={handleVerifyOTP} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                  <div className="relative">
                    <ShieldCheck className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                    <input
                      type="text" required maxLength={6} value={otp} onChange={(e) => setOtp(e.target.value)}
                      placeholder="Enter 6-digit OTP"
                      className="w-full border border-gray-300 rounded py-2 pl-10 pr-3 text-sm text-center tracking-widest focus:outline-none focus:border-blue-500"
                    />
                  </div>
                </div>
                <div className="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700 text-center">
                  Testing Mode: Use OTP 001122
                </div>
                <button
                  type="submit" disabled={isLoading}
                  className="w-full bg-green-600 text-white rounded py-2 font-medium hover:bg-green-700 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {isLoading ? <Loader2 className="animate-spin" size={18} /> : <CheckCircle2 size={18} />}
                  Verify & Register
                </button>
                <button type="button" onClick={() => setIsOtpSent(false)} className="w-full text-sm text-blue-600 hover:underline">
                  Use different email?
                </button>
              </form>
            )}
          </div>
        )}

        <div className="mt-6 pt-4 border-t border-gray-200 text-center text-xs text-gray-500">
          Hospital Management System v1.0
        </div>
      </div>
    </div>
  );
};

export default Login;
