<?php
// article_add.php
require_once 'db_connect.php'; // Connects to DB, starts session, gets theme

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?auth_required=1");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Initialize variables ---
$errors = [];
$article = [
    'customer_article_no' => '',
    'system_article_no' => '',
    'price' => '',
    'status' => 1, // Default status (Changed from 0 based on your schema default for status seems to be 0, adjust if needed)
];

// --- Process Form Submission (Existing Code) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // ... (Keep your existing POST handling logic here) ...
     // 1. Retrieve and Sanitize Input (Trim whitespace)
     $article['customer_name'] = trim($_POST['customer_name'] ?? '');
    $article['customer_article_no'] = trim($_POST['customer_article_no'] ?? '');
    $article['system_article_no'] = trim($_POST['system_article_no'] ?? '');
    $article['price'] = trim($_POST['price'] ?? '');
    $article['status'] = isset($_POST['status']) ? (int)$_POST['status'] : 0;

    // 2. Basic Validation
    if (empty($article['customer_name'])) { $errors[] = "Customer name is required."; }
    if (empty($article['customer_article_no'])) { $errors[] = "Customer Article Number is required."; }
    if (empty($article['system_article_no'])) { $errors[] = "System Article Number is required."; }
    if (empty($article['price'])) { $errors[] = "Price is required."; }
    if (!in_array($article['status'], [0, 1])) { $errors[] = "Invalid status selected."; }

    // 3. If No Errors, Insert into Database
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO articles (customer_name, customer_article_no, system_article_no, price, added_by, added_date, status)
                    VALUES (:cust_name,:cust_no, :sys_no, :price, :added_by, NOW(), :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cust_name', $article['customer_name'], PDO::PARAM_STR);
            $stmt->bindParam(':cust_no', $article['customer_article_no'], PDO::PARAM_STR);
            $stmt->bindParam(':sys_no', $article['system_article_no'], PDO::PARAM_STR);
            $stmt->bindParam(':price', $article['price'], PDO::PARAM_STR);
            $stmt->bindParam(':added_by', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $article['status'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                header("Location: articles_list.php?status=success&msg=Article added successfully.");
                exit();
            } else { $errors[] = "Failed to add article."; }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { $errors[] = "Error: An article with this number might already exist."; }
            else { $errors[] = "Database error: " . $e->getMessage(); }
            error_log("Article Add Error: " . $e->getMessage());
        }
    }
}
// --- End of Existing POST Handling Logic ---


// --- Prepare variables for the form template ---
$form_action = 'article_add.php';
$page_title = 'Add New Article';
$submit_button_text = 'Add Article';
// $article array is already populated with defaults or submitted values

