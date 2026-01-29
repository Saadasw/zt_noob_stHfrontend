Based on the hospital management system we've built, here's a comprehensive analysis of what each user role can see and do:

---

## 1. PATIENT Account

### Dashboard View
| Feature | Can See | Can Do |
|---------|---------|--------|
| **Personal Profile** | Name, DOB, gender, contact info, Medicare number, emergency contact | Update contact details, change password |
| **Appointments** | Upcoming & past appointments, doctor name, date/time, status | Book new appointment, reschedule, cancel (24hrs before) |
| **Medical Records** | Consultation notes, diagnosis history, vitals history | View only (cannot edit) |
| **Prescriptions** | Current & past prescriptions, medication details, dosage | View, request refill |
| **Lab Results** | Test reports, normal/abnormal flags, historical results | View, download PDF |
| **Bills & Payments** | Outstanding bills, payment history, itemized charges | Pay online, view receipts, download invoices |
| **Notifications** | Appointment reminders, lab results ready, bill due alerts | Mark as read |

### Patient CANNOT
- Access other patients' records
- Modify medical records or prescriptions
- Access staff/doctor information beyond basic profiles
- View billing backend or financial reports

---

## 2. DOCTOR Account

### Dashboard View
| Feature | Can See | Can Do |
|---------|---------|--------|
| **Personal Profile** | Name, specialization, license number, qualifications, schedule | Update contact info, set availability, request leave |
| **My Schedule** | Daily/weekly appointments, patient queue, time slots | Manage availability, block time slots, set break times |
| **Today's Appointments** | Patient list for the day, appointment times, visit reasons | Start consultation, mark no-show, reschedule |
| **Patient Records** | Full medical history of assigned patients | Create consultation notes, record vitals, add diagnosis (ICD codes) |
| **Prescriptions** | Patient's current medications, allergy alerts | Create new prescription, select medicines, set dosage, check drug interactions |
| **Lab Orders** | Pending & completed lab tests for their patients | Order new tests, view results, mark as reviewed |
| **Medical Certificates** | Certificates issued | Generate medical certificates, sick leave letters |
| **Referrals** | Referrals made to specialists | Create referral to another doctor/department |
| **Workload Stats** | Patients seen today/week/month, consultation counts | View only |

### Doctor CANNOT
- Access patients not assigned to them (unless emergency)
- Modify billing or financial records
- Create user accounts or manage staff
- Access other doctors' schedules (view only for referrals)
- Dispense medications (pharmacy does this)
- Approve their own leave requests

---

## 3. STAFF Accounts (Department-wise)

### 3.1 RECEPTION / FRONT DESK Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Patient Registration** | Patient search, basic demographics | Register new patients, update contact info, generate patient ID |
| **Appointments** | All appointments for the branch, doctor schedules | Book appointments, reschedule, cancel, check-in patients |
| **Queue Management** | Waiting list, patient status | Update patient status (waiting, in-consultation, completed) |
| **Doctor Availability** | All doctors' schedules, leave calendar | View only |
| **Basic Billing** | Bill amounts, payment status | Collect payments, issue receipts |

### 3.2 NURSING Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Assigned Patients** | Patient list for their ward/unit | View patient details |
| **Vitals Recording** | Patient vital history | Record BP, temperature, pulse, weight, height |
| **Medication Administration** | Prescribed medications, schedule | Mark medication as given, record time |
| **Ward Rounds** | Doctor visit schedule | Update notes from ward rounds |
| **Patient Monitoring** | Alerts for abnormal vitals | Flag critical patients |
| **Bed Management** | Bed occupancy for their ward | Update bed status, request housekeeping |

### 3.3 LABORATORY / PATHOLOGY Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Test Orders** | Pending lab requests from doctors | View patient & test details |
| **Sample Collection** | Sample status, barcodes | Register sample collection, print labels |
| **Test Processing** | Tests in progress | Enter test results, flag abnormal values |
| **Report Generation** | Completed test results | Generate lab report, submit for approval |
| **Pending Approvals** | Reports awaiting pathologist sign-off | View status |
| **Inventory** | Reagents, supplies stock | Request supplies |

**PATHOLOGIST (Senior Lab Staff)**
- All above PLUS: Approve/reject lab reports, add comments, release to patient portal

