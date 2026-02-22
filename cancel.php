<?php
session_start();
include 'config.php';

if (isset($_GET['id']) || isset($_GET['cancel'])) {

    $booking_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_GET['cancel']);

    // Resolve user id from session; fall back to user_name lookup
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $user_id = intval($_SESSION['user_id']);
    } elseif (isset($_SESSION['user_name'])) {
        $username = $conn->real_escape_string($_SESSION['user_name']);
        $res = mysqli_query($conn, "SELECT id FROM users WHERE name='$username' LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            $urow = mysqli_fetch_assoc($res);
            $user_id = intval($urow['id']);
            $_SESSION['user_id'] = $user_id;
        } else {
            $_SESSION['message'] = "Unable to cancel booking (user not found).";
            header("Location: mybooking.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Please sign in to cancel bookings.";
        header("Location: signin.php");
        exit();
    }

    $booking_id = intval($booking_id);

    // Get room id ONLY if booking belongs to this user
    $get = mysqli_query($conn, "SELECT room_id FROM bookings WHERE id='$booking_id' AND user_id='$user_id' LIMIT 1");

    if ($get && mysqli_num_rows($get) > 0) {
        $row = mysqli_fetch_assoc($get);
        $room_id = intval($row['room_id']);

        // Delete booking
        mysqli_query($conn, "DELETE FROM bookings WHERE id='$booking_id' AND user_id='$user_id'");

        // Make room available again
        mysqli_query($conn, "UPDATE rooms SET status='available' WHERE id='$room_id'");

        $_SESSION['message'] = "Booking cancelled successfully!";
    } else {
        $_SESSION['message'] = "Booking not found or doesn't belong to you.";
    }

    header("Location: mybooking.php");
    exit();
}
?>
