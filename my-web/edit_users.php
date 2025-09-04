<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

include('include/connect.php');

// Initialize variables
$user = [];

// Get user data for editing
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT id, username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = 'User not found';
        header("Location: users.php");
        exit();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($email)) {
        $_SESSION['error_message'] = 'Username and email are required';
        header("Location: edit_users.php?id=$id");
        exit();
    }

    if (!empty($password) && $password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match';
        header("Location: edit_users.php?id=$id");
        exit();
    }

    if (!empty($password) && strlen($password) < 8) {
        $_SESSION['error_message'] = 'Password must be at least 8 characters';
        header("Location: edit_users.php?id=$id");
        exit();
    }

    // Check if username or email already exists (excluding current user)
    $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = 'Username or email already exists';
        header("Location: edit_users.php?id=$id");
        exit();
    }

    // Prepare update query
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $username, $email, $hashed_password, $id);
    } else {
        $update_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssi", $username, $email, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'User updated successfully';
        header("Location: users.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Error updating user: ' . $conn->error;
        header("Location: edit_users.php?id=$id");
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
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="mb-0">Edit User</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <form id="editUserForm" method="POST">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="users.php" class="btn btn-outline-danger">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Password validation
    $.validator.addMethod("passwordMatch", function(value, element) {
        return value === "" || value === $("#password").val();
    }, "Passwords must match");

    $("#editUserForm").validate({
        rules: {
            username: {
                required: true,
                minlength: 3
            },
            email: {
                required: true,
                email: true
            },
            password: {
                minlength: 8
            },
            confirm_password: {
                passwordMatch: true
            }
        },
        messages: {
            username: {
                required: "Please enter a username",
                minlength: "Username must be at least 3 characters"
            },
            email: {
                required: "Please enter an email",
                email: "Please enter a valid email address"
            },
            password: {
                minlength: "Password must be at least 8 characters"
            }
        },
        submitHandler: function(form) {
            $.ajax({
                url: 'users/update_user.php',
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'users.php?success=' + encodeURIComponent(response.message);
                    } else {
                        window.location.href = 'edit_users.php?id=<?php echo $user['id']; ?>&error=' + encodeURIComponent(response.message);
                    }
                },
                error: function() {
                    window.location.href = 'edit_users.php?id=<?php echo $user['id']; ?>&error=' + encodeURIComponent('An error occurred during update');
                }
            });
        }
    });
});
</script>

<?php 
$conn->close();
include('include/footer.php'); 
?>