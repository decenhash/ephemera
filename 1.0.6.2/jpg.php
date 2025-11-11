<?php
// Simple version - rename all .jpeg to .jpg in the 'jpeg' folder
$folder = 'jpeg';

if (!is_dir($folder)) {
    die("Error: Folder '$folder' does not exist.");
}

$files = scandir($folder);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $filePath = $folder . '/' . $file;
    
    if (is_file($filePath)) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'jpeg') {
            $newName = pathinfo($filePath, PATHINFO_FILENAME) . '.jpg';
            $newPath = $folder . '/' . $newName;
            
            if (rename($filePath, $newPath)) {
                echo "Renamed: $file → $newName\n";
            } else {
                echo "Failed to rename: $file\n";
            }
        }
    }
}

echo "Done!\n";
?>