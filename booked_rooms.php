<?php
session_start();
include('./database.php'); // Database connection

// Fetch the logged-in house owner's ID from the session
$owner_id = $_SESSION['user_id'];

// Fetch booked rooms with customer details for the logged-in house owner
$query = "
    SELECT b.id AS booking_id, b.check_in_date, b.check_out_date, r.room_name, r.rent_per_day, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.customer_id = u.id
    WHERE r.owner_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booked Rooms - House Owner</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <h1>Your Booked Rooms</h1>
        <nav>
            <ul>
                <li><a href="owner_dashboard.php">Manage Rooms</a></li>
                <li><a href="booked_rooms.php">Booked Rooms</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Booked Rooms Details</h2>
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Room Name</th>
                        <th>Rent per Day (₹)</th>
                        <th>Customer Name</th>
                        <th>Customer Email</th>
                        <th>Customer Phone</th>
                        <th>Check-in Date</th>
                        <th>Check-out Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $booking['booking_id']; ?></td>
                            <td><?php echo $booking['room_name']; ?></td>
                            <td><?php echo $booking['rent_per_day']; ?></td>
                            <td><?php echo $booking['customer_name']; ?></td>
                            <td><?php echo $booking['customer_email']; ?></td>
                            <td><?php echo $booking['customer_phone']; ?></td>
                            <td><?php echo $booking['check_in_date']; ?></td>
                            <td><?php echo $booking['check_out_date']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No booked rooms found.</p>
        <?php endif; ?>
    </main>
</body>
</html>
