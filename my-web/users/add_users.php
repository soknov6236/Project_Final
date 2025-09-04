<?php
include('../include/connect.php');
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

// Initialize response array
$response = ['status' => 'error', 'message' => ''];

// Get and sanitize form data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    $response['message'] = 'All fields are required';
    $_SESSION['error_message'] = $response['message'];
    echo json_encode($response);
    exit();
}

if ($password !== $confirm_password) {
    $response['message'] = 'Passwords do not match';
    $_SESSION['error_message'] = $response['message'];
    echo json_encode($response);
    exit();
}

if (strlen($password) < 8) {
    $response['message'] = 'Password must be at least 8 characters';
    $_SESSION['error_message'] = $response['message'];
    echo json_encode($response);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format';
    $_SESSION['error_message'] = $response['message'];
    echo json_encode($response);
    exit();
}

// Check if username or email already exists
$check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    $response['message'] = 'Username or email already exists';
    $_SESSION['error_message'] = $response['message']; // Store in session
    echo json_encode($response);
    exit();
}
mysqli_stmt_close($check_stmt);

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert the new user
$insert_query = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "sss", $username, $email, $hashed_password);

if (mysqli_stmt_execute($insert_stmt)) {
    $_SESSION['success_message'] = 'User created successfully';
    $response['status'] = 'success';
    $response['message'] = 'User created successfully';
} else {
    $response['message'] = 'Error creating user: ' . mysqli_error($conn);
    $_SESSION['error_message'] = $response['message']; // Store in session
}

mysqli_stmt_close($insert_stmt);
mysqli_close($conn);

echo json_encode($response);
?>