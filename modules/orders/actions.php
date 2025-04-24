<?php
session_start();
require '../db_connect.php';

$action = $_GET['action'] ?? null;

// Debug: Log all POST data
error_log("POST data received: " . print_r($_POST, true));

// --- Helper Action: Get Order Details (for Type C AJAX) ---
if ($action === 'get_details' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $response = ['error' => 'Invalid ID'];

    if ($order_id) {
        try {
            $stmt = $pdo->prepare("SELECT project_no, customer_article_no, system_article_no, price_article, framework_order_no, framework_order_position
                                   FROM orders_initial
                                   WHERE orderID = ? AND order_type = 'F' AND is_active = 1");
            $stmt->execute([$order_id]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($details) {
                $response = $details;
            } else {
                $response['error'] = 'Framework Order not found or not active.';
            }
        } catch (\PDOException $e) {
            error_log("Get Order Details Error: " . $e->getMessage());
            $response['error'] = 'Database error fetching details.';
        }
    }
    echo json_encode($response);
    exit;
}

// --- CREATE ---
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("Creating order with POST data: " . print_r($_POST, true));

    $order_type = $_POST['order_type'] ?? null;
    $data = [];
    $sql_columns = [];
    $sql_placeholders = [];

    // Validate order type
    if (!in_array($order_type, ['F', 'C', 'N'])) {
        $_SESSION['message'] = 'Invalid order type specified.';
        $_SESSION['message_type'] = 'alert-danger';
        header('Location: create.php');
        exit;
    }

    // Use a switch to handle different order types
    switch ($order_type) {
        case 'F':
            // Debug: Log all POST data
            error_log("POST data for type F: " . print_r($_POST, true));

            // Validate required fields for Framework order
            $required_fields = [
                'project_no',
                'framework_order_no',
                'framework_order_position',
                'customer_article_no',
                'system_article_no',
                'price_article',
                'framework_quantity',
                'request_date'
            ];

            // Debug: Check each required field
            foreach ($required_fields as $field) {
                // Skip validation if the field is empty string (from hidden fields)
                if (isset($_POST[$field]) && $_POST[$field] === '') {
                    continue;
                }
                
                if (empty($_POST[$field])) {
                    error_log("Missing required field for type F: " . $field);
                    $_SESSION['message'] = "Please fill in all required fields for Framework order. Missing: " . $field;
                    $_SESSION['message_type'] = 'alert-danger';
                    header('Location: create.php');
                    exit;
                }
            }

            // Get the values from the form fields, ignoring any hidden fields
            $data = [
                'order_type' => $order_type,
                'project_no' => $_POST['project_no'],
                'framework_order_no' => $_POST['framework_order_no'],
                'framework_order_position' => $_POST['framework_order_position'],
                'customer_article_no' => $_POST['customer_article_no'],
                'system_article_no' => $_POST['system_article_no'],
                'price_article' => $_POST['price_article'],
                'framework_quantity' => $_POST['framework_quantity'],
                'request_date' => $_POST['request_date'],
                'is_active' => 1
            ];

            // Debug: Log the data being saved
            error_log("Data being saved for type F: " . print_r($data, true));
            break;

        case 'C':
            // Validate required fields for Call-off order
            $required_fields = [
                'selected_order_id',
                'call_order_number_c',
                'call_order_position_c',
                'ordered_quantity_c',
                'request_date_c'
            ];

            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $_SESSION['message'] = "Please fill in all required fields for Call-off order.";
                    $_SESSION['message_type'] = 'alert-danger';
                    header('Location: create.php');
                    exit;
                }
            }

            // Get framework order details from the parent order
            try {
                $stmt = $pdo->prepare("SELECT framework_order_no, customer_article_no, system_article_no, price_article, project_no, framework_order_position, framework_quantity 
                                     FROM orders_initial 
                                     WHERE orderID = ? AND order_type = 'F' AND is_active = 1");
                $stmt->execute([$_POST['selected_order_id']]);
                $parent_order = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$parent_order) {
                    $_SESSION['message'] = "Selected Framework Order not found or is inactive.";
                    $_SESSION['message_type'] = 'alert-danger';
                    header('Location: create.php');
                    exit;
                }

                $data = [
                    'order_type' => $order_type,
                    'project_no' => $parent_order['project_no'],
                    'framework_order_no' => $parent_order['framework_order_no'],
                    'framework_order_position' => $parent_order['framework_order_position'],
                    'framework_quantity' => $parent_order['framework_quantity'],
                    'customer_article_no' => $parent_order['customer_article_no'],
                    'system_article_no' => $parent_order['system_article_no'],
                    'price_article' => $parent_order['price_article'],
                    'call_order_number' => $_POST['call_order_number_c'],
                    'call_order_position' => $_POST['call_order_position_c'],
                    'ordered_quantity' => $_POST['ordered_quantity_c'],
                    'request_date' => $_POST['request_date_c'],
                    'is_active' => 1
                ];
            } catch (\PDOException $e) {
                error_log("Error fetching parent order details: " . $e->getMessage());
                $_SESSION['message'] = "Error processing Framework Order details.";
                $_SESSION['message_type'] = 'alert-danger';
                header('Location: create.php');
                exit;
            }
            break;

        case 'N':
            // Validate required fields for Normal order
            $required_fields = [
                'n_customer_article_no',
                'n_system_article_no',
                'n_price_article',
                'n_request_date'
            ];

            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $_SESSION['message'] = "Please fill in all required fields for Normal order.";
                    $_SESSION['message_type'] = 'alert-danger';
                    header('Location: create.php');
                    exit;
                }
            }

            $data = [
                'order_type' => $order_type,
                'project_no' => $_POST['n_project_no'] ?? null,
                'framework_order_no' => $_POST['n_framework_order_no'] ?? null,
                'framework_order_position' => $_POST['n_framework_order_position'] ?? null,
                'framework_quantity' => $_POST['n_framework_quantity'] ?? null,
                'customer_article_no' => $_POST['n_customer_article_no'],
                'system_article_no' => $_POST['n_system_article_no'],
                'price_article' => $_POST['n_price_article'],
                'request_date' => $_POST['n_request_date'],
                'is_active' => 1
            ];
            break;
    }

    // Build and Execute SQL
    if (!empty($data)) {
        $sql_columns = array_keys($data);
        $sql_placeholders = array_fill(0, count($data), '?');
        
        $sql = "INSERT INTO orders_initial (" . implode(', ', $sql_columns) . ") VALUES (" . implode(', ', $sql_placeholders) . ")";
        
        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));
            
            if ($result) {
                $_SESSION['message'] = "Order (Type: $order_type) created successfully!";
                $_SESSION['message_type'] = 'alert-success';
                header('Location: index.php');
                exit;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Order creation failed: " . print_r($errorInfo, true));
                $_SESSION['message'] = "Error creating order. Please try again.";
                $_SESSION['message_type'] = 'alert-danger';
                header('Location: create.php');
                exit;
            }
        } catch (\PDOException $e) {
            error_log("Order creation error: " . $e->getMessage());
            $_SESSION['message'] = "Error creating order: " . $e->getMessage();
            $_SESSION['message_type'] = 'alert-danger';
            header('Location: create.php');
            exit;
        }
    } else {
        $_SESSION['message'] = 'No data processed for creation.';
        $_SESSION['message_type'] = 'alert-danger';
        header('Location: create.php');
        exit;
    }
}


