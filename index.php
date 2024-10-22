<?php
session_start();
include('./database.php'); // Include your database configuration

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    
    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
           // Redirect based on user role
           if ($role === 'owner') {
            header("Location: owner_dashboard.php"); // Redirect to owner's dashboard
        } else if ($role === 'customer') {
            header("Location: customer_rooms.php"); // Redirect to customer's room browsing page
        }
        exit();
        } else {
            // Password is incorrect
            $_SESSION['error'] = "Invalid email or password!";
        }
    } else {
        // No user found
        $_SESSION['error'] = "No user found with that email!";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect to login page with an error message if login fails
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Room Booking - Login</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script>
        function showAlert(message) {
            alert(message);
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>Welcome Back</h2>
            <div class="login-form">
                <h3>Login</h3>
                <form id="loginForm" method="POST" action="index.php">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <label for="role">Login as:</label>
                    <select name="role" id="role" required>
                        <option value="customer">Customer</option>
                        <option value="owner">House Owner</option>
                    </select>
                    <button type="submit">Login</button>
                </form>
                <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            showAlert("<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>
