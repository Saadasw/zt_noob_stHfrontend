
import React, { useState, useEffect } from 'react';
import { HashRouter as Router, Routes, Route, Link, useLocation, Navigate } from 'react-router-dom';
import {
  LayoutDashboard,
  Users,
  UserRound,
  Calendar,
  ClipboardList,
  Pill,
  Receipt,
  Bell,
  Settings,
  Search,
  Menu,
  Stethoscope,
  ShieldCheck,
  Database,
  LogOut,
  Building2,
  FlaskConical,
  Bed,
  Package,
  Plus,
  Clock,
  UserCheck
} from 'lucide-react';
import Dashboard from './pages/Dashboard';
import PatientList from './pages/PatientList';
import PatientDetail from './pages/PatientDetail';
import AppointmentList from './pages/AppointmentList';
import PatientAppointmentForm from './pages/PatientAppointmentForm';
import InventoryManagement from './pages/InventoryManagement';
import BillingList from './pages/BillingList';
import MedicalRecordsList from './pages/MedicalRecordsList';
import UserManagement from './pages/UserManagement';
import DatabaseManager from './pages/DatabaseManager';
import BranchManagement from './pages/BranchManagement';
import MedicineManagement from './pages/MedicineManagement';
import LabManagement from './pages/LabManagement';
import RoomManagement from './pages/RoomManagement';
import DoctorScheduleManager from './pages/DoctorScheduleManager';
import DoctorOverrideRequest from './pages/DoctorOverrideRequest';
import PatientProfilePage from './pages/PatientProfilePage';
import PatientLabTests from './pages/PatientLabTests';
import DoctorDirectory from './pages/DoctorDirectory';
import Login from './pages/Login';
import { db } from './utils/storage';
import { User, UserRole } from './types';

const SidebarItem = ({ icon: Icon, label, path, active }: { icon: any, label: string, path: string, active: boolean }) => (
  <Link
    to={path}
    className={`flex items-center gap-2 px-3 py-2 text-sm ${active
        ? 'bg-blue-600 text-white'
        : 'text-gray-700 hover:bg-gray-100'
      }`}
  >
    <Icon size={18} />
    <span>{label}</span>
  </Link>
);

const Navbar = ({ user }: { user: User | null }) => (
  <header className="h-14 bg-white border-b border-gray-300 flex items-center justify-between px-4">
    <div className="flex items-center gap-3">
      <button className="lg:hidden text-gray-600">
        <Menu size={22} />
      </button>
      <div className="relative hidden md:block">
        <Search className="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400" size={16} />
        <input
          type="text"
          placeholder="Search..."
          className="bg-gray-100 border border-gray-300 rounded py-1.5 pl-8 pr-3 text-sm w-48 focus:outline-none focus:border-blue-500"
        />
      </div>
    </div>
    <div className="flex items-center gap-3">
      <button className="p-1.5 text-gray-600 hover:bg-gray-100 rounded relative">
        <Bell size={18} />
        <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
      </button>
      <div className="flex items-center gap-2 pl-3 border-l border-gray-300">
        <div className="text-right hidden sm:block">
          <p className="text-sm font-medium text-gray-800">{user?.name || 'User'}</p>
          <p className="text-xs text-gray-500">{user?.role || 'Guest'}</p>
        </div>
        <div className="relative group cursor-pointer">
          <img
            src={`https://picsum.photos/seed/${user?.id}/36/36`}
            alt="User"
            className="w-9 h-9 rounded-full border border-gray-300"
          />
          <div className="absolute right-0 top-full mt-1 w-40 bg-white border border-gray-300 rounded shadow opacity-0 invisible group-hover:opacity-100 group-hover:visible p-1 z-50">
            <Link to="/profile" className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
              <UserCheck size={14} /> My Profile
            </Link>
            <button
              onClick={() => db.logout()}
              className="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded"
            >
              <LogOut size={14} /> Sign Out
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
);

