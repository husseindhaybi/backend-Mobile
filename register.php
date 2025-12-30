<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['name', 'email', 'password', 'phone']);
if ($error) {
    sendResponse(false, $error);
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];
$phone = trim($data['phone']);


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'Invalid email format');
}


if (strlen($password) < 6) {
    sendResponse(false, 'Password must be at least 6 characters');
}

$conn = getDBConnection();


$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Email already registered');
}
$stmt->close();


$hashed_password = password_hash($password, PASSWORD_DEFAULT);


$stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    sendResponse(true, 'Registration successful', [
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Registration failed: ' . $conn->error);
}
?>