<?php
// Assume $order contains existing data for edit, or defaults for add
// Assume $is_edit is a boolean indicating if we are editing
$order = $order ?? [];
$is_edit = $is_edit ?? false;

// Define ENUM options explicitly for clarity
$order_type_options = ['F', 'C', 'N'];
// Get ENUM values for Status directly from DB (better) or hardcode
$status_options = ['Framework', 'Scheduled', 'Delivered', 'Delayed']; // Add ALL valid options from your schema

// Helper to safely get array values
function val($arr, $key, $default = '') {
    return htmlspecialchars($arr[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
// Helper for date formatting for input type="date"
function dateForInput($dateString) {
     if (empty($dateString) || $dateString === '0000-00-00 00:00:00') return '';
    try { return date('Y-m-d', strtotime($dateString)); } catch (Exception $e) { return ''; }
}
?>

<form action="<?php echo htmlspecialchars($form_action); ?>" method="post" id="order-form" novalidate>
    <?php if ($is_edit): ?>
        <input type="hidden" name="orderID" value="<?php echo val($order, 'orderID'); ?>">
    <?php endif; ?>

    <fieldset style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccc;">
        <legend>Order Details</legend>

        <div class="form-group">
            <label for="customer_name">Customer Name:</label>
            <input type="text" id="customer_name" name="customer_name" value="<?php echo val($order, 'customer_name'); ?>" maxlength="200">
        </div>

        <div class="form-group">
            <label for="order_type">Order Type:</label>
            <select id="order_type" name="order_type">
                <?php foreach($order_type_options as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo (val($order, 'order_type', 'F') == $type) ? 'selected' : ''; ?>>
                        <?php echo $type; // Could map to 'Framework', 'Call', 'Normal' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status:</label>
             <select id="status" name="status">
                 <?php foreach($status_options as $stat): ?>
                    <option value="<?php echo $stat; ?>" <?php echo (val($order, 'status', 'Framework') == $stat) ? 'selected' : ''; ?>>
                        <?php echo $stat; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="project_no">Project Number:</label>
            <input type="text" id="project_no" name="project_no" value="<?php echo val($order, 'project_no'); ?>" maxlength="200">
        </div>
    </fieldset>

     <fieldset style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccc;">
        <legend>Framework Details (If Applicable)</legend>
        <div class="form-group">
            <label for="framework_order_no">Framework Order No:</label>
            <input type="text" id="framework_order_no" name="framework_order_no" value="<?php echo val($order, 'framework_order_no'); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label for="framework_order_position">Framework Order Position:</label>
            <input type="text" id="framework_order_position" name="framework_order_position" value="<?php echo val($order, 'framework_order_position'); ?>" maxlength="30">
        </div>
         <div class="form-group">
            <label for="framework_quantity">Framework Quantity:</label>
            <input type="text" id="framework_quantity" name="framework_quantity" value="<?php echo val($order, 'framework_quantity'); ?>" maxlength="30">
             <small>(Note: Should be numeric)</small>
        </div>
    </fieldset>

    <fieldset style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccc;">
        <legend>Article & Price</legend>
        <div class="form-group">
            <label for="customer_article_no">Customer Article No:</label>
            <select id="customer_article_no_select" onchange="handleArticleSelect(this.value, this.options[this.selectedIndex].getAttribute('data-system-no'), this.options[this.selectedIndex].getAttribute('data-price'))">
                <option value="">-- Select Existing Article --</option>
                <?php
                try {
                    $sql = "SELECT a.customer_article_no, a.system_article_no, a.parent_article_id, a.price,
                           p.customer_article_no as parent_customer_no
                           FROM articles a
                           LEFT JOIN articles p ON a.parent_article_id = p.articleID
                           WHERE a.status = 1 
                           ORDER BY a.customer_article_no ASC";
                    $stmt = $pdo->query($sql);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $selected = (val($order, 'customer_article_no') == $row['customer_article_no']) ? 'selected' : '';
                        $parent_info = $row['parent_article_id'] ? " (Child of: " . htmlspecialchars($row['parent_customer_no']) . ")" : "";
                        echo "<option value='" . htmlspecialchars($row['customer_article_no']) . "' 
                              data-system-no='" . htmlspecialchars($row['system_article_no']) . "'
                              data-price='" . htmlspecialchars($row['price']) . "' {$selected}>" . 
                             htmlspecialchars($row['customer_article_no']) . $parent_info . 
                             " - System: " . htmlspecialchars($row['system_article_no']) . 
                             " - Price: " . htmlspecialchars($row['price']) . "</option>";
                    }
                } catch (PDOException $e) {
                    error_log("Error fetching articles: " . $e->getMessage());
                }
                ?>
            </select>
            <input type="text" id="customer_article_no" name="customer_article_no" value="<?php echo val($order, 'customer_article_no'); ?>" maxlength="100" placeholder="Or enter custom article number">
        </div>

        <div class="form-group">
            <label for="system_article_no">System Article No:</label>
            <input type="text" id="system_article_no" name="system_article_no" value="<?php echo val($order, 'system_article_no'); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label for="price_article">Article Price:</label>
            <input type="text" id="price_article" name="price_article" value="<?php echo val($order, 'price_article'); ?>" maxlength="30">
            <small>(Note: Should be numeric/decimal)</small>
        </div>
        <div class="form-group">
            <label for="stock_price">Stock Price:</label>
            <input type="text" id="stock_price" name="stock_price" value="<?php echo val($order, 'stock_price'); ?>" maxlength="30">
            <small>(Note: Should be numeric/decimal)</small>
        </div>
    </fieldset>

    <fieldset style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccc;">
        <legend>Dates</legend>
        <div class="form-group">
            <label for="request_date">Request Date:</label>
            <input type="date" id="request_date" name="request_date" value="<?php echo dateForInput(val($order, 'request_date')); ?>">
        </div>
         <div class="form-group">
            <label for="confirmed_date">Confirmed Date:</label>
            <input type="date" id="confirmed_date" name="confirmed_date" value="<?php echo dateForInput(val($order, 'confirmed_date')); ?>">
        </div>
         <div class="form-group">
            <label for="delivery_date">Delivery Date:</label>
            <input type="date" id="delivery_date" name="delivery_date" value="<?php echo dateForInput(val($order, 'delivery_date')); ?>">
        </div>
         <div class="form-group">
            <label for="delivery_year">Delivery Year:</label>
             <input type="text" id="delivery_year" name="delivery_year" value="<?php echo dateForInput(val($order, 'delivery_year')); ?>" placeholder="YYYY-MM-DD">
              <small>(Note: Schema says DATETIME? Using date input)</small>
        </div>
        <div class="form-group">
            <label for="delivery_month">Delivery Month:</label>
            <input type="text" id="delivery_month" name="delivery_month" value="<?php echo dateForInput(val($order, 'delivery_month')); ?>" placeholder="YYYY-MM-DD">
             <small>(Note: Schema says DATETIME? Using date input)</small>
        </div>
    </fieldset>

     <fieldset style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccc;">
        <legend>Quantities & Calculated</legend>
         <div class="form-group">
            <label for="called_quantity">Called Quantity:</label>
            <input type="text" id="called_quantity" name="called_quantity" value="<?php echo val($order, 'called_quantity'); ?>" maxlength="30">
             <small>(Note: Should be numeric)</small>
        </div>
        <div class="form-group">
            <label for="uncalled_quantity">Uncalled Quantity:</label>
            <input type="text" id="uncalled_quantity" name="uncalled_quantity" value="<?php echo val($order, 'uncalled_quantity'); ?>" maxlength="30">
            <small>(Note: Should be numeric)</small>
        </div>
         <div class="form-group">
            <label for="delivered_quantity">Delivered Quantity:</label>
            <input type="text" id="delivered_quantity" name="delivered_quantity" value="<?php echo val($order, 'delivered_quantity'); ?>" maxlength="30">
             <small>(Usually calculated)</small>
        </div>
         <div class="form-group">
            <label for="remaining_quantity">Remaining Quantity:</label>
            <input type="text" id="remaining_quantity" name="remaining_quantity" value="<?php echo val($order, 'remaining_quantity'); ?>" maxlength="30">
             <small>(Usually calculated)</small>
        </div>
        <div class="form-group">
            <label for="need_to_pro_quantity">Need-to-Produce Quantity:</label>
            <input type="text" id="need_to_pro_quantity" name="need_to_pro_quantity" value="<?php echo val($order, 'need_to_pro_quantity'); ?>" maxlength="30">
            <small>(Usually calculated)</small>
        </div>
        <div class="form-group">
            <label for="total_price">Total Price:</label>
            <input type="text" id="total_price" name="total_price" value="<?php echo val($order, 'total_price'); ?>" maxlength="30">
             <small>(Usually calculated)</small>
        </div>
         <div class="form-group">
            <label for="uncalled_quantity_price">Uncalled Quantity Price:</label>
            <input type="text" id="uncalled_quantity_price" name="uncalled_quantity_price" value="<?php echo val($order, 'uncalled_quantity_price'); ?>" maxlength="30">
             <small>(Usually calculated)</small>
        </div>
        <div class="form-group">
            <label for="remaining">Remaining (?):</label>
             <input type="text" id="remaining" name="remaining" value="<?php echo val($order, 'remaining'); ?>" maxlength="20">
              <small>(Unclear field meaning)</small>
        </div>
    </fieldset>

    <fieldset style="margin-bottom: 15px; padding: 15px; border: 1px solid #ccc;">
        <legend>Notes</legend>
         <div class="form-group">
            <label for="note">Note:</label>
            <textarea id="note" name="note" rows="5" style="width: 95%;"><?php echo htmlspecialchars($order['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
    </fieldset>

    <!-- Submit Button -->
    <button type="submit" name="submit"><?php echo htmlspecialchars($submit_text); ?></button>
    <a href="orders_list.php" style="margin-left: 15px;">Cancel</a>
</form>

<script>
function handleArticleSelect(customerNo, systemNo, price) {
    const customerInput = document.getElementById('customer_article_no');
    const systemInput = document.getElementById('system_article_no');
    const priceInput = document.getElementById('price_article');
    
    if (customerNo) {
        customerInput.value = customerNo;
        if (systemNo) {
            systemInput.value = systemNo;
        }
        if (price) {
            priceInput.value = price;
        }
    }
}
</script>