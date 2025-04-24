<?php
// register.php
require_once 'db_connect.php'; // Ensures PDO and session_start() are included

$errors = [];
$success_message = '';
$name = '';
$email = '';

// --- Process Registration Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // 1. Retrieve and Sanitize Input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 2. Basic Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) { // Example: Minimum password length
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // 3. Check if email already exists (if no validation errors so far)
    if (empty($errors)) {
        try {
            $sql_check = "SELECT userID FROM users WHERE email = :email";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                $errors[] = "Email address is already registered.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error checking email. Please try again.";
            // Log the detailed error: error_log("Register Check Email Error: " . $e->getMessage());
        }
    }

    // 4. If No Errors, Proceed with Registration
    if (empty($errors)) {
        // --- HASH THE PASSWORD --- Important for security!
        $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Or PASSWORD_DEFAULT

        // --- Set initial status (e.g., 1 for active) ---
        $status = 1; // Assuming 1 means active

        try {
            $sql_insert = "INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, :status)";
            $stmt_insert = $pdo->prepare($sql_insert);

            // Bind parameters
            $stmt_insert->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt_insert->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt_insert->bindParam(':status', $status, PDO::PARAM_INT);

            // Execute the query
            if ($stmt_insert->execute()) {
                $success_message = "Registration successful! You can now log in.";
                // Optionally redirect to login page:
                // header("Location: login.php?registered=1");
                // exit();
                // Clear form fields after success
                $name = '';
                $email = '';
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error during registration. Please try again.";
            // Log the detailed error: error_log("Register Insert Error: " . $e->getMessage());
        }
    }
}
// Get theme class for the body tag
$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post" novalidate>
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                 <!-- You might add password strength indicators here -->
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" name="register">Register</button>
        </form>

        <p class="text-center">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</body>
</html>