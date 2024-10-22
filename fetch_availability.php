<?php
session_start();
include('./database.php');

// Function to get booked dates for a room
function getBookedDates($room_id, $conn) {
    $booked_dates = [];
    $query = "SELECT start_date, end_date FROM bookings WHERE room_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Add the range of booked dates to the array
        $booked_dates[] = [
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date']
        ];
    }

    $stmt->close();
    return $booked_dates;
}

// Check if room_id is set
if (isset($_POST['room_id'])) {
    $room_id = $_POST['room_id'];
    $booked_dates = getBookedDates($room_id, $conn);

    // Format booked dates for display
    if (!empty($booked_dates)) {
        echo '<h3>Booked Dates:</h3>';
        echo '<ul>';
        foreach ($booked_dates as $dates) {
            echo '<li>From: ' . $dates['start_date'] . ' To: ' . $dates['end_date'] . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No bookings found for this room.</p>';
    }
}

$conn->close();
?>
