<?php
$is_valid = null;
$message = '';
$input_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_link = trim($_POST['whatsapp_link'] ?? '');
    
    if (empty($input_link)) {
        $message = 'Please enter a WhatsApp group link.';
        $is_valid = false;
    } else {
        // Validate URL format
        if (!filter_var($input_link, FILTER_VALIDATE_URL)) {
            $message = 'Invalid URL format.';
            $is_valid = false;
        } 
        // Check if it's a WhatsApp group link
        elseif (strpos($input_link, 'https://chat.whatsapp.com/') !== 0) {
            $message = 'Please enter a valid WhatsApp group link (should start with https://chat.whatsapp.com/).';
            $is_valid = false;
        } else {
            // Check if the group exists by making a HTTP request
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $input_link,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_NOBODY => true, // HEAD request only
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]);
            
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // WhatsApp returns 200 for valid groups and 404 for invalid/non-existent groups
            if ($http_code === 200) {
                $is_valid = true;
                $message = '✅ This WhatsApp group link is valid and the group exists!';
            } elseif ($http_code === 404) {
                $is_valid = false;
                $message = '❌ This WhatsApp group link is invalid or the group does not exist.';
            } else {
                $is_valid = false;
                $message = '❌ Unable to verify the link. HTTP Error: ' . $http_code;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Group Link Validator</title>
</head>
<body>
    <h1>WhatsApp Group Link Validator</h1>
    
    <form method="POST">
        <label for="whatsapp_link">Enter WhatsApp Group Link:</label><br><br>
        <input type="text" 
               id="whatsapp_link" 
               name="whatsapp_link" 
               value="<?php echo htmlspecialchars($input_link); ?>" 
               placeholder="https://chat.whatsapp.com/XXXXXXXXXXX" 
               style="width: 300px; padding: 8px;"
               required>
        <br><br>
        <button type="submit" style="padding: 8px 16px;">Check Link</button>
    </form>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <br>
        <div style="<?php echo $is_valid ? 'color: green;' : 'color: red;'; ?> font-weight: bold;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <br><hr>
    <h3>How it works:</h3>
    <ul>
        <li>Enter a WhatsApp group invite link (format: https://chat.whatsapp.com/XXXXXXXXXXX)</li>
        <li>The system will check if the group exists by making a HTTP request</li>
        <li>Valid groups return HTTP 200, invalid/non-existent groups return HTTP 404</li>
    </ul>
    
    <p><strong>Example valid format:</strong> https://chat.whatsapp.com/JNFLBsYgUNC8wSyiMIlRrk</p>
</body>
</html>