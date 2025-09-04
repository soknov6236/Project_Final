<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = 'All fields are required';
        header("Location: add_new_users.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match';
        header("Location: add_new_users.php");
        exit();
    }

    if (strlen($password) < 8) {
        $_SESSION['error_message'] = 'Password must be at least 8 characters';
        header("Location: add_new_users.php");
        exit();
    }

    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = 'Username or email already exists';
        header("Location: add_new_users.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insert_sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'User added successfully';
        header("Location: users.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Error adding user: ' . $conn->error;
        header("Location: add_new_users.php");
        exit();
    }
}

include('include/header.php');
include('include/sidebar.php');
include('include/topbar.php');

// Check for messages
$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="page-header-title">
                    <h5 class="mb-0 font-medium">Add New User</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard/index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                    <li class="breadcrumb-item">Add New User</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>User Information</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="userForm" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                        <small class="text-muted">Minimum 8 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">Save User</button>
                                <a href="users.php" class="btn btn-outline-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script> 
$(document).ready(function() {
    // Password match validation
    $('#password, #confirm_password').on('keyup', function() {
        if ($('#password').val() !== $('#confirm_password').val()) {
            $('#confirm_password').get(0).setCustomValidity("Passwords don't match");
        } else {
            $('#confirm_password').get(0).setCustomValidity('');
        }
    });

    // Form submission
    $("#userForm").on("submit", function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "users/add_users.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = "users.php?success=" + encodeURIComponent(response.message);
                } else {
                    window.location.href = "add_new_users.php?error=" + encodeURIComponent(response.message);
                }
            },
            error: function(xhr, status, error) {
                window.location.href = "add_new_users.php?error=" + encodeURIComponent(error);
            }
        });
    });
});
</script>

<?php include('include/footer.php'); ?>