<?php
session_start();
include('./database.php');

// Function to check room availability
function isRoomAvailable($room_id, $start_date, $end_date, $conn) {
    $query = "SELECT * FROM bookings WHERE room_id = ? AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?)
              )";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $room_id, $end_date, $start_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // If there are any overlapping bookings, the room is not available
    return $result->num_rows === 0;
}

// Fetch all available rooms
$query = "SELECT * FROM rooms";
$result = $conn->query($query);

// If a booking is being made
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_room'])) {
    $room_id = $_POST['room_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $customer_id = $_SESSION['user_id']; // Assuming the logged-in customer ID is in session

    // Validate the dates
    $today = date('Y-m-d');
    if (strtotime($start_date) < strtotime($today)) {
        $_SESSION['error'] = "Check-in date cannot be in the past.";
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $_SESSION['error'] = "End date must be later than check-in date.";
    } else {
        // Check availability
        if (isRoomAvailable($room_id, $start_date, $end_date, $conn)) {
            // Insert booking into the database
            $booking_query = "INSERT INTO bookings (room_id, customer_id, start_date, end_date, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($booking_query);
            $stmt->bind_param("iiss", $room_id, $customer_id, $start_date, $end_date);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Room booked successfully!";
            } else {
                $_SESSION['error'] = "Failed to book room.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Room is not available for the selected dates.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Rooms - Customer</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function showAlert(message) {
            alert(message);
        }

        function showCalendar(roomId) {
            $.ajax({
                url: 'fetch_availability.php', // Create this file to fetch created_at dates
                type: 'POST',
                data: { room_id: roomId },
                success: function(data) {
                    $('#calendarModal .modal-body').html(data); // Load the data into the modal
                    $('#calendarModal').show(); // Show the modal
                }
            });
        }

        $(document).ready(function() {
            $('#calendarModal .close').click(function() {
                $('#calendarModal').hide(); // Hide modal on close
            });
        });
    </script>
    <style>
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            padding-top: 60px; /* Location of the box */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        header {
            text-align: center;
            margin-top: 20px;
        }
        h1 {
            font-size: 2em;
            margin: 0;
            padding: 10px;
        }
    </style>
   
</head>
<body>
    <header>
        <h1>Available Rooms for Booking</h1>
        <a href="index.php" style="float: right;">Logout</a> <!-- Logout Button -->
    </header>
    <a href="customer_booked_rooms.php">View Your Booked Rooms</a>

    <!-- Available Rooms Section -->
    <div class="room-list-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($room = $result->fetch_assoc()): ?>
                <div class="room-card">
                    <!-- Displaying the image stored in LONGBLOB -->
                    <div class="room-image">
    <?php 
    // Fetch the image path from the database (img_path column assumed to store file path)
    if (!empty($room['img_path'])): ?>
        <img src="<?php echo $room['img_path']; ?>" alt="Room Image" style="width:100%; height:auto;">
    <?php else: ?>
        <p>No image available</p>
    <?php endif; ?>
</div>

                    <h3><?php echo $room['room_name']; ?></h3>
                    <p><?php echo $room['description']; ?></p>
                    <p>Floor Size: <?php echo $room['floor_size']; ?></p>
                    <p>Beds: <?php echo $room['number_of_beds']; ?></p>
                    <p>Amenities: <?php echo $room['amenities']; ?></p>
                    <p>Rent per Day: ₹<?php echo $room['rent_per_day']; ?></p>
                    <p>Booking Period: <?php echo $room['min_booking_days']; ?> - <?php echo $room['max_booking_days']; ?> days</p>

                    <!-- Booking Form -->
                    <form action="customer_rooms.php" method="POST" onsubmit="return confirm('Are you sure you want to book this room?');">
                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                        
                        <!-- Availability Check -->
                        <label for="start_date">Check-in Date:</label>
                        <input type="date" name="start_date" required>
                        
                        <label for="end_date">Check-out Date:</label>
                        <input type="date" name="end_date" required>
                        
                        <button type="submit" name="book_room">Book Now</button>
                        <button type="button" onclick="showCalendar(<?php echo $room['id']; ?>)">Check Available</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No rooms are available at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- Calendar Modal -->
    <div id="calendarModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Room Availability Calendar</h2>
            <div class="modal-body">
                <!-- Content will be loaded here from fetch_availability.php -->
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <script>
            showAlert("<?php echo $_SESSION['message']; unset($_SESSION['message']); ?>");
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            showAlert("<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>
