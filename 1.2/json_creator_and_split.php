<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Processor</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .container { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>File Processor</h2>
        
        <?php
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userInput = trim($_POST['words'] ?? '');
            
            if (!empty($userInput)) {
                processFiles($userInput);
            } else {
                echo '<div class="message error">Please enter some words separated by commas.</div>';
            }
        }

        function processFiles($userInput) {
            // Split words with maximum of 5 results
            $words = explode(',', $userInput);
            $words = array_slice($words, 0, 5);
            $words = array_map('trim', $words);
            
            // Remove empty values
            $words = array_filter($words);
            
            if (empty($words)) {
                echo '<div class="message error">No valid words found after processing.</div>';
                return;
            }
            
            // Define directories
            $tmpDir = 'tmp_files';
            $filesDir = 'files';
            $jsonDir = 'json_search';
            
            // Create directories if they don't exist
            if (!file_exists($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }
            if (!file_exists($filesDir)) {
                mkdir($filesDir, 0755, true);
            }
            if (!file_exists($jsonDir)) {
                mkdir($jsonDir, 0755, true);
            }
            
            // Check if tmp_files directory exists and has files
            if (!is_dir($tmpDir)) {
                echo '<div class="message error">Temporary files directory does not exist.</div>';
                return;
            }
            
            $files = scandir($tmpDir);
            $files = array_diff($files, ['.', '..']);
            
            if (empty($files)) {
                echo '<div class="message error">No files found in temporary directory.</div>';
                return;
            }
            
            $processedFiles = [];
            $errors = [];
            
            // Process each file
            foreach ($files as $file) {
                $sourcePath = $tmpDir . '/' . $file;
                
                // Skip directories
                if (is_dir($sourcePath)) {
                    continue;
                }
                
                // Get file extension and type
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $fileType = strtolower($extension);
                
                // Get file size
                $fileSize = filesize($sourcePath);
                $formattedSize = formatFileSize($fileSize);
                
                // Calculate SHA-256 hash of the file
                $fileHash = hash_file('sha256', $sourcePath);
                
                if ($fileHash === false) {
                    $errors[] = "Failed to calculate hash for: $file";
                    continue;
                }
                
                // Create new filename with hash and original extension
                $newFilename = $fileHash . ($extension ? '.' . $extension : '');
                $destinationPath = $filesDir . '/' . $newFilename;
                
                // Move file
                if (rename($sourcePath, $destinationPath)) {
                    $processedFiles[] = [
                        'original' => $file,
                        'hash' => $fileHash,
                        'new_filename' => $newFilename,
                        'type' => $fileType,
                        'size' => $formattedSize
                    ];
                    
                    // Create individual JSON file for this file
                    createIndividualJsonFile($file, $fileHash, $fileType, $formattedSize, $jsonDir);
                    
                } else {
                    $errors[] = "Failed to move file: $file";
                }
            }
            
            // Create JSON files for each word with automatic splitting
            foreach ($words as $word) {
                createWordJsonFiles($word, $processedFiles, $jsonDir);
            }
            
            // Display results
            if (!empty($processedFiles)) {
                echo '<div class="message success">';
                echo '<strong>Successfully processed ' . count($processedFiles) . ' file(s):</strong><br>';
                foreach ($processedFiles as $file) {
                    echo '- ' . $file['original'] . ' → ' . $file['new_filename'] . ' (' . $file['size'] . ')<br>';
                }
                echo '</div>';
                
                echo '<div class="message success">';
                echo '<strong>Created JSON files for words:</strong><br>';
                foreach ($words as $word) {
                    $baseFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $word);
                    $jsonFiles = glob($jsonDir . '/' . $baseFilename . '*.json');
                    foreach ($jsonFiles as $jsonFile) {
                        $filename = basename($jsonFile);
                        $fileSize = formatFileSize(filesize($jsonFile));
                        $entryCount = countJsonEntries($jsonFile);
                        echo '- ' . $filename . ' (' . $fileSize . ', ' . $entryCount . ' entries)<br>';
                    }
                }
                echo '</div>';
                
                echo '<div class="message success">';
                echo '<strong>Created individual JSON files for each file:</strong><br>';
                foreach ($processedFiles as $file) {
                    echo '- ' . $file['hash'] . '.json<br>';
                }
                echo '</div>';
            }
            
            if (!empty($errors)) {
                echo '<div class="message error">';
                echo '<strong>Errors encountered:</strong><br>';
                foreach ($errors as $error) {
                    echo '- ' . $error . '<br>';
                }
                echo '</div>';
            }
        }

        function createWordJsonFiles($word, $processedFiles, $jsonDir) {
            // Sanitize base filename
            $baseFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $word);
            
            $currentFileIndex = 0;
            $currentEntries = [];
            $currentSize = 0;
            
            foreach ($processedFiles as $processedFile) {
                $entry = "{\n    \"title\": \"{$processedFile['original']}\",\n    \"filename\": \"{$processedFile['hash']}\"\n}";
                $entrySize = strlen($entry);
                
                // Check if adding this entry would exceed limits
                if (count($currentEntries) >= 200 || ($currentSize + $entrySize) > 50000 || !empty($currentEntries) && ($currentSize + $entrySize + 1) > 50000) {
                    // Save current batch and start new one
                    saveWordJsonFile($baseFilename, $currentFileIndex, $currentEntries, $jsonDir);
                    $currentFileIndex++;
                    $currentEntries = [];
                    $currentSize = 0;
                }
                
                // Add comma if not first entry
                if (!empty($currentEntries)) {
                    $currentSize += 1; // Comma size
                }
                
                $currentEntries[] = $entry;
                $currentSize += $entrySize;
            }
            
            // Save any remaining entries
            if (!empty($currentEntries)) {
                saveWordJsonFile($baseFilename, $currentFileIndex, $currentEntries, $jsonDir);
            }
        }

        function saveWordJsonFile($baseFilename, $fileIndex, $entries, $jsonDir) {
            if ($fileIndex === 0) {
                $jsonFilename = $jsonDir . '/' . $baseFilename . '.json';
            } else {
                $jsonFilename = $jsonDir . '/' . $baseFilename . '_' . $fileIndex . '.json';
            }
            
            $jsonContent = implode(",", $entries);
            
            // Write JSON file using fopen
            $fileHandle = fopen($jsonFilename, 'w');
            if ($fileHandle) {
                fwrite($fileHandle, $jsonContent);
                fclose($fileHandle);
                return true;
            } else {
                return false;
            }
        }

        function countJsonEntries($jsonFile) {
            if (!file_exists($jsonFile)) {
                return 0;
            }
            
            $content = file_get_contents($jsonFile);
            // Count the number of objects by counting the opening braces
            return substr_count($content, '{');
        }

        function createIndividualJsonFile($originalFilename, $fileHash, $fileType, $fileSize, $jsonDir) {
            // Current date in the specified format
            $currentDate = date('Y-m-d H:i:s');
            
            // Build JSON content with the exact pattern
            $jsonContent = "{\n";
            $jsonContent .= '    "user": "",' . "\n";
            $jsonContent .= '    "title": "' . $originalFilename . '",' . "\n";
            $jsonContent .= '    "description": "",' . "\n";
            $jsonContent .= '    "date": "' . $currentDate . '",' . "\n";
            $jsonContent .= '    "category": "",' . "\n";
            $jsonContent .= '    "size": "' . $fileSize . '",' . "\n";
            $jsonContent .= '    "type": "' . $fileType . '",' . "\n";
            $jsonContent .= '    "url": "",' . "\n";
            $jsonContent .= '    "TON": "",' . "\n";
            $jsonContent .= '    "SOL": "",' . "\n";
            $jsonContent .= '    "PAYPAL": "",' . "\n";
            $jsonContent .= '    "BTC": ""' . "\n";
            $jsonContent .= "}";
            
            // Create JSON filename using file hash
            $jsonFilename = "json" . '/' . $fileHash . '.json';
            
            // Write JSON file using fopen
            $fileHandle = fopen($jsonFilename, 'w');
            if ($fileHandle) {
                fwrite($fileHandle, $jsonContent);
                fclose($fileHandle);
                return true;
            } else {
                return false;
            }
        }

        function formatFileSize($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } elseif ($bytes > 1) {
                return $bytes . ' bytes';
            } elseif ($bytes == 1) {
                return '1 byte';
            } else {
                return '0 bytes';
            }
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="words">Enter words (separated by commas, max 5):</label>
                <input type="text" id="words" name="words" placeholder="word1, word2, word3, word4, word5" required>
            </div>
            <button type="submit">Process Files</button>
        </form>
        
        <div style="margin-top: 20px; font-size: 14px; color: #666;">
            <strong>How it works:</strong>
            <ol>
                <li>Enter up to 5 words separated by commas</li>
                <li>Click "Process Files" to move files from "tmp_files" to "files" directory</li>
                <li>Files are renamed using SHA-256 hash + original extension</li>
                <li>JSON files are created in "json_folder" for each word with automatic splitting</li>
                <li>Individual JSON files are created for each file using hash as filename</li>
            </ol>
            
            <strong>Automatic JSON Splitting:</strong>
            <ul>
                <li>Maximum file size: 50KB</li>
                <li>Maximum entries per file: 200</li>
                <li>Files are automatically split: cat.json, cat_1.json, cat_2.json, etc.</li>
            </ul>
            
            <strong>Individual JSON File Pattern:</strong>
            <pre style="background: white; padding: 10px; border-radius: 4px; margin-top: 10px;">
{
    "user": "",
    "title": "original_file.jpg",
    "description": "",
    "date": "2025-10-26 01:54:10",
    "category": "",
    "size": "2.50 MB",
    "type": "jpg",
    "url": "",
    "TON": "",
    "SOL": "",
    "PAYPAL": "",
    "BTC": ""
}</pre>
        </div>
    </div>
</body>
</html>