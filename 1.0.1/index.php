<?php
// Ensure json and files folders exist
if (!is_dir("json")) {
    mkdir("json", 0777, true);
}
if (!is_dir("files")) {
    mkdir("files", 0777, true);
}

$message = "";
$linkToShow = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include ("ip_tmp.php");

    $url = trim($_POST['url'] ?? '');

    if (empty($url)) {
        $message = "Error: The 'url' field is mandatory.";
    } else {
        $fileUploaded = false;
        $uploadedSize = "";
        $uploadedType = "";
        $uploadedTitle = "";
        $fileHash = "";
        $jsonFilename = "";

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileSize = $_FILES['file']['size'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileSize > 10 * 1024 * 1024) {
                $message = "Error: File too large (max 10MB).";
            } elseif ($fileExt === "php") {
                $message = "Error: PHP files are not allowed.";
            } else {
                $fileHash = hash_file("sha256", $fileTmp);
                $newFileName = $fileHash . "." . $fileExt;
                $filePath = "files/" . $newFileName;

                if (file_exists($filePath)) {
                    $message = "Error: This file already exists.";
                } else {
                    if (move_uploaded_file($fileTmp, $filePath)) {
                        $fileUploaded = true;
                        $uploadedSize = $fileSize;
                        $uploadedType = $fileExt;
                        $uploadedTitle = pathinfo($fileName, PATHINFO_FILENAME);
                        $message = "Success: File uploaded successfully!";
                    } else {
                        $message = "Error: Failed to move uploaded file.";
                    }
                }
            }
        }

        if (strpos($message, "Error:") === false) {
            if ($fileUploaded) {
                $jsonFilename = "json/" . $fileHash . ".json";
            } else {
                $jsonFilename = "json/" . hash("sha256", $url) . ".json";
            }

            if (file_exists($jsonFilename)) {
                $message = "Error: This entry already exists.";
            } else {
                $titleInput = trim($_POST['title'] ?? "");

                $maxLength = 300;

                $user =  substr($_POST['user'], 0, $maxLength);
                $titleInput =  substr($titleInput, 0, $maxLength);
                $titleInput = strtolower($titleInput);
                $description =  substr($_POST['description'], 0, $maxLength);
                $url =  substr($url, 0, 1000);
                $category =  substr($_POST['category'], 0, $maxLength);
                $PIX =  substr($_POST['PIX'], 0, $maxLength);
                $SOL =  substr($_POST['SOL'], 0, $maxLength);
                $PAYPAL =  substr($_POST['PAYPAL'], 0, $maxLength); 
                $BTC = substr($_POST['BTC'], 0, $maxLength);

                $data = [
                    "user"        => $user,
                    "title"       => $titleInput !== "" ? $titleInput : ($fileUploaded ? $uploadedTitle : ""),
                    "description" => $description,
                    "date"        => date("Y-m-d H:i:s"),
                    "category"    => $category,
                    "size"        => $fileUploaded ? $uploadedSize : ($_POST['size'] ?? ""),
                    "type"        => $fileUploaded ? $uploadedType : ($_POST['type'] ?? ""),
                    "url"         => $url,
                    "PIX"         => $PIX,
                    "SOL"         => $SOL,
                    "PAYPAL"      => $PAYPAL,
                    "BTC"         => $BTC
                ];

                $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $fp = fopen($jsonFilename, "w");
                if ($fp) {
                    fwrite($fp, $jsonData);
                    fclose($fp);
                    
                    // Set the appropriate link based on whether a file was uploaded or not
                    if ($fileUploaded) {
                        $message .= " Data saved successfully!";
                        $linkToShow = "<a href=\"files/{$newFileName}\" target=\"_blank\">Open Uploaded File</a>";
                    } else {
                        $message = "Success: Data saved successfully (no file uploaded).";
                        $linkToShow = "<a href=\"{$jsonFilename}\" target=\"_blank\">Open JSON File</a>";
                    }

                    include ("json_search_generation.php");                    

                } else {
                    $message = "Error: Could not write JSON file.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="logo.jpg">
    <title>Decenhash File Sharing Sytem</title>
    <style>
        /* Minimalist CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Header with top menu */
        header {
            background-color: #fff;
            border-bottom: 1px solid #eaeaea;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-weight: 600;
            font-size: 1.25rem;
            color: #2e7d32;
        }

        nav {
            display: flex;
            gap: 1.5rem;
        }

        nav a {
            text-decoration: none;
            color: #555;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        nav a:hover {
            color: #2e7d32;
        }

        .top-right-image {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .top-right-image:hover {
            opacity: 0.8;
        }

        /* Main content layout */
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            main {
                grid-template-columns: 1fr;
            }
        }

        /* Form and info sections */
        .form-section, .info-section {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-section h3, .info-section h1 {
            margin-bottom: 1.5rem;
            color: #2e7d32;
        }

        .info-section h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Form elements */
        form label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #555;
        }

        form input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        form input:focus {
            outline: none;
            border-color: #2e7d32;
        }

        form button {
            width: 100%;
            padding: 0.75rem;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        form button:hover {
            background-color: #256628;
        }

        /* Message styling */
        .message {
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Info section content */
        .info-section p {
            margin-bottom: 1.5rem;
            color: #555;
        }

        .info-section ul {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-section li {
            margin-bottom: 0.75rem;
            color: #555;
        }

        .info-section code {
            background-color: #f5f5f5;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9rem;
        }

        /* Search section */
        .search-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eaeaea;
        }

        .search-section h3 {
            margin-bottom: 1rem;
            color: #2e7d32;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .search-button {
            width: 100%;
            padding: 0.75rem;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .search-button:hover {
            background-color: #256628;
        }

        .search-button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        /* Results section */
        .results-section {
            margin-top: 2rem;
        }

        .results-section h3 {
            margin-bottom: 1rem;
            color: #2e7d32;
        }

        .result-item {
            background: #f8f9fa;
            border: 1px solid #eaeaea;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .result-item:hover {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .result-link {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
            flex: 1;
            transition: color 0.2s;
        }

        .result-link:hover {
            color: #1b5e20;
            text-decoration: underline;
        }

        .info-button {
            background-color: #2196f3;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }

        .info-button:hover {
            background-color: #1976d2;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
            background: #f8f9fa;
            border-radius: 6px;
        }

        /* Account Purchase Section */
        .account-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eaeaea;
        }

        .account-section h3 {
            margin-bottom: 1.5rem;
            color: #2e7d32;
            text-align: center;
        }

        .account-tiers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .account-tier {
            background: #f8f9fa;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .account-tier:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .account-tier.junior {
            border-top: 4px solid #4caf50;
        }

        .account-tier.pro {
            border-top: 4px solid #2196f3;
        }

        .account-tier.master {
            border-top: 4px solid #ff9800;
        }

        .tier-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .tier-price {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2e7d32;
        }

        .tier-features {
            list-style: none;
            padding: 0;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .tier-features li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
            color: #555;
        }

        .tier-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4caf50;
            font-weight: bold;
        }

        .purchase-button {
            width: 100%;
            padding: 0.75rem;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .purchase-button:hover {
            background-color: #256628;
        }

        .payment-methods {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eaeaea;
        }

        .payment-methods h4 {
            margin-bottom: 1rem;
            color: #555;
        }

        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .payment-icon {
            padding: 0.5rem 1rem;
            background: #f5f5f5;
            border-radius: 4px;
            font-weight: 500;
            color: #555;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
            color: #777;
            font-size: 0.9rem;
            border-top: 1px solid #eaeaea;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">DECENHASH</div>
        <nav>
            <a href='index.php'>home</a> 
            <a href='rank_file.php'>file rank</a>
            <a href='rank_user.php'>user rank</a>
            <a href='search.php'>search</a>
        </nav>
        <!-- Replace with your actual image and link -->
        <a href="https://t.me/decenhash" target="_blank">
            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iNCIgZmlsbD0iIzJlN2QzMiIvPgo8cGF0aCBkPSJNMTYgMTBMMTAgMTZMMTYgMjJNMjIgMTZIMTBNMTYgMTBMMjIgMTZMMTYgMjJNMjIgMTZIMTBaIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4K" 
                 alt="External Link" 
                 class="top-right-image">
        </a>
    </header>

    <main>
        <section class="info-section">
            <h1>DECENHASH</h1>
            
            <div class="search-section">
                <h3>Quick Search</h3>
                <input type="text" class="search-input" placeholder="Enter at least 3 characters to search..." id="searchInput">
                <button class="search-button" id="searchButton" disabled>Search</button>
                
                <div class="results-section">
                    <h3>Search Results</h3>
                    <div id="resultsContainer">
                        <!-- Results will be displayed here -->
                    </div>
                </div>
            </div>
Get credits and exchange for benefits such as a premium account among others (minimum of five dollars).
<br><br>

PIX    : decenhash@gmail.com <br>
BTC    : 1DenPrDp1ACKnaBcFsRW1c9Kuvgv1mXiZh<br>
Solana : HFMw58NL83GbF9MPX5A9EzjADXpWAxmHFQquUoacpoY4<br>
PayPal : <a href="https://www.paypal.com/donate/?hosted_button_id=P7QXZJ3X7SVSE">Click here</a>
        </section>

        <section class="form-section">
            <h3>Create Entry</h3>
            
<?php if (!empty($linkToShow)): ?>
    <div class="success-link">
        <?php echo "<div class='message success'>Success: File uploaded successfully! " . $linkToShow . "</div>"; ?>
    </div>
<?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <label>User: <input type="text" name="user"></label>
                <label>Title: <input type="text" name="title"></label>
                <label>Description: <input type="text" name="description"></label>
                <label>Category: <input type="text" name="category"></label>                
                <label>URL (mandatory): <input type="url" name="url" required></label>
                <label>BTC: <input type="text" name="BTC"></label>
                <label>SOL: <input type="text" name="SOL"></label> 
                <label>PIX: <input type="text" name="PIX"></label>                
                <label>PAYPAL: <input type="text" name="PAYPAL"></label>                
                <label>Upload File (max 10MB): <input type="file" name="file"></label>
                <button type="submit">Save</button>
            </form>
        </section>

    </main>

    <footer>
        <p>&copy; 2025 D E C E N H A S H.&nbsp; A l l &nbsp; r i g h t s &nbsp;  r e s e r v e d.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const resultsContainer = document.getElementById('resultsContainer');            

            // Enable/disable search button based on input length
            searchInput.addEventListener('input', function() {
                searchButton.disabled = this.value.length < 3;
            });

            // Search when button is clicked
            searchButton.addEventListener('click', performSearch);

            // Search when Enter key is pressed
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.length >= 3) {
                    performSearch();
                }
            });

            function performSearch() {
                const searchTerm = searchInput.value.trim();
                
                if (searchTerm.length < 3) {
                    return;
                }

                // Show loading state
                resultsContainer.innerHTML = '<div class="loading">Searching...</div>';
                searchButton.disabled = true;

                // Construct the search file path
                const searchFile = `json_search/${searchTerm}.json`;

                fetch(searchFile)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('File not found');
                        }
                        return response.text(); // Get as text first to handle malformed JSON
                    })
                    .then(text => {
                        console.log('Raw response:', text);
                        const data = parseJsonSafely(text);
                        displayResults(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (error.message === 'File not found') {
                            resultsContainer.innerHTML = '<div class="no-results">No results found for "' + searchTerm + '".</div>';
                        } else {
                            resultsContainer.innerHTML = '<div class="message error">Error: ' + error.message + '</div>';
                        }
                    })
                    .finally(() => {
                        searchButton.disabled = false;
                    });
            }

            function parseJsonSafely(text) {
                try {
                    // First try to parse directly
                    return JSON.parse(text);
                } catch (e) {
                    console.warn('Direct JSON parse failed, attempting to fix...', e);
                    
                    // Try to fix common JSON issues
                    let fixedText = text;
                    
                    // Remove BOM if present
                    fixedText = fixedText.replace(/^\uFEFF/, '');
                    
                    // Remove extra content after the main JSON object
                    const lastBraceIndex = fixedText.lastIndexOf('}');
                    if (lastBraceIndex !== -1) {
                        fixedText = fixedText.substring(0, lastBraceIndex + 1);
                    }
                    
                    // Remove trailing commas before } or ]
                    fixedText = fixedText.replace(/,\s*([}\]])/g, '$1');
                    
                    // Remove any non-printable characters except whitespace
                    fixedText = fixedText.replace(/[^\x20-\x7E\n\r\t]/g, '');
                    
                    console.log('Fixed text:', fixedText);
                    
                    try {
                        return JSON.parse(fixedText);
                    } catch (e2) {
                        console.error('Could not fix JSON:', e2);
                        
                        // Last resort: try to extract JSON objects using regex
                        const jsonMatches = fixedText.match(/\{[^{}]*\}/g);
                        if (jsonMatches && jsonMatches.length > 0) {
                            console.log('Found potential JSON objects via regex:', jsonMatches);
                            const validObjects = [];
                            for (let match of jsonMatches) {
                                try {
                                    const obj = JSON.parse(match);
                                    if (obj && typeof obj === 'object') {
                                        validObjects.push(obj);
                                    }
                                } catch (e3) {
                                    // Continue to next match
                                }
                            }
                            if (validObjects.length > 0) {
                                return validObjects;
                            }
                        }
                        
                        throw new Error('Invalid JSON format in search file');
                    }
                }
            }

            function displayResults(data) {
                console.log('Displaying data:', data);
                
                resultsContainer.innerHTML = '';

                if (!data) {
                    resultsContainer.innerHTML = '<div class="no-results">No valid data found.</div>';
                    return;
                }

                // Handle different data structures
                let results = [];

                if (Array.isArray(data)) {
                    // If it's an array, use all items that have title and filename
                    results = data.filter(item => item && item.title && item.filename);
                } else if (typeof data === 'object') {
                    // If it's a single object with title and filename
                    if (data.title && data.filename) {
                        results = [data];
                    } else {
                        // If it's an object containing multiple entries, extract them
                        results = extractResultsFromObject(data);
                    }
                }

                if (results.length === 0) {
                    resultsContainer.innerHTML = '<div class="no-results">No valid results found.</div>';
                    return;
                }

                // Display all results
                results.forEach(result => {
                    createResultElement(result);
                });
            }

            function extractResultsFromObject(obj) {
                const results = [];
                
                function extract(currentObj) {
                    if (!currentObj || typeof currentObj !== 'object') return;
                    
                    // If current object has title and filename, add it to results
                    if (currentObj.title && currentObj.filename) {
                        results.push(currentObj);
                    }
                    
                    // Recursively check all properties
                    Object.values(currentObj).forEach(value => {
                        if (value && typeof value === 'object') {
                            if (Array.isArray(value)) {
                                value.forEach(item => extract(item));
                            } else {
                                extract(value);
                            }
                        }
                    });
                }
                
                extract(obj);
                return results;
            }

            function createResultElement(result) {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';

                // Create title link
                const titleLink = document.createElement('a');
                titleLink.href = `redirect.php?hash=${encodeURIComponent(result.filename)}`;
                titleLink.target = '_blank';
                titleLink.className = 'result-link';
                titleLink.textContent = result.title || 'Untitled';

                // Create info button
                const infoButton = document.createElement('button');
                infoButton.className = 'info-button';
                infoButton.textContent = 'info';
                infoButton.onclick = function() {
                    window.open(`json/${result.filename}.json`, '_blank');
                };

                resultItem.appendChild(titleLink);
                resultItem.appendChild(infoButton);
                resultsContainer.appendChild(resultItem);
            }
        });
    </script>
</body>
</html>