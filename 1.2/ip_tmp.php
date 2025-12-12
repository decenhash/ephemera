<?php
// Get user IP address
$userIP = $_SERVER['REMOTE_ADDR'];

// Get current date for salt (year, month, day, and hour)
$currentDate = date('YmdH'); // Format: YearMonthDayHour
$salt = $currentDate;

// Combine IP with salt and hash using SHA-256
$dataToHash = $userIP . $salt;
$hashedIP = hash('sha256', $dataToHash);

// Define the directory for storing files
$directory = 'ip_tmp';

// Create directory if it doesn't exist
if (!is_dir($directory)) {
    if (!mkdir($directory, 0755, true)) {
        die("Error: Could not create directory '$directory'");
    }
}

// Create file path
$filePath = $directory . '/' . $hashedIP . '.txt';

// Check if file already exists
if (file_exists($filePath)) {
    die("Error: File already exists for this IP and time period. Please try again later.");
}

// Create empty file using fopen
$fileHandle = fopen($filePath, 'x'); // 'x' mode creates and opens for writing only if it doesn't exist

// Check if file was created successfully
if ($fileHandle === false) {
    die("Error: Could not create file. It may already exist.");
}

// Close the file handle
fclose($fileHandle);

//echo "Success: File created successfully for IP: " . htmlspecialchars($userIP);

// Optional: Display some debug information
//echo "<br><br>Debug Info:<br>";
//echo "IP Address: " . htmlspecialchars($userIP) . "<br>";
//echo "Salt (Date): " . htmlspecialchars($salt) . "<br>";
//echo "Hashed IP: " . $hashedIP . "<br>";
//echo "File Path: " . htmlspecialchars($filePath);
?>