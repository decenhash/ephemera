<?php
// --- PHP SERVER-SIDE LOGIC ---

$message = ''; // To store success/error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Get and validate inputs
    $username = $_POST['username'] ?? '';
    $comment = $_POST['comment'] ?? '';
    $file = $_POST['file'] ?? '';

    // Security: Enforce strict alphanumeric-only and length rules on the server
    if (!preg_match('/^[a-zA-Z0-9]{1,50}$/', $username)) {
        $message = 'Error: Username must be 1-50 letters and numbers only.';
    } elseif (!preg_match('/^[a-zA-Z0-9 ]{1,500}$/', $comment)) {
        // Allowing spaces for comments, but no other special chars
        $message = 'Error: Comment must be 1-500 letters, numbers, and spaces only.';
    } elseif (!preg_match('/^[a-zA-Z0-9]{1,200}$/', $file)) {
        $message = 'Error: File must be 1-200 letters and numbers only.';
    } else {
        // 2. All inputs are valid, proceed with file logic
        $dir = 'HTML_comment';
        
        // Create directory if it doesn't exist
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Security: Use basename() to prevent path traversal (e.g., '../')
        // This is redundant with our regex, but it's good practice (defense-in-depth)
        $safe_filename = basename($file) . '.html';
        $base_filepath = $dir . '/' . $safe_filename;
        $target_filepath = $base_filepath;

        // 3. Check file size logic
        if (file_exists($base_filepath)) {
            // Check file size (200kb = 200 * 1024 bytes)
            if (filesize($base_filepath) > (200 * 1024)) {
                $date_suffix = date('dmY'); // Format: 25122025
                $target_filepath = $dir . '/' . basename($file) . $date_suffix . '.html';
                $message = 'Note: Original file exceeded 200kb. Saved to new file with date suffix.';
            }
        }

        // 4. Format and save the content
        
        // Security: Sanitize output with htmlspecialchars() to prevent Stored XSS
        // This converts <script> to &lt;script&gt;, making it harmless
        $safe_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safe_comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

        $content_to_write = $safe_username . ' : ' . $safe_comment . ' <br><hr>' . PHP_EOL;

        // Use fopen with 'a' (append) mode
        $file_handle = fopen($target_filepath, 'a');
        if ($file_handle) {
            fwrite($file_handle, $content_to_write);
            fclose($file_handle);
            if (empty($message)) {
                $message = 'Success: Your comment has been saved!';
            }
        } else {
            $message = 'Error: Could not open or write to the file.';
        }
    }
}

