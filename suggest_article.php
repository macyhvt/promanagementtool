<?php
// suggest_articles.php
require_once 'db_connect.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

// --- Get Search Term ---
$term = trim(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$suggestions = []; // Will now be array of objects

if (!empty($term)) {
    try {
        $search_term = $term . '%';

        // Fetch DISTINCT customer article numbers AND an associated articleID
        // Using MAX(articleID) assumes you want to link to the 'latest' article
        // if multiple share the same customer_article_no. Adjust logic if needed (e.g., MIN(articleID)).
        $sql = "SELECT customer_article_no, MAX(articleID) as articleID
                FROM articles
                WHERE customer_article_no LIKE :term
                GROUP BY customer_article_no -- Ensure unique customer article numbers in suggestions
                ORDER BY customer_article_no ASC
                LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':term', $search_term, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch results as associative arrays
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Changed from FETCH_COLUMN

    } catch (PDOException $e) {
        error_log("Article Suggestion Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not retrieve suggestions.']);
        exit();
    }
}

// --- Output Suggestions as JSON ---
header('Content-Type: application/json');
echo json_encode($suggestions); // Output array of objects: [{cust_no: '...', id: ...}]
exit();
?>