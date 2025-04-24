<?php
// article_inline_add.php
require_once 'db_connect.php'; // Handles DB connection, session start

header('Content-Type: application/json'); // Set response type to JSON

// Initialize response structure
$response = ['success' => false, 'errors' => [], 'article' => null];

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    $response['errors'][] = 'Authentication required.';
    http_response_code(403); // Forbidden
    echo json_encode($response);
    exit();
}
$user_id = $_SESSION['user_id'];

// --- Check Request Method ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['errors'][] = 'Invalid request method.';
    http_response_code(405); // Method Not Allowed
    echo json_encode($response);
    exit();
}

// --- Retrieve and Sanitize Input (Including parent_article_id) ---
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_article_no = trim($_POST['customer_article_no'] ?? '');
$system_article_no = trim($_POST['system_article_no'] ?? '');
$price = trim($_POST['price'] ?? '');
$status = isset($_POST['status']) ? (int)$_POST['status'] : null; // Allow null for validation

// Get and validate parent_article_id
// filter_input returns false if validation fails, NULL if var doesn't exist
$parent_article_id = filter_input(INPUT_POST, 'parent_article_id', FILTER_VALIDATE_INT);
if ($parent_article_id === false) {
    // If filter_input returns false, it means the value exists but isn't a valid int.
    // We treat this as an invalid input rather than NULL for linking purposes.
    // However, if the intention is to allow empty string submission to mean NULL, adjust logic.
    // For simplicity here, treat invalid as NULL. Revisit if strict validation needed.
     $parent_article_id = null;
} elseif ($parent_article_id === null) {
    // If filter_input returns NULL, the variable wasn't set or was empty.
    $parent_article_id = null; // Explicitly NULL
}
// If $parent_article_id is 0 after filter_input, it's a valid int but likely not a valid ID,
// DB foreign key constraint should handle this case if 0 doesn't exist as an articleID.

// --- Basic Server-Side Validation ---
if (empty($customer_name)) { $response['errors'][] = "Customer Name is required."; }
if (empty($customer_article_no)) { $response['errors'][] = "Customer Article Number is required."; }
if (empty($system_article_no)) { $response['errors'][] = "System Article Number is required."; }
if (empty($price)) { $response['errors'][] = "Price is required."; }
// Add more specific price validation if needed (e.g., is_numeric or regex)
if ($status === null || !in_array($status, [0, 1])) { $response['errors'][] = "Invalid status selected."; }
// Optional: Add validation to check if parent_article_id exists if not null
// if ($parent_article_id !== null) { /* ... check if parent ID exists in DB ... */ }

// --- If Validation Fails ---
if (!empty($response['errors'])) {
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit();
}

// --- If Validation Passes, Insert into Database ---
try {
    $pdo->beginTransaction(); // Start transaction

    // SQL includes parent_article_id
    $sql_insert = "INSERT INTO articles (customer_name,customer_article_no, system_article_no, price, added_by, added_date, status, parent_article_id)
                   VALUES (:cust_name,:cust_no, :sys_no, :price, :added_by, NOW(), :status, :parent_id)";
    $stmt_insert = $pdo->prepare($sql_insert);

    // Bind parameters
    $stmt_insert->bindParam(':cust_name', $customer_name, PDO::PARAM_STR);
    $stmt_insert->bindParam(':cust_no', $customer_article_no, PDO::PARAM_STR);
    $stmt_insert->bindParam(':sys_no', $system_article_no, PDO::PARAM_STR);
    $stmt_insert->bindParam(':price', $price, PDO::PARAM_STR); // Still string due to schema
    $stmt_insert->bindParam(':added_by', $user_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':status', $status, PDO::PARAM_INT);

    // Bind parent_article_id carefully (handle NULL)
    if ($parent_article_id !== null) {
        $stmt_insert->bindParam(':parent_id', $parent_article_id, PDO::PARAM_INT);
    } else {
        $stmt_insert->bindValue(':parent_id', null, PDO::PARAM_NULL); // Use bindValue for explicit NULL
    }


    if ($stmt_insert->execute()) {
        $new_article_id = $pdo->lastInsertId();

        // Fetch the newly added article's complete data to send back
        $sql_fetch = "SELECT
                         a.*,
                         u_add.name AS added_by_name,
                         NULL AS edited_by_name  -- New row won't have an editor yet
                      FROM articles a
                      LEFT JOIN users u_add ON a.added_by = u_add.userID
                      WHERE a.articleID = :id";
        $stmt_fetch = $pdo->prepare($sql_fetch);
        $stmt_fetch->bindParam(':id', $new_article_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $new_article_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($new_article_data) {
             $pdo->commit(); // Commit transaction
             $response['success'] = true;
             $response['article'] = $new_article_data; // Contains the full data, including parent_id
             echo json_encode($response);
             exit();
        } else {
             $pdo->rollBack(); // Rollback if fetch failed post-insert
             $response['errors'][] = 'Failed to retrieve newly added article data after insertion.';
        }

    } else {
        $pdo->rollBack(); // Rollback if insert failed
        $response['errors'][] = 'Failed to add article to the database.';
    }
} catch (PDOException $e) {
     if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Ensure rollback on exception
     }
    // Check for specific errors like duplicate entry or foreign key violation
    if ($e->getCode() == 23000) { // Integrity constraint violation
         if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
             $response['errors'][] = "Database Error: An article with this number might already exist.";
         } elseif (strpos($e->getMessage(), 'Cannot add or update a child row: a foreign key constraint fails') !== false && strpos($e->getMessage(), 'fk_parent_article') !== false){
             $response['errors'][] = "Database Error: The selected Parent Article ID does not exist.";
         } else {
              $response['errors'][] = "Database Error: Integrity constraint violation."; // More generic
         }
    } else {
        $response['errors'][] = "Database error during insertion."; // Generic error for other issues
    }
    error_log("Inline Article Add Error: [" . $e->getCode() . "] " . $e->getMessage()); // Log detailed error
}

// --- If Execution Reaches Here, Something Went Wrong ---
// Determine appropriate status code based on whether errors were set
$statusCode = !empty($response['errors']) ? 400 : 500; // Bad Request if validation/known DB issue, else Internal Server Error
http_response_code($statusCode);
echo json_encode($response);
exit();
?>