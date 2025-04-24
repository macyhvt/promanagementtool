<?php
// update_article_order.php
require_once 'db_connect.php'; // Handles DB connection, session start

header('Content-Type: application/json');
$response = ['success' => false, 'errors' => []];

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    $response['errors'][] = 'Authentication required.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

// --- Check Request Method ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['errors'][] = 'Invalid request method.';
    http_response_code(405);
    echo json_encode($response);
    exit();
}

// --- Get and Validate Input ---
$ordered_ids = $_POST['ordered_ids'] ?? null;

if (!is_array($ordered_ids)) {
    $response['errors'][] = 'Invalid order data received.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// --- Update Order in Database ---
if (empty($ordered_ids)) {
    // Nothing to update if array is empty
    $response['success'] = true; // Technically success, no action needed
    echo json_encode($response);
    exit();
}

try {
    $pdo->beginTransaction();

    // Prepare the update statement ONCE outside the loop
    $sql = "UPDATE articles SET order_index = :order_index WHERE articleID = :article_id";
    $stmt = $pdo->prepare($sql);

    // Loop through the received IDs and update their order_index based on their position
    foreach ($ordered_ids as $index => $article_id) {
        // Sanitize/validate article_id
        $article_id = filter_var($article_id, FILTER_VALIDATE_INT);
        if ($article_id === false || $article_id <= 0) {
            // Invalid ID found in the array, stop processing
            throw new InvalidArgumentException("Invalid article ID ($article_id) found in order data.");
        }

        $order_index = $index; // Use 0-based index

        $stmt->bindParam(':order_index', $order_index, PDO::PARAM_INT);
        $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        $stmt->execute(); // Execute for each article
    }

    $pdo->commit(); // Commit all updates at once
    $response['success'] = true;

} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $response['errors'][] = $e->getMessage(); // Specific error from validation
    http_response_code(400); // Bad request due to invalid input
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); } // Rollback on error
    $response['errors'][] = 'Database error updating article order.';
    error_log("Update Article Order Error: " . $e->getMessage());
    http_response_code(500); // Internal server error
}

// --- Output Response ---
echo json_encode($response);
exit();
?>