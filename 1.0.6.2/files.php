<?php
// List files in specified folders
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>File List</title>
</head>
<body>
    <?php echo $fileList; ?>
</body>
</html>