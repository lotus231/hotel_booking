<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include "config.php";

// capture booking-related output into buffer so we can render inside the page
ob_start();
// Users may browse without signing in. Resolve user id only when signed in.
$user_id = null;
if (isset($_SESSION['user_name'])) {
    $username = $_SESSION['user_name'];
    $user_sql = "SELECT id FROM users WHERE name='$username'";
    $user_result = $conn->query($user_sql);
    if ($user_result && $user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_id = $user_row['id'];
    }
}

// Handle booking
// Ensure bookings table has check_in and check_out columns
$col_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'check_in'");
if ($col_check && $col_check->num_rows == 0) {
    $conn->query("ALTER TABLE bookings ADD check_in DATE NULL, ADD check_out DATE NULL");
}

// Booking flow: show form when ?book=ID, and handle POST to create a dated booking
if (isset($_GET['book'])) {
    $room_id = intval($_GET['book']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];

        // basic validation
        if (empty($check_in) || empty($check_out)) {
            echo "<p style='color:red;'>Please select check-in and check-out dates.</p>";
        } elseif (strtotime($check_in) >= strtotime($check_out)) {
            echo "<p style='color:red;'>Check-out must be after check-in.</p>";
        } else {
            // Check for overlapping bookings for this room
            $ci = $conn->real_escape_string($check_in);
            $co = $conn->real_escape_string($check_out);
            $overlap_sql = "SELECT * FROM bookings WHERE room_id='$room_id' AND NOT (check_out <= '$ci' OR check_in >= '$co')";
            $overlap_res = $conn->query($overlap_sql);

            if ($overlap_res && $overlap_res->num_rows > 0) {
                echo "<p style='color:red;'>Room is already booked for the selected dates.</p>";
            } else {
                // Require login to finalize booking
                if (!$user_id) {
                    // redirect to signin with return url so user can continue booking after login
                    $ret = urlencode("home.php?book=".$room_id."&ci=".$ci."&co=".$co);
                    header("Location: signin.php?return=$ret");
                    exit();
                }

                // Insert booking with dates
                $insert_booking = "INSERT INTO bookings (user_id, room_id, check_in, check_out, booking_date) VALUES ('$user_id', '$room_id', '$ci', '$co', NOW())";
                if ($conn->query($insert_booking) === TRUE) {
                    $_SESSION['message'] = "Room booked successfully!";
                    header("Location: mybooking.php");
                    exit();
                } else {
                    echo "<p style='color:red;'>Error creating booking: " . $conn->error . "</p>";
                }
            }
        }
    }

    // Show booking form
    $room_q = $conn->query("SELECT * FROM rooms WHERE id='$room_id' LIMIT 1");
    if ($room_q && $room_q->num_rows > 0) {
        $r = $room_q->fetch_assoc();
        echo "<h3>Book Room " . htmlspecialchars($r['room_number']) . " (" . htmlspecialchars($r['room_type']) . ")</h3>";
        if (!$user_id) {
            // prompt to sign in first
            $ret = urlencode("home.php?book=".$room_id);
            echo "<p class='muted'>You must <a href='signin.php?return=$ret'>sign in</a> to complete a booking. New user? <a href='signup.php'>Sign up</a>.</p>";
            echo "<p><a href='home.php'>Back to rooms</a></p>";
        } else {
            echo "<form method='POST' action='home.php?book=".$room_id."'>";
            echo "Check-in: <input type='date' name='check_in' required> <br><br>";
            echo "Check-out: <input type='date' name='check_out' required> <br><br>";
            echo "<button type='submit' name='book_room' class='btn'>Confirm Booking</button>";
            echo "</form>";
            echo "<p><a href='home.php'>Back to rooms</a></p>";
        }
    } else {
        echo "<p>Room not found.</p>";
    }
}

// collect booking HTML for rendering inside the page
$booking_html = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Home - Hotel Booking</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="container">
    <?php echo $booking_html ?? ''; ?>
    <div class="welcome">
        <div>
            <h2 style="margin:0">Welcome, <?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'); ?>!</h2>
            <div class="muted">Browse rooms and make a booking</div>
        </div>
        <div>
            <?php if(isset($_SESSION['user_name'])): ?>
                <a class="btn" href="mybooking.php">My Bookings</a>
            <?php else: ?>
                <a class="btn ghost" href="signin.php">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($_SESSION['message'])){ echo '<div class="message success">'.htmlspecialchars($_SESSION['message']).'</div>'; unset($_SESSION['message']); } ?>

    <section class="card">
        <div style="display:flex;align-items:center;justify-content:space-between">
            <h3 style="margin:0">Available Rooms</h3>
            <div class="muted">Select dates when booking</div>
        </div>

        <div class="rooms-grid">
        <?php
        $sql = "SELECT * FROM rooms";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="room">';
                echo '<div style="display:flex;justify-content:space-between;align-items:center">';
                echo '<div><h4>Room '.htmlspecialchars($row['room_number']).'</h4><div class="meta">'.htmlspecialchars($row['room_type']).'</div></div>';
                echo '<div class="price">Rs '.htmlspecialchars($row['price']).'</div>';
                echo '</div>';
                echo '<div class="muted">Status: '.htmlspecialchars($row['status']).'</div>';

                if($row['status'] == 'available'){
                    echo '<div style="margin-top:8px">';
                    echo '<a class="btn" href="book_room.php?room='.intval($row['id']).'">Book Now</a>';
                    echo '</div>';
                } else {
                    if ($user_id) {
                        $check_booking = "SELECT * FROM bookings WHERE user_id='$user_id' AND room_id='".intval($row['id'])."'";
                        $booking_result = $conn->query($check_booking);
                        if ($booking_result && $booking_result->num_rows > 0) {
                            echo '<div style="margin-top:8px"><span class="muted">Your Booking</span></div>';
                        } else {
                            echo '<div style="margin-top:8px"><span class="muted">Booked</span></div>';
                        }
                    } else {
                        echo '<div style="margin-top:8px"><span class="muted">Booked</span></div>';
                    }
                }

                echo '</div>';
            }
        } else {
            echo '<p>No rooms available.</p>';
        }
        ?>
        </div>
    </section>

    </main>

<?php include 'footer.php'; ?>

</body>
</html>
