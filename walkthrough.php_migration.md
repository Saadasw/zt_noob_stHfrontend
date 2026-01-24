# St. George Hospital - Frontend to PHP Migration Walkthrough

The static HTML frontend for St. George Hospital has been successfully migrated to a modular PHP structure. This allows for dynamic content loading, session management, and shared layout components.

## 📁 Directory Structure Updates

The project now follows this structure:

- **`/includes`**: Contains shared components (`header.php`, `footer.php`, `auth_session.php`, `db_connect.php`) and role-specific sidebars/navbars.
- **`/admin-portal`**: Admin pages (`dashboard.php`, `users.php`, etc.)
- **`/doctor-portal`**: Doctor pages (`dashboard.php`, `patients.php`, `consultation.php`, etc.)
- **`/staff-portal`**: Staff pages (`dashboard.php`, `appointments.php`, `billing.php`, etc.)
- **`/patient-portal`**: Patient pages (`dashboard.php`, `appointments.php`, `medical-records.php`, etc.)

## 🚀 How to Access the Portals

You can now access the portals using `.php` extensions. Ensure your local server (XAMPP/WAMP/MAMP) is running.

### 1. Admin Portal
- **Login**: `admin@stgeorgehospital.org` / `admin123`
- **Dashboard**: `http://localhost/zt_noob_stHfrontend/admin-portal/dashboard.php`
- **Check**: Verify the sidebar links point to `.php` files and the user name "Admin User" is displayed in the navbar.

### 2. Doctor Portal
- **Login**: `dr.james@stgeorgehospital.org` / `doctor123`
- **Dashboard**: `http://localhost/zt_noob_stHfrontend/doctor-portal/dashboard.php`
- **Check**: 
    - Verify `Consultation`, `Prescriptions`, and `Lab Orders` pages load correctly.
    - Confirm the "Dr. James Wilson" name appears dynamically.

### 3. Staff Portal
- **Login**: `jane.smith@stgeorgehospital.org` / `staff123`
- **Dashboard**: `http://localhost/zt_noob_stHfrontend/staff-portal/dashboard.php`
- **Check**: Verify `Billing`, `Appointments` (scheduling view), and `Patient Registration` pages.

### 4. Patient Portal
- **Login**: `john.smith@email.com` / `patient123`
- **Dashboard**: `http://localhost/zt_noob_stHfrontend/patient-portal/dashboard.php`
- **Check**: Verify `My Appointments`, `Medical Records`, and `Lab Results` are accessible.

## 🛠️ Key Technical Changes

1.  **Session Authentication**:
    - Every page now starts with `require '../includes/auth_session.php';`.
    - `require_role(['role_name'])` enforces access control.

2.  **Shared Components**:
    - `header.php`: Contains the `<head>` section with dynamic titles and CSS links.
    - `navbar_*.php`: Role-specific top navigation bars.
    - `sidebar_*.php`: Role-specific sidebars with active state highlighting.
    - `footer.php`: Closing `</body>` and `</html>` tags.

3.  **Database Connection**:
    - `config/db_connect.php` is included in all pages to allow for future database queries.

## ✅ Next Steps

- **Database Integration**: The pages currently display static HTML content wrapped in PHP. The next phase will involve replacing the static table rows and patient cards with `SELECT` queries from the database.
- **Form Actions**: Connect the "Save", "Update", and "Submit" buttons to actual PHP form handling scripts.
