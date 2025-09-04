<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

require_once('include/connect.php');
include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data here
    $success_message = "Settings updated successfully!";
}
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">System Settings</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard/index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">System</a></li>
                    <li class="breadcrumb-item" aria-current="page">Settings</li>
                </ul>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>System Configuration</h5>
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                <?= $success_message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <!-- General Settings -->
                            <div class="mb-4">
                                <h6 class="mb-3">General Settings</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="company_name" class="form-label">Company Name</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name" value="Your Company">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Timezone</label>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <option value="America/New_York" selected>Eastern Time (ET)</option>
                                                <option value="America/Chicago">Central Time (CT)</option>
                                                <option value="America/Denver">Mountain Time (MT)</option>
                                                <option value="America/Los_Angeles">Pacific Time (PT)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="currency" class="form-label">Currency</label>
                                            <select class="form-select" id="currency" name="currency">
                                                <option value="USD" selected>US Dollar ($)</option>
                                                <option value="EUR">Euro (€)</option>
                                                <option value="GBP">British Pound (£)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_format" class="form-label">Date Format</label>
                                            <select class="form-select" id="date_format" name="date_format">
                                                <option value="Y-m-d" selected>YYYY-MM-DD</option>
                                                <option value="m/d/Y">MM/DD/YYYY</option>
                                                <option value="d/m/Y">DD/MM/YYYY</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Settings -->
                            <div class="mb-4">
                                <h6 class="mb-3">Email Settings</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_host" class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="smtp.example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_port" class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="587">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_username" class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="your@email.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_password" class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="••••••••">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Invoice Settings -->
                            <div class="mb-4">
                                <h6 class="mb-3">Invoice Settings</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                            <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" value="INV-">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="invoice_start" class="form-label">Starting Invoice Number</label>
                                            <input type="number" class="form-control" id="invoice_start" name="invoice_start" value="1001">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="invoice_terms" class="form-label">Default Terms</label>
                                    <textarea class="form-control" id="invoice_terms" name="invoice_terms" rows="3">Payment due within 30 days</textarea>
                                </div>
                            </div>

                            <!-- Security Settings -->
                            <div class="mb-4">
                                <h6 class="mb-3">Security Settings</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="force_ssl" name="force_ssl" checked>
                                    <label class="form-check-label" for="force_ssl">Force HTTPS/SSL</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                    <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-secondary me-2">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?>