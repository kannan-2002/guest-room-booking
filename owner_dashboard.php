<?php
// Start the session and include the database connection
session_start();
include('./database.php');

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy(); // End session
    header("Location: index.php"); // Redirect to login page
    exit();
}

// Fetch the logged-in house owner's ID from the session
$owner_id = $_SESSION['user_id'];
$edit_mode = false; // Flag to check if we are in edit mode
$room = []; // Initialize room data array

// Fetch rooms owned by the logged-in house owner
$query = "SELECT * FROM rooms WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if edit mode is requested
if (isset($_GET['edit'])) {
    $room_id = $_GET['edit'];
    $edit_query = "SELECT * FROM rooms WHERE id = ? AND owner_id = ?";
    $stmt = $conn->prepare($edit_query);
    $stmt->bind_param("ii", $room_id, $owner_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    $room = $edit_result->fetch_assoc();
    $edit_mode = true;
}

// Handle add room and update room form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_room']) || isset($_POST['update_room'])) {
        // Collect room data from the form
        $room_name = $_POST['room_name'];
        $description = $_POST['description'];
        $floor_size = $_POST['floor_size'];
        $number_of_beds = $_POST['number_of_beds'];
        $amenities = $_POST['amenities'];
        $rent_per_day = $_POST['rent_per_day'];
        $min_booking_days = $_POST['min_booking_days'];
        $max_booking_days = $_POST['max_booking_days'];

        // Handle image upload
        $image_path = null; // Initialize image path
        if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
            // Get the file type
            $fileType = $_FILES['img']['type'];

            // Allow only certain types of images
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($fileType, $allowedTypes)) {
                $image_name = time() . "_" . basename($_FILES['img']['name']); // Unique image name
                $image_path = 'uploads/' . $image_name; // Set the image path

                // Move the uploaded file to the target directory
                if (move_uploaded_file($_FILES['img']['tmp_name'], $image_path)) {
                    $image_path = addslashes($image_path); // Escape special characters for the database
                } else {
                    $_SESSION['error'] = "Failed to upload image.";
                }
            } else {
                $_SESSION['error'] = "Invalid image type. Only JPG, PNG, and GIF are allowed.";
            }
        }

        if (empty($_SESSION['error'])) { // Proceed only if there are no errors
            if (isset($_POST['add_room'])) {
                // Insert new room into the database
                $insert_query = "INSERT INTO rooms (owner_id, room_name, description, floor_size, number_of_beds, amenities, rent_per_day, min_booking_days, max_booking_days, img_path, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("isssisssis", $owner_id, $room_name, $description, $floor_size, $number_of_beds, $amenities, $rent_per_day, $min_booking_days, $max_booking_days, $image_path);
            } elseif (isset($_POST['update_room'])) {
                $room_id = $_POST['room_id'];
                // Update room information; include img_path if uploaded
                if ($image_path) {
                    // If the image is uploaded, include img_path in the query
                    $update_query = "UPDATE rooms SET room_name = ?, description = ?, floor_size = ?, number_of_beds = ?, amenities = ?, rent_per_day = ?, min_booking_days = ?, max_booking_days = ?, img_path = ? WHERE id = ? AND owner_id = ?";
                    $stmt = $conn->prepare($update_query);
                    if ($stmt === false) {
                        die('Prepare failed: ' . $conn->error);
                    }
                    $stmt->bind_param("sssisiiiisi", $room_name, $description, $floor_size, $number_of_beds, $amenities, $rent_per_day, $min_booking_days, $max_booking_days, $image_path, $room_id, $owner_id);
                } else {
                    // If no image is uploaded, omit img_path from the query
                    $update_query = "UPDATE rooms SET room_name = ?, description = ?, floor_size = ?, number_of_beds = ?, amenities = ?, rent_per_day = ?, min_booking_days = ?, max_booking_days = ? WHERE id = ? AND owner_id = ?";
                    $stmt = $conn->prepare($update_query);
                    if ($stmt === false) {
                        die('Prepare failed: ' . $conn->error);
                    }
                    $stmt->bind_param("sssisiiiii", $room_name, $description, $floor_size, $number_of_beds, $amenities, $rent_per_day, $min_booking_days, $max_booking_days, $room_id, $owner_id);
                }
                

            }

            // Execute the insert or update query
            if ($stmt->execute()) {
                $_SESSION['message'] = isset($_POST['add_room']) ? "Room added successfully!" : "Room updated successfully!";
                header("Location: owner_dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to " . (isset($_POST['add_room']) ? "add" : "update") . " room.";
            }
        }
    }
}

