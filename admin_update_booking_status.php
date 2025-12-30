<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['booking_id', 'admin_id', 'status']);
if ($error) {
    sendResponse(false, $error);
}

$booking_id = (int)$data['booking_id'];
$admin_id = (int)$data['admin_id'];
$status = trim($data['status']);


$valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
if (!in_array($status, $valid_statuses)) {
    sendResponse(false, 'Invalid status. Must be: pending, confirmed, cancelled, or completed');
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
$stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Booking not found');
}
$stmt->close();


$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $booking_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse(true, 'Booking status updated successfully', [
        'booking_id' => $booking_id,
        'status' => $status
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Failed to update booking status');
}
?>