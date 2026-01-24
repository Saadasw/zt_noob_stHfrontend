<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'Billing - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'billing';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Billing</h1>
                <p class="page-subtitle">Create bills and receive payments</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active">Create Bill</button>
            <button class="tab">Receive Payment</button>
            <button class="tab">Transactions</button>
        </div>

        <!-- Tab 1: Create Bill -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create Bill</h3>
            </div>

            <div class="section-header">STEP 1: SELECT PATIENT</div>
            <div class="form-group">
                <div class="flex gap-2">
                    <input type="text" class="form-input" placeholder="🔍 Enter patient name or ID..."
                        style="flex: 1;">
                    <button class="btn btn-outline">Search</button>
                </div>
            </div>
            
            <div class="section-header">STEP 2: ADD BILL ITEMS</div>
            <div class="form-group">
                <div class="flex gap-2">
                    <select class="form-select" style="flex: 1;">
                        <option>Select Service...</option>
                        <option>Consultation - Dr. Sarah Johnson ($85.00)</option>
                        <option>Lab - Complete Blood Count ($45.00)</option>
                    </select>
                    <button class="btn btn-outline">+ Add</button>
                </div>
            </div>

            <div class="section-header">BILL ITEMS</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background: #f9fafb;">
                            <td colspan="3" style="text-align: right;"><strong>TOTAL</strong></td>
                            <td colspan="2"><strong style="font-size: 18px;">$0.00</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2 mt-4">
                <button class="btn btn-primary">Generate Bill</button>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
