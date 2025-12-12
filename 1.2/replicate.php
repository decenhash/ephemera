<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allowed folder names
$allowedFolders = ['files', 'json', 'others', 'json_search'];

function downloadFilesFromWebpage($url) {
    global $allowedFolders;
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return "Error: Invalid URL provided.";
    }
    
    // Get the webpage content
    $htmlContent = file_get_contents($url);
    if ($htmlContent === false) {
        return "Error: Unable to fetch the webpage content.";
    }
    
    // Create DOM document and load HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress HTML parsing warnings
    $dom->loadHTML($htmlContent);
    libxml_clear_errors();
    
    // Find all links in the webpage
    $links = $dom->getElementsByTagName('a');
    $downloadedFiles = [];
    $skippedFiles = [];
    
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        
        // Skip empty links and anchor links
        if (empty($href) || strpos($href, '#') === 0) {
            continue;
        }
        
        // Convert relative URLs to absolute URLs
        $fileUrl = resolveUrl($url, $href);
        
        // Check if the URL is in an allowed folder
        if (isUrlInAllowedFolder($fileUrl, $allowedFolders)) {
            $downloadResult = downloadFile($fileUrl);
            if ($downloadResult['success']) {
                $downloadedFiles[] = $downloadResult['file'];
            } else {
                $skippedFiles[] = $downloadResult['file'] . " - " . $downloadResult['reason'];
            }
        } else {
            $skippedFiles[] = $fileUrl . " - Folder not allowed or file in root";
        }
    }
    
    // Generate result message
    $result = "Download completed!\n";
    $result .= "Downloaded files: " . count($downloadedFiles) . "\n";
    $result .= "Skipped files: " . count($skippedFiles) . "\n\n";
    
    if (!empty($downloadedFiles)) {
        $result .= "Downloaded:\n- " . implode("\n- ", $downloadedFiles) . "\n\n";
    }
    
    if (!empty($skippedFiles)) {
        $result .= "Skipped:\n- " . implode("\n- ", $skippedFiles);
    }
    
    return $result;
}

function resolveUrl($baseUrl, $relativeUrl) {
    // If the URL is already absolute, return it
    if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
        return $relativeUrl;
    }
    
    // Parse the base URL
    $baseParts = parse_url($baseUrl);
    
    // Handle relative URLs starting with /
    if (strpos($relativeUrl, '/') === 0) {
        return $baseParts['scheme'] . '://' . $baseParts['host'] . $relativeUrl;
    }
    
    // Handle relative URLs without leading slash
    $basePath = dirname($baseParts['path']);
    if ($basePath === '/') {
        $basePath = '';
    }
    
    return $baseParts['scheme'] . '://' . $baseParts['host'] . $basePath . '/' . $relativeUrl;
}

function isUrlInAllowedFolder($url, $allowedFolders) {
    $urlParts = parse_url($url);
    
    // Check if the path contains an allowed folder
    if (isset($urlParts['path'])) {
        $path = $urlParts['path'];
        
        // Split path into segments
        $pathSegments = explode('/', trim($path, '/'));
        
        // Check each segment to see if it matches an allowed folder
        foreach ($pathSegments as $segment) {
            if (in_array($segment, $allowedFolders)) {
                // Make sure the folder is not the last segment (which would be the filename)
                $folderIndex = array_search($segment, $pathSegments);
                if ($folderIndex < count($pathSegments) - 1) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

function downloadFile($fileUrl) {
    $urlParts = parse_url($fileUrl);
    $path = $urlParts['path'];
    
    // Extract filename and folder
    $pathSegments = explode('/', trim($path, '/'));
    $filename = end($pathSegments);
    
    // Find the allowed folder in the path
    global $allowedFolders;
    $targetFolder = null;
    foreach ($pathSegments as $segment) {
        if (in_array($segment, $allowedFolders)) {
            $targetFolder = $segment;
            break;
        }
    }
    
    if (!$targetFolder) {
        return ['success' => false, 'file' => $fileUrl, 'reason' => 'No allowed folder found'];
    }
    
    // Create the folder if it doesn't exist
    if (!is_dir($targetFolder) && !mkdir($targetFolder, 0755, true)) {
        return ['success' => false, 'file' => $fileUrl, 'reason' => 'Cannot create folder'];
    }
    
    $filePath = $targetFolder . '/' . $filename;
    
    // Check if file already exists
    if (file_exists($filePath)) {
        return ['success' => false, 'file' => $fileUrl, 'reason' => 'File already exists'];
    }
    
    // Download the file
    $fileContent = file_get_contents($fileUrl);
    if ($fileContent === false) {
        return ['success' => false, 'file' => $fileUrl, 'reason' => 'Download failed'];
    }
    
    // Save the file
    if (file_put_contents($filePath, $fileContent) === false) {
        return ['success' => false, 'file' => $fileUrl, 'reason' => 'Save failed'];
    }
    
    return ['success' => true, 'file' => $filePath];
}

// HTML form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url']);
    $result = downloadFilesFromWebpage($url);
} else {
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webpage File Downloader</title>
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
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="url"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 14px;
        }
        .info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Webpage File Downloader</h1>
        
        <div class="info">
            <br> Download all files of a website. (If the site uses a text file list (files.txt) instead of links use <a href="file_downloader.php">file_downloader.php</a>)<br><br>
            <strong>Allowed folders:</strong> files, json, others, json_search<br>
            <strong>Note:</strong> Only files inside these folders will be downloaded. Files in root or other folders will be skipped.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="url">Enter Webpage URL:</label>
                <input type="url" id="url" name="url" placeholder="https://example.com/files.php" required>
            </div>
            <button type="submit">Download Files</button>
        </form>
        
        <?php if ($result !== null): ?>
            <div class="result">
                <?php echo htmlspecialchars($result); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>