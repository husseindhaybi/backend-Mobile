<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['admin_id', 'service_id']);
if ($error) {
    sendResponse(false, $error);
}

$admin_id = (int)$data['admin_id'];
$service_id = (int)$data['service_id'];

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


$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE service_id = ? AND status IN ('pending', 'confirmed')");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row['count'] > 0) {
    $conn->close();
    sendResponse(false, 'Cannot delete service with active bookings');
}


$stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse(true, 'Service deleted successfully', ['service_id' => $service_id]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Failed to delete service');
}
?>