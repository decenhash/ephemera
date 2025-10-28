<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        /* Fixed vertical menu at the top */
        .top-menu {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #0066cc; /* Blue background */
            display: flex;
            align-items: center;
            padding: 10px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            height: 50px;
            justify-content: space-between; /* Space between left and right elements */
        }

        .menu-left {
            display: flex;
            align-items: center;
        }

        .ephemera-link {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            margin-right: 20px;
        }

        .ephemera-link:hover {
            text-decoration: underline;
        }

        .menu-right {
            display: flex;
            align-items: center;
        }

        .search-input {
            flex: 1;
            max-width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 10px;
        }

        .search-input:focus {
            outline: none;
            border-color: #004c99;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.3);
        }

        .menu-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 8px;
            min-width: 80px;
        }

        .search-btn {
            background-color: #25D366; /* Green color */
            color: white;
        }

        .insert-btn {
            background-color: #888; /* Gray */
            color: white;
        }

        .nav-btn {
            background-color: #888; /* Gray */
            color: white;
            padding: 8px 16px;
            margin-left: 10px;
            border: 0px;
        }

        .menu-btn:hover, .nav-btn:hover {
            opacity: 0.9;
        }

        .menu-btn:disabled, .nav-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Main content below the fixed menu */
        .main-content {
            margin-top: 70px; /* Space for fixed menu */
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 20px;
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
            margin-top: 0;
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

        input[type="text"], input[type="url"], input[type="file"] {
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
            margin-bottom: 15px;
        }

        .success {
            color: #25D366;
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #c8e6c9;
            margin-bottom: 15px;
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
        .results-section {
            margin-top: 20px;
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

        /* Navigation controls */
        .nav-controls {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 10px 0;
        }

        /* Modal styles */
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
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .modal h2 {
            margin-top: 0;
            color: #25D366;
            text-align: center;
        }

        /* JSON Modal specific styles */
        .json-modal {
            display: none;
            position: fixed;
            z-index: 2001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .json-modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
        }

        .json-field {
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .json-field:last-child {
            border-bottom: none;
        }

        .json-field-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .json-field-value {
            color: #666;
            word-break: break-word;
        }

        .json-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .json-error {
            color: #ff4444;
            background-color: #ffeaea;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ffcccc;
            margin-top: 15px;
        }

        /* Title section styles */
        .title-section {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .title-section h1 {
            font-size: 2.5rem;
            margin: 0 0 15px 0;
            color: white;
        }

        .title-section p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
            opacity: 0.9;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>

    <!-- Fixed Top Menu -->
    <div class="top-menu">
        <div class="menu-left">
            <a href="https://example.com" class="ephemera-link" target="_blank">Ephemera</a>
        </div>
        <div class="menu-right">
            <input type="text" class="search-input" placeholder="Enter at least 3 characters..." id="searchInput">
            <button class="menu-btn search-btn" id="searchButton" disabled>Search</button>
            <button class="menu-btn insert-btn" id="insertButton">Insert</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Title Section -->
            <div class="title-section" id="titleSection">
                <h1>WhatsApp Group Manager</h1>
                <p>Find and join WhatsApp groups based on your interests. Search for groups by topic, category, or keywords. You can also add new groups to our growing collection.</p>
            </div>
            
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
            
            <div class="info-box">
                <div class="results-section">
                    <div id="resultsContainer">
                        <!-- Results will be displayed here -->
                    </div>
                    <div id="navControls" class="nav-controls" style="display: none;">
                        <button class="nav-btn" id="prevButton">Prev</button>
                        <button class="nav-btn" id="nextButton">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Insert Modal -->
    <div id="insertModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">×</span>
            <h2>Add New Group</h2>
            <form action="whats.php" method="post" enctype="multipart/form-data" id="groupForm">
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

                    <div class="form-group">
                        <label>Thumbnail (max 10MB): <input type="file" name="file"></label>
                    </div>
                </div>

                <button type="submit" class="btn">Save</button>
            </form>
        </div>
    </div>

    <!-- JSON Info Modal -->
    <div id="jsonModal" class="json-modal">
        <div class="json-modal-content">
            <span class="close" id="closeJsonModal">×</span>
            <h2>Group Information</h2>
            <div id="jsonContent">
                <!-- JSON content will be displayed here -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const insertButton = document.getElementById('insertButton');
            const modal = document.getElementById('insertModal');
            const closeModal = document.getElementById('closeModal');
            const toggleLink = document.getElementById('toggleMore');
            const additionalFields = document.getElementById('additionalFields');
            const resultsContainer = document.getElementById('resultsContainer');
            const titleSection = document.getElementById('titleSection');
            const navControls = document.getElementById('navControls');
            const prevButton = document.getElementById('prevButton');
            const nextButton = document.getElementById('nextButton');
            
            // JSON Modal elements
            const jsonModal = document.getElementById('jsonModal');
            const closeJsonModal = document.getElementById('closeJsonModal');
            const jsonContent = document.getElementById('jsonContent');

            // Navigation state
            let currentSearchTerm = '';
            let currentPage = 0;
            let hasNextPage = false;
            let hasPrevPage = false;

            let showMoreFields = false;

            // Toggle additional fields in modal
            toggleLink.addEventListener('click', function() {
                showMoreFields = !showMoreFields;
                additionalFields.style.display = showMoreFields ? 'block' : 'none';
                toggleLink.textContent = showMoreFields ? '- Hide additional fields' : '+ Show more fields';
            });

            // Open modal on Insert button click
            insertButton.addEventListener('click', function() {
                modal.style.display = 'flex';
                hideTitleSection();
            });

            // Close modal
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // Close JSON modal
            closeJsonModal.addEventListener('click', function() {
                jsonModal.style.display = 'none';
            });

            // Navigation buttons
            prevButton.addEventListener('click', loadPrevPage);
            nextButton.addEventListener('click', loadNextPage);

            // Close modals when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
                if (e.target === jsonModal) {
                    jsonModal.style.display = 'none';
                }
            });

            // Enable/disable search button
            searchInput.addEventListener('input', function() {
                searchButton.disabled = this.value.trim().length < 3;
            });

            // Search on button click
            searchButton.addEventListener('click', performSearch);

            // Search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim().length >= 3) {
                    performSearch();
                }
            });

            function hideTitleSection() {
                titleSection.classList.add('hidden');
            }

            function showTitleSection() {
                titleSection.classList.remove('hidden');
            }

            function performSearch() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                if (searchTerm.length < 3) return;

                currentSearchTerm = searchTerm;
                currentPage = 0;
                
                hideTitleSection();
                loadSearchResults(searchTerm, 0);
            }

            function loadSearchResults(searchTerm, page) {
                let searchFile;
                
                if (page === 0) {
                    searchFile = `json_search/${searchTerm}.json`;
                } else {
                    searchFile = `json_search/${searchTerm}_${page}.json`;
                }

                resultsContainer.innerHTML = '<div class="loading">Searching...</div>';
                searchButton.disabled = true;
                navControls.style.display = 'none';

                fetch(searchFile)
                    .then(response => {
                        if (!response.ok) throw new Error('File not found');
                        return response.text();
                    })
                    .then(text => {
                        const data = parseJsonSafely(text);
                        displayResults(data);
                        
                        // Check if next page exists
                        checkNextPage(searchTerm, page);
                        
                        // Update navigation state
                        hasPrevPage = page > 0;
                        updateNavButtons();
                        
                        // Show navigation if we have results
                        if (data && ((Array.isArray(data) && data.length > 0) || 
                            (data.title && data.filename))) {
                            navControls.style.display = 'flex';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resultsContainer.innerHTML = 
                            error.message === 'File not found' 
                                ? `<div class="no-results">No results found for "${searchTerm}".</div>`
                                : `<div class="error">Error: ${error.message}</div>`;
                        
                        // Hide navigation on error
                        navControls.style.display = 'none';
                    })
                    .finally(() => {
                        searchButton.disabled = false;
                    });
            }

            function checkNextPage(searchTerm, currentPage) {
                const nextPage = currentPage + 1;
                const nextFile = `json_search/${searchTerm}_${nextPage}.json`;
                
                fetch(nextFile)
                    .then(response => {
                        hasNextPage = response.ok;
                        updateNavButtons();
                    })
                    .catch(() => {
                        hasNextPage = false;
                        updateNavButtons();
                    });
            }

            function loadNextPage() {
                if (!hasNextPage) return;
                
                currentPage++;
                loadSearchResults(currentSearchTerm, currentPage);
            }

            function loadPrevPage() {
                if (!hasPrevPage) return;
                
                currentPage--;
                loadSearchResults(currentSearchTerm, currentPage);
            }

            function updateNavButtons() {
                prevButton.disabled = !hasPrevPage;
                nextButton.disabled = !hasNextPage;
            }

            function parseJsonSafely(text) {
                try { return JSON.parse(text); }
                catch (e) {
                    let fixed = text.replace(/^\uFEFF/, '')
                                    .replace(/,\s*([}\]])/g, '$1')
                                    .replace(/[^\x20-\x7E\n\r\t]/g, '');
                    const last = fixed.lastIndexOf('}');
                    if (last !== -1) fixed = fixed.substring(0, last + 1);
                    try { return JSON.parse(fixed); }
                    catch (e2) {
                        const matches = fixed.match(/\{[^{}]*\}/g) || [];
                        const valid = [];
                        for (let m of matches) {
                            try {
                                const obj = JSON.parse(m);
                                if (obj && obj.title && obj.filename) valid.push(obj);
                            } catch {} 
                        }
                        return valid.length > 0 ? valid : null;
                    }
                }
            }

            function displayResults(data) {
                resultsContainer.innerHTML = '';
                if (!data || (Array.isArray(data) && data.length === 0)) {
                    resultsContainer.innerHTML = '<div class="no-results">No valid results found.</div>';
                    return;
                }

                let results = [];
                if (Array.isArray(data)) {
                    results = data.filter(r => r && r.title && r.filename);
                } else if (data && data.title && data.filename) {
                    results = [data];
                } else {
                    results = extractResultsFromObject(data);
                }

                if (results.length === 0) {
                    resultsContainer.innerHTML = '<div class="no-results">No valid results found.</div>';
                    return;
                }

                const grid = document.createElement('div');
                grid.className = 'results-grid';
                resultsContainer.appendChild(grid);

                results.forEach(r => createResultElement(r, grid));
            }

            function extractResultsFromObject(obj) {
                const res = [];
                function walk(o) {
                    if (!o || typeof o !== 'object') return;
                    if (o.title && o.filename) res.push(o);
                    Object.values(o).forEach(v => {
                        if (v && typeof v === 'object') {
                            Array.isArray(v) ? v.forEach(walk) : walk(v);
                        }
                    });
                }
                walk(obj);
                return res;
            }

            function createResultElement(result, container) {
                const cont = document.createElement('div');
                cont.className = 'result-container';

                const bg = document.createElement('div');
                bg.className = 'result-background';

                const hash = result.filename;
                const jpg = `files/${hash}.jpg`, png = `files/${hash}.png`;

                checkImageExists(jpg, exists => {
                    if (exists) bg.style.backgroundImage = `url('${jpg}')`;
                    else checkImageExists(png, ex => {
                        if (ex) bg.style.backgroundImage = `url('${png}')`;
                        else bg.style.background = 'linear-gradient(135deg, #25D366, #128C7E)';
                    });
                });

                const overlay = document.createElement('div');
                overlay.className = 'result-overlay';

                const title = document.createElement('div');
                title.className = 'result-title';
                title.textContent = result.title || 'Untitled';

                const actions = document.createElement('div');
                actions.className = 'result-actions';

                const join = document.createElement('a');
                join.href = `redirect_url.html?hash=${encodeURIComponent(hash)}`;
                join.target = '_blank';
                join.className = 'result-link';
                join.textContent = 'Join';

                const info = document.createElement('button');
                info.className = 'info-button';
                info.textContent = 'Info';
                info.onclick = () => showJsonInfo(hash);

                actions.appendChild(join);
                actions.appendChild(info);
                overlay.appendChild(title);
                overlay.appendChild(actions);
                bg.appendChild(overlay);
                cont.appendChild(bg);
                container.appendChild(cont);
            }

            function checkImageExists(url, cb) {
                const img = new Image();
                img.onload = () => cb(true);
                img.onerror = () => cb(false);
                img.src = url;
            }

            // New function to display JSON content in modal
            function showJsonInfo(hash) {
                jsonContent.innerHTML = '<div class="json-loading">Loading information...</div>';
                jsonModal.style.display = 'flex';
                
                const jsonFile = `json/${hash}.json`;
                
                fetch(jsonFile)
                    .then(response => {
                        if (!response.ok) throw new Error('JSON file not found');
                        return response.json();
                    })
                    .then(data => {
                        // Format the JSON data as fields with labels and values
                        let html = '';
                        
                        // Create a field for each property in the JSON
                        for (const [key, value] of Object.entries(data)) {
                            // Skip empty values
                            if (!value || value === '' || value === null) continue;
                            
                            html += `
                                <div class="json-field">
                                    <div class="json-field-label">${formatFieldName(key)}</div>
                                    <div class="json-field-value">${formatFieldValue(value)}</div>
                                </div>
                            `;
                        }
                        
                        jsonContent.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error loading JSON:', error);
                        jsonContent.innerHTML = `<div class="json-error">Error loading information: ${error.message}</div>`;
                    });
            }
            
            // Helper function to format field names (convert camelCase to Title Case)
            function formatFieldName(name) {
                // Handle acronyms
                if (name === 'BTC' || name === 'SOL' || name === 'PIX' || name === 'PAYPAL') {
                    return name;
                }
                
                // Convert camelCase to Title Case with spaces
                return name
                    .replace(/([A-Z])/g, ' $1')
                    .replace(/^./, str => str.toUpperCase())
                    .trim();
            }
            
            // Helper function to format field values
            function formatFieldValue(value) {
                if (typeof value === 'boolean') {
                    return value ? 'Yes' : 'No';
                }
                
                if (Array.isArray(value)) {
                    return value.join(', ');
                }
                
                // If it's a URL, make it clickable
                if (typeof value === 'string' && 
                   (value.startsWith('http://') || value.startsWith('https://'))) {
                    return `<a href="${value}" target="_blank">${value}</a>`;
                }
                
                return value;
            }
        });
    </script>
</body>
</html>