// --- UPDATE ---
elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
     $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
     $order_type = $_POST['order_type'] ?? null; // Type cannot change, get from hidden field

     if (!$order_id || !$order_type) {
         $_SESSION['message'] = 'Invalid Order ID or Type for update.';
         $_SESSION['message_type'] = 'alert-danger';
         header('Location: index.php');
         exit;
     }

    $data = [];
    $sql_set_parts = [];

     // Use a switch to collect data and build SET clause
    switch ($order_type) {
        case 'F':
             $data = [
                // Collect all fields relevant to F from $_POST, similar to create
                 'project_no' => $_POST['project_no'] ?? null,
                 'framework_order_no' => $_POST['framework_order_no'] ?? null,
                 'framework_order_position' => $_POST['framework_order_position'] ?? null,
                 'customer_article_no' => $_POST['customer_article_no'] ?? null,
                 'system_article_no' => $_POST['system_article_no'] ?? null,
                 'price_article' => $_POST['price_article'] ?? null,
                 'request_date' => $_POST['request_date'] ?? null,
                 'framework_quantity' => $_POST['framework_quantity'] ?? null,
                 'status' => $_POST['status'] ?? 'Framework',
                 'total_price' => $_POST['total_price'] ?? null,
                 'note' => $_POST['note'] ?? null,
                 'delivered_quantity' => $_POST['delivered_quantity'] ?? null,
                 'remaining_quantity' => $_POST['remaining_quantity'] ?? null,
                 'need_to_pro_quantity' => $_POST['need_to_pro_quantity'] ?? null,
                 'called_quantity' => $_POST['called_quantity'] ?? null,
                 'uncalled_quantity' => $_POST['uncalled_quantity'] ?? null,
                 'stock_price' => $_POST['stock_price'] ?? null,
                 'uncalled_quantity_price' => $_POST['uncalled_quantity_price'] ?? null,
                 'remaining' => $_POST['remaining'] ?? null,
                 // order_type and parent_order_id don't change for F
                 'orderID' => $order_id // Needed for WHERE clause
             ];
            break;

        case 'C':
            $parent_id = filter_input(INPUT_POST, 'parent_order_id', FILTER_VALIDATE_INT);
             if (!$parent_id) { /* Error handling */ }
             $data = [
                 'parent_order_id' => $parent_id,
                 'project_no' => $_POST['call_order_number_c'] ?? null, // Reused field
                 'framework_order_position' => $_POST['call_order_position_c'] ?? null, // Reused field
                 'framework_quantity' => $_POST['ordered_quantity_c'] ?? null, // Reused field
                 'request_date' => $_POST['request_date_c'] ?? null,
                 // Get article/price/parent details from hidden fields
                 'customer_article_no' => $_POST['c_fetched_customer_article_no'] ?? null,
                 'system_article_no' => $_POST['c_fetched_system_article_no'] ?? null,
                 'price_article' => $_POST['c_fetched_price_article'] ?? null,
                 'framework_order_no' => $_POST['c_fetched_f_order_no'] ?? null,
                 // Potentially add status/note if editable for C
                 'status' => $_POST['status'] ?? 'Scheduled',
                 'note' => $_POST['note'] ?? null,
                 'orderID' => $order_id // Needed for WHERE clause
            ];
             break;

        case 'N':
            $data = [
                // Collect all fields relevant to N from $_POST, similar to create
                'project_no' => $_POST['n_project_no'] ?? null,
                'framework_order_no' => $_POST['n_framework_order_no'] ?? null,
                'framework_order_position' => $_POST['n_framework_order_position'] ?? null,
                'customer_article_no' => $_POST['n_customer_article_no'],
                'system_article_no' => $_POST['n_system_article_no'],
                'price_article' => $_POST['n_price_article'],
                'request_date' => $_POST['n_request_date'],
                'framework_quantity' => $_POST['n_framework_quantity'] ?? null,
                'status' => $_POST['status'] ?? 'Scheduled',
                'total_price' => $_POST['total_price'] ?? null,
                'note' => $_POST['note'] ?? null,
                'delivered_quantity' => $_POST['delivered_quantity'] ?? null,
                'remaining_quantity' => $_POST['remaining_quantity'] ?? null,
                'need_to_pro_quantity' => $_POST['need_to_pro_quantity'] ?? null,
                'called_quantity' => $_POST['called_quantity'] ?? null,
                'uncalled_quantity' => $_POST['uncalled_quantity'] ?? null,
                'stock_price' => $_POST['stock_price'] ?? null,
                'uncalled_quantity_price' => $_POST['uncalled_quantity_price'] ?? null,
                'remaining' => $_POST['remaining'] ?? null,
                 // order_type and parent_order_id don't change for N
                 'orderID' => $order_id // Needed for WHERE clause
            ];
             break;

         default:
            $_SESSION['message'] = 'Invalid Order Type for update.';
            $_SESSION['message_type'] = 'alert-danger';
            header('Location: edit.php?id=' . $order_id);
            exit;
     }

     // Build SET part of SQL dynamically
     foreach ($data as $key => $value) {
         if ($key !== 'orderID') { // Don't include primary key in SET clause
             $sql_set_parts[] = "`" . $key . "` = :" . $key;
         }
     }

    // Execute SQL
    if (!empty($sql_set_parts)) {
        $sql = "UPDATE orders_initial SET " . implode(', ', $sql_set_parts) . " WHERE orderID = :orderID";
         try {
             $stmt = $pdo->prepare($sql);
             $stmt->execute($data); // Pass the whole $data array for named parameters

            $_SESSION['message'] = "Order (Type: $order_type) updated successfully!";
            $_SESSION['message_type'] = 'alert-success';
         } catch (\PDOException $e) {
             $_SESSION['message'] = "Error updating order (Type: $order_type): " . $e->getMessage();
             $_SESSION['message_type'] = 'alert-danger';
             header('Location: edit.php?id=' . $order_id); // Redirect back to edit on error
             exit;
         }
    } else {
        $_SESSION['message'] = 'No data processed for update.';
        $_SESSION['message_type'] = 'alert-danger';
    }

     header('Location: index.php'); // Redirect to list view
     exit;
}


