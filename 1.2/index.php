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
                $message = "Error: No PHP files are allowed.";
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
                $TON =  substr($_POST['TON'], 0, $maxLength);
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
                    "TON"         => $TON,
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
    <title>E2</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 70px; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
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
        a {
            color: #666;
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

        /* --- TOP MENU STYLES --- */
        .top-menu-bar {
            background-color: #004a99; /* Blue background */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 10px 20px;
            box-sizing: border-box;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .top-menu-content {
            display: flex;
            align-items: center;
            gap: 15px;
            max-width: 1200px; /* Aligns with .container */
            margin: 0 auto;
        }
        
        .top-menu-content #searchInput {
            flex-grow: 1; 
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .menu-buttons {
            display: flex;
            gap: 10px;
            flex-shrink: 0; 
        }

        .menu-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            font-size: 14px;
        }
        
        #searchButton {
            background-color: #007bff; /* Blue */
        }
        #searchButton:hover {
            background-color: #0056b3;
        }
        #searchButton:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        #openModalBtn {
            background-color: #6c757d; /* Gray */
        }
        #openModalBtn:hover {
            background-color: #5a6268;
        }

        /* --- MODAL STYLES (Insert and Info) --- */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px; 
            border-radius: 8px;
            position: relative;
        }
        
        .close-btn {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
        }

        /* --- NEW: Styles for formatted Info Modal --- */
        #infoModalContent {
            max-height: 400px;
            overflow-y: auto;
        }
        .info-item {
            padding: 10px 5px;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-item strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
            text-transform: capitalize;
        }
        .info-item span {
            color: #555;
            word-wrap: break-word;
        }

        /* --- NEW: Welcome Message Style --- */
        #welcomeMessage {
            text-align: center;
            padding: 40px 20px;
            color: #555;
            border-bottom: 1px dashed #ddd;
            margin-bottom: 20px;
        }
        #welcomeMessage h1 {
            color: #444;
            margin-top: 0;
        }
        #welcomeMessage p {
            font-size: 1.1em;
            color: #666;
        }

        /* --- SEARCH RESULTS STYLING --- */
        .search-section {
            margin-top: 20px;
        }
        
        .results-section {
            margin-top: 20px;
            min-height: 200px; 
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .result-container {
            width: 300px;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .result-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .result-background {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .result-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 15px;
            color: white;
        }
        
        .result-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }
        
        .result-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .result-link {
            background-color: #25D366;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
        }
        
        .info-button {
            background-color: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .info-button:hover {
            background-color: rgba(255,255,255,0.3);
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

        /* --- NEW: Pagination Styles --- */
        .pagination-controls {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .prev-btn, .next-btn {
            background-color: #6c757d; /* Gray */
            display: none;
        }
        .prev-btn:hover, .next-btn:hover {
            background-color: #5a6268;
        }
        .prev-btn:disabled, .next-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            color: #888;
        }
        .E2-link {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            margin-right: 20px;
        }

    </style>
</head>
<body>

    <div class="top-menu-bar">
        <div class="top-menu-content">
            <a href="index.php" class="E2-link">E2</a> <input type="text" class="search-input" placeholder="Enter at least 3 characters to search..." id="searchInput">
            
            <div class="menu-buttons">
                <button class="menu-btn" id="searchButton" disabled>Search</button>
                <button class="menu-btn" id="openModalBtn">Insert</button>
            </div>
        </div>
    </div>

    <div id="insertModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeInsertModalBtn">&times;</span>
            <div id="groupForm">
                <h2>Insert</h2>
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title: <input type="text" name="title"></label>
                    </div>
                    <div class="form-group">
                        <label>URL: <input type="url" name="url" required></label>
                    </div>
                    <button type="submit" class="btn">Save</button>
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
                            <label>TON: <input type="text" name="TON"></label>
                        </div>
                        <div class="form-group">
                            <label>PAYPAL: <input type="text" name="PAYPAL"></label>
                        </div>
                        <div class="form-group">
                         <label>File (max 10MB): <input type="file" name="file"></label>
                        </div>
                    </div>              
                </form>
            </div>
        </div>
    </div>

    <div id="infoModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeInfoModalBtn">&times;</span>
            <h2>Information</h2>
            <div id="infoModalContent">Loading...</div>
        </div>
    </div>

    <div class="container">
        
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
        
        <?php if (empty($_POST['url']) && empty($linkToShow)): ?>
        <div id="welcomeMessage">
            <h1>E2Ephemera</h1>

            <i>Static File sharing websties</i>

            <br><br>
        <div align="left">
<div align="right">
<b>Templates</b> : <a href="template_1.html">1</a> / <a href="template_2.html">2</a> / <a href="template_3.html">3</a> /  <a href="template_4.html">4</a> /  <a href="template_5.html">5</a> /  <a href="template_6.html">6</a> /  <a href="template_7.html">7</a> /  <a href="template_8.html">8</a> /  <a href="template_9.html">9</a> / <a href="whats_simple.php">10</a>
</div>
<hr>
<br>
<br>

The project's goal is to make it easy to create a static sharing site using JSON for search, pagination, metadata etc. 
If you want to generate the JSON documents automatically or send multiple files at once, place the desired files inside the 'tmp_files' folder and run <a href="json_creator.php">JSON creator</a>. You can rename them in ascending order running <a href="list_and_rename.php">File rename</a> beforehand.

<br><br>

You can also create HTML pages without JSON using <a href="index_html.php">index_html</a>.

<br>
<br>
<br>
<br>

            <a href="files.php">All files</a>/ 
            <a href="rank_file.php">Rank</a>/
            <a href="list_and_rename.php">File rename</a>/
            <a href="json_creator.php">JSON creator</a>/
            <a href="replicate.php">Site replication</a>/
            <a href="file_downloader.php">File downloader</a>/
            <a href="files_html.php">File list in HTML</a>/ 
            <a href="files_txt.php">File list in TXT</a>/
            <a href="servers.php">Servers search</a>/
            <a href="servers.html">Add server</a>/
            <a href="comment.php">Comment</a>/
            <a href="search_engine.php">Sites search</a>/
            <a href="java/Server.java">Server.java</a> / <a href="java/Client.java">Client.java</a> /
            <a href="java/Server.java">index.html</a>
<br><br><br><br>
<i style="font-size: 12px;">All rights reserved</i>
        </div>                     

        </div>

        <?php endif; ?>

        <div class="search-section">
            <div class="results-section">
                <div id="resultsContainer">
                    </div>
            </div>

            <div class="pagination-controls">
                <button class="menu-btn prev-btn" id="prevButton">Prev</button>
                <button class="menu-btn next-btn" id="nextButton">Next</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- MODAL SCRIPT (INSERT) ---
            const insertModal = document.getElementById('insertModal');
            const openInsertBtn = document.getElementById('openModalBtn');
            const closeInsertBtn = document.getElementById('closeInsertModalBtn');

            openInsertBtn.addEventListener('click', () => insertModal.style.display = 'block');
            closeInsertBtn.addEventListener('click', () => insertModal.style.display = 'none');
            
            // --- MODAL SCRIPT (INFO) ---
            const infoModal = document.getElementById('infoModal');
            const closeInfoBtn = document.getElementById('closeInfoModalBtn');
            const infoModalContent = document.getElementById('infoModalContent');

            closeInfoBtn.addEventListener('click', () => infoModal.style.display = 'none');

            // Close modals by clicking outside
            window.addEventListener('click', function(event) {
                if (event.target == insertModal) {
                    insertModal.style.display = 'none';
                }
                if (event.target == infoModal) {
                    infoModal.style.display = 'none';
                }
            });

            // --- SEARCH & RESULTS SCRIPT ---
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const resultsContainer = document.getElementById('resultsContainer');
            const toggleLink = document.getElementById('toggleMore');
            const additionalFields = document.getElementById('additionalFields');
            const welcomeMessage = document.getElementById('welcomeMessage'); // Welcome message
            
            // --- Pagination Variables ---
            const nextButton = document.getElementById('nextButton');
            const prevButton = document.getElementById('prevButton'); // Prev button
            let currentSearchBase = '';
            let currentSearchCounter = 0;
            
            let showMoreFields = false;
            
            // Toggle additional fields
            if (toggleLink) {
                toggleLink.addEventListener('click', function() {
                    showMoreFields = !showMoreFields;
                    additionalFields.style.display = showMoreFields ? 'block' : 'none';
                    toggleLink.textContent = showMoreFields ? '- Hide additional fields' : '+ Show more fields';
                });
            }

            // Enable/disable search button
            searchInput.addEventListener('input', function() {
                searchButton.disabled = this.value.length < 3;
            });

            // --- EVENT LISTENERS ---
            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.length >= 3) {
                    performSearch();
                }
            });
            nextButton.addEventListener('click', performNextSearch);
            prevButton.addEventListener('click', performPrevSearch); // Prev button listener

            /**
             * Performs a new search from the search bar.
             * Resets the pagination counter.
             */
            function performSearch() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                if (searchTerm.length < 3) return;

                // Reset pagination
                currentSearchBase = searchTerm;
                currentSearchCounter = 0;
                
                fetchAndDisplay(currentSearchBase);
            }

            /**
             * Performs a search for the next page.
             */
            function performNextSearch() {
                if (!currentSearchBase) return; 

                currentSearchCounter++;
                const nextSearchTerm = `${currentSearchBase}_${currentSearchCounter}`;
                
                fetchAndDisplay(nextSearchTerm);
            }

            /**
             * NEW: Performs a search for the previous page.
             */
            function performPrevSearch() {
                if (!currentSearchBase || currentSearchCounter === 0) return; // Can't go back

                currentSearchCounter--;
                
                let searchTerm;
                if (currentSearchCounter === 0) {
                    searchTerm = currentSearchBase; // Back to base file
                } else {
                    searchTerm = `${currentSearchBase}_${currentSearchCounter}`;
                }
                
                fetchAndDisplay(searchTerm);
            }


            /**
             * Refactored function to fetch and display results.
             * @param {string} searchTerm - The term to search for (e.g., "cat" or "cat_1")
             */
            function fetchAndDisplay(searchTerm) {
                // NEW: Hide welcome message on search
                if (welcomeMessage) {
                    welcomeMessage.style.display = 'none';
                }

                // Show loading state and disable buttons
                resultsContainer.innerHTML = '<div class="loading">Searching...</div>';
                searchButton.disabled = true;
                nextButton.disabled = true;
                prevButton.disabled = true;

                const searchFile = `json_search/${searchTerm}.json`;

                fetch(searchFile)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('File not found');
                        }
                        return response.text(); 
                    })
                    .then(text => {
                        console.log('Raw response:', text);
                        const data = parseJsonSafely(text);
                        displayResults(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        searchButton.disabled = false; // Re-enable search
                        
                        if (error.message === 'File not found') {
                            resultsContainer.innerHTML = '<div class="no-results">No results found for "' + searchTerm + '".</div>';
                            nextButton.style.display = 'none'; // No next file
                            // Show 'Prev' if we are not on the base page
                            prevButton.style.display = (currentSearchCounter > 0) ? 'inline-block' : 'none';
                            prevButton.disabled = false;
                        } else {
                            resultsContainer.innerHTML = '<div class="message error">Error: ' + error.message + '</div>';
                        }
                    })
                    .finally(() => {
                        // Re-enable search button (moved to catch/displayResults)
                    });
            }

            function parseJsonSafely(text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.warn('Direct JSON parse failed, attempting to fix...', e);
                    let fixedText = text.replace(/^\uFEFF/, '');
                    const lastBraceIndex = fixedText.lastIndexOf('}');
                    if (lastBraceIndex !== -1) {
                        fixedText = fixedText.substring(0, lastBraceIndex + 1);
                    }
                    fixedText = fixedText.replace(/,\s*([}\]])/g, '$1');
                    fixedText = fixedText.replace(/[^\x20-\x7E\n\r\t]/g, '');
                    console.log('Fixed text:', fixedText);
                    try {
                        return JSON.parse(fixedText);
                    } catch (e2) {
                        console.error('Could not fix JSON:', e2);
                        const jsonMatches = fixedText.match(/\{[^{}]*\}/g);
                        if (jsonMatches && jsonMatches.length > 0) {
                            const validObjects = [];
                            for (let match of jsonMatches) {
                                try {
                                    const obj = JSON.parse(match);
                                    if (obj && typeof obj === 'object') {
                                        validObjects.push(obj);
                                    }
                                } catch (e3) { /* continue */ }
                            }
                            if (validObjects.length > 0) return validObjects;
                        }
                        throw new Error('Invalid JSON format in search file');
                    }
                }
            }

            function displayResults(data) {
                console.log('Displaying data:', data);
                
                resultsContainer.innerHTML = ''; // Clear previous results
                searchButton.disabled = false; // Re-enable search button
                
                if (!data) {
                    resultsContainer.innerHTML = '<div class="no-results">No valid data found.</div>';
                    return;
                }

                let results = [];
                if (Array.isArray(data)) {
                    results = data.filter(item => item && item.title && item.filename);
                } else if (typeof data === 'object') {
                    if (data.title && data.filename) {
                        results = [data];
                    } else {
                        results = extractResultsFromObject(data);
                    }
                }

                // --- NEW Pagination Button Logic ---
                nextButton.disabled = false;
                prevButton.disabled = false;

                if (results.length === 0) {
                    resultsContainer.innerHTML = '<div class="no-results">No valid results found.</div>';
                    nextButton.style.display = 'none'; // No results, no next
                    prevButton.style.display = (currentSearchCounter > 0) ? 'inline-block' : 'none';
                } else {
                    const resultsGrid = document.createElement('div');
                    resultsGrid.className = 'results-grid';
                    resultsContainer.appendChild(resultsGrid);

                    results.forEach(result => {
                        createResultElement(result, resultsGrid);
                    });

                    // Results were found, show buttons
                    nextButton.style.display = 'inline-block';
                    prevButton.style.display = 'inline-block';
                    //prevButton.style.display = (currentSearchCounter > 0) ? 'inline-block' : 'none';
                }
            }

            function extractResultsFromObject(obj) {
                const results = [];
                function extract(currentObj) {
                    if (!currentObj || typeof currentObj !== 'object') return;
                    if (currentObj.title && currentObj.filename) {
                        results.push(currentObj);
                    }
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

            function createResultElement(result, container) {
                const resultContainer = document.createElement('div');
                resultContainer.className = 'result-container';
                
                const backgroundDiv = document.createElement('div');
                backgroundDiv.className = 'result-background';
                
                const hash = result.filename;
                const jpgPath = `files/${hash}.jpg`;
                const pngPath = `files/${hash}.png`;
                
                checkImageExists(jpgPath, function(exists) {
                    if (exists) {
                        backgroundDiv.style.backgroundImage = `url('${jpgPath}')`;
                    } else {
                        checkImageExists(pngPath, function(pngExists) {
                            if (pngExists) {
                                backgroundDiv.style.backgroundImage = `url('${pngPath}')`;
                            } else {
                                backgroundDiv.style.background = 'linear-gradient(135deg, #25D366, #128C7E)';
                            }
                        });
                    }
                });
                
                const overlayDiv = document.createElement('div');
                overlayDiv.className = 'result-overlay';
                
                const titleDiv = document.createElement('div');
                titleDiv.className = 'result-title';
                titleDiv.textContent = result.title || 'Untitled';
                
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'result-actions';
                
                const joinLink = document.createElement('a');
                joinLink.href = `redirect.html?hash=${encodeURIComponent(result.filename)}`;
                joinLink.target = '_blank';
                joinLink.className = 'result-link';
                joinLink.textContent = 'Join';
                
                const infoButton = document.createElement('button');
                infoButton.className = 'info-button';
                infoButton.textContent = 'Info';
                
                // --- NEW: Info Button Click Logic (Formatted) ---
                infoButton.addEventListener('click', () => {
                    infoModalContent.innerHTML = '<div class="loading">Loading...</div>';
                    infoModal.style.display = 'block';

                    fetch(`json/${result.filename}.json`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Could not load file.');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Build formatted HTML
                            infoModalContent.innerHTML = ''; // Clear loading
                            for (const key in data) {
                                if (data.hasOwnProperty(key)) {
                                    const itemDiv = document.createElement('div');
                                    itemDiv.className = 'info-item';
                                    
                                    const keyStrong = document.createElement('strong');
                                    keyStrong.textContent = key.replace(/_/g, ' ') + ':'; // Format key
                                    
                                    const valueSpan = document.createElement('span');
                                    valueSpan.textContent = data[key] || 'N/A'; // Show N/A if empty
                                    
                                    itemDiv.appendChild(keyStrong);
                                    itemDiv.appendChild(valueSpan);
                                    infoModalContent.appendChild(itemDiv);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching info:', error);
                            infoModalContent.innerHTML = `<div class="error">${error.message}</div>`;
                        });
                });
                
                actionsDiv.appendChild(joinLink);
                actionsDiv.appendChild(infoButton);
                overlayDiv.appendChild(titleDiv);
                overlayDiv.appendChild(actionsDiv);
                backgroundDiv.appendChild(overlayDiv);
                resultContainer.appendChild(backgroundDiv);
                
                container.appendChild(resultContainer);
            }
            
            function checkImageExists(url, callback) {
                const img = new Image();
                img.onload = () => callback(true);
                img.onerror = () => callback(false);
                img.src = url;
            }
        });
    </script>
</body>
</html>