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
    //include ("ip_tmp.php");

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
    <title>Telegram Groups</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            color: #0088cc;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: #555;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #0088cc;
        }
        
        .toggle-link {
            display: inline-block;
            color: #0088cc;
            font-size: 14px;
            margin: 10px 0 20px;
            cursor: pointer;
            padding: 4px 0;
        }
        
        .additional-fields {
            display: none;
            border-top: 1px solid #e1e5e9;
            padding-top: 20px;
            margin-top: 10px;
        }
        
        .btn {
            background: #000;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .search-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e1e5e9;
        }
        
        .search-input {
            margin-bottom: 15px;
        }
        
        .search-button {
            background: #000;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .search-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .results-section {
            margin-top: 25px;
        }
        
        .result-item {
            padding: 16px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .result-link {
            color: #0088cc;
            text-decoration: none;
            font-weight: 500;
            flex: 1;
        }
        
        .info-button {
            background: #000;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }
        
        .loading, .no-results {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 15px;
        }
        
        .group-info {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
        
        .file-input {
            padding: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Telegram Groups</h1>

<?php if (!empty($linkToShow)): ?>
    <div class="success">
        <?php echo "<div class='message success'>Success: " . $linkToShow . "</div>"; ?>
    </div>
<?php endif; ?>        

<?php if (empty($linkToShow) && isset($_POST['url'])): ?>
    <div class="error">
        <?php echo "<div class='message error'>Error: The link already exists</div>"; ?>
    </div>
<?php endif; ?>    
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter group title">
            </div>
            
            <div class="form-group">
                <label>URL (mandatory)</label>
                <input type="url" name="url" required placeholder="https://t.me/groupname">
            </div>
            
            <div class="toggle-link" id="toggleMore">+ Show more fields</div>
            
            <div class="additional-fields" id="additionalFields">
                <div class="form-group">
                    <label>User</label>
                    <input type="text" name="user" placeholder="Group admin username">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Group description">
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g., Technology, Business">
                </div>
                
                <div class="form-group">
                    <label>BTC</label>
                    <input type="text" name="BTC" placeholder="Bitcoin address">
                </div>
                
                <div class="form-group">
                    <label>SOL</label>
                    <input type="text" name="SOL" placeholder="Solana address">
                </div>
                
                <div class="form-group">
                    <label>PIX</label>
                    <input type="text" name="PIX" placeholder="PIX key">
                </div>
                
                <div class="form-group">
                    <label>PAYPAL</label>
                    <input type="text" name="PAYPAL" placeholder="PayPal email">
                </div>

            </div>
            
            <button type="submit" class="btn">Add Group to Directory</button>
        </form>
        
        <div class="search-section">
            <h2 style="font-size: 18px; margin-bottom: 15px;">Search Groups</h2>
            <input type="text" class="search-input" placeholder="Enter at least 3 characters to search..." id="searchInput">
            <button class="search-button" id="searchButton" disabled>Search</button>
            
            <div class="results-section">
                <div id="resultsContainer">
                    <!-- Results will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const resultsContainer = document.getElementById('resultsContainer');
            const toggleLink = document.getElementById('toggleMore');
            const additionalFields = document.getElementById('additionalFields');
            
            let showMoreFields = false;
            
            // Toggle additional fields
            toggleLink.addEventListener('click', function() {
                showMoreFields = !showMoreFields;
                additionalFields.style.display = showMoreFields ? 'block' : 'none';
                toggleLink.textContent = showMoreFields ? '- Hide additional fields' : '+ Show more fields';
            });

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
                const searchTerm = searchInput.value.trim().toLowerCase();
                
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