<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method');
}


if (!isset($_GET['admin_id']) || empty($_GET['admin_id'])) {
    sendResponse(false, 'Admin ID is required');
}

$admin_id = (int)$_GET['admin_id'];

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


$sql = "SELECT 
            b.id,
            b.booking_date,
            b.booking_time,
            b.status,
            b.notes,
            b.created_at,
            u.name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            s.name as service_name,
            s.description as service_description,
            s.price,
            s.duration
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN services s ON b.service_id = s.id
        ORDER BY b.booking_date DESC, b.booking_time DESC";

$result = $conn->query($sql);

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
            'user' => [
                'name' => $row['user_name'],
                'email' => $row['user_email'],
                'phone' => $row['user_phone']
            ],
            'service' => [
                'name' => $row['service_name'],
                'description' => $row['service_description'],
                'price' => (float)$row['price'],
                'duration' => (int)$row['duration']
            ]
        ];
    }
}

$conn->close();

sendResponse(true, 'Bookings retrieved successfully', $bookings);
?>