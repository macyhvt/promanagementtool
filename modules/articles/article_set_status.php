<?php
// article_set_status.php
require_once 'db_connect.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?auth_required=1");
    exit();
}
$user_id = $_SESSION['user_id']; // For edit_by field

// 1. Get Article ID and Action from URL, Validate
$article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS); // 'activate' or 'deactivate'

if (!$article_id) {
    header("Location: articles_list.php?status=error&msg=Invalid article ID.");
    exit();
}

if ($action !== 'activate' && $action !== 'deactivate') {
    header("Location: articles_list.php?status=error&msg=Invalid action specified.");
    exit();
}

// Determine the new status based on the action
$new_status = ($action === 'activate') ? 1 : 0; // 1 for active, 0 for inactive
$action_past_tense = ($action === 'activate') ? 'activated' : 'deactivated'; // For messages

// --- Perform Status Update ---
try {
    // Optional: Check if article exists
    $sql_check = "SELECT articleID FROM articles WHERE articleID = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':id', $article_id, PDO::PARAM_INT);
    $stmt_check->execute();

    if ($stmt_check->rowCount() === 0) {
        header("Location: articles_list.php?status=error&msg=Article not found.");
        exit();
    }

    // Proceed with update
    $sql_update = "UPDATE articles SET
                       status = :new_status,
                       edit_by = :edit_by,
                       edit_date = NOW()
                   WHERE articleID = :id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $stmt_update->bindParam(':edit_by', $user_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':id', $article_id, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        // Success
        header("Location: articles_list.php?status=success&msg=Article successfully " . $action_past_tense . ".");
        exit();
    } else {
        // Update failed
        header("Location: articles_list.php?status=error&msg=Failed to update article status.");
        exit();
    }

} catch (PDOException $e) {
    header("Location: articles_list.php?status=error&msg=Database error during status update.");
    error_log("Article Status Update Error: " . $e->getMessage());
    exit();
}
?>