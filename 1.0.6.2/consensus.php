<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hash-Based JSON File Finder</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .description {
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.6;
            color: #ddd;
        }
        
        .input-section {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        input {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        button {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #4A00E0, #8E2DE2);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        button:disabled {
            background: #555;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .results-section {
            margin-top: 30px;
        }
        
        .server-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 10px;
        }
        
        .server-item {
            padding: 12px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .server-url {
            font-weight: bold;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status.found {
            background: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }
        
        .status.not-found {
            background: rgba(244, 67, 54, 0.3);
            color: #F44336;
        }
        
        .status.checking {
            background: rgba(255, 193, 7, 0.3);
            color: #FFC107;
        }
        
        .save-section {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .save-btn {
            background: linear-gradient(135deg, #00b09b, #96c93d);
            padding: 15px 30px;
            font-size: 1.1rem;
        }
        
        .hidden {
            display: none;
        }
        
        .summary {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            text-align: center;
        }
        
        .progress-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(90deg, #4A00E0, #8E2DE2);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transform: translateX(150%);
            transition: transform 0.5s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            border-left: 5px solid #4CAF50;
        }
        
        .notification.error {
            border-left: 5px solid #F44336;
        }
        
        @media (max-width: 600px) {
            .input-section {
                flex-direction: column;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hash-Based JSON File Finder</h1>
        
        <div class="description">
            <p>Enter a hash value to search for matching JSON files across multiple servers.</p>
            <p>The system will check each server's JSON folder for a file named [hash].json.</p>
        </div>
        
        <div class="input-section">
            <input type="text" id="hashInput" placeholder="Enter hash value (e.g., abc123def456)">
            <button id="searchBtn">Search Servers</button>
        </div>
        
        <div class="progress-bar">
            <div class="progress" id="progressBar"></div>
        </div>
        
        <div class="results-section">
            <h2>Server Results</h2>
            <div class="server-list" id="serverList">
                <!-- Server results will be displayed here -->
            </div>
            
            <div class="summary" id="summary">
                <!-- Summary will be displayed here -->
            </div>
            
            <div class="save-section">
                <button id="saveBtn" class="save-btn hidden">Save Most Common File</button>
            </div>
        </div>
    </div>
    
    <div class="notification" id="notification"></div>

    <?php
    // PHP code to load servers from servers.txt file
    function loadServers() {
        $servers = [];
        $defaultServer = 'http://localhost/ephemera';
        
        // Add the default server
        $servers[] = $defaultServer;
        
        // Try to load additional servers from servers.txt
        if (file_exists('servers.txt')) {
            $fileContent = file_get_contents('servers.txt');
            $lines = explode("\n", $fileContent);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && filter_var($line, FILTER_VALIDATE_URL)) {
                    $servers[] = $line;
                }
            }
        } else {
            // If servers.txt doesn't exist, create it with the default server
            file_put_contents('servers.txt', $defaultServer . "\n");
        }
        
        return $servers;
    }
    
    $servers = loadServers();
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hashInput = document.getElementById('hashInput');
            const searchBtn = document.getElementById('searchBtn');
            const serverList = document.getElementById('serverList');
            const saveBtn = document.getElementById('saveBtn');
            const summary = document.getElementById('summary');
            const progressBar = document.getElementById('progressBar');
            const notification = document.getElementById('notification');
            
            // Server list from PHP
            const servers = <?php echo json_encode($servers); ?>;
            
            let serverResults = [];
            let fileContents = {};
            
            // Initialize the server list display
            function initializeServerList() {
                serverList.innerHTML = '';
                servers.forEach(server => {
                    const serverItem = document.createElement('div');
                    serverItem.className = 'server-item';
                    serverItem.innerHTML = `
                        <span class="server-url">${server}</span>
                        <span class="status checking">Checking...</span>
                    `;
                    serverList.appendChild(serverItem);
                });
            }
            
            // Show notification
            function showNotification(message, type = 'success') {
                notification.textContent = message;
                notification.className = `notification ${type}`;
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }
            
            // Check if a file exists on a server
            async function checkServer(server, hash) {
                const url = `${server}/json/${hash}.json`;
                
                try {
                    const response = await fetch(url, { method: 'HEAD' });
                    if (response.ok) {
                        // If file exists, get its content
                        const contentResponse = await fetch(url);
                        if (contentResponse.ok) {
                            const content = await contentResponse.text();
                            return { exists: true, content };
                        }
                    }
                    return { exists: false, content: null };
                } catch (error) {
                    return { exists: false, content: null, error: error.message };
                }
            }
            
            // Update progress bar
            function updateProgress(completed, total) {
                const percentage = (completed / total) * 100;
                progressBar.style.width = `${percentage}%`;
            }
            
            // Find the most common file content
            function findMostCommonFile() {
                const contentCounts = {};
                let maxCount = 0;
                let mostCommonContent = null;
                
                for (const content of Object.values(fileContents)) {
                    contentCounts[content] = (contentCounts[content] || 0) + 1;
                    if (contentCounts[content] > maxCount) {
                        maxCount = contentCounts[content];
                        mostCommonContent = content;
                    }
                }
                
                return { content: mostCommonContent, count: maxCount, total: Object.keys(fileContents).length };
            }
            
            // Perform the search across all servers
            async function performSearch() {
                const hash = hashInput.value.trim();
                if (!hash) {
                    showNotification('Please enter a hash value', 'error');
                    return;
                }
                
                // Reset state
                serverResults = [];
                fileContents = {};
                saveBtn.classList.add('hidden');
                summary.innerHTML = '';
                
                // Initialize UI
                initializeServerList();
                searchBtn.disabled = true;
                searchBtn.textContent = 'Searching...';
                
                // Check each server
                const serverItems = serverList.querySelectorAll('.server-item');
                
                for (let i = 0; i < servers.length; i++) {
                    const server = servers[i];
                    const result = await checkServer(server, hash);
                    
                    // Update UI for this server
                    const statusElement = serverItems[i].querySelector('.status');
                    
                    if (result.exists) {
                        statusElement.textContent = 'File Found';
                        statusElement.className = 'status found';
                        fileContents[server] = result.content;
                    } else {
                        statusElement.textContent = 'File Not Found';
                        statusElement.className = 'status not-found';
                    }
                    
                    serverResults.push({
                        server,
                        exists: result.exists,
                        content: result.content
                    });
                    
                    // Update progress
                    updateProgress(i + 1, servers.length);
                }
                
                // Show summary and save button if files were found
                const foundCount = serverResults.filter(r => r.exists).length;
                
                if (foundCount > 0) {
                    const mostCommon = findMostCommonFile();
                    
                    summary.innerHTML = `
                        <p>Found ${foundCount} out of ${servers.length} servers with the file.</p>
                        <p>The most common file appears on ${mostCommon.count} servers.</p>
                    `;
                    
                    saveBtn.classList.remove('hidden');
                } else {
                    summary.innerHTML = `<p>No servers found with a file matching the hash "${hash}".</p>`;
                }
                
                searchBtn.disabled = false;
                searchBtn.textContent = 'Search Servers';
            }
            
            // Save the most common file
            function saveMostCommonFile() {
                const mostCommon = findMostCommonFile();
                
                if (mostCommon.content) {
                    // In a real application, this would save to the server
                    // For this demo, we'll just show a notification
                    showNotification(`File saved successfully! It was found on ${mostCommon.count} servers.`);
                    
                    // Create a download link for the file
                    const blob = new Blob([mostCommon.content], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `${hashInput.value}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    showNotification('No file to save', 'error');
                }
            }
            
            // Event listeners
            searchBtn.addEventListener('click', performSearch);
            
            hashInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            saveBtn.addEventListener('click', saveMostCommonFile);
            
            // Initialize with a sample hash
            hashInput.value = 'sample123hash456';
        });
    </script>
</body>
</html>