# St. George Hospital Management System

A comprehensive hospital management system built with PHP and MySQL.

---

## 🚀 Quick Start (XAMPP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) installed (PHP 7.4+ and MySQL)
- Web browser

### Step 1: Clone/Copy Project
```
Copy everything of this project folder to: C:\xampp\htdocs\stgeorgehospital
```

### Step 2: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache**
3. Start **MySQL**

### Step 3: Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Go to **Import** tab
3. Choose file: `database/schema.sql`
4. Click **Go**

### Step 4: Seed Test Data
Open your browser and visit:
```
http://localhost/stgeorgehospital/database/seeds/seed1.php
```

You should see a success message with all seeded data.

### Step 5: Login
Visit: http://localhost/stgeorgehospital/auth/login.php

---

## 🔐 Test Credentials

All passwords are: **`123`**

| Role | Email | Portal |
|------|-------|--------|
| Admin | admin@stgeorgehospital.org | `/admin-portal/` |
| Doctor | dr.sarah@stgeorgehospital.org | `/doctor-portal/` |
| Doctor | dr.michael@stgeorgehospital.org | `/doctor-portal/` |
| Staff | jane.smith@stgeorgehospital.org | `/staff-portal/` |
| Patient | john@gmail.com | `/patient-portal/` |
| Patient | emma@gmail.com | `/patient-portal/` |
| Patient | mike@gmail.com | `/patient-portal/` |

---

## 📦 What's Seeded

| Category | Data |
|----------|------|
| **Branches** | Melbourne CBD, Sydney CBD |
| **Users** | 1 Admin, 2 Doctors, 1 Staff, 5 Patients |
| **Medicines** | 10 common medicines (Paracetamol, Amoxicillin, etc.) |
| **Lab Test Types** | 8 tests (CBC, Lipid Panel, Glucose, etc.) |
| **Appointments** | 3 sample appointments for today |
| **Bills** | 1 sample pending bill with items |

---

## ✅ Feature Implementation Checklist

### ADMIN Portal
| Feature | Status | Notes |
|---------|--------|-------|
| Dashboard with stats | ✅ Done | Real data from database |
| User Management (CRUD) | ✅ Done | Create, search, delete users |
| Branch Management | ✅ Done | Create, view, deactivate branches |
| Doctor Management | ✅ Done | Create doctors with profiles |
| Departments | ✅ Done | Create and manage departments |
| Service Charges | ✅ Done | Manage pricing, update inline |
| Reports & Analytics | ✅ Done | Revenue, patients, appointments stats |
| Role & Permissions | ❌ Not Done | Hardcoded roles only |
| Audit Logs | ❌ Not Done | |
| System Settings | ❌ Not Done | |

### STAFF Portal
| Feature | Status | Notes |
|---------|--------|-------|
| Patient Registration | ✅ Done | Full form with profile creation |
| Patient Search | ✅ Done | Search by name, phone, patient ID |
| Appointment Booking | ✅ Done | Book appointments for patients |
| Check-in / Cancel | ✅ Done | Update appointment status |
| Billing - Create Bills | ✅ Done | Add items, generate invoice |
| Billing - Process Payments | ✅ Done | Cash, card, bank transfer |
| Laboratory | ✅ Done | Sample collection, result entry |
| Pharmacy | ✅ Done | View prescriptions, dispense |
| Queue Management | ⚠️ Partial | Basic queue via appointment status |

### DOCTOR Portal
| Feature | Status | Notes |
|---------|--------|-------|
| Today's Appointments | ✅ Done | View patient queue |
| Patient Queue (Waiting/Current) | ✅ Done | Grouped by status |
| Start Consultation | ✅ Done | Opens consultation form |
| Write Consultation Notes | ✅ Done | Chief complaint, diagnosis, treatment |
| Complete Consultation | ✅ Done | Saves medical record |
| Write Prescriptions | ✅ Done | Multiple medications |
| Order Lab Tests | ✅ Done | Select tests, priority |
| View Patient History | ⚠️ Partial | Via consultation page |
| Medical Certificates | ❌ Not Done | UI exists, not wired |
| Referrals | ❌ Not Done | UI exists, not wired |

### PATIENT Portal
| Feature | Status | Notes |
|---------|--------|-------|
| Dashboard | ✅ Done | Stats from database |
| View Appointments | ✅ Done | Upcoming and past |
| Book Appointment (Self) | ✅ Done | Select doctor, date, time |
| Cancel Appointment | ✅ Done | 24-hour advance rule |
| Reschedule | ⚠️ Partial | Link exists, redirects to booking |
| View Medical Records | ✅ Done | Completed consultations |
| View Lab Results | ✅ Done | Ordered and completed tests |
| View Prescriptions | ❌ Not Done | UI exists, not wired |
| View Bills | ❌ Not Done | UI exists, not wired |
| Pay Online | ❌ Not Done | |

### NOT IMPLEMENTED (Out of Scope)
| Feature | Reason |
|---------|--------|
| Nursing Staff Role | Separate role not created |
| HR / Housekeeping | Separate modules |
| Notifications System | Would need background jobs |
| SMS/Email Alerts | External service integration |
| Insurance/Medicare Claims | Complex billing logic |
| Audit Logging | Would need middleware |
| Backup & Restore | Server admin feature |

---

## 📂 Project Structure

```
zt_noob_stHfrontend/
├── admin-portal/      # Admin pages
├── doctor-portal/     # Doctor pages
├── staff-portal/      # Staff pages
├── patient-portal/    # Patient pages
├── auth/              # Login, logout
├── config/            # Database connection
├── includes/          # Shared components (header, sidebar, navbar)
├── database/
│   ├── schema.sql     # Database schema
│   └── seeds/
│       └── seed1.php  # Seed script
├── scripts/
│   └── setup_admin.php # Initial admin setup
├── FEATURES.md        # Feature specifications
└── README.md          # This file
```

---

## 🔧 Troubleshooting

### "Table doesn't exist" error
Make sure you imported `schema.sql` before running the seed script.

### Login not working
1. Check database connection in `config/db_connect.php`
2. Verify username/password (default: root with no password for XAMPP)
3. Run the seed script again

### Blank page
1. Enable PHP error reporting in `php.ini`
2. Check Apache error logs in `C:\xampp\apache\logs\error.log`

---

## 📝 License

This project is for educational purposes.
