<?php
// articles_list.php
require_once 'db_connect.php'; // Handles DB connection, session, theme

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- Sorting Logic ---
$allowed_sort_cols = [ // Whitelist allowed columns for security
    'articleID' => 'a.articleID',
    'customer_article_no' => 'a.customer_article_no',
    'system_article_no' => 'a.system_article_no',
    'added_by' => 'u_add.name', // Sort by user name
    'added_date' => 'a.added_date',
    'edited_by' => 'u_edit.name', // Sort by user name
    'edit_date' => 'a.edit_date',
    'order_index' => 'a.order_index' // Allow sorting by custom order
];
$sort_col_param = filter_input(INPUT_GET, 'sort_col', FILTER_SANITIZE_SPECIAL_CHARS);
$sort_dir_param = filter_input(INPUT_GET, 'sort_dir', FILTER_SANITIZE_SPECIAL_CHARS);

// Default sort order (use custom order first, then ID)
$sort_col_sql = $allowed_sort_cols['order_index'];
$sort_dir_sql = 'ASC';
$current_sort_col = 'order_index';
$current_sort_dir = 'asc';

// Validate and apply user sorting
if ($sort_col_param && isset($allowed_sort_cols[$sort_col_param])) {
    $current_sort_col = $sort_col_param;
    $sort_col_sql = $allowed_sort_cols[$sort_col_param];
    if ($sort_dir_param && strtolower($sort_dir_param) === 'desc') {
        $current_sort_dir = 'desc';
        $sort_dir_sql = 'DESC';
    } else {
        $current_sort_dir = 'asc';
        $sort_dir_sql = 'ASC';
    }
}
// Construct the final ORDER BY clause
// Add a secondary sort column for stable sorting
$order_by_sql = "ORDER BY {$sort_col_sql} {$sort_dir_sql}, a.articleID DESC";


// --- Fetch Articles (Apply Sorting) ---
$active_articles = [];
$inactive_articles = [];
$fetch_error = '';
try {
    // Prepare the base query with joins
    $sql_select_join = "SELECT
                            a.articleID, a.customer_name,a.customer_article_no, a.system_article_no, a.price,
                            a.added_date, a.edit_date, a.status, a.parent_article_id, a.order_index, -- Include order_index
                            u_add.name AS added_by_name,
                            u_edit.name AS edited_by_name
                        FROM articles a
                        LEFT JOIN users u_add ON a.added_by = u_add.userID
                        LEFT JOIN users u_edit ON a.edit_by = u_edit.userID";

    // Fetch Active Articles (status = 1) applying the ORDER BY
    $sql_active = $sql_select_join . " WHERE a.status = 1 " . $order_by_sql;
    $stmt_active = $pdo->query($sql_active);
    $active_articles = $stmt_active->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Inactive Articles (status = 0) also applying ORDER BY
    $sql_inactive = $sql_select_join . " WHERE a.status = 0 " . $order_by_sql;
    $stmt_inactive = $pdo->query($sql_inactive);
    $inactive_articles = $stmt_inactive->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $fetch_error = "Error fetching articles: " . $e->getMessage();
    error_log("Article List Fetch Error: " . $e->getMessage());
}

// --- Helper Function: Get Status Text ---
function getStatusText($status_code) {
    return ($status_code == 1) ? 'Active' : 'Inactive';
}

// --- Theme ---
$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';

