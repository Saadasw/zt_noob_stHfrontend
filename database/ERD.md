# Hospital Management System - Database ERD

This document contains the Entity-Relationship Diagram for the St. George Hospital Management System.

## ERD Diagram

```mermaid
---
config:
  layout: elk
---
erDiagram
	direction TB
	User {
		VARCHAR(255) id PK ""  
		VARCHAR(255) email UK ""  
		VARCHAR(255) password  ""  
		VARCHAR(255) name  ""  
		VARCHAR(255) role  "admin/doctor/staff/patient"  
		VARCHAR(20) phone  ""  
		BOOLEAN isActive  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	ProfileImage {
		VARCHAR(255) id PK ""  
		VARCHAR(255) userId FK ""  
		VARCHAR(255) imageUrl  ""  
		VARCHAR(255) fileType  ""  
		BOOLEAN isCurrent  ""  
		DATETIME uploadedAt  ""  
		DATETIME createdAt  ""  
	}

	Branch {
		VARCHAR(255) id PK ""  
		VARCHAR(255) name  ""  
		VARCHAR(50) code UK ""  
		VARCHAR(500) address  ""  
		VARCHAR(100) city  ""  
		VARCHAR(100) state  ""  
		VARCHAR(20) phone  ""  
		VARCHAR(255) email  ""  
		BOOLEAN isActive  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	DoctorProfile {
		VARCHAR(255) id PK ""  
		VARCHAR(255) userId FK ""  
		VARCHAR(255) branchId FK "primary branch"  
		VARCHAR(255) specialization  ""  
		VARCHAR(100) licenseNumber UK ""  
		float consultationFee  ""  
		INT slotDuration  "in minutes"  
		BOOLEAN isAvailable  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	StaffProfile {
		VARCHAR(255) id PK ""  
		VARCHAR(255) userId FK ""  
		VARCHAR(255) branchId FK ""  
		VARCHAR(100) department  ""  
		VARCHAR(100) employeeId UK ""  
		VARCHAR(50) designation  ""  
		DATE joinDate  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	PatientProfile {
		VARCHAR(255) id PK ""  
		VARCHAR(255) userId FK ""  
		VARCHAR(100) patientId UK ""  
		VARCHAR(100) nationalId  ""  
		DATE dateOfBirth  ""  
		VARCHAR(20) gender  ""  
		VARCHAR(10) bloodGroup  ""  
		VARCHAR(500) address  ""  
		VARCHAR(255) emergencyContact  ""  
		VARCHAR(20) emergencyPhone  ""  
		TEXT allergies  ""  
		TEXT medicalHistory  ""  
		VARCHAR(50) preferredLanguage  ""  
		BOOLEAN consentStatus  ""  
		DATE consentDate  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	DoctorBranchAssignment {
		VARCHAR(255) id PK ""  
		VARCHAR(255) doctorId FK ""  
		VARCHAR(255) branchId FK ""  
		BOOLEAN isPrimaryBranch  ""  
		DATETIME assignedAt  ""  
		DATETIME createdAt  ""  
	}

	DoctorWeeklySchedule {
		VARCHAR(255) id PK ""  
		VARCHAR(255) doctorId FK ""  
		VARCHAR(255) branchId FK ""  
		INT dayOfWeek  "0=Sunday to 6=Saturday"  
		TIME startTime  ""  
		TIME endTime  ""  
		BOOLEAN isActive  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	DoctorScheduleOverride {
		VARCHAR(255) id PK ""  
		VARCHAR(255) doctorId FK ""  
		VARCHAR(255) branchId FK ""  
		DATE date  ""  
		VARCHAR(50) type  "leave/extra_hours/modified"  
		TIME startTime  ""  
		TIME endTime  ""  
		VARCHAR(255) reason  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	TimeSlot {
		VARCHAR(255) id PK ""  
		VARCHAR(255) scheduleId FK ""  
		VARCHAR(255) doctorId FK "denormalized for performance"  
		VARCHAR(255) branchId FK "denormalized for performance"  
		DATE slotDate  ""  
		TIME startTime  ""  
		TIME endTime  ""  
		VARCHAR(50) status  "available/booked/blocked/completed/cancelled/expired"  
		VARCHAR(255) blockedReason  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Appointment {
		VARCHAR(255) id PK ""  
		VARCHAR(100) appointmentNo UK ""  
		VARCHAR(255) patientId FK ""  
		VARCHAR(255) doctorId FK "denormalized for performance"  
		VARCHAR(255) branchId FK "denormalized for performance"  
		VARCHAR(255) timeSlotId FK ""  
		DATE appointmentDate  "denormalized for performance"  
		TIME startTime  "denormalized for performance"  
		TIME endTime  "denormalized for performance"  
		VARCHAR(50) type  "consultation/follow_up/emergency/procedure"  
		TEXT reason  ""  
		VARCHAR(50) status  "scheduled/confirmed/checked_in/in_progress/completed/cancelled/no_show"  
		BOOLEAN reminderSent  ""  
		DATETIME checkedInAt  ""  
		DATETIME startedAt  ""  
		DATETIME completedAt  ""  
		DATETIME cancelledAt  ""  
		VARCHAR(255) cancelledBy FK ""  
		TEXT cancellationReason  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	MedicalRecord {
		VARCHAR(255) id PK ""  
		VARCHAR(100) recordNo UK ""  
		VARCHAR(255) patientId FK ""  
		VARCHAR(255) doctorId FK ""  
		VARCHAR(255) appointmentId FK ""  
		VARCHAR(255) branchId FK ""  
		INT version  "for versioning"  
		TEXT chiefComplaint  ""  
		TEXT symptoms  ""  
		TEXT diagnosis  ""  
		TEXT treatmentPlan  ""  
		TEXT notes  ""  
		VARCHAR(50) status  "draft/finalized/amended"  
		DATETIME finalizedAt  ""  
		VARCHAR(255) updatedBy FK ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	MedicalRecordHistory {
		VARCHAR(255) id PK ""  
		VARCHAR(255) medicalRecordId FK ""  
		INT version  ""  
		TEXT chiefComplaint  ""  
		TEXT symptoms  ""  
		TEXT diagnosis  ""  
		TEXT treatmentPlan  ""  
		TEXT notes  ""  
		VARCHAR(255) modifiedBy FK ""  
		TEXT modificationReason  ""  
		DATETIME createdAt  ""  
	}

	Vitals {
		VARCHAR(255) id PK ""  
		VARCHAR(255) medicalRecordId FK ""  
		VARCHAR(255) recordedBy FK ""  
		FLOAT temperature  "in Celsius"  
		INT bloodPressureSys  ""  
		INT bloodPressureDia  ""  
		INT pulseRate  ""  
		INT respiratoryRate  ""  
		FLOAT weight  "in kg"  
		FLOAT height  "in cm"  
		FLOAT bmi  "denormalized for performance"  
		INT oxygenSaturation  ""  
		FLOAT bloodGlucose  ""  
		TEXT notes  ""  
		DATETIME recordedAt  ""  
		DATETIME createdAt  ""  
	}

	Prescription {
		VARCHAR(255) id PK ""  
		VARCHAR(100) prescriptionNo UK ""  
		VARCHAR(255) medicalRecordId FK ""  
		VARCHAR(255) patientId FK "denormalized for performance"  
		VARCHAR(255) doctorId FK "denormalized for performance"  
		VARCHAR(255) branchId FK "denormalized for performance"  
		VARCHAR(50) status  "active/partially_dispensed/fully_dispensed/expired/cancelled"  
		DATE validUntil  ""  
		TEXT notes  ""  
		VARCHAR(255) updatedBy FK ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	PrescriptionItem {
		VARCHAR(255) id PK ""  
		VARCHAR(255) prescriptionId FK ""  
		VARCHAR(255) medicineId FK ""  
		VARCHAR(100) dosage  ""  
		VARCHAR(100) frequency  ""  
		VARCHAR(100) duration  ""  
		INT quantity  ""  
		INT refillsAllowed  ""  
		INT refillsRemaining  ""  
		TEXT instructions  ""  
		VARCHAR(50) dispenseStatus  "pending/partial/complete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Medicine {
		VARCHAR(255) id PK ""  
		VARCHAR(255) name  ""  
		VARCHAR(255) genericName  ""  
		VARCHAR(100) code UK ""  
		VARCHAR(100) category  ""  
		VARCHAR(255) manufacturer  ""  
		VARCHAR(100) dosageForm  "tablet/capsule/syrup/injection/cream"  
		VARCHAR(100) strength  ""  
		float unitPrice  ""  
		BOOLEAN requiresPrescription  ""  
		BOOLEAN isActive  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Inventory {
		VARCHAR(255) id PK ""  
		VARCHAR(255) medicineId FK ""  
		VARCHAR(255) branchId FK ""  
		INT quantity  "CHECK >= 0"  
		INT reorderLevel  ""  
		VARCHAR(100) batchNumber  ""  
		DATE manufacturingDate  ""  
		DATE expiryDate  ""  
		float costPrice  ""  
		VARCHAR(50) status  "in_stock/low_stock/out_of_stock/expired"  
		DATETIME lastUpdated  ""  
		DATETIME createdAt  ""  
	}

	DispenseLog {
		VARCHAR(255) id PK ""  
		VARCHAR(100) dispenseNo UK ""  
		VARCHAR(255) prescriptionItemId FK ""  
		VARCHAR(255) inventoryId FK ""  
		VARCHAR(255) dispensedBy FK ""  
		INT dispensedQuantity  ""  
		INT remainingRefills  ""  
		VARCHAR(50) status  "dispensed/returned/cancelled"  
		float unitPriceAtDispense  ""  
		float totalAmount  ""  
		TEXT notes  ""  
		DATETIME dispensedAt  ""  
		DATETIME createdAt  ""  
	}

	LabTestType {
		VARCHAR(255) id PK ""  
		VARCHAR(255) name UK ""  
		VARCHAR(50) code UK ""  
		VARCHAR(100) category  ""  
		float price  ""  
		VARCHAR(100) turnaroundTime  ""  
		TEXT description  ""  
		TEXT sampleRequirements  ""  
		TEXT preparationInstructions  ""  
		BOOLEAN isActive  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	LabTest {
		VARCHAR(255) id PK ""  
		VARCHAR(100) testNo UK ""  
		VARCHAR(255) patientId FK "denormalized for performance"  
		VARCHAR(255) doctorId FK "denormalized for performance"  
		VARCHAR(255) technicianId FK ""  
		VARCHAR(255) testTypeId FK ""  
		VARCHAR(255) branchId FK "denormalized for performance"  
		VARCHAR(255) medicalRecordId FK ""  
		VARCHAR(50) priority  "routine/urgent/stat"  
		VARCHAR(50) status  "ordered/sample_pending/sample_collected/processing/completed/cancelled"  
		TEXT clinicalNotes  ""  
		TEXT result  ""  
		TEXT technicianNotes  ""  
		VARCHAR(500) reportUrl  ""  
		VARCHAR(255) verifiedBy FK ""  
		VARCHAR(255) updatedBy FK ""  
		DATETIME orderedAt  ""  
		DATETIME sampleCollectedAt  "denormalized for performance"  
		DATETIME processingStartedAt  ""  
		DATETIME completedAt  ""  
		DATETIME verifiedAt  ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	LabTestSample {
		VARCHAR(255) id PK ""  
		VARCHAR(100) sampleNo UK ""  
		VARCHAR(255) labTestId FK ""  
		VARCHAR(50) sampleType  "blood/urine/stool/tissue/swab/saliva/csf"  
		VARCHAR(100) collectionMethod  ""  
		VARCHAR(255) collectedBy FK ""  
		DATETIME collectedAt  ""  
		VARCHAR(50) storageCondition  "room_temp/refrigerated/frozen"  
		VARCHAR(100) containerType  ""  
		VARCHAR(100) quantity  ""  
		VARCHAR(50) status  "collected/in_transit/received/processing/analyzed/disposed/rejected"  
		VARCHAR(255) rejectionReason  ""  
		DATETIME receivedAt  ""  
		DATETIME processedAt  ""  
		DATETIME disposedAt  ""  
		TEXT notes  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Room {
		VARCHAR(255) id PK ""  
		VARCHAR(255) branchId FK ""  
		VARCHAR(50) roomNumber UK ""  
		VARCHAR(50) roomType  "general/semi_private/private/icu/nicu/operation"  
		VARCHAR(50) floor  ""  
		INT capacity  ""  
		floaot dailyRate  ""  
		VARCHAR(50) status  "available/occupied/maintenance/reserved"  
		TEXT amenities  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Bed {
		VARCHAR(255) id PK ""  
		VARCHAR(255) roomId FK ""  
		VARCHAR(50) bedNumber  "UK(roomId, bedNumber)"  
		VARCHAR(50) status  "available/occupied/reserved/maintenance"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Admission {
		VARCHAR(255) id PK ""  
		VARCHAR(100) admissionNo UK ""  
		VARCHAR(255) patientId FK ""  
		VARCHAR(255) doctorId FK ""  
		VARCHAR(255) roomId FK ""  
		VARCHAR(255) bedId FK ""  
		VARCHAR(255) branchId FK "denormalized for performance"  
		DATETIME admissionDate  ""  
		DATETIME expectedDischargeDate  ""  
		DATETIME actualDischargeDate  ""  
		VARCHAR(50) status  "admitted/discharged/transferred/deceased"  
		VARCHAR(50) admissionType  "emergency/elective/transfer"  
		TEXT admissionReason  ""  
		TEXT dischargeNotes  ""  
		VARCHAR(255) dischargedBy FK ""  
		VARCHAR(255) updatedBy FK ""  
		DATETIME deletedAt  "soft delete"  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Bill {
		VARCHAR(255) id PK ""  
		VARCHAR(100) billNo UK ""  
		VARCHAR(255) patientId FK ""  
		VARCHAR(255) branchId FK ""  
		VARCHAR(255) appointmentId FK ""  
		VARCHAR(255) admissionId FK ""  
		float subtotal  ""  
		float taxRate  ""  
		float taxAmount  "denormalized for performance"  
		float discountPercentage  ""  
		float discountAmount  "denormalized for performance"  
		VARCHAR(255) discountReason  ""  
		float totalAmount  ""  
		float paidAmount  ""  
		float dueAmount  "denormalized for performance"  
		VARCHAR(50) paymentStatus  "pending/partial/paid/overdue/cancelled/refunded"  
		DATE dueDate  ""  
		VARCHAR(255) updatedBy FK ""  
		DATETIME createdAt  ""  
		DATETIME paidAt  ""  
		DATETIME updatedAt  ""  
	}

	BillItem {
		VARCHAR(255) id PK ""  
		VARCHAR(255) billId FK ""  
		VARCHAR(255) description  ""  
		VARCHAR(50) itemType  "consultation/lab_test/medicine/room/procedure/other"  
		VARCHAR(255) referenceId  "links to specific entity"  
		INT quantity  ""  
		float unitPrice  ""  
		float discountAmount  ""  
		float amount  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	Payment {
		VARCHAR(255) id PK ""  
		VARCHAR(255) billId FK ""  
		VARCHAR(100) receiptNo UK ""  
		float amount  ""  
		VARCHAR(50) method  "cash/card/bank_transfer/upi/cheque"  
		VARCHAR(255) transactionRef  ""  
		VARCHAR(50) status  "pending/completed/failed/refunded"  
		VARCHAR(255) processedBy FK ""  
		TEXT notes  ""  
		DATETIME paidAt  ""  
		DATETIME createdAt  ""  
	}

	Notification {
		VARCHAR(255) id PK ""  
		VARCHAR(255) userId FK ""  
		VARCHAR(255) title  ""  
		TEXT message  ""  
		VARCHAR(50) type  "appointment/lab_result/prescription/billing/system/reminder"  
		VARCHAR(50) priority  "low/normal/high/urgent"  
		VARCHAR(50) channel  "in_app/email/sms/push"  
		BOOLEAN isRead  ""  
		BOOLEAN isSent  ""  
		VARCHAR(255) referenceType  ""  
		VARCHAR(255) referenceId  ""  
		DATETIME readAt  ""  
		DATETIME sentAt  ""  
		DATETIME createdAt  ""  
	}

	NotificationTemplate {
		VARCHAR(255) id PK ""  
		VARCHAR(100) code UK ""  
		VARCHAR(255) name  ""  
		VARCHAR(50) type  ""  
		VARCHAR(255) subject  ""  
		TEXT bodyTemplate  ""  
		BOOLEAN isActive  ""  
		DATETIME createdAt  ""  
		DATETIME updatedAt  ""  
	}

	AuditLog {
		VARCHAR(255) id PK ""  
		VARCHAR(255) userId FK ""  
		VARCHAR(255) sessionId  ""  
		VARCHAR(255) requestId  ""  
		VARCHAR(100) action  "create/read/update/delete/login/logout/export/print"  
		VARCHAR(100) entityType  ""  
		VARCHAR(255) entityId  ""  
		JSON oldValue  ""  
		JSON newValue  ""  
		VARCHAR(50) ipAddress  ""  
		TEXT userAgent  ""  
		VARCHAR(50) status  "success/failure"  
		TEXT errorMessage  ""  
		DATETIME createdAt  ""  
	}

	SystemSetting {
		VARCHAR(255) id PK ""  
		VARCHAR(100) key UK ""  
		TEXT value  ""  
		VARCHAR(50) valueType  "string/number/boolean/json"  
		VARCHAR(255) category  ""  
		TEXT description  ""  
		BOOLEAN isEditable  ""  
		DATETIME updatedAt  ""  
		DATETIME createdAt  ""  
	}

	User||--o{ProfileImage:"uploads"
	User||--o|DoctorProfile:"has"
	User||--o|StaffProfile:"has"
	User||--o|PatientProfile:"has"
	DoctorProfile||--o{DoctorBranchAssignment:"works at"
	Branch||--o{DoctorBranchAssignment:"employs"
	DoctorProfile||--o{DoctorWeeklySchedule:"has schedule"
	DoctorProfile||--o{DoctorScheduleOverride:"has overrides"
	Branch||--o{DoctorScheduleOverride:"applies to"
	DoctorWeeklySchedule||--o{TimeSlot:"generates"
	Branch||--o{TimeSlot:"hosts"
	TimeSlot||--o|Appointment:"booked as"
	PatientProfile||--o{Appointment:"books"
	DoctorProfile||--o{Appointment:"attends"
	Branch||--o{Appointment:"hosts"
	Appointment||--o|MedicalRecord:"results in"
	PatientProfile||--o{MedicalRecord:"has"
	DoctorProfile||--o{MedicalRecord:"creates"
	Branch||--o{MedicalRecord:"stores"
	MedicalRecord||--o|Vitals:"includes"
	MedicalRecord||--o{MedicalRecordHistory:"has versions"
	MedicalRecord||--o{Prescription:"contains"
	PatientProfile||--o{Prescription:"receives"
	DoctorProfile||--o{Prescription:"prescribes"
	Branch||--o{Prescription:"processes"
	Prescription||--|{PrescriptionItem:"contains"
	Medicine||--o{PrescriptionItem:"prescribed as"
	Medicine||--o{Inventory:"stocked in"
	Branch||--o{Inventory:"maintains"
	PrescriptionItem||--o{DispenseLog:"dispensed via"
	Inventory||--o{DispenseLog:"deducted from"
	StaffProfile||--o{DispenseLog:"dispenses"
	LabTestType||--o{LabTest:"categorizes"
	PatientProfile||--o{LabTest:"undergoes"
	DoctorProfile||--o{LabTest:"orders"
	StaffProfile||--o{LabTest:"processes"
	Branch||--o{LabTest:"conducts"
	MedicalRecord||--o{LabTest:"orders"
	LabTest||--o{LabTestSample:"requires"
	StaffProfile||--o{LabTestSample:"collects"
	Branch||--o{Room:"contains"
	Room||--o{Bed:"contains"
	Room||--o{Admission:"accommodates"
	Bed||--o{Admission:"assigned to"
	PatientProfile||--o{Admission:"admitted"
	DoctorProfile||--o{Admission:"admits"
	Branch||--o{Admission:"manages"
	PatientProfile||--o{Bill:"owes"
	Branch||--o{Bill:"generates"
	Appointment||--o|Bill:"generates"
	Admission||--o|Bill:"generates"
	Bill||--|{BillItem:"contains"
	Bill||--o{Payment:"settled by"
	StaffProfile||--o{Payment:"processes"
	User||--o{Notification:"receives"
	User||--o{AuditLog:"generates"
	Branch||--o{DoctorProfile:"employs"
	Branch||--o{StaffProfile:"employs"
```

## Entity Summary

| Category | Entities |
|----------|----------|
| **Users & Profiles** | User, ProfileImage, DoctorProfile, StaffProfile, PatientProfile |
| **Branches & Assignments** | Branch, DoctorBranchAssignment |
| **Scheduling** | DoctorWeeklySchedule, DoctorScheduleOverride, TimeSlot |
| **Appointments** | Appointment |
| **Medical Records** | MedicalRecord, MedicalRecordHistory, Vitals |
| **Prescriptions** | Prescription, PrescriptionItem |
| **Pharmacy** | Medicine, Inventory, DispenseLog |
| **Laboratory** | LabTestType, LabTest, LabTestSample |
| **Facilities** | Room, Bed, Admission |
| **Billing** | Bill, BillItem, Payment |
| **System** | Notification, NotificationTemplate, AuditLog, SystemSetting |

## User Roles

- **Admin** - Full system access
- **Doctor** - Patient care, appointments, prescriptions, lab orders
- **Staff** - Inventory, dispensing, lab processing, billing
- **Patient** - View appointments, records, prescriptions, bills
