<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['email', 'password']);
if ($error) {
    sendResponse(false, $error);
}

$email = trim($data['email']);
$password = $data['password'];

$conn = getDBConnection();


$stmt = $conn->prepare("SELECT id, name, email, password, phone, is_admin FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Invalid email or password');
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();


if (password_verify($password, $user['password'])) {
  
    unset($user['password']);
    
    sendResponse(true, 'Login successful', $user);
} else {
    sendResponse(false, 'Invalid email or password');
}
?>