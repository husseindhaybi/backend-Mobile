<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['admin_id', 'name', 'description', 'price', 'duration']);
if ($error) {
    sendResponse(false, $error);
}

$admin_id = (int)$data['admin_id'];
$name = trim($data['name']);
$description = trim($data['description']);
$price = (float)$data['price'];
$duration = (int)$data['duration'];
$image_url = isset($data['image_url']) ? trim($data['image_url']) : 'https://via.placeholder.com/150';

if ($price <= 0) {
    sendResponse(false, 'Price must be greater than 0');
}

if ($duration <= 0) {
    sendResponse(false, 'Duration must be greater than 0');
}

$conn = getDBConnection();


$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'User not found');
}

$user = $result->fetch_assoc();
if (!$user['is_admin']) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Unauthorized access');
}
$stmt->close();


$stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, image_url) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdis", $name, $description, $price, $duration, $image_url);

if ($stmt->execute()) {
    $service_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    sendResponse(true, 'Service added successfully', [
        'id' => $service_id,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'duration' => $duration,
        'image_url' => $image_url
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Failed to add service: ' . $conn->error);
}
?>