<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit();
}

include('../include/connect.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    
    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'You cannot delete your own account!';
        $response['message'] = 'You cannot delete your own account!';
        echo json_encode($response);
        exit();
    }

    // First, check if the user exists
    $check_sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = 'User not found';
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit();
    }

    // Delete the user
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'User deleted successfully';
        $response['success'] = true;
        $response['message'] = 'User deleted successfully';
    } else {
        $_SESSION['error_message'] = 'Error deleting user: ' . $conn->error;
        $response['message'] = 'Error deleting user: ' . $conn->error;
    }
    
    $stmt->close();
} else {
    $_SESSION['error_message'] = 'Invalid request method';
    $response['message'] = 'Invalid request method';
}

$conn->close();
echo json_encode($response);
?>