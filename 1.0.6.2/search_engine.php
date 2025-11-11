<?php

// Load the sitenames from the external PHP file
$sitename = ['upload'];

// Array of TLDs
$tlds = require 'tlds.php';

// Directories for storing URLs
$directories = ['online' => 'online', 'offline' => 'offline', 'json' => 'JSON', 'json_search' => 'json_search'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true); // Ensure permission and recursive creation
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
        echo "Error: Unable to create file $filename.<br>";
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
        echo "Error: Unable to append to file $filename.<br>";
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

// Iterate over sitenames and TLDs
foreach ($sitename as $name) {
    foreach ($tlds as $tld) {
        $filenameOnline = $directories['online'] . '/' . $name . '.' . $tld . '.txt';
        $filenameOffline = $directories['offline'] . '/' . $name . '.' . $tld . '.txt';

        // Skip if either online or offline file already exists
        if (file_exists($filenameOnline) || file_exists($filenameOffline)) {
            continue; // Skip to the next URL check
        }

        $url = 'http://' . $name . '.' . $tld;
        $headers = @get_headers($url);

        // Check if the URL is reachable
        if ($headers && strpos($headers[0], '200') !== false) {
            echo "$name.$tld is online<br>";
            saveToFile($filenameOnline);
            
            // Create JSON file for online URL
            $jsonData = createJsonData($url);
            $jsonFilename = $directories['json'] . '/' . hash('sha256', $url) . '.json';
            saveToFile($jsonFilename, $jsonData);
            
            // Prepare data for json_search file
            $searchFilename = $directories['json_search'] . '/' . $name . '.json';
            $title = preg_replace('#^https?://#', '', $url); // Remove http:// or https://
            $filehash = hash('sha256', $url); // Hash without extension
            
            // Append to json_search file
            appendToJsonSearch($searchFilename, $title, $filehash);
            
        } else {
            echo "$name.$tld is offline or unreachable<br>";
            saveToFile($filenameOffline);
        }
    }
}

echo "URL check completed.";

?>