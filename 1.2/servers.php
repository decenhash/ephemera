<?php
// Get user search input
$search = $_GET['search'] ?? '';

function getBaseUrl($url) {
    // Extract base URL without the current file
    $parts = parse_url($url);
    $scheme = $parts['scheme'] ?? 'http';
    $host = $parts['host'] ?? '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    return "{$scheme}://{$host}{$port}";
}

// Read list of server URLs from servers.txt
$servers = file('servers.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$results = [];

if ($search) {
    foreach ($servers as $serverUrl) {
        $baseUrl = getBaseUrl($serverUrl);
        // Build JSON file path based on user input at /json_search/ folder
        $jsonUrl = rtrim($baseUrl, '/') . '/json_search/' . urlencode($search) . '.json';

        // Check if file exists by trying to fetch
        $content = @file_get_contents($jsonUrl);
        if ($content === false) {
            // File does not exist or cannot fetch, skip to next server
            continue;
        }

        // Decode JSON content expected to be an array of objects
        $dataArray = json_decode("[$content]", true);
        if (!is_array($dataArray)) {
            continue;
        }

        // Collect entries for this server
        foreach ($dataArray as $entry) {
            $results[] = [
                'title' => $entry['title'],
                'hash' => $entry['filename'],
                'ping' => '', // No ping available in JSON
                'url' => $jsonUrl,
                'baseUrl' => $baseUrl
            ];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>JSON Search Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 900px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4287f5;
            color: white;
        }
        tr:nth-child(even) {background-color: #f9f9f9;}
        a {
            color: #1a0dab;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h1>Search JSON Files</h1>

<form method="get" action="">
    <label for="search">Enter search term (file name):</label>
    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" required />
    <button type="submit">Search</button>
</form>

<?php if ($search): ?>
    <?php if (empty($results)): ?>
        <p>No JSON file found or no entries in file for "<strong><?= htmlspecialchars($search) ?></strong>".</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Hash</th>
                    <th>Ping</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td>
                            <a href="<?= htmlspecialchars($row['baseUrl'] . '/redirect.html?hash=' . $row['hash']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($row['title']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars($row['baseUrl'] . '/redirect.html?hash=' . $row['hash']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($row['hash']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($row['ping']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($row['baseUrl']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars($row['url']) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
