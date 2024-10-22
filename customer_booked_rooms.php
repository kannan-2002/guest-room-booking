<?php
session_start();
include('./database.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the customer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

$customer_id = $_SESSION['user_id'];

// Fetch rooms booked by the customer
$query = "
    SELECT r.id, r.room_name, r.description, r.floor_size, r.number_of_beds, r.amenities, r.rent_per_day, r.img_path, b.start_date, b.end_date 
    FROM rooms r
    JOIN bookings b ON r.id = b.room_id 
    WHERE b.customer_id = ?
    ORDER BY b.start_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Booked Rooms</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <h1>Your Booked Rooms</h1>
        <a href="customer_rooms.php" style="float: right;">Back to Room Listings</a>
    </header>

    <div class="room-list-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($room = $result->fetch_assoc()): ?>
                <div class="room-card">
                    <div class="room-image">
                        <!-- Displaying the room image if available -->
                        <?php if (!empty($room['img_path'])): ?>
                            <img src="<?php echo $room['img_path']; ?>" alt="Room Image" style="width:100%; height:auto;">
                        <?php else: ?>
                            <p>No image available</p>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo $room['room_name']; ?></h3>
                    <p><?php echo $room['description']; ?></p>
                    <p>Floor Size: <?php echo $room['floor_size']; ?> m²</p>
                    <p>Beds: <?php echo $room['number_of_beds']; ?></p>
                    <p>Amenities: <?php echo $room['amenities']; ?></p>
                    <p>Rent per Day: ₹<?php echo $room['rent_per_day']; ?></p>
                    <p><strong>Booking Period:</strong> <?php echo $room['start_date']; ?> to <?php echo $room['end_date']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't booked any rooms yet.</p>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <script>
            alert("<?php echo $_SESSION['message']; unset($_SESSION['message']); ?>");
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            alert("<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>