// Get theme class for the body tag
$theme_class = ($_SESSION['user_theme'] === 'dark') ? 'dark-theme' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css"> <!-- Base styles -->
    <link rel="stylesheet" href="theme.css"> <!-- Theme overrides -->
    <style>
        /* --- Styles for Suggestions --- */
        .suggestions-container {
            position: relative; /* Needed for absolute positioning of list */
        }
        .suggestions-list {
            position: absolute;
            border: 1px solid #ccc;
            border-top: none;
            z-index: 99;
            /*position the autocomplete items to be the same width as the container:*/
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            max-height: 150px; /* Limit height */
            overflow-y: auto; /* Add scroll if needed */
            display: none; /* Initially hidden */
            list-style: none; /* Remove default list styling */
            padding: 0;
            margin: 0;
        }
        .suggestions-list li {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
         .suggestions-list li:last-child {
            border-bottom: none;
        }
        .suggestions-list li:hover {
            background-color: #e9e9e9;
        }
         /* --- Dark Theme Adjustments --- */
        body.dark-theme .suggestions-list {
            background-color: #444;
            border-color: #666;
        }
        body.dark-theme .suggestions-list li {
            border-bottom-color: #555;
        }
        body.dark-theme .suggestions-list li:hover {
            background-color: #555;
        }
        /* --- End Suggestion Styles --- */
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
    <div class="container">
        <!-- Optional: Add consistent navigation like in dashboard -->
        <!-- <div class="nav-links"> ... </div> -->

        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        <p><a href="articles_list.php">« Back to List</a></p>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php // --- Modified Form Include --- ?>
        <form action="<?php echo htmlspecialchars($form_action); ?>" method="post" novalidate>

            <div class="form-group suggestions-container"> <?php // Wrap input and list ?>
                <label for="customer_name">Customer Name:</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($article['customer_name'] ?? ''); ?>" required autocomplete="off">
                
            </div>
            <div class="form-group suggestions-container"> <?php // Wrap input and list ?>
                <label for="customer_article_no">Customer Article No:</label>
                <input type="text" id="customer_article_no" name="customer_article_no" value="<?php echo htmlspecialchars($article['customer_article_no'] ?? ''); ?>" required autocomplete="off"> <?php // autocomplete="off" is important ?>
                <ul id="suggestions-list" class="suggestions-list"></ul> <?php // Add suggestions list ?>
            </div>

            <div class="form-group">
                <label for="system_article_no">System Article No:</label>
                <input type="text" id="system_article_no" name="system_article_no" value="<?php echo htmlspecialchars($article['system_article_no'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($article['price'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="1" <?php echo ($article['status'] ?? 0) == 1 ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($article['status'] ?? 0) == 0 ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" name="submit"><?php echo htmlspecialchars($submit_button_text); ?></button>
            <a href="articles_list.php" style="display: block; text-align: center; margin-top: 15px;">Cancel</a>

        </form>


    </div>

    <script>
        const inputField = document.getElementById('customer_article_no');
        const suggestionsList = document.getElementById('suggestions-list');
        let debounceTimeout;

        // Function to fetch and display suggestions
        const fetchSuggestions = async (term) => {
            if (term.length < 1) { // Minimum characters to trigger search (adjust if needed)
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'none';
                return;
            }

            try {
                // Encode the term for the URL
                const encodedTerm = encodeURIComponent(term);
                const response = await fetch(`suggest_article.php?term=${encodedTerm}`);

                if (!response.ok) {
                    // Handle HTTP errors (like 403 Forbidden if not logged in, 500 Internal Server Error)
                    console.error(`Error fetching suggestions: ${response.status} ${response.statusText}`);
                    suggestionsList.innerHTML = '<li style="color: red; cursor: default;">Error loading suggestions</li>'; // User-friendly error
                    suggestionsList.style.display = 'block';
                    return;
                }

                const suggestions = await response.json();

                // Clear previous suggestions
                suggestionsList.innerHTML = '';

                if (suggestions.length > 0) {
                    suggestions.forEach(suggestion => {
                        const li = document.createElement('li');
                        li.textContent = suggestion;
                        // When a suggestion is clicked, fill the input and hide the list
                        li.addEventListener('click', () => {
                            inputField.value = suggestion;
                            suggestionsList.innerHTML = '';
                            suggestionsList.style.display = 'none';
                        });
                        suggestionsList.appendChild(li);
                    });
                    suggestionsList.style.display = 'block'; // Show the list
                } else {
                    // Optional: Show "No suggestions found" or just hide
                     // suggestionsList.innerHTML = '<li style="color: grey; cursor: default;">No matches found</li>';
                     // suggestionsList.style.display = 'block';
                     suggestionsList.style.display = 'none'; // Hide if no suggestions
                }

            } catch (error) {
                console.error('Error fetching suggestions:', error);
                suggestionsList.innerHTML = '<li style="color: red; cursor: default;">Error loading suggestions</li>';
                suggestionsList.style.display = 'block';
            }
        };

        // Event listener for input changes with debouncing
        inputField.addEventListener('input', (e) => {
            const searchTerm = e.target.value;

            // Clear the previous debounce timeout
            clearTimeout(debounceTimeout);

            // Set a new timeout to fetch suggestions after a delay (e.g., 300ms)
            debounceTimeout = setTimeout(() => {
                fetchSuggestions(searchTerm);
            }, 300); // Adjust delay as needed (ms)
        });

        // Hide suggestions when clicking outside the input/list
        document.addEventListener('click', (e) => {
            // If the click is not on the input field AND not inside the suggestions list
            if (e.target !== inputField && !suggestionsList.contains(e.target)) {
                 suggestionsList.innerHTML = '';
                 suggestionsList.style.display = 'none';
            }
        });

         // Optional: Hide on Escape key
         inputField.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                 suggestionsList.innerHTML = '';
                 suggestionsList.style.display = 'none';
            }
         });


    </script>

</body>
</html>