// --- Helper function for generating sort links ---
function getSortLink($col_key, $col_name, $current_col, $current_dir) {
    $link_dir = 'asc';
    $arrow = ' <i class="fa fa-sort" style="color: #ccc;"></i>'; // Default neutral sort icon
    if ($col_key === $current_col) {
        if ($current_dir === 'asc') {
            $link_dir = 'desc';
            $arrow = ' <i class="fa fa-sort-up"></i>'; // Ascending arrow
        } else {
            // $link_dir is already 'asc'
            $arrow = ' <i class="fa fa-sort-down"></i>'; // Descending arrow
        }
    }
    // Basic link generation - does not preserve other GET params currently
    return "articles_list.php?sort_col={$col_key}&sort_dir={$link_dir}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles</title>
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
            max-width: 1200px;
            margin: 80px auto 20px;
            padding: 0 20px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: var(--secondary-color);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary-color);
            font-weight: 600;
            color: var(--accent-color);
        }

        tr:hover {
            background-color: var(--card-bg);
        }

        /* Action Links */
        .action-links a {
            color: var(--text-color);
            text-decoration: none;
            margin-right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .action-links a:hover {
            background-color: var(--accent-color);
        }

        .action-links a.edit {
            color: var(--success-color);
        }

        .action-links a.deactivate {
            color: var(--danger-color);
        }

        .action-links a.activate {
            color: var(--success-color);
        }

        /* Form Elements */
        input[type="text"],
        select {
            background-color: var(--secondary-color);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 8px 12px;
            border-radius: 4px;
            width: 100%;
        }

        input[type="text"]:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        /* Messages */
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

        /* Section Titles */
        .section-title {
            color: var(--accent-color);
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--border-color);
        }

        /* Suggestions List */
        .suggestions-list {
            background-color: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }

        .suggestions-list li {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .suggestions-list li:hover {
            background-color: var(--accent-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }
        .link-white{
            color: #fff;
            text-decoration:unset;
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--border-color);
            border-radius: 50%;
            border-top-color: var(--accent-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Drag and Drop */
        .sortable-placeholder {
            background-color: var(--card-bg);
            border: 2px dashed var(--accent-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="top-menu">
        <div class="menu-items">
            <div class="menu-left">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="orders/index.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="articles_list.php" class="menu-item active">
                    <i class="fas fa-box"></i> Articles
                </a>
            </div>
            <div class="menu-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <a class="link-white" href="preferences.php"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></a>
                    </div>
                        <a class="link-white" href="preferences.php"><span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span></a>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Manage Articles</h1>

        <!-- Global Messages Area -->
        <div id="global-message-area">
            <!-- Saving Indicator for Drag/Drop -->
            <!-- <div id="saving-indicator">Saving order... <div class="spinner" style="display: inline-block;"></div></div> -->
            <!-- PHP Success/Error Messages -->
            <?php if (isset($_GET['status'])): ?>
                <div class="message <?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($_GET['msg'] ?? ''); ?>
                </div>
            <?php endif; ?>
            <?php if ($fetch_error): ?>
                <div class="message error"><p><?php echo htmlspecialchars($fetch_error); ?></p></div>
            <?php endif; ?>
        </div>

        <!-- Active Articles Section -->
        <h2 class="section-title">Active Articles</h2>
        <div class="top-actions">
            <button id="show-add-form-button">
                <i class="fas fa-plus"></i> Add New Article
            </button>
            <span class="drag-info">
                <?php echo ($current_sort_col === 'order_index' && $current_sort_dir === 'asc') ? 
                    'Drag rows to reorder.' : 
                    'Sort by custom order to enable drag-and-drop reordering.'; ?>
            </span>
        </div>

        <table id="active-articles-table">
            <thead>
                <tr>
                    <!-- Use helper function to generate sortable headers -->
                    <th style="width: 5%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('articleID', 'ID', $current_sort_col, $current_sort_dir); ?>">ID<?php echo ($current_sort_col === 'articleID') ? $arrow : ''; ?></a></th>
                    <th style="width: 10%;">Customer name</th> <!-- Not sorting VARCHAR price -->
                    <th style="width: 15%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('customer_article_no', 'Customer No', $current_sort_col, $current_sort_dir); ?>">Customer No<?php echo ($current_sort_col === 'customer_article_no') ? $arrow : ''; ?></a></th>
                    <th style="width: 15%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('system_article_no', 'System No', $current_sort_col, $current_sort_dir); ?>">System No<?php echo ($current_sort_col === 'system_article_no') ? $arrow : ''; ?></a></th>
                    <th style="width: 10%;">Price</th> <!-- Not sorting VARCHAR price -->
                    <th style="width: 10%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('added_by', 'Added By', $current_sort_col, $current_sort_dir); ?>">Added By<?php echo ($current_sort_col === 'added_by') ? $arrow : ''; ?></a></th>
                    <th style="width: 12%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('added_date', 'Added Date', $current_sort_col, $current_sort_dir); ?>">Added Date<?php echo ($current_sort_col === 'added_date') ? $arrow : ''; ?></a></th>
                    <th style="width: 10%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('edited_by', 'Last Edit By', $current_sort_col, $current_sort_dir); ?>">Last Edit By<?php echo ($current_sort_col === 'edited_by') ? $arrow : ''; ?></a></th>
                    <th style="width: 12%;" class="sortable"><a class="link-white" href="<?php echo getSortLink('edit_date', 'Last Edit Date', $current_sort_col, $current_sort_dir); ?>">Last Edit Date<?php echo ($current_sort_col === 'edit_date') ? $arrow : ''; ?></a></th>
                    <th style="width: 11%;">Actions</th>
                </tr>
            </thead>
            <!-- ID for jQuery UI Sortable target -->
            <tbody id="active-articles-sortable-body">
                <!-- Inline Add Row - Should NOT be sortable -->
                <tr id="add-article-row" style="display: none;">
                    <td>New</td>
                    <td>
                        <input type="text" id="inline-customer-name" placeholder="Customer name" required>
                        <span class="inline-error-message" id="inline-customer-error"></span>
                    </td>
                    <td class="suggestions-container">
                        <input type="text" id="inline-cust-no" placeholder="Customer No" required autocomplete="off">
                        <input type="hidden" id="inline-parent-id"> <!-- Hidden input for parent ID -->
                        <ul class="suggestions-list inline-suggestions-list"></ul>
                        <span class="parent-info" id="inline-parent-info"></span>
                        <span class="inline-error-message" id="inline-cust-no-error"></span>
                    </td>
                    <td>
                        <input type="text" id="inline-sys-no" placeholder="System No" required>
                        <span class="inline-error-message" id="inline-sys-no-error"></span>
                    </td>
                    <td>
                        <input type="text" id="inline-price" placeholder="Price" required>
                        <span class="inline-error-message" id="inline-price-error"></span>
                    </td>
                    <td colspan="4"> <!-- Span across added/edit columns -->
                         <select id="inline-status">
                             <option value="1" selected>Active</option>
                             <option value="0">Inactive</option>
                         </select>
                          <span class="inline-error-message" id="inline-status-error"></span>
                    </td>
                    <td class="inline-actions">
                        <button id="save-inline-article" class="button-like" style="background-color:#5cb85c;">Save</button>
                        <button id="cancel-inline-article" class="button-like" style="background-color:#d9534f;">Cancel</button>
                        <span class="inline-error-message" id="inline-general-error"></span>
                    </td>
                </tr>

                <!-- Existing Active Articles Loop -->
                <?php if (empty($active_articles) && !$fetch_error): ?>
                    <tr id="no-active-articles-row"><td colspan="9" class="no-articles">No active articles found.</td></tr>
                <?php elseif (!empty($active_articles)): ?>
                    <?php foreach ($active_articles as $article): ?>
                    <tr data-article-id="<?php echo $article['articleID']; ?>"> <?php // Add data attribute for sorting ?>
                        <td><?php echo htmlspecialchars($article['articleID']); ?></td>
                        <td><?php echo htmlspecialchars($article['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($article['customer_article_no']); ?><?php if($article['parent_article_id']) echo ' <em style="font-size:0.8em; color:grey;">(Child of '.$article['parent_article_id'].')</em>'; ?></td>
                        <td><?php echo htmlspecialchars($article['system_article_no']); ?></td>
                        <td><?php echo htmlspecialchars($article['price']); ?></td>
                        <td><?php echo htmlspecialchars($article['added_by_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $article['added_date'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($article['added_date']))) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($article['edited_by_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $article['edit_date'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($article['edit_date']))) : 'N/A'; ?></td>
                        <td class="action-links">
                            <a href="article_edit.php?id=<?php echo $article['articleID']; ?>" class="edit" title="Edit">Edit</a>
                            <a href="article_set_status.php?id=<?php echo $article['articleID']; ?>&action=deactivate" class="deactivate" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this article?');">Deactivate</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ===================== Inactive Articles Section ===================== -->
        <h3 class="section-title">Inactive Articles</h3>
         <table id="inactive-articles-table">
             <thead>
                  <tr>
                    <!-- Can add sortable links here too if desired, using the same helper function -->
                    <th style="width: 5%;">ID</th><th style="width: 15%;">Customer Name</th><th style="width: 15%;">Customer No</th><th style="width: 15%;">System No</th>
                    <th style="width: 10%;">Price</th><th style="width: 10%;">Added By</th><th style="width: 12%;">Added Date</th>
                    <th style="width: 10%;">Last Edit By</th><th style="width: 12%;">Last Edit Date</th><th style="width: 11%;">Actions</th>
                </tr>
             </thead>
             <tbody>
                  <?php if (empty($inactive_articles) && !$fetch_error): ?>
                     <tr id="no-inactive-articles-row"><td colspan="9" class="no-articles">No inactive articles found.</td></tr>
                 <?php elseif (!empty($inactive_articles)): ?>
                     <?php foreach ($inactive_articles as $article): ?>
                     <tr>
                         <td><?php echo htmlspecialchars($article['articleID']); ?></td>
                         <td><?php echo htmlspecialchars($article['customer_name']); ?></td>
                         <td><?php echo htmlspecialchars($article['customer_article_no']); ?><?php if($article['parent_article_id']) echo ' <em style="font-size:0.8em; color:grey;">(Child of '.$article['parent_article_id'].')</em>'; ?></td>
                         <td><?php echo htmlspecialchars($article['system_article_no']); ?></td>
                         <td><?php echo htmlspecialchars($article['price']); ?></td>
                         <td><?php echo htmlspecialchars($article['added_by_name'] ?? 'N/A'); ?></td>
                         <td><?php echo $article['added_date'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($article['added_date']))) : 'N/A'; ?></td>
                         <td><?php echo htmlspecialchars($article['edited_by_name'] ?? 'N/A'); ?></td>
                         <td><?php echo $article['edit_date'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($article['edit_date']))) : 'N/A'; ?></td>
                          <td class="action-links">
                             <a href="article_edit.php?id=<?php echo $article['articleID']; ?>" class="edit" title="Edit">Edit</a>
                             <a href="article_set_status.php?id=<?php echo $article['articleID']; ?>&action=activate" class="activate" title="Activate" onclick="return confirm('Are you sure you want to reactivate this article?');">Activate</a>
                          </td>
                     </tr>
                     <?php endforeach; ?>
                 <?php endif; ?>
             </tbody>
         </table>

    </div> <!-- /container -->

    <!-- Include jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        $(document).ready(function() {

            // --- Cache Selectors ---
            const addFormRow = $('#add-article-row');
            const showButton = $('#show-add-form-button');
            const saveButton = $('#save-inline-article');
            const cancelButton = $('#cancel-inline-article');
            const activeTableBody = $('#active-articles-table tbody'); // Cache the specific tbody
            const activeSortableBody = $('#active-articles-sortable-body'); // Alias for clarity
            const globalMsgArea = $('#global-message-area');
            const savingIndicator = $('#saving-indicator');
            const inlineCustNoInput = $('#inline-cust-no');
            const inlineParentIdInput = $('#inline-parent-id');
            const inlineParentInfo = $('#inline-parent-info');
            const inlineSuggestionsList = addFormRow.find('.inline-suggestions-list');
            const currentSortCol = "<?php echo $current_sort_col; // Pass current sort col ?>";
            const currentSortDir = "<?php echo $current_sort_dir; // Pass current sort dir ?>";


            // --- Helper: Clear Inline Form ---
            function clearInlineForm() {
                addFormRow.find('input[type="text"]').val('');
                inlineParentIdInput.val('');
                inlineParentInfo.text('');
                inlineCustNoInput.removeClass('has-parent');
                addFormRow.find('select').val('1');
                addFormRow.find('.inline-error-message').text('');
                inlineSuggestionsList.empty().hide();
            }

            // --- Helper: Display Global Message ---
             function showGlobalMessage(type, message) {
                const alertClass = (type === 'success') ? 'success' : 'error';
                const messageDiv = $(`<div class="message ${alertClass}" style="display: none;"></div>`).text(message);
                // Clear previous dynamic messages before adding new one
                globalMsgArea.find('.message.success, .message.error').not(':has(p)').remove(); // Remove messages not generated by PHP
                globalMsgArea.append(messageDiv); // Append new message
                messageDiv.fadeIn().delay(4000).fadeOut(function() { $(this).remove(); });
            }

            // --- Show/Hide Add Form Row ---
            showButton.on('click', function() {
                clearInlineForm();
                addFormRow.slideDown();
                $(this).hide();
                $('#no-active-articles-row').hide();
            });

            cancelButton.on('click', function() {
                addFormRow.slideUp(function() { clearInlineForm(); });
                showButton.show();
                if (activeTableBody.find('tr').not(addFormRow).length === 0) {
                    $('#no-active-articles-row').show();
                }
            });

            // --- Save Inline Article (AJAX) ---
            saveButton.on('click', function() {
                addFormRow.find('.inline-error-message').text('');
                let hasError = false;

                const custName = $('#inline-customer-name').val().trim();
                const custNo = inlineCustNoInput.val().trim();
                const sysNo = $('#inline-sys-no').val().trim();
                const price = $('#inline-price').val().trim();
                
                const status = $('#inline-status').val();
                const parentId = inlineParentIdInput.val() || null;

                if (!custName) { $('#inline-cust-name-error').text('Required'); hasError = true; }
                if (!custNo) { $('#inline-cust-no-error').text('Required'); hasError = true; }
                if (!sysNo) { $('#inline-sys-no-error').text('Required'); hasError = true; }
                if (!price) { $('#inline-price-error').text('Required'); hasError = true; }
                if (status === null || (status != '0' && status != '1')) { $('#inline-status-error').text('Invalid'); hasError = true; }
                if (hasError) return;

                saveButton.prop('disabled', true).text('Saving...');
                cancelButton.prop('disabled', true);

                $.ajax({
                    type: 'POST', url: 'article_inline_add.php',
                    data: { customer_name: custName,customer_article_no: custNo, system_article_no: sysNo, price: price, status: status, parent_article_id: parentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.article) {
                            const newArticle = response.article;
                            const addedDate = newArticle.added_date ? new Date(newArticle.added_date).toLocaleString() : 'N/A';
                            const parentInfoHtml = newArticle.parent_article_id ? ` <em style="font-size:0.8em; color:grey;">(Child of ${$('<div>').text(newArticle.parent_article_id).html()})</em>` : '';

                            // Construct new row - IMPORTANT: Must have data-article-id for sorting
                            const newRowHtml = `
                                <tr data-article-id="${newArticle.articleID}">
                                    <td>${$('<div>').text(newArticle.articleID).html()}</td>
                                    <td>${$('<div>').text(newArticle.customer_name).html()}</td>
                                    <td>${$('<div>').text(newArticle.customer_article_no).html()}${parentInfoHtml}</td>
                                    <td>${$('<div>').text(newArticle.system_article_no).html()}</td>
                                    
                                    <td>${$('<div>').text(newArticle.price).html()}</td>
                                    <td>${$('<div>').text(newArticle.added_by_name || 'N/A').html()}</td>
                                    <td>${$('<div>').text(addedDate).html()}</td>
                                    <td>N/A</td><td>N/A</td>
                                    <td class="action-links">
                                        <a href="article_edit.php?id=${newArticle.articleID}" class="edit" title="Edit">Edit</a>
                                        <a href="article_set_status.php?id=${newArticle.articleID}&action=deactivate" class="deactivate" title="Deactivate" onclick="return confirm('Are you sure?');">Deactivate</a>
                                    </td>
                                </tr>`;

                             // Prepend or append based on current sort? For simplicity, always prepend.
                             activeTableBody.prepend(newRowHtml);

                             $('#no-active-articles-row').hide();
                             addFormRow.slideUp(function() { clearInlineForm(); });
                             showButton.show();
                             showGlobalMessage('success', 'Article added successfully!');
                             // Re-enable sorting if it was disabled
                             if (currentSortCol === 'order_index' && currentSortDir === 'asc') {
                                activeSortableBody.sortable('enable');
                             }


                        } else {
                            const errorMsg = response.errors && response.errors.length > 0 ? response.errors.join(' ') : 'Failed to add article.';
                            $('#inline-general-error').text(errorMsg);
                        }
                    },
                    error: function(jqXHR) {
                        console.error("AJAX Error saving article:", jqXHR.status, jqXHR.responseText);
                        let errorMsg = 'An unexpected error occurred saving the article.';
                         try { const jsonResp = JSON.parse(jqXHR.responseText); if (jsonResp.errors) { errorMsg = jsonResp.errors.join(' '); } } catch(e) {}
                         if (jqXHR.status === 403) errorMsg = 'Authentication error.';
                        $('#inline-general-error').text(errorMsg);
                    },
                    complete: function() {
                        saveButton.prop('disabled', false).text('Save');
                        cancelButton.prop('disabled', false);
                    }
                });
            });

            // --- Inline Suggestions Logic ---
            let inlineDebounceTimeout;
            const fetchInlineSuggestions = async (term) => {
                 if (term.length < 1) { inlineSuggestionsList.empty().hide(); return; }
                 try {
                     const response = await fetch(`suggest_article.php?term=${encodeURIComponent(term)}`);
                     if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                     const suggestions = await response.json();

                     inlineSuggestionsList.empty();
                     if (suggestions && Array.isArray(suggestions) && suggestions.length > 0) {
                         suggestions.forEach(suggestion => {
                             const li = $('<li></li>')
                                 .text(suggestion.customer_article_no)
                                 .data('parentId', suggestion.articleID)
                                 .on('click', function() {
                                     inlineCustNoInput.val($(this).text());
                                     inlineParentIdInput.val($(this).data('parentId'));
                                     inlineParentInfo.text(`Linked to Parent ID: ${$(this).data('parentId')}`);
                                     inlineSuggestionsList.empty().hide();
                                 });
                             inlineSuggestionsList.append(li);
                         });
                         inlineSuggestionsList.show();
                     } else { inlineSuggestionsList.hide(); }
                 } catch (error) { console.error('Error fetching inline suggestions:', error); inlineSuggestionsList.empty().hide(); }
             };

            inlineCustNoInput.on('input', function() {
                 inlineParentIdInput.val(''); inlineParentInfo.text('');
                 clearTimeout(inlineDebounceTimeout);
                 const searchTerm = $(this).val();
                 if(searchTerm.length > 0) {
                    inlineDebounceTimeout = setTimeout(() => { fetchInlineSuggestions(searchTerm); }, 300);
                 } else { inlineSuggestionsList.empty().hide(); }
            });
            $(document).on('click', function(e) { if (!inlineCustNoInput.is(e.target) && !inlineSuggestionsList.is(e.target) && inlineSuggestionsList.has(e.target).length === 0 ) { inlineSuggestionsList.empty().hide(); } });
            inlineCustNoInput.on('keydown', function(e) { if (e.key === 'Escape') { inlineSuggestionsList.empty().hide(); } });

            // --- Drag and Drop Reordering ---
            // Only enable sorting if the current sort is by order_index ASC
             const isSortableEnabled = (currentSortCol === 'order_index' && currentSortDir === 'asc');

             activeSortableBody.sortable({
                items: "tr:not(#add-article-row)", // Exclude add row
                placeholder: "sortable-placeholder",
                helper: 'clone', axis: 'y', opacity: 0.7, cursor: 'move', tolerance: 'pointer',
                disabled: !isSortableEnabled, // Disable if not sorted correctly
                start: function(event, ui) {
                    ui.placeholder.height(ui.item.height());
                },
                update: function(event, ui) {
                    savingIndicator.fadeIn();
                    const orderedArticleIds = activeSortableBody.sortable('toArray', { attribute: 'data-article-id' });

                    $.ajax({
                        type: 'POST', url: 'update_article_order.php',
                        data: { ordered_ids: orderedArticleIds },
                        dataType: 'json',
                        success: function(response) {
                            if (!response.success) {
                                showGlobalMessage('error', 'Error saving order: ' + (response.errors ? response.errors.join(' ') : 'Unknown error.'));
                                console.error("Error saving order:", response.errors);
                                // Revert visual order on failure
                                $(this).sortable('cancel');
                            } else {
                                // Optional: Subtle success feedback if needed, but often just hiding spinner is enough
                                // showGlobalMessage('success', 'Order saved.');
                            }
                        },
                        error: function(jqXHR) {
                            console.error("AJAX Error saving order:", jqXHR.status, jqXHR.responseText);
                            showGlobalMessage('error', 'Network or server error saving order. Please try again.');
                            $(this).sortable('cancel'); // Revert on network error
                        },
                        complete: function() {
                             savingIndicator.fadeOut();
                        }
                    });
                }
            });

            // Prevent text selection during drag
            activeSortableBody.disableSelection();

        }); // end document ready
    </script>

</body>
</html>