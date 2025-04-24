<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'customer_article_no' => 'N/A',
    'system_article_no' => 'N/A',
    'price_article' => 'N/A'
];

// Get order ID from request
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($order_id) {
    try {
        // Fetch order details
        $sql = "SELECT o.customer_article_no, o.system_article_no, o.price_article 
                FROM orders_initial o 
                WHERE o.orderID = :order_id AND o.order_type = 'F' AND o.is_active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $response['success'] = true;
            $response['customer_article_no'] = $order['customer_article_no'] ?? 'N/A';
            $response['system_article_no'] = $order['system_article_no'] ?? 'N/A';
            $response['price_article'] = $order['price_article'] ?? 'N/A';
        }
    } catch (PDOException $e) {
        error_log("Error fetching order details: " . $e->getMessage());
    }
}

echo json_encode($response); 