// Handle delete room request
if (isset($_POST['delete_room'])) {
    $room_id = $_POST['room_id'];
    $delete_query = "DELETE FROM rooms WHERE id = ? AND owner_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $room_id, $owner_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Room deleted successfully!";
        header("Location: owner_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete room.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - House Owner</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <h1>Manage Your Rooms</h1>
        <form action="" method="POST" style="display: inline; float: right;">
            <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
    </header>

    <!-- Form to Add or Edit a Room -->
    <div class="room-form">
        <h3><?php echo $edit_mode ? 'Edit Room' : 'Add a New Room'; ?></h3>
        <form action="owner_dashboard.php" method="POST" enctype="multipart/form-data">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
            <?php endif; ?>
            <input type="text" name="room_name" placeholder="Room Name" value="<?php echo $edit_mode ? $room['room_name'] : ''; ?>" required>
            <textarea name="description" placeholder="Room Description" required><?php echo $edit_mode ? $room['description'] : ''; ?></textarea>
            <input type="text" name="floor_size" placeholder="Floor Size" value="<?php echo $edit_mode ? $room['floor_size'] : ''; ?>" required>
            <input type="number" name="number_of_beds" placeholder="Number of Beds" value="<?php echo $edit_mode ? $room['number_of_beds'] : ''; ?>" required>
            <input type="text" name="amenities" placeholder="Amenities" value="<?php echo $edit_mode ? $room['amenities'] : ''; ?>" required>
            <input type="number" name="rent_per_day" placeholder="Rent per Day (₹)" value="<?php echo $edit_mode ? $room['rent_per_day'] : ''; ?>" required>
            <input type="number" name="min_booking_days" placeholder="Min Booking Days" value="<?php echo $edit_mode ? $room['min_booking_days'] : ''; ?>" required>
            <input type="number" name="max_booking_days" placeholder="Max Booking Days" value="<?php echo $edit_mode ? $room['max_booking_days'] : ''; ?>" required>
            <label>Upload Room Image:</label>
            <input type="file" name="img" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?>>
            <button type="submit" name="<?php echo $edit_mode ? 'update_room' : 'add_room'; ?>"><?php echo $edit_mode ? 'Update Room' : 'Add Room'; ?></button>
        </form>
    </div>
<!-- Display List of Rooms -->
<div class="room-list" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px;">
    <h2>Your Rooms</h2>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($room = $result->fetch_assoc()): ?>
            <div class="room-card" style="border: 1px solid #ccc; border-radius: 10px; padding: 15px; box-shadow: 0px 4px 6px rgba(0,0,0,0.1);">
                <img src="<?php echo $room['img_path'] ? $room['img_path'] : 'assets/default_room.jpg'; ?>" alt="Room Image" style="width: 100%; height: auto; border-radius: 10px;">
                <h3><?php echo $room['room_name']; ?></h3>
                <p><?php echo $room['description']; ?></p>
                <p><strong>₹<?php echo $room['rent_per_day']; ?></strong> per day</p>
                <form action="owner_dashboard.php" method="GET" style="display:inline;">
                    <button type="submit" name="edit" value="<?php echo $room['id']; ?>">Edit</button>
                </form>
                <form action="owner_dashboard.php" method="POST" style="display:inline;">
                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                    <button type="submit" name="delete_room" onclick="return confirm('Are you sure you want to delete this room?');">Delete</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No rooms available. Add a new room to start managing!</p>
    <?php endif; ?>
</div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
</body>
</html>
<?php

include('./database.php');

// Ensure the owner is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch the logged-in house owner's ID from the session
$owner_id = $_SESSION['user_id'];

// Fetch rooms owned by the logged-in house owner along with customer details
$query = "
    SELECT b.id AS booking_id, r.room_name, b.start_date, b.end_date, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.customer_id = u.id
    WHERE r.owner_id = ?
    ORDER BY b.start_date DESC";

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
    <title>Customer Booked Rooms - House Owner</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 20px;">

    <header>
        <h1 style="text-align: center;">Customer Booked Rooms</h1>
    
    </header>

    <div class="room-list" style="margin-top: 20px;">
        <table style="width: 1100px; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px;">Room Name</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Customer Name</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Customer Email</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Customer Phone</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Booking Start Date</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Booking End Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($booking = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($booking['room_name']); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($booking['customer_email']); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($booking['customer_phone']); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($booking['start_date']); ?></td>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($booking['end_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="border: 1px solid #ddd; padding: 8px; text-align: center;">No bookings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="color: green; text-align: center; margin-top: 20px;">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div style="color: red; text-align: center; margin-top: 20px;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>