### 3.4 PHARMACY Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Prescriptions Queue** | Incoming prescriptions from doctors | View prescription details |
| **Dispensing** | Medication details, patient allergies | Verify prescription, dispense medication, print label |
| **Inventory** | Medicine stock, expiry dates | Check stock, update quantities, flag low stock |
| **Drug Interactions** | Alerts for patient's current medications | View alerts, contact doctor if needed |
| **Sales/Billing** | Medication prices, patient bills | Generate pharmacy bill, process payment |
| **Purchase Orders** | Stock requests, supplier info | Create purchase orders, receive stock |

### 3.5 BILLING / ACCOUNTS Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Patient Bills** | All bills, itemized charges, payment status | Generate bills, add charges, apply discounts |
| **Payments** | Payment history, modes (cash/card/insurance) | Process payments, issue receipts, process refunds |
| **Insurance Claims** | Medicare/private insurance claims | Submit claims, track status, handle rejections |
| **Outstanding Dues** | Unpaid bills, aging reports | Send payment reminders, flag overdue accounts |
| **Financial Reports** | Daily/weekly/monthly revenue | Generate collection reports |
| **Price Master** | Service charges, room rates | View only (admin updates) |

### 3.6 HOUSEKEEPING Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Room Status** | Rooms needing cleaning, bed status | View assigned tasks |
| **Task List** | Cleaning requests, priorities | Mark task as complete, report issues |
| **Bed Turnover** | Discharge alerts, room ready status | Update room status (cleaning/ready) |

### 3.7 HR / HUMAN RESOURCES Staff

| Feature | Can See | Can Do |
|---------|---------|--------|
| **Employee Records** | Staff profiles, employment details | Update employee info, manage documents |
| **Attendance** | Staff attendance, leave records | Record attendance, approve/reject leave |
| **Payroll Data** | Salary info, bank details, tax | Process payroll, generate payslips |
| **Recruitment** | Job postings, applications | Manage hiring process |
| **Training Records** | Staff certifications, training history | Schedule training, update records |

---

## 4. ADMIN Account (Super Admin)

### Full System Access

| Module | Can See | Can Do |
|--------|---------|--------|
| **Dashboard** | System-wide stats, revenue, patient counts, bed occupancy | View analytics, export reports |
| **User Management** | All users (patients, doctors, staff) | Create/edit/deactivate accounts, assign roles, reset passwords |
| **Role & Permissions** | All roles, permission matrix | Create custom roles, modify permissions |
| **Branch Management** | All branch details, performance | Add/edit branches, set branch managers |
| **Department Management** | All departments | Create/edit departments, assign heads |
| **Doctor Management** | All doctor profiles, schedules | Add doctors, assign departments, manage credentials |
| **Staff Management** | All staff records | Hire/terminate staff, change roles, transfer between branches |
| **Patient Data** | All patient records (audit purposes) | View only (cannot modify medical records) |
| **Service Charges** | All service prices, room rates | Set/update pricing |
| **Inventory Settings** | Medicine catalog, test catalog | Add/edit items, set reorder levels |
| **System Configuration** | SMS/email templates, notification settings | Configure system settings |
| **Audit Logs** | All system activities, who did what | View, export, cannot delete |
| **Reports** | All reports - financial, operational, clinical | Generate any report, schedule auto-reports |
| **Backup & Restore** | Backup history | Trigger backup, restore (with approval) |
| **Archive/Deleted Records** | Soft-deleted records | View deleted data, restore if needed |

---

## Summary Matrix

| Feature | Patient | Doctor | Reception | Nurse | Lab | Pharmacy | Billing | Admin |
|---------|:-------:|:------:|:---------:|:-----:|:---:|:--------:|:-------:|:-----:|
| View own profile | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Book appointments | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View medical records | Own | Assigned | ❌ | Ward | ❌ | ❌ | ❌ | Audit |
| Create prescriptions | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Dispense medicine | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Process payments | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| Enter lab results | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Manage users | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| View all reports | ❌ | Own | ❌ | ❌ | Lab | Pharmacy | Finance | ✅ |
| System settings | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

---

Would you like me to create a detailed document or diagram for any specific role?