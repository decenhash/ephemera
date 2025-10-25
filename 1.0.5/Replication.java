import java.io.BufferedReader;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.HashSet;
import java.util.Scanner;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class Replication {

    // Regex to find <a ... href="..." ...> tags.
    private static final Pattern LINK_PATTERN = 
        Pattern.compile("(?i)<a\\s+[^>]*href\\s*=\\s*['\"]([^'\"]+)['\"]");

    // --- NEW ---
    // A set of the *only* folder names we are allowed to download from.
    // Set.of() requires Java 9 or later.
    private static final Set<String> ALLOWED_FOLDERS = Set.of(
        "files", "json", "others", "json_search"
    );
    // If you are using Java 8 or older, use this instead:
    /*
    private static final Set<String> ALLOWED_FOLDERS = new HashSet<>(
        Arrays.asList("files", "json", "others", "json_search")
    );
    // (You would also need to add: import java.util.Arrays;)
    */

    public static void main(String[] args) {
        Scanner scanner = new Scanner(System.in);
        System.out.print("Enter the full URL to scan (e.g., http://example.com/files.php): ");
        String urlString = scanner.nextLine();
        scanner.close();

        URL baseUrl;
        try {
            baseUrl = new URL(urlString);
        } catch (MalformedURLException e) {
            System.err.println("Invalid URL provided. Please include http:// or https://");
            return;
        }

        try {
            // 1. Fetch the HTML content from the base URL
            String htmlContent = fetchHtml(baseUrl);
            if (htmlContent == null) {
                System.err.println("Could not fetch content from " + baseUrl);
                return;
            }

            // 2. Parse the HTML to find, filter, and download links
            parseAndDownloadLinks(htmlContent, baseUrl);

        } catch (IOException e) {
            System.err.println("An error occurred: " + e.getMessage());
        }
    }

    /**
     * Downloads the HTML content of a given URL as a String.
     */
    private static String fetchHtml(URL url) throws IOException {
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        connection.setRequestMethod("GET");
        connection.setRequestProperty("User-Agent", "Mozilla/5.0");

        int responseCode = connection.getResponseCode();
        if (responseCode == HttpURLConnection.HTTP_OK) {
            StringBuilder content = new StringBuilder();
            try (BufferedReader reader = new BufferedReader(new InputStreamReader(
                    connection.getInputStream(), StandardCharsets.UTF_8))) {
                String line;
                while ((line = reader.readLine()) != null) {
                    content.append(line).append(System.lineSeparator());
                }
            }
            return content.toString();
        } else {
            System.err.println("GET request failed. Response Code: " + responseCode);
            return null;
        }
    }

    /**
     * Parses HTML content, finds links, and downloads them
     * based on the specified folder structure rules.
     */
    private static void parseAndDownloadLinks(String html, URL baseUrl) {
        Matcher matcher = LINK_PATTERN.matcher(html);
        Set<URL> processedUrls = new HashSet<>(); // To avoid downloading the same URL multiple times

        while (matcher.find()) {
            String href = matcher.group(1); // Get the URL from the href attribute

            try {
                // Resolve the link (it could be relative, like "/files/1.jpg")
                URL absoluteUrl = new URL(baseUrl, href);

                // Ensure we only process http/https URLs and haven't processed this URL yet
                String protocol = absoluteUrl.getProtocol();
                if ((!protocol.equals("http") && !protocol.equals("https")) 
                        || !processedUrls.add(absoluteUrl)) {
                    continue; 
                }

                // Check the path structure
                String path = absoluteUrl.getPath();
                if (path.startsWith("/")) {
                    path = path.substring(1); // Remove leading slash
                }

                String[] parts = path.split("/");

                // --- MODIFIED LOGIC ---
                // We only want paths with exactly two parts: "folder/file.ext"
                if (parts.length == 2) {
                    String folderName = parts[0];
                    String fileName = parts[1];

                    // Check if the filename is not empty AND
                    // if the folder name is in our allowed list.
                    if (!fileName.isEmpty() && ALLOWED_FOLDERS.contains(folderName)) {
                        // 3. Download the file
                        downloadFile(absoluteUrl, folderName, fileName);
                    } else if (!fileName.isEmpty()) {
                        System.out.println("Skipping (disallowed folder): " + folderName + "/" + fileName);
                    }
                }
                // --- End of modified logic ---

            } catch (MalformedURLException e) {
                System.err.println("Skipping malformed link: " + href);
            }
        }
    }

    /**
     * Downloads a single file from a URL and saves it to a specific folder/file path.
     */
    private static void downloadFile(URL fileUrl, String folderName, String fileName) {
        try {
            // Create the target directory if it doesn't exist
            File directory = new File(folderName);
            if (!directory.exists()) {
                if (directory.mkdir()) {
                    System.out.println("Created directory: " + folderName);
                } else {
                    System.err.println("Failed to create directory: " + folderName);
                    return;
                }
            }

            // Create the final file object
            File localFile = new File(directory, fileName);

            // Check if file already exists
            if (localFile.exists()) {
                System.out.println("Skipping (already exists): " + localFile.getPath());
                return;
            }

            // Open connection to the file URL
            HttpURLConnection fileConnection = (HttpURLConnection) fileUrl.openConnection();
            fileConnection.setRequestProperty("User-Agent", "Mozilla/5.0");

            if (fileConnection.getResponseCode() == HttpURLConnection.HTTP_OK) {
                System.out.println("Downloading: " + fileUrl + " -> " + localFile.getPath());

                // Use try-with-resources to automatically close streams
                try (InputStream in = fileConnection.getInputStream();
                     FileOutputStream out = new FileOutputStream(localFile)) {
                    
                    byte[] buffer = new byte[4096];
                    int bytesRead;
                    while ((bytesRead = in.read(buffer)) != -1) {
                        out.write(buffer, 0, bytesRead);
                    }
                }
                System.out.println("Success: " + localFile.getPath());
            } else {
                System.err.println("Failed to download " + fileUrl + ". Server responded: " 
                    + fileConnection.getResponseCode());
            }

        } catch (IOException e) {
            System.err.println("Error downloading " + fileUrl + ": " + e.getMessage());
        }
    }
}