const Layout = ({ children, user }: { children?: React.ReactNode, user: User | null }) => {
  const location = useLocation();

  return (
    <div className="flex min-h-screen bg-gray-100">
      <aside className="w-56 bg-white border-r border-gray-300 hidden lg:flex flex-col">
        <div className="p-4 flex items-center gap-2 border-b border-gray-300">
          <div className="w-8 h-8 bg-blue-600 rounded flex items-center justify-center text-white">
            <Stethoscope size={18} />
          </div>
          <span className="text-lg font-bold text-gray-800">St. George</span>
        </div>

        <nav className="flex-1 py-2 overflow-y-auto">
          <p className="px-3 text-xs font-semibold text-gray-500 mb-2 mt-2">Main Menu</p>
          <SidebarItem icon={LayoutDashboard} label="Dashboard" path="/" active={location.pathname === '/'} />

          {(user?.role === UserRole.ADMIN || user?.role === UserRole.DOCTOR || user?.role === UserRole.STAFF) && (
            <SidebarItem icon={Users} label="Patients" path="/patients" active={location.pathname.startsWith('/patients')} />
          )}

          <SidebarItem icon={Calendar} label="Appointments" path="/appointments" active={location.pathname === '/appointments'} />

          {user?.role === UserRole.PATIENT && (
            <>
              <SidebarItem icon={Stethoscope} label="Find Doctors" path="/doctor-directory" active={location.pathname === '/doctor-directory'} />
              <SidebarItem icon={Plus} label="Book Slot" path="/book-appointment" active={location.pathname === '/book-appointment'} />
              <SidebarItem icon={FlaskConical} label="Lab Tests" path="/my-labs" active={location.pathname === '/my-labs'} />
            </>
          )}

          {user?.role === UserRole.DOCTOR && (
            <SidebarItem icon={Clock} label="My Availability" path="/my-availability" active={location.pathname === '/my-availability'} />
          )}

          <p className="px-3 text-xs font-semibold text-gray-500 mb-2 mt-4">Operations</p>

          {user?.role === UserRole.ADMIN && (
            <SidebarItem icon={Clock} label="Physician Sched" path="/doctor-schedules" active={location.pathname === '/doctor-schedules'} />
          )}

          <SidebarItem icon={Package} label="Branch Stock" path="/inventory" active={location.pathname === '/inventory'} />

          {(user?.role === UserRole.ADMIN || user?.role === UserRole.STAFF) && (
            <SidebarItem icon={Receipt} label="Billing" path="/billing" active={location.pathname === '/billing'} />
          )}

          <p className="px-3 text-xs font-semibold text-gray-500 mb-2 mt-4">Resources</p>
          <SidebarItem icon={Pill} label="Medicine Catalog" path="/medicine-catalog" active={location.pathname === '/medicine-catalog'} />
          <SidebarItem icon={FlaskConical} label="Admin Lab Tests" path="/lab-tests" active={location.pathname === '/lab-tests'} />
          <SidebarItem icon={Bed} label="Rooms" path="/rooms" active={location.pathname === '/rooms'} />

          <p className="px-3 text-xs font-semibold text-gray-500 mb-2 mt-4">System Admin</p>
          {user?.role === UserRole.ADMIN && (
            <>
              <SidebarItem icon={Building2} label="Branches" path="/branches" active={location.pathname === '/branches'} />
              <SidebarItem icon={ShieldCheck} label="Users" path="/users" active={location.pathname === '/users'} />
              <SidebarItem icon={Database} label="Mock Database" path="/database" active={location.pathname === '/database'} />
            </>
          )}
          <SidebarItem icon={Settings} label="Settings" path="/settings" active={location.pathname === '/settings'} />
        </nav>

        <div className="p-2 border-t border-gray-300">
          <button
            onClick={() => db.logout()}
            className="flex items-center gap-2 px-3 py-2 w-full text-gray-700 hover:bg-red-50 hover:text-red-600 text-sm"
          >
            <LogOut size={18} /> Logout
          </button>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <Navbar user={user} />
        <main className="flex-1 overflow-y-auto p-4">
          {children}
        </main>
      </div>
    </div>
  );
};

export default function App() {
  const [user, setUser] = useState<User | null>(db.getCurrentUser());

  useEffect(() => {
    db.init();
    setUser(db.getCurrentUser());
  }, []);

  const handleLoginSuccess = () => {
    setUser(db.getCurrentUser());
  };

  if (!user) {
    return <Login onLoginSuccess={handleLoginSuccess} />;
  }

  const isProfileIncomplete = user.role === UserRole.PATIENT && user.isProfileComplete === false;

  return (
    <Router>
      <Layout user={user}>
        <Routes>
          <Route path="/profile" element={<PatientProfilePage />} />

          {isProfileIncomplete ? (
            <Route path="*" element={<Navigate to="/profile" replace />} />
          ) : (
            <>
              <Route path="/" element={<Dashboard />} />
              <Route path="/patients" element={<PatientList />} />
              <Route path="/patients/:id" element={<PatientDetail />} />
              <Route path="/appointments" element={<AppointmentList />} />
              <Route path="/doctor-directory" element={<DoctorDirectory />} />
              <Route path="/book-appointment" element={user.role === UserRole.PATIENT ? <PatientAppointmentForm /> : <Navigate to="/appointments" replace />} />
              <Route path="/my-labs" element={user.role === UserRole.PATIENT ? <PatientLabTests /> : <Navigate to="/" replace />} />
              <Route path="/doctor-schedules" element={user.role === UserRole.ADMIN ? <DoctorScheduleManager /> : <Navigate to="/" replace />} />
              <Route path="/my-availability" element={user.role === UserRole.DOCTOR ? <DoctorOverrideRequest /> : <Navigate to="/" replace />} />
              <Route path="/records" element={<MedicalRecordsList />} />
              <Route path="/inventory" element={<InventoryManagement />} />
              <Route path="/billing" element={<BillingList />} />
              <Route path="/medicine-catalog" element={<MedicineManagement />} />
              <Route path="/lab-tests" element={<LabManagement />} />
              <Route path="/rooms" element={<RoomManagement />} />
              <Route path="/users" element={user.role === UserRole.ADMIN ? <UserManagement /> : <Navigate to="/" replace />} />
              <Route path="/branches" element={user.role === UserRole.ADMIN ? <BranchManagement /> : <Navigate to="/" replace />} />
              <Route path="/database" element={user.role === UserRole.ADMIN ? <DatabaseManager /> : <Navigate to="/" replace />} />
              <Route path="*" element={<Navigate to="/" replace />} />
            </>
          )}
        </Routes>
      </Layout>
    </Router>
  );
}
