<?php
// A set of the *only* folder names we are allowed to download from.
define('ALLOWED_FOLDERS', ['files', 'json', 'others', 'json_search']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urlString = $_POST['url'] ?? '';
    
    if (empty($urlString)) {
        $error = "Please enter a URL";
    } else {
        // Validate URL format
        if (!filter_var($urlString, FILTER_VALIDATE_URL)) {
            $error = "Invalid URL provided. Please include http:// or https://";
        } else {
            // Process the download
            processDownload($urlString);
        }
    }
}

function processDownload($urlString) {
    echo "<h3>Processing URL: " . htmlspecialchars($urlString) . "</h3>";
    
    // 1. Fetch the text content from the URL
    $textContent = fetchTextContent($urlString);
    
    if ($textContent === null || empty($textContent)) {
        echo "<p style='color: red;'>Could not fetch content or file is empty from " . htmlspecialchars($urlString) . "</p>";
        return;
    }
    
    echo "<p>Text file content fetched successfully.</p>";
    
    // 2. Parse the text file line by line and download
    processFileLines($textContent, $urlString);
    
    echo "<p><strong>--- All processing finished ---</strong></p>";
}

function fetchTextContent($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0\r\n',
            'follow_location' => 1,
            'max_redirects' => 20
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        $http_response_header = $http_response_header ?? [];
        $responseCode = extractResponseCode($http_response_header);
        echo "<p style='color: red;'>GET request failed. Server responded with: " . $responseCode . "</p>";
        return null;
    }
    
    return $content;
}

function extractResponseCode($headers) {
    if (!empty($headers[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches);
        return $matches[1] ?? 'Unknown';
    }
    return 'Unknown';
}

function processFileLines($fileContent, $textFileUrl) {
    $lines = explode("\n", str_replace("\r", "", $fileContent));
    $totalLines = 0;
    $downloadedCount = 0;
    
    foreach ($lines as $line) {
        $totalLines++;
        $path = trim($line);
        
        // Skip empty lines or comment lines
        if (empty($path) || strpos($path, '#') === 0) {
            continue;
        }
        
        $parts = explode('/', $path);
        
        // Core logic from Java version
        if (count($parts) == 2) {
            $folderName = $parts[0];
            $fileName = $parts[1];
            
            if (empty($fileName)) {
                echo "<p>Skipping (directory path): " . htmlspecialchars($path) . "</p>";
            } else if (in_array($folderName, ALLOWED_FOLDERS)) {
                // Build absolute URL
                $baseUrl = dirname($textFileUrl) . '/';
                $absoluteUrl = $baseUrl . $path;
                
                // Attempt to download the file
                if (downloadFile($absoluteUrl, $folderName, $fileName)) {
                    $downloadedCount++;
                }
            } else {
                echo "<p>Skipping (disallowed folder): " . htmlspecialchars($path) . "</p>";
            }
        } else if (count($parts) == 1 && !empty($parts[0])) {
            echo "<p>Skipping (root file): " . htmlspecialchars($path) . "</p>";
        } else if (count($parts) > 2) {
            echo "<p>Skipping (nested path): " . htmlspecialchars($path) . "</p>";
        }
    }
    
    // Print the final summary
    echo "<h4>--- Summary ---</h4>";
    echo "<p>Total lines processed: " . $totalLines . "</p>";
    echo "<p>Files successfully downloaded: " . $downloadedCount . "</p>";
}

function downloadFile($fileUrl, $folderName, $fileName) {
    // Create directory if it doesn't exist
    if (!is_dir($folderName)) {
        if (!mkdir($folderName, 0755, true)) {
            echo "<p style='color: red;'>Failed to create directory: " . htmlspecialchars($folderName) . "</p>";
            return false;
        }
    }
    
    $localFilePath = $folderName . '/' . $fileName;
    
    if (file_exists($localFilePath)) {
        echo "<p>Skipping (already exists): " . htmlspecialchars($localFilePath) . "</p>";
        return false;
    }
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0\r\n',
            'follow_location' => 1,
            'max_redirects' => 20
        ]
    ]);
    
    $fileContent = @file_get_contents($fileUrl, false, $context);
    
    if ($fileContent === false) {
        $http_response_header = $http_response_header ?? [];
        $responseCode = extractResponseCode($http_response_header);
        echo "<p style='color: red;'>Failed to download " . htmlspecialchars($fileUrl) . ". Server responded: " . $responseCode . "</p>";
        return false;
    }
    
    if (file_put_contents($localFilePath, $fileContent) !== false) {
        echo "<p>Success: " . htmlspecialchars($localFilePath) . "</p>";
        return true;
    } else {
        echo "<p style='color: red;'>Error writing file: " . htmlspecialchars($localFilePath) . "</p>";
        return false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>File Downloader</title>
</head>
<body>
    <h2>File Downloader</h2>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <label for="url">Enter the full URL to the text file (e.g., http://example.com/files.txt):</label><br>
        <input type="text" id="url" name="url" style="width: 400px;" 
               value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : ''; ?>">
        <br><br>
        <input type="submit" value="Download Files">
    </form>
</body>
</html>