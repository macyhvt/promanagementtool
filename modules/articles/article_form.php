<?php
// article_form.php
// This file assumes certain variables are set before it's included:
// $form_action (e.g., 'article_add.php' or 'article_edit.php?id=...')
// $page_title (e.g., 'Add New Article' or 'Edit Article')
// $submit_button_text (e.g., 'Add Article' or 'Update Article')
// $article (array containing article data for editing, or empty/default for adding)
// $errors (array of validation errors)

// --- Ensure $article has default values if adding ---
$article = $article ?? [
    'articleID' => null, // Not needed for add if AUTO_INCREMENT
    'customer_article_no' => '',
    'system_article_no' => '',
    'price' => '',
    'status' => 0, // Default status from schema
];

?>
<form action="<?php echo htmlspecialchars($form_action); ?>" method="post" novalidate>
    <!-- Include articleID for editing -->
    <?php if (!empty($article['articleID'])): ?>
        <input type="hidden" name="articleID" value="<?php echo htmlspecialchars($article['articleID']); ?>">
    <?php endif; ?>

    <!-- **IMPORTANT NOTE on articleID for Adding **
         If your articleID is NOT AUTO_INCREMENT, you MUST add an input field here:
         <div class="form-group">
             <label for="articleID">Article ID:</label>
             <input type="number" id="articleID" name="articleID" value="<?php echo htmlspecialchars($article['articleID'] ?? ''); ?>" required>
         </div>
    -->

    <div class="form-group">
        <label for="customer_article_no">Customer Article No:</label>
        <input type="text" id="customer_article_no" name="customer_article_no" value="<?php echo htmlspecialchars($article['customer_article_no'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
        <label for="system_article_no">System Article No:</label>
        <input type="text" id="system_article_no" name="system_article_no" value="<?php echo htmlspecialchars($article['system_article_no'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
        <label for="price">Price:</label>
        <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($article['price'] ?? ''); ?>" required>
        <!-- Consider input type="number" step="0.01" if column is DECIMAL -->
    </div>

    <div class="form-group">
        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="1" <?php echo ($article['status'] ?? 0) == 1 ? 'selected' : ''; ?>>Active</option>
            <option value="0" <?php echo ($article['status'] ?? 0) == 0 ? 'selected' : ''; ?>>Inactive</option>
            <!-- Add other status options if applicable -->
        </select>
    </div>

    <button type="submit" name="submit"><?php echo htmlspecialchars($submit_button_text); ?></button>
    <a href="articles_list.php" style="display: block; text-align: center; margin-top: 15px;">Cancel</a>

</form>