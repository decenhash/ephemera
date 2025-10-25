<?php

// Define folder paths
$jsonFolder = 'json';
$jsonTmpFolder = 'json_tmp';
$jsonSearchFolder = 'json_search';

// Create directories if they don't exist
if (!is_dir($jsonTmpFolder)) {
    mkdir($jsonTmpFolder, 0755, true);
}
if (!is_dir($jsonSearchFolder)) {
    mkdir($jsonSearchFolder, 0755, true);
}

// Get all JSON files from the json folder
$jsonFiles = glob($jsonFolder . '/*.json');

foreach ($jsonFiles as $jsonFile) {
    // Get the filename without path
    $filename = basename($jsonFile);
    
    // Check if file exists in json_tmp folder
    $tmpFile = $jsonTmpFolder . '/' . $filename;
    if (file_exists($tmpFile)) {
        continue; // Skip to next file if it already exists in json_tmp
    }
    
    // Create empty file in json_tmp folder
    $tmpHandle = fopen($tmpFile, 'w');
    if ($tmpHandle) {
        fclose($tmpHandle);
    }
    
    // Read and decode the JSON file
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    // Check if JSON decoding was successful and title exists
    if ($data === null || !isset($data['title'])) {
        continue; // Skip if JSON is invalid or title doesn't exist
    }
    
    $title = $data['title'];
    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    
    // Split title into individual words
    $words = preg_split('/\s+/', trim($title));
    
    foreach ($words as $word) {
        $word = trim($word);
        $wordLength = strlen($word);
        
        // Generate combinations from 3 to 12 characters for each word
        for ($comboLength = 3; $comboLength <= 12; $comboLength++) {
            if ($wordLength >= $comboLength) {
                for ($i = 0; $i <= $wordLength - $comboLength; $i++) {
                    $wordCombination = substr($word, $i, $comboLength);
                    
                    // Create the search filename
                    $searchFilename = $jsonSearchFolder . '/' . strtolower($wordCombination) . '.json';
                    
                    // Prepare data to write
                    $searchData = [
                        'title' => $data['title'],
                        'filename' => $filenameWithoutExt
                    ];
                    
                    $searchDataJson = json_encode($searchData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    
                    // Write to search file using fopen
                    $searchHandle = fopen($searchFilename, 'a');
                    if ($searchHandle) {
                        fwrite($searchHandle, $searchDataJson . ",\n");
                        fclose($searchHandle);
                    }
                }
            }
        }
    }
}

//echo "Processing completed!\n";

?>