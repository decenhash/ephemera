<?php

// --- Configuration ---
// Define the maximum file size in bytes (50 KB)
const MAX_JSON_FILE_SIZE = 50 * 1024;

/**
 * Finds the next available JSON file for appending search data.
 *
 * Checks the base file (e.g., "word.json") first.
 * If it's full (>= MAX_JSON_FILE_SIZE), it checks numbered files
 * (e.g., "word_1.json", "word_2.json", ...) until it finds
 * one that is not full or does not exist.
 *
 * @param string $folder The directory to search in.
 * @param string $baseName The base name of the file (e.g., "hello").
 * @return string The full path to the file to write to.
 */
function findAvailableSearchFile(string $folder, string $baseName): string
{
    // 1. Check the base filename first (e.g., "word.json")
    $targetFilename = $folder . '/' . $baseName . '.json';
 
    $fileSize = 0;
    if (file_exists($targetFilename)) {
        // Get the size of the file
        $fileSize = filesize($targetFilename);
    }
 
    // If the file doesn't exist or is under the limit, it's available.
    if ($fileSize < MAX_JSON_FILE_SIZE) {
        return $targetFilename;
    }
 
    // 2. The base file is full. Start checking numbered files (e.g., "word_1.json")
    $i = 1;
    while (true) {
        // Construct the new filename with the number
        $numberedFilename = $folder . '/' . $baseName . '_' . $i . '.json';
 
        $fileSize = 0;
        if (file_exists($numberedFilename)) {
            $fileSize = filesize($numberedFilename);
        }
 
        // If this numbered file is available, return it
        if ($fileSize < MAX_JSON_FILE_SIZE) {
            return $numberedFilename;
        }
 
        // This file is also full, so increment $i and check the next one
        $i++;
    }
 
    // This loop will always return a filename, as it will eventually
    // find a number $i for which the file does not exist.
}

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

        // Only filenames without special characters are created for search

        $word = trim($word);

        $wordC = preg_replace('/[^a-zA-Z0-9]/', '', $word);
        
        $wordLength = strlen($word);
         
        $wordCLength = strlen($wordC);

        if ($wordLength != $wordCLength){continue;}
        
        // Generate combinations from 3 to 12 characters for each word
        for ($comboLength = 3; $comboLength <= 12; $comboLength++) {
            if ($wordLength >= $comboLength) {
                for ($i = 0; $i <= $wordLength - $comboLength; $i++) {
                    $wordCombination = substr($word, $i, $comboLength);
                    
                    // Create the search filename
                    //$searchFilename = $jsonSearchFolder . '/' . strtolower($wordCombination) . '.json';
                    $searchFilename = findAvailableSearchFile($jsonSearchFolder, strtolower($wordCombination));
                    
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