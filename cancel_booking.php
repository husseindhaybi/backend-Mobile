<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);


$error = validateFields($data, ['booking_id', 'user_id']);
if ($error) {
    sendResponse(false, $error);
}

$booking_id = (int)$data['booking_id'];
$user_id = (int)$data['user_id'];

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Booking not found');
}

$booking = $result->fetch_assoc();
$stmt->close();


if ($booking['status'] === 'cancelled') {
    $conn->close();
    sendResponse(false, 'Booking is already cancelled');
}


$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse(true, 'Booking cancelled successfully', ['booking_id' => $booking_id]);
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Failed to cancel booking');
}
?>