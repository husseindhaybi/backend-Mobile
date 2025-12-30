<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method');
}


if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    sendResponse(false, 'User ID is required');
}

$user_id = (int)$_GET['user_id'];

$conn = getDBConnection();


$sql = "SELECT 
            b.id,
            b.booking_date,
            b.booking_time,
            b.status,
            b.notes,
            b.created_at,
            s.name as service_name,
            s.description as service_description,
            s.price,
            s.duration
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'id' => (int)$row['id'],
            'booking_date' => $row['booking_date'],
            'booking_time' => $row['booking_time'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'service' => [
                'name' => $row['service_name'],
                'description' => $row['service_description'],
                'price' => (float)$row['price'],
                'duration' => (int)$row['duration']
            ]
        ];
    }
}

$stmt->close();
$conn->close();

sendResponse(true, 'Bookings retrieved successfully', $bookings);
?>