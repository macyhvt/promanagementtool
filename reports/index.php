<?php
// login.php
require_once 'db_connect.php'; // Ensures PDO and session_start() are included

$errors = [];
$email = '';

// --- Redirect if already logged in ---
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// --- Process Login Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // 1. Retrieve and Sanitize Input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Basic Validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // 3. If No Basic Errors, Attempt Login
    if (empty($errors)) {
        try {
            // Prepare SQL to fetch user by email
            // Select necessary fields including the hashed password and status
            $sql = "SELECT userID, name, email, password, status FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC); // Use fetch() as email should be unique

            // 4. Verify User and Password
            if ($user) {
                // Check if the account is active (assuming status 1 = active)
                if ($user['status'] == 1) {
                    // Verify the provided password against the stored hash
                    if (password_verify($password, $user['password'])) {
                        // Password is correct! Login successful.

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Store user information in the session
                        $_SESSION['user_id'] = $user['userID'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email']; // Store email if needed

                        // Redirect to the dashboard
                        header("Location: dashboard.php");
                        exit(); // Important: stop script execution after redirect
                    } else {
                        // Password incorrect
                        $errors[] = "Invalid email or password.";
                    }
                } else {
                    // Account exists but is not active (e.g., pending verification, banned)
                     $errors[] = "Your account is not active. Please contact support.";
                     // You could be more specific based on the status code if needed
                }
            } else {
                // No user found with that email
                $errors[] = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Login failed due to a server error. Please try again later.";
            // Log the detailed error: error_log("Login Error: " . $e->getMessage());
        }
    }
}

// --- Display potential messages from other pages (e.g., registration, logout) ---
$info_message = '';
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $info_message = "Registration successful! Please log in.";
}
if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1') {
    $info_message = "You have been logged out successfully.";
}
if (isset($_GET['auth_required']) && $_GET['auth_required'] == '1') {
    $errors[] = "You must be logged in to access that page."; // Change to error style
}
// Get theme class for the body tag
$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($info_message)): ?>
            <div class="message success"> <!-- Or a neutral style -->
                <p><?php echo htmlspecialchars($info_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" novalidate>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>

        <p class="text-center">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</body>
</html>