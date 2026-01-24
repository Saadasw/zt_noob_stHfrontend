// Patient Portal TypeScript Interfaces
// Based on database-erd.md schema

// ============================================
// CORE TYPES
// ============================================

export type UserRole = 'admin' | 'doctor' | 'staff' | 'patient';
export type Gender = 'male' | 'female' | 'other';
export type BloodGroup = 'A+' | 'A-' | 'B+' | 'B-' | 'AB+' | 'AB-' | 'O+' | 'O-';

// ============================================
// USER & AUTHENTICATION
// ============================================

export interface User {
    id: string;
    email: string;
    name: string;
    role: UserRole;
    phone: string;
    isActive: boolean;
    createdAt: Date;
    updatedAt: Date;
}

export interface ProfileImage {
    id: string;
    userId: string;
    imageUrl: string;
    fileType: string;
    isCurrent: boolean;
    uploadedAt: Date;
}

// ============================================
// PATIENT PROFILE
// ============================================

export interface PatientProfile {
    id: string;
    userId: string;
    patientId: string; // e.g., PAT-2026-000123
    nationalId: string;
    dateOfBirth: Date;
    gender: Gender;
    bloodGroup: BloodGroup;
    address: string;
    emergencyContact: string;
    emergencyPhone: string;
    allergies: string[];
    medicalHistory: string;
    preferredLanguage: string;
    consentStatus: boolean;
    consentDate: Date;
    createdAt: Date;
    updatedAt: Date;
}

export interface EmergencyContact {
    name: string;
    relationship: string;
    phone: string;
    email: string;
    address: string;
}

// ============================================
// BRANCH
// ============================================

export interface Branch {
    id: string;
    name: string;
    code: string;
    address: string;
    city: string;
    state: string;
    phone: string;
    email: string;
    isActive: boolean;
}

// ============================================
// DOCTOR
// ============================================

export interface DoctorProfile {
    id: string;
    userId: string;
    user?: User;
    branchId: string;
    branch?: Branch;
    specialization: string;
    licenseNumber: string;
    consultationFee: number;
    slotDuration: number; // in minutes
    isAvailable: boolean;
}

// ============================================
// APPOINTMENTS
// ============================================

export type AppointmentType = 'consultation' | 'follow_up' | 'emergency' | 'procedure';
export type AppointmentStatus = 'scheduled' | 'confirmed' | 'checked_in' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';

export interface TimeSlot {
    id: string;
    scheduleId: string;
    doctorId: string;
    branchId: string;
    slotDate: Date;
    startTime: string; // HH:MM format
    endTime: string;
    status: 'available' | 'booked' | 'blocked' | 'completed' | 'cancelled' | 'expired';
}

export interface Appointment {
    id: string;
    appointmentNo: string; // e.g., APT-2026-001234
    patientId: string;
    patient?: PatientProfile;
    doctorId: string;
    doctor?: DoctorProfile;
    branchId: string;
    branch?: Branch;
    timeSlotId: string;
    timeSlot?: TimeSlot;
    appointmentDate: Date;
    startTime: string;
    endTime: string;
    type: AppointmentType;
    reason: string;
    status: AppointmentStatus;
    reminderSent: boolean;
    checkedInAt?: Date;
    startedAt?: Date;
    completedAt?: Date;
    cancelledAt?: Date;
    cancelledBy?: string;
    cancellationReason?: string;
    createdAt: Date;
    updatedAt: Date;
}

// ============================================
// MEDICAL RECORDS
// ============================================

export type MedicalRecordStatus = 'draft' | 'finalized' | 'amended';

export interface Vitals {
    id: string;
    medicalRecordId: string;
    recordedBy: string;
    temperature: number; // Celsius
    bloodPressureSys: number;
    bloodPressureDia: number;
    pulseRate: number;
    respiratoryRate: number;
    weight: number; // kg
    height: number; // cm
    bmi: number;
    oxygenSaturation: number;
    bloodGlucose: number;
    notes: string;
    recordedAt: Date;
}

export interface MedicalRecord {
    id: string;
    recordNo: string; // e.g., MR-2026-001234
    patientId: string;
    patient?: PatientProfile;
    doctorId: string;
    doctor?: DoctorProfile;
    appointmentId: string;
    appointment?: Appointment;
    branchId: string;
    branch?: Branch;
    version: number;
    chiefComplaint: string;
    symptoms: string;
    diagnosis: string;
    treatmentPlan: string;
    notes: string;
    status: MedicalRecordStatus;
    vitals?: Vitals;
    prescriptions?: Prescription[];
    labTests?: LabTest[];
    finalizedAt?: Date;
    createdAt: Date;
    updatedAt: Date;
}

// ============================================
// PRESCRIPTIONS
// ============================================

export type PrescriptionStatus = 'active' | 'partially_dispensed' | 'fully_dispensed' | 'expired' | 'cancelled';
export type DispenseStatus = 'pending' | 'partial' | 'complete';

export interface Medicine {
    id: string;
    name: string;
    genericName: string;
    code: string;
    category: string;
    manufacturer: string;
    dosageForm: 'tablet' | 'capsule' | 'syrup' | 'injection' | 'cream';
    strength: string;
    unitPrice: number;
    requiresPrescription: boolean;
    isActive: boolean;
}