// --- PHP logic for AJAX fetch ---
// This part handles the live-loading GET request from JavaScript
if (isset($_GET['loadfile'])) {
    $file_to_load = $_GET['loadfile'];
    
    // Security: Apply the same strict validation for loading
    if (preg_match('/^[a-zA-Z0-9]{1,200}$/', $file_to_load)) {
        $dir = 'HTML_comment';
        $safe_filename = basename($file_to_load) . '.html'; // Use basename for safety
        $filepath = $dir . '/' . $safe_filename;

        if (file_exists($filepath)) {
            // Read and output the file content
            echo file_get_contents($filepath);
        } else {
            // File not found, but the filename is valid
            echo '<p class="text-gray-500 italic">File not found. A new file will be created on submit.</p>';
        }
    } else {
        // Invalid filename format
        echo '<p class="text-red-500">Invalid file name format.</p>';
    }
    exit; // Stop script execution to only return the file content
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-Code" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Saver</title>
    <!-- Load Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
        }
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        input, textarea {
            transition: all 0.3s ease;
        }
        input:focus, textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .preview-box {
            background-color: #fafafa;
            border: 1px dashed #d1d5db;
            min-height: 150px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="antialiased">

    <div class="container">
        
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Comment System</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Left Side: Form -->
            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Add a New Comment</h2>
                
                <!-- Form posts to the same file -->
                <form id="commentForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    
                    <!-- Server Message -->
                    <?php if (!empty($message)): ?>
                        <div id="serverMessage" class="p-3 rounded-md mb-4 <?php echo strpos($message, 'Error') === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Client-side Error Message -->
                    <div id="errorMessage" class="p-3 rounded-md mb-4 bg-red-100 text-red-700 hidden"></div>

                    <div class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="username" name="username" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none" maxlength="50" required>
                            <span class="text-xs text-gray-500">Letters/Numbers only, max 50.</span>
                        </div>
                        
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700">Comment</label>
                            <textarea id="comment" name="comment" rows="6" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none" maxlength="500" required></textarea>
                            <span class="text-xs text-gray-500">Letters/Numbers/Spaces only, max 500.</span>
                        </div>
                        
                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700">File Name or hash</label>
                            <input type="text" id="file" name="file" value="<?php if(isset($_GET['hash'])){echo $_GET['hash'];}?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none" maxlength="200" required>
                            <span class="text-xs text-gray-500">Letters/Numbers only, max 200.</span>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save Comment
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Side: Live Preview -->
            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">File Preview</h2>
                <div id="previewBox" class="preview-box p-4 rounded-md">
                    <p class="text-gray-500 italic">Start typing in the "File Name" field to see a preview.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- --- JAVASCRIPT CLIENT-SIDE LOGIC --- -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            const form = document.getElementById('commentForm');
            const usernameInput = document.getElementById('username');
            const commentInput = document.getElementById('comment');
            const fileInput = document.getElementById('file');
            const previewBox = document.getElementById('previewBox');
            const errorMessage = document.getElementById('errorMessage');
            
            // Validation Regex (matches user request: letters and numbers)
            // Note: We allow spaces in comments for readability.
            const validUsernameRegex = /^[a-zA-Z0-9]*$/;
            const validCommentRegex = /^[a-zA-Z0-9 ]*$/;
            const validFileRegex = /^[a-zA-Z0-9]*$/;

            // Helper function to show errors
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.classList.remove('hidden');
            }

            // Helper function to hide errors
            function hideError() {
                errorMessage.classList.add('hidden');
            }

            // 1. Client-Side Validation on Submit
            form.addEventListener('submit', function(event) {
                hideError();
                let isValid = true;

                // Check username
                if (!validUsernameRegex.test(usernameInput.value) || usernameInput.value.length === 0) {
                    showError('Username must contain only letters and numbers and cannot be empty.');
                    isValid = false;
                }
                
                // Check comment
                if (!validCommentRegex.test(commentInput.value) || commentInput.value.length === 0) {
                    if (isValid) showError('Comment must contain only letters, numbers, and spaces and cannot be empty.');
                    isValid = false;
                }
                
                // Check file
                if (!validFileRegex.test(fileInput.value) || fileInput.value.length === 0) {
                    if (isValid) showError('File name must contain only letters and numbers and cannot be empty.');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault(); // Stop form from submitting
                }
            });

            // 2. Live Loading content
            let fetchTimeout;
            fileInput.addEventListener('input', function() {
                // Clear any existing timer
                clearTimeout(fetchTimeout);

                const fileName = fileInput.value;
                
                // Validate filename format before fetching
                if (!validFileRegex.test(fileName)) {
                    previewBox.innerHTML = '<p class="text-red-500">File name can only contain letters and numbers.</p>';
                    return;
                }
                
                if (fileName.length === 0) {
                     previewBox.innerHTML = '<p class="text-gray-500 italic">Enter a file name to preview.</p>';
                    return;
                }

                // Debounce: Wait 300ms after user stops typing to fetch
                fetchTimeout = setTimeout(() => {
                    previewBox.innerHTML = '<p class="text-gray-500 italic">Loading...</p>';
                    
                    // Fetch content from the same PHP file, using a query parameter
                    fetch(`<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?loadfile=${fileName}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.text();
                        })
                        .then(data => {
                            // The data is already sanitized (HTML-safe) from the server
                            // so it's safe to set with innerHTML.
                            previewBox.innerHTML = data;
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            previewBox.innerHTML = '<p class="text-red-500">Error loading preview.</p>';
                        });
                }, 300);
            });

            // 3. Clear server message on new input to prevent confusion
            form.addEventListener('input', () => {
                const serverMsg = document.getElementById('serverMessage');
                if (serverMsg) {
                    serverMsg.style.display = 'none';
                }
            });

        });
    </script>

</body>
</html>