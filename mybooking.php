<?php
session_start();
if(isset($_SESSION['message'])){
    echo "<p style='color:green; font-weight:bold;'>".$_SESSION['message']."</p>";
    unset($_SESSION['message']);
}

include "config.php";

// Protect page
if (!isset($_SESSION['user_name'])) {
    header("Location: signin.php");
    exit();
}

// Get logged in user ID
$username = $_SESSION['user_name'];
$user_sql = "SELECT id FROM users WHERE name='$username'";
$user_result = $conn->query($user_sql);
$user_row = $user_result->fetch_assoc();
//$user_id = $user_row['id'];

// Handle Cancel Booking
if (isset($_GET['cancel'])) {

    $booking_id = $_GET['cancel'];

    // Get room id before deleting booking
    $room_sql = "SELECT room_id FROM bookings WHERE id='$booking_id' AND user_id='$user_id'";
    $room_result = $conn->query($room_sql);

    if ($room_result->num_rows == 1) {

        $room_row = $room_result->fetch_assoc();
        $room_id = $room_row['room_id'];

        // Delete booking
        $delete_sql = "DELETE FROM bookings WHERE id='$booking_id'";
        $conn->query($delete_sql);

        // Make room available again
        $update_sql = "UPDATE rooms SET status='available' WHERE id='$room_id'";
        $conn->query($update_sql);

        echo "<p style='color:green;'>Booking cancelled successfully!</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
</head>
<body>

<h2>My Bookings - <?php echo $_SESSION['user_name']; ?></h2>

<?php
$sql = "SELECT bookings.id AS booking_id, rooms.room_number, rooms.room_type, rooms.price, bookings.booking_date
    , bookings.check_in, bookings.check_out
    FROM bookings
        JOIN rooms ON bookings.room_id = rooms.id
        WHERE bookings.user_id = '$user_id'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Room Number</th><th>Type</th><th>Price</th><th>Check-in</th><th>Check-out</th><th>Booking Date</th><th>Action</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['room_number']."</td>";
        echo "<td>".$row['room_type']."</td>";
        echo "<td>Rs ".$row['price']."</td>";
        echo "<td>".(isset($row['check_in'])?htmlspecialchars($row['check_in']):'')."</td>";
        echo "<td>".(isset($row['check_out'])?htmlspecialchars($row['check_out']):'')."</td>";
        echo "<td>".$row['booking_date']."</td>";
        echo "<td><a href='cancel.php?id=".$row['booking_id']."' onclick=\"return confirm('Are you sure you want to cancel this booking?');\">Cancel</a></td>";
        echo "</tr>";
    }

    echo "</table>";

} else {
    echo "<p>You have no bookings yet.</p>";
}

?>

<br>
<a href="index.php">Back to Home</a>
<a href="logout.php">Logout</a>



</body>
</html>
