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
            
            // Sort files naturally (1, 2, 3, 10 instead of 1, 10, 2, 3)
            natsort($fileList);
            $fileList = array_values($fileList);
            
            $renamedFiles = [];
            $errors = [];
            
            // Rename files in order
            foreach ($fileList as $index => $originalFile) {
                $sourcePath = $tmpDir . '/' . $originalFile;
                
                // Get file extension
                $extension = pathinfo($originalFile, PATHINFO_EXTENSION);
                
                // Create new filename
                $newFilename = $prefix . '_' . ($index + 1) . ($extension ? '.' . $extension : '');
                $destinationPath = $tmpDir . '/' . $newFilename;
                
                // Check if new filename already exists
                if (file_exists($destinationPath)) {
                    $errors[] = "Cannot rename '$originalFile' to '$newFilename' - target file already exists";
                    continue;
                }
                
                // Rename file
                if (rename($sourcePath, $destinationPath)) {
                    $renamedFiles[] = [
                        'original' => $originalFile,
                        'new' => $newFilename
                    ];
                } else {
                    $errors[] = "Failed to rename file: $originalFile";
                }
            }
            
            // Display results
            if (!empty($renamedFiles)) {
                echo '<div class="message success">';
                echo '<strong>Successfully renamed ' . count($renamedFiles) . ' file(s):</strong><br>';
                echo '<div class="file-list">';
                foreach ($renamedFiles as $file) {
                    echo '• ' . $file['original'] . ' → <strong>' . $file['new'] . '</strong><br>';
                }
                echo '</div>';
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
                natsort($fileList);
                echo '<div class="message success">';
                echo '<strong>Current files in directory:</strong><br>';
                echo '<div class="file-list">';
                foreach ($fileList as $file) {
                    echo '• ' . $file . '<br>';
                }
                echo '</div>';
                echo '</div>';
            }
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="prefix">Enter prefix for file names:</label>
                <input type="text" id="prefix" name="prefix" placeholder="e.g., dog, cat, photo" required>
                <small style="color: #666;">Files will be renamed as: prefix_1.ext, prefix_2.ext, etc.</small>
            </div>
            <button type="submit">Rename Files</button>
        </form>
        
        <div style="margin-top: 20px; font-size: 14px; color: #666;">
            <strong>How it works:</strong>
            <ol>
                <li>Enter a prefix (e.g., "dog")</li>
                <li>Click "Rename Files" to rename all files in "tmp_files" directory</li>
                <li>Files will be renamed in ordered way: dog_1.jpg, dog_2.png, dog_3.pdf, etc.</li>
                <li>Original file extensions are preserved</li>
                <li>Files are processed in natural order</li>
            </ol>
            
            <strong>Example:</strong>
            <ul>
                <li>Input: "dog"</li>
                <li>Files before: image1.jpg, photo.png, document.pdf</li>
                <li>Files after: dog_1.jpg, dog_2.png, dog_3.pdf</li>
            </ul>
            
            <strong>Directory Structure:</strong>
            <pre style="background: white; padding: 10px; border-radius: 4px; margin-top: 10px;">
your-project/
├── index.php (this file)
└── tmp_files/ (place your files here)
    ├── original_file1.jpg
    ├── original_file2.png
    └── original_file3.pdf
            </pre>
        </div>
    </div>
</body>
</html>