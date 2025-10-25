<?php
// List files and write directly to file
function writeFilesToTxt() {
    $folders = ['json', 'json_search', 'files', 'others'];
    $filename = 'files.txt';
    $fileHandle = fopen($filename, 'w');
    
    if (!$fileHandle) {
        echo "Error opening file '$filename' for writing";
        return;
    }
    
    $fileCount = 0;
    
    foreach ($folders as $folder) {
        if (!is_dir($folder)) continue;
        
        $files = scandir($folder);
        $files = array_diff($files, ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $folder . '/' . $file;
            if (is_file($filePath)) {
                fwrite($fileHandle, $filePath . PHP_EOL);
                $fileCount++;
            }
        }
    }
    
    fclose($fileHandle);
    echo "Successfully wrote $fileCount file paths to '$filename'";
}

// Execute the function
writeFilesToTxt();
?>