<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Renamer</title>
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
        .file-list { background: white; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .file-type-section { margin: 15px 0; padding: 10px; background: #e9ecef; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>File Renamer</h2>
        
        <?php
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userInput = trim($_POST['prefix'] ?? '');
            
            if (!empty($userInput)) {
                renameFiles($userInput);
            } else {
                echo '<div class="message error">Please enter a prefix for file names.</div>';
            }
        }

        function renameFiles($prefix) {
            // Define directory
            $tmpDir = 'tmp_files';
            
            // Create directory if it doesn't exist
            if (!file_exists($tmpDir)) {
                mkdir($tmpDir, 0755, true);
                echo '<div class="message error">Temporary files directory created. Please add some files to rename.</div>';
                return;
            }
            
            // Check if tmp_files directory exists
            if (!is_dir($tmpDir)) {
                echo '<div class="message error">Temporary files directory does not exist.</div>';
                return;
            }
            
            // Get all files from directory
            $files = scandir($tmpDir);
            $files = array_diff($files, ['.', '..']);
            
            // Filter out directories, keep only files
            $fileList = [];
            foreach ($files as $file) {
                $filePath = $tmpDir . '/' . $file;
                if (is_file($filePath)) {
                    $fileList[] = $file;
                }
            }
            
            if (empty($fileList)) {
                echo '<div class="message error">No files found in temporary directory.</div>';
                return;
            }
            
            // Group files by extension
            $filesByExtension = [];
            foreach ($fileList as $file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $extension = strtolower($extension ?: 'no_extension');
                if (!isset($filesByExtension[$extension])) {
                    $filesByExtension[$extension] = [];
                }
                $filesByExtension[$extension][] = $file;
            }
            
            // Sort files naturally within each extension group
            foreach ($filesByExtension as $extension => $files) {
                natsort($filesByExtension[$extension]);
                $filesByExtension[$extension] = array_values($filesByExtension[$extension]);
            }
            
            $renamedFiles = [];
            $errors = [];
            
            // Rename files by extension with separate counters
            foreach ($filesByExtension as $extension => $files) {
                $counter = 1;
                
                foreach ($files as $originalFile) {
                    $sourcePath = $tmpDir . '/' . $originalFile;
                    
                    // Create new filename with extension-specific counter
                    if ($extension === 'no_extension') {
                        $newFilename = $prefix . '_' . $counter;
                    } else {
                        $newFilename = $prefix . '_' . $counter . '.' . $extension;
                    }
                    $destinationPath = $tmpDir . '/' . $newFilename;
                    
                    // Check if new filename already exists
                    if (file_exists($destinationPath)) {
                        $errors[] = "Cannot rename '$originalFile' to '$newFilename' - target file already exists";
                        $counter++;
                        continue;
                    }
                    
                    // Rename file
                    if (rename($sourcePath, $destinationPath)) {
                        $renamedFiles[$extension][] = [
                            'original' => $originalFile,
                            'new' => $newFilename
                        ];
                    } else {
                        $errors[] = "Failed to rename file: $originalFile";
                    }
                    
                    $counter++;
                }
            }
            
            // Display results
            if (!empty($renamedFiles)) {
                echo '<div class="message success">';
                echo '<strong>Successfully renamed ' . getTotalFileCount($renamedFiles) . ' file(s):</strong><br>';
                
                foreach ($renamedFiles as $extension => $files) {
                    $displayExtension = ($extension === 'no_extension') ? 'No Extension' : strtoupper($extension);
                    echo '<div class="file-type-section">';
                    echo '<strong>' . $displayExtension . ' files (' . count($files) . '):</strong><br>';
                    echo '<div class="file-list">';
                    foreach ($files as $file) {
                        echo '• ' . $file['original'] . ' → <strong>' . $file['new'] . '</strong><br>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            
            if (!empty($errors)) {
                echo '<div class="message error">';
                echo '<strong>Errors encountered:</strong><br>';
                foreach ($errors as $error) {
                    echo '• ' . $error . '<br>';
                }
                echo '</div>';
            }
            
            // Show current files in directory after renaming
            showCurrentFiles($tmpDir);
        }

        function getTotalFileCount($renamedFiles) {
            $total = 0;
            foreach ($renamedFiles as $extension => $files) {
                $total += count($files);
            }
            return $total;
        }

        function showCurrentFiles($directory) {
            $files = scandir($directory);
            $files = array_diff($files, ['.', '..']);
            
            $fileList = [];
            foreach ($files as $file) {
                $filePath = $directory . '/' . $file;
                if (is_file($filePath)) {
                    $fileList[] = $file;
                }
            }
            
            if (!empty($fileList)) {
                // Group by extension for display
                $filesByExtension = [];
                foreach ($fileList as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    $extension = strtolower($extension ?: 'no_extension');
                    if (!isset($filesByExtension[$extension])) {
                        $filesByExtension[$extension] = [];
                    }
                    $filesByExtension[$extension][] = $file;
                }
                
                // Sort extensions alphabetically
                ksort($filesByExtension);
                
                echo '<div class="message success">';
                echo '<strong>Current files in directory (grouped by type):</strong><br>';
                
                foreach ($filesByExtension as $extension => $files) {
                    $displayExtension = ($extension === 'no_extension') ? 'No Extension' : strtoupper($extension);
                    natsort($files);
                    echo '<div class="file-type-section">';
                    echo '<strong>' . $displayExtension . ' (' . count($files) . ' files):</strong><br>';
                    echo '<div class="file-list">';
                    foreach ($files as $file) {
                        echo '• ' . $file . '<br>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="prefix">Enter prefix for file names:</label>
                <input type="text" id="prefix" name="prefix" placeholder="e.g., dog, cat, photo" required>
                <small style="color: #666;">Files will be renamed with separate counters for each file type</small>
            </div>
            <button type="submit">Rename Files</button>
        </form>
        
        <div style="margin-top: 20px; font-size: 14px; color: #666;">
            <strong>How it works:</strong>
            <ol>
                <li>Enter a prefix (e.g., "dog")</li>
                <li>Click "Rename Files" to rename all files in "tmp_files" directory</li>
                <li>Each file type gets its own counter starting from 1</li>
                <li>Original file extensions are preserved</li>
                <li>Files are grouped and displayed by type</li>
            </ol>
            
            <strong>Example:</strong>
            <ul>
                <li>Input: "project"</li>
                <li>Files before: img1.jpg, img2.jpg, song1.mp3, song2.mp3, doc1.pdf, data.xlsx</li>
                <li>Files after: 
                    <ul>
                        <li>JPG: project_1.jpg, project_2.jpg</li>
                        <li>MP3: project_1.mp3, project_2.mp3</li>
                        <li>PDF: project_1.pdf</li>
                        <li>XLSX: project_1.xlsx</li>
                    </ul>
                </li>
            </ul>
            
            <strong>Features:</strong>
            <ul>
                <li>Separate counters for each file type</li>
                <li>Handles files without extensions</li>
                <li>Natural sorting within each file type</li>
                <li>Clear grouping in the results display</li>
            </ul>
        </div>
    </div>
</body>
</html>