export interface PrescriptionItem {
    id: string;
    prescriptionId: string;
    medicineId: string;
    medicine?: Medicine;
    dosage: string;
    frequency: string;
    duration: string;
    quantity: number;
    refillsAllowed: number;
    refillsRemaining: number;
    instructions: string;
    dispenseStatus: DispenseStatus;
}

export interface Prescription {
    id: string;
    prescriptionNo: string; // e.g., RX-2026-004521
    medicalRecordId: string;
    patientId: string;
    patient?: PatientProfile;
    doctorId: string;
    doctor?: DoctorProfile;
    branchId: string;
    branch?: Branch;
    status: PrescriptionStatus;
    validUntil: Date;
    notes: string;
    items: PrescriptionItem[];
    createdAt: Date;
    updatedAt: Date;
}

// ============================================
// LAB TESTS
// ============================================

export type LabTestPriority = 'routine' | 'urgent' | 'stat';
export type LabTestStatus = 'ordered' | 'sample_pending' | 'sample_collected' | 'processing' | 'completed' | 'cancelled';

export interface LabTestType {
    id: string;
    name: string;
    code: string;
    category: string;
    price: number;
    turnaroundTime: string;
    description: string;
    sampleRequirements: string;
    preparationInstructions: string;
    isActive: boolean;
}

export interface LabTestResult {
    parameter: string;
    result: string;
    referenceRange: string;
    status: 'normal' | 'low' | 'high' | 'critical';
}

export interface LabTest {
    id: string;
    testNo: string; // e.g., LAB-2026-008834
    patientId: string;
    patient?: PatientProfile;
    doctorId: string;
    doctor?: DoctorProfile;
    technicianId?: string;
    testTypeId: string;
    testType?: LabTestType;
    branchId: string;
    branch?: Branch;
    medicalRecordId?: string;
    priority: LabTestPriority;
    status: LabTestStatus;
    clinicalNotes: string;
    result: string;
    results?: LabTestResult[]; // Parsed results
    technicianNotes: string;
    reportUrl: string;
    interpretation?: string;
    verifiedBy?: string;
    orderedAt: Date;
    sampleCollectedAt?: Date;
    processingStartedAt?: Date;
    completedAt?: Date;
    verifiedAt?: Date;
    createdAt: Date;
    updatedAt: Date;
}

// ============================================
// BILLING & PAYMENTS
// ============================================

export type PaymentStatus = 'pending' | 'partial' | 'paid' | 'overdue' | 'cancelled' | 'refunded';
export type PaymentMethod = 'cash' | 'card' | 'bank_transfer' | 'upi' | 'cheque';
export type BillItemType = 'consultation' | 'lab_test' | 'medicine' | 'room' | 'procedure' | 'other';

export interface BillItem {
    id: string;
    billId: string;
    description: string;
    itemType: BillItemType;
    referenceId: string;
    quantity: number;
    unitPrice: number;
    discountAmount: number;
    amount: number;
}

export interface Bill {
    id: string;
    billNo: string; // e.g., INV-2026-001234
    patientId: string;
    patient?: PatientProfile;
    branchId: string;
    branch?: Branch;
    appointmentId?: string;
    admissionId?: string;
    subtotal: number;
    taxRate: number;
    taxAmount: number;
    discountPercentage: number;
    discountAmount: number;
    discountReason?: string;
    totalAmount: number;
    paidAmount: number;
    dueAmount: number;
    paymentStatus: PaymentStatus;
    dueDate: Date;
    items: BillItem[];
    createdAt: Date;
    paidAt?: Date;
    updatedAt: Date;
}

export interface Payment {
    id: string;
    billId: string;
    bill?: Bill;
    receiptNo: string; // e.g., TXN-2026-009876
    amount: number;
    method: PaymentMethod;
    transactionRef: string;
    status: 'pending' | 'completed' | 'failed' | 'refunded';
    processedBy?: string;
    notes: string;
    paidAt: Date;
    createdAt: Date;
}

// ============================================
// NOTIFICATIONS
// ============================================

export type NotificationType = 'appointment' | 'lab_result' | 'prescription' | 'billing' | 'system' | 'reminder';
export type NotificationPriority = 'low' | 'normal' | 'high' | 'urgent';
export type NotificationChannel = 'in_app' | 'email' | 'sms' | 'push';

export interface Notification {
    id: string;
    userId: string;
    title: string;
    message: string;
    type: NotificationType;
    priority: NotificationPriority;
    channel: NotificationChannel;
    isRead: boolean;
    isSent: boolean;
    referenceType?: string;
    referenceId?: string;
    readAt?: Date;
    sentAt?: Date;
    createdAt: Date;
}

// ============================================
// DASHBOARD DATA
// ============================================

export interface PatientDashboardData {
    patient: PatientProfile;
    upcomingAppointmentsCount: number;
    labResultsReadyCount: number;
    activePrescriptionsCount: number;
    outstandingBillsAmount: number;
    nextAppointment?: Appointment;
    recentActivity: ActivityItem[];
}

export interface ActivityItem {
    id: string;
    type: 'lab_result' | 'prescription' | 'appointment' | 'bill';
    title: string;
    description: string;
    date: Date;
    referenceId: string;
}
