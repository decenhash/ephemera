<?php
// CSS Style and HTML Structure
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Checker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: 300;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
        }
        
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #2980b9;
        }
        
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        
        .status {
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 3px;
            font-family: monospace;
        }
        
        .online {
            background: #d4edda;
            color: #155724;
        }
        
        .offline {
            background: #f8d7da;
            color: #721c24;
        }
        
        .completed {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>URL Availability Checker</h1>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="sitename">Site Name:</label>
                <input type="text" id="sitename" name="sitename" placeholder="Enter site name (e.g., pastebin)" value="' . (isset($_POST['sitename']) ? htmlspecialchars($_POST['sitename']) : '') . '">
            </div>
            <button type="submit">Check URLs</button>
        </form>';

// PHP Processing Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sitename'])) {
    $userSitename = trim($_POST['sitename']);
    $sitename = [$userSitename];
    
    // Array of TLDs (you can replace this with your tlds.php file)
    $tlds = ['com', 'org', 'net', 'io', 'info', 'biz'];
    
    // Directories for storing URLs
    $directories = ['online' => 'online', 'offline' => 'offline', 'json' => 'JSON', 'json_search' => 'json_search'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Function to handle file creation
    function saveToFile($filename, $content = "") {
        $file = fopen($filename, 'w');
        if ($file) {
            fwrite($file, $content);
            fclose($file);
            return true;
        } else {
            echo '<div class="status">Error: Unable to create file ' . htmlspecialchars($filename) . '</div>';
            return false;
        }
    }
    
    // Function to append data to JSON search file
    function appendToJsonSearch($filename, $title, $filehash) {
        $jsonEntry = [
            "title" => $title,
            "filename" => $filehash
        ];
        
        $jsonContent = json_encode($jsonEntry, JSON_PRETTY_PRINT) . ",\n";
        
        // Append to file without erasing previous content
        $file = fopen($filename, 'a');
        if ($file) {
            fwrite($file, $jsonContent);
            fclose($file);
            return true;
        } else {
            echo '<div class="status">Error: Unable to append to file ' . htmlspecialchars($filename) . '</div>';
            return false;
        }
    }
    
    // Function to create JSON data with the specified pattern
    function createJsonData($url) {
        $date = date('Y-m-d H:i:s');
        
        return json_encode([
            "user" => "",
            "title" => "",
            "description" => "",
            "date" => $date,
            "category" => "",
            "size" => "",
            "type" => "",
            "url" => $url,
            "TON" => "",
            "SOL" => "",
            "PAYPAL" => "",
            "BTC" => ""
        ], JSON_PRETTY_PRINT);
    }
    
    echo '<div class="results">';
    echo '<h3>Results:</h3>';
    
    // Iterate over sitenames and TLDs
    foreach ($sitename as $name) {
        foreach ($tlds as $tld) {
            $filenameOnline = $directories['online'] . '/' . $name . '.' . $tld . '.txt';
            $filenameOffline = $directories['offline'] . '/' . $name . '.' . $tld . '.txt';
    
            // Skip if either online or offline file already exists
            if (file_exists($filenameOnline) || file_exists($filenameOffline)) {
                continue;
            }
    
            $url = 'http://' . $name . '.' . $tld;
            $headers = @get_headers($url);
    
            // Check if the URL is reachable
            if ($headers && strpos($headers[0], '200') !== false) {
                echo '<div class="status online">' . htmlspecialchars($name . '.' . $tld) . ' is online</div>';
                saveToFile($filenameOnline);
                
                // Create JSON file for online URL
                $jsonData = createJsonData($url);
                $jsonFilename = $directories['json'] . '/' . hash('sha256', $url) . '.json';
                saveToFile($jsonFilename, $jsonData);
                
                // Prepare data for json_search file
                $searchFilename = $directories['json_search'] . '/' . $name . '.json';
                $title = preg_replace('#^https?://#', '', $url);
                $filehash = hash('sha256', $url);
                
                // Append to json_search file
                appendToJsonSearch($searchFilename, $title, $filehash);
                
            } else {
                echo '<div class="status offline">' . htmlspecialchars($name . '.' . $tld) . ' is offline or unreachable</div>';
                saveToFile($filenameOffline);
            }
        }
    }
    
    echo '<div class="completed">URL check completed for ' . htmlspecialchars($userSitename) . '</div>';
    echo '</div>';
}

echo '
    </div>
</body>
</html>';
?>