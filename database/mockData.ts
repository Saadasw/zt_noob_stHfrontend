
import { UserRole, User, Branch, PatientProfile, DoctorProfile, Appointment, AppointmentStatus, MedicalRecord, Medicine, Inventory, Bill, PaymentStatus } from './types';

export const mockUsers: User[] = [
  { id: 'u1', email: 'admin@stgeorgehospital.org', name: 'Dr. Sarah Mitchell', role: UserRole.ADMIN, phone: '555-0101', isActive: true },
  { id: 'u2', email: 'john.doe@email.com', name: 'John Doe', role: UserRole.PATIENT, phone: '555-0102', isActive: true },
  { id: 'u3', email: 'dr.james@stgeorgehospital.org', name: 'Dr. James Wilson', role: UserRole.DOCTOR, phone: '555-0103', isActive: true },
  { id: 'u4', email: 'alice.staff@stgeorgehospital.org', name: 'Alice Thompson', role: UserRole.STAFF, phone: '555-0104', isActive: true },
  { id: 'u5', email: 'robert.smith@email.com', name: 'Robert Smith', role: UserRole.PATIENT, phone: '555-0105', isActive: false },
  { id: 'u6', email: 'clara.n@stgeorgehospital.org', name: 'Nurse Clara Nightingale', role: UserRole.STAFF, phone: '555-0106', isActive: true },
];

export const mockBranches: Branch[] = [
  { 
    id: 'b1', 
    name: 'St. George Central', 
    code: 'SGC01', 
    address: '77 Cathedral Square',
    city: 'London', 
    state: 'UK',
    phone: '555-9000',
    email: 'central@stgeorgehospital.org',
    isActive: true,
    createdAt: '2023-01-01T08:00:00'
  },
  { 
    id: 'b2', 
    name: 'St. George East Clinic', 
    code: 'SGE02', 
    address: '456 Riverside Way',
    city: 'London', 
    state: 'UK',
    phone: '555-8000',
    email: 'east@stgeorgehospital.org',
    isActive: true,
    createdAt: '2023-03-15T09:30:00'
  },
];

export const mockPatients: PatientProfile[] = [
  { 
    id: 'p1', 
    userId: 'u2', 
    patientId: 'PAT-001', 
    dateOfBirth: '1985-05-15', 
    gender: 'Male', 
    bloodGroup: 'O+', 
    allergies: 'Peanuts, Penicillin',
    medicalHistory: 'Type 2 Diabetes',
    user: mockUsers[1]
  }
];

// Removed availableDays, startTime, and endTime as they are not defined in the DoctorProfile interface in types.ts
export const mockDoctors: DoctorProfile[] = [
  { 
    id: 'd1', 
    userId: 'u3', 
    branchId: 'b1', 
    specialization: 'Cardiology', 
    licenseNumber: 'LIC-10022',
    consultationFee: 150,
    slotDuration: 30,
    user: mockUsers[2]
  }
];

export const mockAppointments: Appointment[] = [
  {
    id: 'a1',
    appointmentNo: 'SGH-1001',
    patientId: 'p1',
    doctorId: 'd1',
    branchId: 'b1',
    appointmentDate: '2023-12-01',
    startTime: '10:00',
    endTime: '10:30',
    dateTime: '2023-12-01T10:00:00',
    duration: 30,
    status: AppointmentStatus.CONFIRMED,
    reason: 'Regular heart checkup',
    notes: 'Patient requested morning slot.',
    createdAt: '2023-11-25T10:00:00',
    patient: mockPatients[0],
    doctor: mockDoctors[0],
    type: 'Consultation',
    reminderSent: false
  }
];

export const mockRecords: MedicalRecord[] = [
  {
    id: 'r1',
    recordNo: 'REC-5001',
    patientId: 'p1',
    doctorId: 'd1',
    chiefComplaint: 'Chest tightness',
    symptoms: 'Mild pain, shortness of breath',
    diagnosis: 'Angina Pectoris',
    treatmentPlan: 'Rest, Nitroglycerin, Stress Test scheduled',
    createdAt: '2023-11-20T14:30:00'
  }
];

export const mockMedicines: Medicine[] = [
  { 
    id: 'm1', 
    name: 'Amoxicillin', 
    genericName: 'Amoxicillin', 
    code: 'MED-AMX-01',
    category: 'Antibiotic', 
    manufacturer: 'PharmaCore',
    dosageForm: 'Capsule',
    strength: '500mg',
    unitPrice: 12.50, 
    requiresPrescription: true,
    isActive: true
  },
  { 
    id: 'm2', 
    name: 'Metformin', 
    genericName: 'Metformin', 
    code: 'MED-MET-02',
    category: 'Antidiabetic', 
    manufacturer: 'GlobalHealth',
    dosageForm: 'Tablet',
    strength: '500mg',
    unitPrice: 8.00, 
    requiresPrescription: true,
    isActive: true
  },
];

export const mockInventory: Inventory[] = [
  { 
    id: 'inv1', 
    medicineId: 'm1', 
    branchId: 'b1', 
    quantity: 500, 
    reorderLevel: 50, 
    batchNumber: 'BT-2024-001',
    expiryDate: '2025-12-31',
    lastUpdated: new Date().toISOString(),
    medicine: mockMedicines[0] 
  },
  { 
    id: 'inv2', 
    medicineId: 'm2', 
    branchId: 'b1', 
    quantity: 300, 
    reorderLevel: 30, 
    batchNumber: 'BT-2024-042',
    expiryDate: '2024-08-15',
    lastUpdated: new Date().toISOString(),
    medicine: mockMedicines[1] 
  },
];

export const mockBills: Bill[] = [
  { id: 'bill1', billNo: 'INV-2001', patientId: 'p1', totalAmount: 150.00, paymentStatus: PaymentStatus.PAID, createdAt: '2023-11-20T16:00:00', patient: mockPatients[0] }
];
