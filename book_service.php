<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['user_id', 'service_id', 'booking_date', 'booking_time']);
if ($error) {
    sendResponse(false, $error);
}

$user_id = (int)$data['user_id'];
$service_id = (int)$data['service_id'];
$booking_date = trim($data['booking_date']);
$booking_time = trim($data['booking_time']);
$notes = isset($data['notes']) ? trim($data['notes']) : '';


$date_parts = explode('-', $booking_date);
if (count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
    sendResponse(false, 'Invalid date format. Use YYYY-MM-DD');
}


if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $booking_time)) {
    sendResponse(false, 'Invalid time format. Use HH:MM (24-hour format)');
}


$current_date = date('Y-m-d');
if ($booking_date < $current_date) {
    sendResponse(false, 'Cannot book for past dates');
}

$conn = getDBConnection();


$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'User not found');
}
$stmt->close();


$stmt = $conn->prepare("SELECT id, name, price FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Service not found');
}
$service = $result->fetch_assoc();
$stmt->close();


$stmt = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND service_id = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'");
$stmt->bind_param("iiss", $user_id, $service_id, $booking_date, $booking_time);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'You already have a booking for this service at this time');
}
$stmt->close();


$stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date, booking_time, notes, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("iisss", $user_id, $service_id, $booking_date, $booking_time, $notes);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    sendResponse(true, 'Booking created successfully', [
        'booking_id' => $booking_id,
        'service_name' => $service['name'],
        'booking_date' => $booking_date,
        'booking_time' => $booking_time,
        'status' => 'pending'
    ]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Booking failed: ' . $conn->error);
}
?>