<?php
session_start();
require_once('../include/connect.php');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Get and sanitize input data
$user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Validate required fields
if (empty($user_id) || empty($username) || empty($email)) {
    $response['message'] = 'Required fields are missing';
    echo json_encode($response);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit();
}

// Check if password is being changed
$change_password = !empty($password);
if ($change_password) {
    if ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit();
    }
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters';
        echo json_encode($response);
        exit();
    }
}

// Check if username or email already exists (excluding current user)
$check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ssi", $username, $email, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $response['message'] = 'Username or email already exists';
    echo json_encode($response);
    exit();
}
$check_stmt->close();

// Prepare the update query
if ($change_password) {
    // Update with password change
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET username = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $username, $email, $hashed_password, $user_id);
} else {
    // Update without password change
    $update_query = "UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $username, $email, $user_id);
}

// Execute the update
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'User updated successfully';
        
        // If admin is editing their own profile, update session data
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
        }
    } else {
        $response['message'] = 'No changes were made';
    }
} else {
    $response['message'] = 'Error updating user: ' . $conn->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>