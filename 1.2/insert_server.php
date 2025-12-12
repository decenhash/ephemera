<?php
// Get the user IP address
$user_ip = $_SERVER['REMOTE_ADDR'];

// Define the salt
$salt = 'cat';

// Calculate the SHA256 hash of the IP with the salt
$hash = hash('sha256', $user_ip . $salt);

// Directory and file paths
$directory = 'tmp_servers';
$file_path = $directory . '/' . $hash;

// Check if the directory exists, if not create it
if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}

// If the file exists, stop execution
if (file_exists($file_path)) {
    die('Access denied: Server hash exists.');
}

// Create the file to mark this IP hash
file_put_contents($file_path, "");

// Validate URL input from POST or GET, assuming input field name is 'url'
if (isset($_REQUEST['url'])) {
    $url = $_REQUEST['url'];
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        // Append the URL to servers.txt
        file_put_contents('servers.txt', $url . PHP_EOL, FILE_APPEND);
        echo "ok";
    }
}
?>
