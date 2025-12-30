<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method');
}

$conn = getDBConnection();


$sql = "SELECT id, name, description, price, duration, image_url FROM services ORDER BY name";
$result = $conn->query($sql);

$services = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'duration' => (int)$row['duration'],
            'image_url' => $row['image_url']
        ];
    }
}

$conn->close();

sendResponse(true, 'Services retrieved successfully', $services);
?>