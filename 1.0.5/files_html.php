<?php
// List files in specified folders and write to file
function listFilesInFolders() {
    $folders = ['json', 'json_search', 'files', 'others'];
    $output = '';
    
    foreach ($folders as $folder) {
        $output .= "<h3>Files in folder: $folder</h3>";
        
        // Check if folder exists
        if (!is_dir($folder)) {
            $output .= "Folder '$folder' does not exist<br><br>";
            continue;
        }
        
        // Get all files in the folder
        $files = scandir($folder);
        
        // Remove . and .. entries
        $files = array_diff($files, ['.', '..']);
        
        if (empty($files)) {
            $output .= "No files found in '$folder'<br><br>";
            continue;
        }
        
        // List each file as a link
        foreach ($files as $file) {
            $filePath = $folder . '/' . $file;
            
            // Check if it's a file (not a subdirectory)
            if (is_file($filePath)) {
                $output .= "<a href=\"$filePath\">$filePath</a><br>";
            }
        }
        $output .= "<br>";
    }
    
    return $output;
}

// Generate the file list
$fileList = listFilesInFolders();

// Create the complete HTML content
$htmlContent = '<!DOCTYPE html>
<html>
<head>
    <title>File List</title>
</head>
<body>
    ' . $fileList . '
</body>
</html>';

// Write to file using fopen
$filename = 'files.html';
$fileHandle = fopen($filename, 'w');

if ($fileHandle) {
    $bytesWritten = fwrite($fileHandle, $htmlContent);
    fclose($fileHandle);
    
    if ($bytesWritten !== false) {
        echo "Successfully wrote $bytesWritten bytes to '$filename'<br>";
        echo "File created: <a href='$filename'>$filename</a>";
    } else {
        echo "Error writing to file '$filename'";
    }
} else {
    echo "Error opening file '$filename' for writing";
}
?>