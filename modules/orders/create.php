<?php
session_start();
require '../db_connect.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch existing Framework orders (type 'F') for the dropdown for type 'C'
$framework_orders_f = [];
try {
    $stmt = $pdo->query("SELECT orderID, framework_order_no,framework_order_position, project_no FROM orders_initial WHERE order_type = 'F' AND is_active = 1 ORDER BY framework_order_no");
    $framework_orders_f = $stmt->fetchAll();
} catch (\PDOException $e) {
    // Handle error - maybe log it, don't stop the page load entirely
    error_log("Error fetching framework orders (F): " . $e->getMessage());
}

// Fetch active articles for dropdown
$active_articles = [];
try {
    $stmt = $pdo->query("SELECT articleID, customer_article_no, system_article_no, price FROM articles WHERE status = 1 ORDER BY customer_article_no");
    $active_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching articles: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-color: #ecf0f1;
            --text-muted: #bdc3c7;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --card-bg: #2c3e50;
            --border-color: #34495e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--primary-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .top-menu {
            background-color: var(--secondary-color);
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .menu-items {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .menu-left, .menu-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-item {
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .menu-item:hover {
            background-color: var(--accent-color);
        }

        .menu-item.active {
            background-color: var(--accent-color);
        }

        .container {
            max-width: 1100px;
            margin: 80px auto 20px;
            padding: 0 20px;
        }

        .card {
            background-color: var(--secondary-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .field-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="password"],
        select,
        input[type="datetime-local"] {
            width: 100%;
            padding: 10px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
            font-size: 16px;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus,
        input[type="datetime-local"]:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .message.success {
            background-color: var(--success-color);
            color: white;
        }

        .message.error {
            background-color: var(--danger-color);
            color: white;
        }

        .suggestions-container {
            display: none;
            position: absolute;
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
        }
        
        .suggestion-item {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .suggestion-item:hover {
            background-color: var(--accent-color);
        }
        
        .field-group-c {
            position: relative;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.9em;
            margin-top: 5px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert.success {
            background-color: var(--success-color);
            color: white;
        }

        .alert.error {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-create {
            background-color: var(--success-color);
        }

        .btn-create:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <nav class="top-menu">
        <div class="menu-items">
            <div class="menu-left">
                <a href="../dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="index.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="../articles_list.php" class="menu-item">
                    <i class="fas fa-box"></i> Articles
                </a>
            </div>
            <div class="menu-right">
                <a href="../preferences.php" class="menu-item">
                    <i class="fas fa-cog"></i> Preferences
                </a>
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Create New Order</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert <?= $_SESSION['message_type'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <form action="actions.php?action=create" method="POST" id="order-form" onsubmit="return validateForm(event)">
            <!-- Order Type - Always Visible -->
            <div class="field-group field-group-common">
                <label for="order_type">Order Type:</label>
                <select id="order_type" name="order_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="F">F (Framework)</option>
                    <option value="C">C (Call-off)</option>
                    <option value="N">N (Normal)</option>
                </select>
            </div>

            <!-- == Fields for Type 'F' (Framework) == -->
            <div class="field-group field-group-f">
                <label for="project_no">Project No:</label>
                <input type="text" id="project_no" name="project_no" maxlength="200">
            </div>
            <div class="field-group field-group-f">
                <label for="framework_order_no">Framework Order No:</label>
                <input type="text" id="framework_order_no" name="framework_order_no" maxlength="100" required>
            </div>
            <div class="field-group field-group-f">
                <label for="framework_order_position">Framework Order Position:</label>
                <input type="text" id="framework_order_position" name="framework_order_position" maxlength="30" required>
            </div>
            <div class="field-group field-group-f">
                <label for="customer_article_no">Customer Article No:</label>
                <select id="customer_article_no" name="customer_article_no" required>
                    <option value="">-- Select Article --</option>
                    <?php foreach ($active_articles as $article): ?>
                        <option value="<?= htmlspecialchars($article['articleID']) ?>" 
                                data-system-no="<?= htmlspecialchars($article['system_article_no']) ?>"
                                data-price="<?= htmlspecialchars($article['price']) ?>">
                            <?= htmlspecialchars($article['customer_article_no']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-group field-group-f">
                <label for="system_article_no">System Article No:</label>
                <input type="text" id="system_article_no" name="system_article_no" maxlength="100" required readonly>
            </div>
            <div class="field-group field-group-f">
                <label for="price_article">Article Price:</label>
                <input type="text" id="price_article" name="price_article" maxlength="30" required>
            </div>
            <div class="field-group field-group-f">
                <label for="framework_quantity">Framework Quantity:</label>
                <input type="text" id="framework_quantity" name="framework_quantity" maxlength="30" required>
            </div>
            <div class="field-group field-group-f">
                <label for="request_date">Request Date:</label>
                <input type="datetime-local" id="request_date" name="request_date" required>
            </div>

            <!-- == Fields for Type 'C' (Call-off) == -->
            <div class="field-group field-group-c" style="display: none;">
                <label for="parent_order_id">Referenced Framework Order (F):</label>
                <input type="text" id="parent_order_id" name="parent_order_id" class="autocomplete-input" placeholder="Type to search framework orders...">
                <input type="hidden" id="selected_order_id" name="selected_order_id">
                <div id="suggestions" class="suggestions-container"></div>
            </div>
            <div class="field-group field-group-c field-group-article-display">
                <strong>Referenced Order Details:</strong><br>
                Project No: <span id="display_c_project_no">N/A</span><br>
                Cust. Article No: <span id="display_c_customer_article_no">N/A</span><br>
                Sys. Article No: <span id="display_c_system_article_no">N/A</span><br>
                Article Price: <span id="display_c_price_article">N/A</span><br>
            </div>
            <input type="hidden" id="c_framework_order_no" name="c_framework_order_no">
            <input type="hidden" id="c_customer_article_no" name="c_customer_article_no">
            <input type="hidden" id="c_system_article_no" name="c_system_article_no">
            <input type="hidden" id="c_price_article" name="c_price_article">
            <div class="field-group field-group-c">
                <label for="call_order_number_c">Call Order Number:</label>
                <input type="text" id="call_order_number_c" name="call_order_number_c" maxlength="100">
            </div>
            <div class="field-group field-group-c">
                <label for="call_order_position_c">Call Order Position:</label>
                <input type="text" id="call_order_position_c" name="call_order_position_c" maxlength="30">
            </div>
            <div class="field-group field-group-c">
                <label for="ordered_quantity_c">Ordered Quantity:</label>
                <input type="text" id="ordered_quantity_c" name="ordered_quantity_c" maxlength="30">
            </div>
            <div class="field-group field-group-c">
                <label for="request_date_c">Request Date:</label>
                <input type="datetime-local" id="request_date_c" name="request_date_c">
            </div>

            <!-- == Fields for Type 'N' (Normal) == -->
            <div class="field-group field-group-n">
                <label for="n_customer_article_no">Customer Article No:</label>
                <select id="n_customer_article_no" name="n_customer_article_no" required>
                    <option value="">-- Select Article --</option>
                    <?php foreach ($active_articles as $article): ?>
                        <option value="<?= htmlspecialchars($article['articleID']) ?>" 
                                data-system-no="<?= htmlspecialchars($article['system_article_no']) ?>"
                                data-price="<?= htmlspecialchars($article['price']) ?>">
                            <?= htmlspecialchars($article['customer_article_no']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-group field-group-n">
                <label for="n_system_article_no">System Article No:</label>
                <input type="text" id="n_system_article_no" name="n_system_article_no" maxlength="100" required readonly>
            </div>
            <div class="field-group field-group-n">
                <label for="n_project_no">Project No:</label>
                <input type="text" id="n_project_no" name="n_project_no" maxlength="200">
            </div>
            <div class="field-group field-group-n">
                <label for="n_order_number">Order Number:</label>
                <input type="text" id="n_order_number" name="n_framework_order_no" maxlength="100">
            </div>
            <div class="field-group field-group-n">
                <label for="n_order_position">Order Position:</label>
                <input type="text" id="n_order_position" name="n_framework_order_position" maxlength="30">
            </div>
            <div class="field-group field-group-n">
                <label for="n_ordered_quantity">Ordered Quantity:</label>
                <input type="text" id="n_ordered_quantity" name="n_framework_quantity" maxlength="30">
            </div>
            <div class="field-group field-group-n">
                <label for="n_request_date">Request Date:</label>
                <input type="datetime-local" id="n_request_date" name="n_request_date">
            </div>
            <div class="field-group field-group-n">
                <label for="n_price_article">Article Price:</label>
                <input type="text" id="n_price_article" name="n_price_article" maxlength="30" required readonly>
            </div>

            <!-- Submit Button -->
            <div class="field-group field-group-common">
                <button type="submit" class="btn btn-create">
                    <i class="fas fa-plus"></i> Create Order
                </button>
                <a href="index.php" class="btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function validateForm(event) {
            const orderType = document.getElementById('order_type').value;
            let isValid = true;
            let missingFields = [];

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => el.remove());

            // Validate based on order type
            if (orderType === 'F') {
                const requiredFields = [
                    { id: 'framework_order_no', name: 'Framework Order No' },
                    { id: 'framework_order_position', name: 'Framework Order Position' },
                    { id: 'customer_article_no', name: 'Customer Article No' },
                    { id: 'system_article_no', name: 'System Article No' },
                    { id: 'price_article', name: 'Price Article' },
                    { id: 'framework_quantity', name: 'Framework Quantity' },
                    { id: 'request_date', name: 'Request Date' }
                ];

                requiredFields.forEach(field => {
                    const input = document.getElementById(field.id);
                    if (!input.value.trim()) {
                        isValid = false;
                        missingFields.push(field.name);
                        showError(input, `${field.name} is required`);
                    }
                });
            } else if (orderType === 'N') {
                const requiredFields = [
                    { id: 'n_customer_article_no', name: 'Customer Article No' },
                    { id: 'n_system_article_no', name: 'System Article No' },
                    { id: 'n_project_no', name: 'Project No' },
                    { id: 'n_framework_order_no', name: 'Order Number' },
                    { id: 'n_framework_order_position', name: 'Order Position' },
                    { id: 'n_framework_quantity', name: 'Ordered Quantity' },
                    { id: 'n_request_date', name: 'Request Date' },
                    { id: 'n_price_article', name: 'Article Price' }
                ];

                requiredFields.forEach(field => {
                    const input = document.getElementById(field.id);
                    if (!input.value.trim()) {
                        isValid = false;
                        missingFields.push(field.name);
                        showError(input, `${field.name} is required`);
                    }
                });
            }

            if (!isValid) {
                event.preventDefault();
                alert('Please fill in all required fields: ' + missingFields.join(', '));
                return false;
            }
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const orderTypeSelect = document.getElementById('order_type');
            const fieldGroups = {
                'F': document.querySelectorAll('.field-group-f'),
                'C': document.querySelectorAll('.field-group-c'),
                'N': document.querySelectorAll('.field-group-n')
            };

            function toggleFields() {
                const selectedType = orderTypeSelect.value;
                
                // Hide all field groups first
                Object.values(fieldGroups).forEach(groups => {
                    groups.forEach(group => {
                        group.style.display = 'none';
                        // Remove required attribute from all fields when hidden
                        group.querySelectorAll('[required]').forEach(field => {
                            field.removeAttribute('required');
                        });
                    });
                });
                
                // Show fields for selected type
                if (selectedType && fieldGroups[selectedType]) {
                    fieldGroups[selectedType].forEach(group => {
                        group.style.display = 'block';
                        // Add required attribute to fields that should be required
                        if (selectedType === 'F') {
                            group.querySelectorAll('#framework_order_no, #framework_order_position, #customer_article_no, #system_article_no, #price_article, #framework_quantity, #request_date').forEach(field => {
                                field.setAttribute('required', 'required');
                            });
                        } else if (selectedType === 'N') {
                            group.querySelectorAll('#n_customer_article_no, #n_system_article_no, #n_project_no, #n_framework_order_no, #n_framework_order_position, #n_framework_quantity, #n_request_date, #n_price_article').forEach(field => {
                                field.setAttribute('required', 'required');
                            });
                        }
                    });
                }
            }

            // Initial toggle
            toggleFields();
            
            // Toggle on change
            orderTypeSelect.addEventListener('change', toggleFields);

            // Function to handle article selection
            function handleArticleSelection(selectElement, systemNoElement, priceElement) {
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                if (selectedOption.value) {
                    systemNoElement.value = selectedOption.dataset.systemNo;
                    priceElement.value = selectedOption.dataset.price;
                } else {
                    systemNoElement.value = '';
                    priceElement.value = '';
                }
            }

            // Add event listeners for Framework type
            const frameworkSelect = document.getElementById('customer_article_no');
            const frameworkSystemNo = document.getElementById('system_article_no');
            const frameworkPrice = document.getElementById('price_article');
            
            if (frameworkSelect && frameworkSystemNo && frameworkPrice) {
                frameworkSelect.addEventListener('change', function() {
                    handleArticleSelection(this, frameworkSystemNo, frameworkPrice);
                });
            }

            // Add event listeners for Normal type
            const normalSelect = document.getElementById('n_customer_article_no');
            const normalSystemNo = document.getElementById('n_system_article_no');
            const normalPrice = document.getElementById('n_price_article');
            
            if (normalSelect && normalSystemNo && normalPrice) {
                normalSelect.addEventListener('change', function() {
                    handleArticleSelection(this, normalSystemNo, normalPrice);
                });
            }

            // Add this new function for handling autocomplete
            function setupAutocomplete() {
                const input = document.getElementById('parent_order_id');
                const selectedOrderId = document.getElementById('selected_order_id');
                const suggestionsContainer = document.getElementById('suggestions');
                let frameworkOrders = <?= json_encode($framework_orders_f) ?>;

                input.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    suggestionsContainer.innerHTML = '';
                    
                    if (searchTerm.length < 2) {
                        suggestionsContainer.style.display = 'none';
                        return;
                    }

                    const filteredOrders = frameworkOrders.filter(order => {
                        const orderText = (order.framework_order_position + (order.project_no ? ' / ' + order.project_no : '')).toLowerCase();
                        return orderText.includes(searchTerm);
                    });

                    if (filteredOrders.length > 0) {
                        filteredOrders.forEach(order => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = order.framework_order_position + (order.project_no ? ' / ' + order.project_no : '');
                            div.setAttribute('data-order-id', order.orderID);
                            div.setAttribute('data-project', order.project_no || '');
                            
                            div.addEventListener('click', function() {
                                input.value = this.textContent;
                                selectedOrderId.value = this.getAttribute('data-order-id');
                                suggestionsContainer.style.display = 'none';
                                
                                // Update display fields
                                const projectNo = this.getAttribute('data-project');
                                document.getElementById('display_c_project_no').textContent = projectNo || 'N/A';

                                // Fetch order details
                                fetch(`get_order_details.php?id=${this.getAttribute('data-order-id')}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.error) {
                                            console.error('Error:', data.error);
                                            return;
                                        }
                                        // Update display fields
                                        document.getElementById('display_c_customer_article_no').textContent = data.customer_article_no || 'N/A';
                                        document.getElementById('display_c_system_article_no').textContent = data.system_article_no || 'N/A';
                                        document.getElementById('display_c_price_article').textContent = data.price_article || 'N/A';
                                        
                                        // Update hidden fields
                                        document.getElementById('c_framework_order_no').value = data.framework_order_no || '';
                                        document.getElementById('c_customer_article_no').value = data.customer_article_no || '';
                                        document.getElementById('c_system_article_no').value = data.system_article_no || '';
                                        document.getElementById('c_price_article').value = data.price_article || '';
                                    })
                                    .catch(error => {
                                        console.error('Error fetching order details:', error);
                                        // Reset display fields
                                        document.getElementById('display_c_customer_article_no').textContent = 'N/A';
                                        document.getElementById('display_c_system_article_no').textContent = 'N/A';
                                        document.getElementById('display_c_price_article').textContent = 'N/A';
                                        // Reset hidden fields
                                        document.getElementById('c_framework_order_no').value = '';
                                        document.getElementById('c_customer_article_no').value = '';
                                        document.getElementById('c_system_article_no').value = '';
                                        document.getElementById('c_price_article').value = '';
                                    });
                            });
                            
                            suggestionsContainer.appendChild(div);
                        });
                        suggestionsContainer.style.display = 'block';
                    } else {
                        suggestionsContainer.style.display = 'none';
                    }
                });

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                        suggestionsContainer.style.display = 'none';
                    }
                });
            }

            // Call setupAutocomplete when the page loads
            setupAutocomplete();
        });
    </script>
</body>
</html>