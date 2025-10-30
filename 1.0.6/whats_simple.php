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
    <title>Group Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #25D366;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], input[type="url"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="url"]:focus {
            outline: none;
            border-color: #25D366;
            box-shadow: 0 0 5px rgba(37, 211, 102, 0.3);
        }
        .btn {
            background-color: #25D366;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background-color: #1da851;
        }
        .error {
            color: #ff4444;
            background-color: #ffeaea;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ffcccc;
        }
        .success {
            color: #25D366;
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #c8e6c9;
        }
        .info-box {
            background-color: #e8f5e8;
            border: 1px solid #c8e6c9;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }
        .info-box h3 {
            color: #25D366;
            margin-bottom: 10px;
        }
        .info-box ul {
            list-style-position: inside;
            color: #666;
        }
        .info-box li {
            margin-bottom: 5px;
        }

        a {
            color: #666;
            text-decoration: none; 
        }
        
        /* Additional fields styling */
        .additional-fields {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }
        
        .toggle-link {
            color: #25D366;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
        }
        
        /* Search results styling */
        .search-section {
            margin-top: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        
        .search-button {
            background-color: #25D366;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .search-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .results-section {
            margin-top: 20px;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            border-radius: 6px;
            border-left: 4px solid #25D366;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .result-link {
            color: #333;
            font-weight: bold;
            text-decoration: none;
            flex-grow: 1;
        }
        
        .result-link:hover {
            color: #25D366;
        }
        
        .info-button {
            background-color: #25D366;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .info-button:hover {
            background-color: #1da851;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            background-color: #f9f9f9;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WhatsApp Groups</h1>
        
<?php if (!empty($linkToShow)): ?>
    <div class="success">
        <?php echo "Success: " . $linkToShow; ?>
    </div>
<?php endif; ?>

<?php if (empty($linkToShow) && isset($_POST['url'])): ?>
    <div class="error">
        <?php echo "Error: The link already exists" . "<br>"; ?>
    </div>
<?php endif; ?>  
        
        <div id="groupForm">
            <form action="whats.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Group name: <input type="text" name="title"></label>
                </div>
                
                <div class="form-group">
                    <label>Link: <input type="url" name="url" required></label>
                </div>
                
                <span class="toggle-link" id="toggleMore">+ Show more fields</span>
                
                <div class="additional-fields" id="additionalFields">
                    <div class="form-group">
                        <label>User: <input type="text" name="user"></label>
                    </div>
                    
                    <div class="form-group">
                        <label>Description: <input type="text" name="description"></label>
                    </div>
                    
                    <div class="form-group">
                        <label>Category: <input type="text" name="category"></label>
                    </div>
                    
                    <div class="form-group">
                        <label>BTC: <input type="text" name="BTC"></label>
                    </div>
                    
                    <div class="form-group">
                        <label>SOL: <input type="text" name="SOL"></label>
                    </div>
                    
                    <div class="form-group">
                        <label>PIX: <input type="text" name="PIX"></label>
                    </div>
                    
                    <div class="form-group">
                        <label>PAYPAL: <input type="text" name="PAYPAL"></label>
                    </div>
                   
                </div>
                
                <button type="submit" class="btn">Save</button>
            </form>
        </div>
        
        <div class="info-box">
            <div class="search-section">
                <h3>Search</h3>
                <input type="text" class="search-input" placeholder="Enter at least 3 characters to search..." id="searchInput">
                <button class="search-button" id="searchButton" disabled>Search</button>
                
                <div class="results-section">
                    <div id="resultsContainer">
                        <!-- Results will be displayed here -->
                    </div>
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