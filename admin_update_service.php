<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['admin_id', 'service_id', 'name', 'description', 'price', 'duration']);
if ($error) {
    sendResponse(false, $error);
}

$admin_id = (int)$data['admin_id'];
$service_id = (int)$data['service_id'];
$name = trim($data['name']);
$description = trim($data['description']);
$price = (float)$data['price'];
$duration = (int)$data['duration'];
$image_url = isset($data['image_url']) ? trim($data['image_url']) : null;


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


$stmt = $conn->prepare("SELECT id FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Service not found');
}
$stmt->close();


if ($image_url) {
    $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, image_url = ? WHERE id = ?");
    $stmt->bind_param("ssdisi", $name, $description, $price, $duration, $image_url, $service_id);
} else {
    $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ? WHERE id = ?");
    $stmt->bind_param("ssdii", $name, $description, $price, $duration, $service_id);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    sendResponse(true, 'Service updated successfully', [
        'id' => $service_id,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'duration' => $duration
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Failed to update service');
}
?>