// --- DELETE (Soft Delete) ---
elseif ($action === 'delete' && isset($_GET['id'])) {
    // ... (Delete logic remains the same - soft delete) ...
     $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$order_id) {
        $_SESSION['message'] = 'Invalid Order ID for delete.';
        $_SESSION['message_type'] = 'alert-danger';
    } else {
        // Perform soft delete by setting is_active to 0
        $sql = "UPDATE orders_initial SET is_active = 0 WHERE orderID = ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$order_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['message'] = 'Order marked as inactive successfully!';
                $_SESSION['message_type'] = 'alert-success';
            } else {
                $_SESSION['message'] = 'Order not found or already inactive.';
                $_SESSION['message_type'] = 'alert-danger';
            }
        } catch (\PDOException $e) {
             $_SESSION['message'] = 'Error deleting order: ' . $e->getMessage(); // Debugging
             $_SESSION['message_type'] = 'alert-danger';
        }
    }
    header('Location: index.php');
    exit;

}

// --- UPDATE CELL ---
elseif ($action === 'update_cell' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $column = filter_input(INPUT_POST, 'column', FILTER_SANITIZE_STRING);
    $value = filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING);
    
    if (!$order_id || !$column) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Map column names to database fields
    $column_mapping = [
        'id' => 'orderID',
        'type' => 'order_type',
        'project_no' => 'project_no',
        'order_no' => 'framework_order_no',
        'position' => 'framework_order_position',
        'customer_article' => 'customer_article_no',
        'system_article' => 'system_article_no',
        'quantity' => 'framework_quantity',
        'price' => 'price_article',
        'request_date' => 'request_date'
    ];
    
    if (!isset($column_mapping[$column])) {
        echo json_encode(['success' => false, 'message' => 'Invalid column']);
        exit;
    }
    
    $db_column = $column_mapping[$column];
    
    try {
        $sql = "UPDATE orders_initial SET $db_column = ? WHERE orderID = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$value, $order_id]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    } catch (\PDOException $e) {
        error_log("Cell update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// --- SPLIT ---
elseif ($action === 'split') {
    $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$order_id) {
        $_SESSION['message'] = 'Invalid Order ID.';
        $_SESSION['message_type'] = 'alert-danger';
        header('Location: index.php');
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get the original order details
        $stmt = $pdo->prepare("SELECT * FROM orders_initial WHERE orderID = ?");
        $stmt->execute([$order_id]);
        $original_order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$original_order) {
            throw new Exception("Original order not found.");
        }

        // Get the next split position
        $stmt = $pdo->prepare("SELECT MAX(split_position) as max_position FROM orders_initial WHERE parent_order_id = ?");
        $stmt->execute([$order_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_position = ($result['max_position'] ?? 0) + 1;

        // Create new order with split details
        $new_order = $original_order;
        unset($new_order['orderID']); // Remove the ID to create a new record
        $new_order['parent_order_id'] = $order_id;
        $new_order['split_position'] = $next_position;
        $new_order['is_active'] = 1;

        // Insert the new order
        $columns = implode(', ', array_keys($new_order));
        $values = implode(', ', array_fill(0, count($new_order), '?'));
        $sql = "INSERT INTO orders_initial ($columns) VALUES ($values)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(array_values($new_order));

        if ($result) {
            $pdo->commit();
            $_SESSION['message'] = "Order split successfully!";
            $_SESSION['message_type'] = 'alert-success';
        } else {
            throw new Exception("Failed to create split order.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order split error: " . $e->getMessage());
        $_SESSION['message'] = "Error splitting order: " . $e->getMessage();
        $_SESSION['message_type'] = 'alert-danger';
    }
    
    header('Location: index.php');
    exit;
}

// --- ACTIVATE/DEACTIVATE ---
elseif ($action === 'activate' || $action === 'deactivate') {
    $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$order_id) {
        $_SESSION['message'] = 'Invalid Order ID.';
        $_SESSION['message_type'] = 'alert-danger';
        header('Location: index.php');
        exit;
    }

    try {
        $is_active = ($action === 'activate') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE orders_initial SET is_active = ? WHERE orderID = ?");
        $result = $stmt->execute([$is_active, $order_id]);
        
        if ($result) {
            $_SESSION['message'] = "Order " . ($is_active ? "activated" : "deactivated") . " successfully!";
            $_SESSION['message_type'] = 'alert-success';
        } else {
            $_SESSION['message'] = "Error updating order status.";
            $_SESSION['message_type'] = 'alert-danger';
        }
    } catch (\PDOException $e) {
        error_log("Order status update error: " . $e->getMessage());
        $_SESSION['message'] = "Error updating order status: " . $e->getMessage();
        $_SESSION['message_type'] = 'alert-danger';
    }
    
    header('Location: index.php');
    exit;
}

// --- Invalid Action ---
else {
    // ... (Invalid action handling remains the same) ...
     $_SESSION['message'] = 'Invalid action requested.';
     $_SESSION['message_type'] = 'alert-danger';
     header('Location: index.php');
     exit;
}

?>