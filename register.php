<?php
session_start();
include('./database.php'); // Include your database configuration

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $role);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        // Set success message in session
        $_SESSION['message'] = "Registration successful! You can now log in.";
    } else {
        // Set error message in session
        $_SESSION['error'] = "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Room Booking - Register</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script>
        function showAlert(message, redirect = null) {
            alert(message);
            if (redirect) {
                window.location.href = redirect;
            }
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>Create an Account</h2>
            <div class="login-form">
                <h3>Register</h3>
                <form action="register.php" method="POST" class="register-form">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="tel" name="phone" placeholder="Mobile Number" required>
                    <label for="role">Register as:</label>
                    <select name="role" id="role" required>
                        <option value="customer">Customer</option>
                        <option value="owner">House Owner</option>
                    </select>
                    <button type="submit">Register</button>
                </form>
                <p class="register-link">Already have an account? <a href="index.php">Login here</a></p>
            </div>
        </div>
    </div>

    <!-- Check for success message -->
    <?php if (isset($_SESSION['message'])): ?>
        <script>
            showAlert("<?php echo $_SESSION['message']; unset($_SESSION['message']); ?>", "index.php");
        </script>
    <?php endif; ?>

    <!-- Check for error message -->
    <?php if (isset($_SESSION['error'])): ?>
        <script>
            showAlert("<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>
