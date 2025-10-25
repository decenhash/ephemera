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
// Note: Regex imports are no longer needed
// import java.util.regex.Matcher;
// import java.util.regex.Pattern;

public class FileDownloader {

    // A set of the *only* folder names we are allowed to download from.
    private static final Set<String> ALLOWED_FOLDERS = Set.of(
        "files", "json", "others", "json_search"
    );

    // A single scanner to be used for all user input
    private static final Scanner scanner = new Scanner(System.in);

    public static void main(String[] args) {
        System.out.print("Enter the full URL to the text file (e.g., http://example.com/files.txt): ");
        String urlString = scanner.nextLine();

        URL textFileUrl;
        try {
            textFileUrl = new URL(urlString);
        } catch (MalformedURLException e) {
            System.err.println("Invalid URL provided. Please include http:// or https://");
            return;
        }

        try {
            // 1. Fetch the text content from the URL
            System.out.println("Fetching content from: " + textFileUrl);
            String textContent = fetchTextContent(textFileUrl);
            
            if (textContent == null || textContent.isEmpty()) {
                System.err.println("Could not fetch content or file is empty from " + textFileUrl);
            } else {
                System.out.println("Text file content fetched successfully.");
                // 2. Parse the text file line by line and download
                processFileLines(textContent, textFileUrl);
            }

        } catch (IOException e) {
            System.err.println("An error occurred: " + e.getMessage());
        }

        // Wait for user input before exiting
        System.out.println("\n--- All processing finished ---");
        System.out.println("Press Enter to exit...");
        scanner.nextLine(); // Wait for user to press Enter
        scanner.close(); // Close the scanner at the very end
    }

    /**
     * Downloads the text content of a given URL as a String.
     */
    private static String fetchTextContent(URL url) throws IOException {
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        connection.setRequestMethod("GET");
        connection.setRequestProperty("User-Agent", "Mozilla/5.0");
        connection.setInstanceFollowRedirects(true); 

        int responseCode = connection.getResponseCode();
        if (responseCode == HttpURLConnection.HTTP_OK) { // 200
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
            System.err.println("GET request failed. Server responded with: " + responseCode);
            return null;
        }
    }

    /**
     * Parses the text content line by line and attempts to download
     * based on the specified folder structure rules.
     */
    private static void processFileLines(String fileContent, URL textFileUrl) {
        // \R is a regex for any universal newline character (e.g., \n, \r\n)
        String[] lines = fileContent.split("\\R");
        
        int totalLines = 0;
        int downloadedCount = 0;

        for (String line : lines) {
            totalLines++;
            String path = line.trim();

            // Skip empty lines or comment lines
            if (path.isEmpty() || path.startsWith("#")) {
                continue;
            }

            String[] parts = path.split("/");

            try {
                // --- This is the core logic from your request ---
                // We only want paths with exactly two parts: "folder/file.ext"
                if (parts.length == 2) {
                    String folderName = parts[0];
                    String fileName = parts[1];

                    if (fileName.isEmpty()) {
                        System.out.println("Skipping (directory path): " + path);
                    } else if (ALLOWED_FOLDERS.contains(folderName)) {
                        // This path matches all criteria.
                        // Resolve the relative path (e.g., "json/test.json")
                        // against the text file's URL (e.g., "http://example.com/files.txt")
                        // to get the full URL (e.g., "http://example.com/json/test.json")
                        URL absoluteUrl = new URL(textFileUrl, path);

                        // Attempt to download the file
                        if (downloadFile(absoluteUrl, folderName, fileName)) {
                            downloadedCount++;
                        }
                    } else {
                        System.out.println("Skipping (disallowed folder): " + path);
                    }
                } else if (parts.length == 1 && !parts[0].isEmpty()) {
                    System.out.println("Skipping (root file): " + path);
                } else if (parts.length > 2) {
                    System.out.println("Skipping (nested path): " + path);
                }
                // --- End of core logic ---

            } catch (MalformedURLException e) {
                System.err.println("Skipping malformed URL for path: " + path);
            }
        }
        
        // Print the final summary
        System.out.println("\n--- Summary ---");
        System.out.println("Total lines processed: " + totalLines);
        System.out.println("Files successfully downloaded: " + downloadedCount);
    }

    /**
     * Downloads a file.
     * @return true if download was successful, false otherwise.
     */
    private static boolean downloadFile(URL fileUrl, String folderName, String fileName) {
        File directory = new File(folderName);
        if (!directory.exists()) {
            if (directory.mkdir()) {
                System.out.println("Created directory: " + folderName);
            } else {
                System.err.println("Failed to create directory: " + folderName);
                return false;
            }
        }

        File localFile = new File(directory, fileName);

        if (localFile.exists()) {
            System.out.println("Skipping (already exists): " + localFile.getPath());
            return false; // Not a new download
        }

        try {
            HttpURLConnection fileConnection = (HttpURLConnection) fileUrl.openConnection();
            fileConnection.setRequestProperty("User-Agent", "Mozilla/5.0");
            fileConnection.setInstanceFollowRedirects(true);

            if (fileConnection.getResponseCode() == HttpURLConnection.HTTP_OK) {
                System.out.println("Downloading: " + fileUrl + " -> " + localFile.getPath());

                try (InputStream in = fileConnection.getInputStream();
                     FileOutputStream out = new FileOutputStream(localFile)) {
                    
                    byte[] buffer = new byte[4096];
                    int bytesRead;
                    while ((bytesRead = in.read(buffer)) != -1) {
                        out.write(buffer, 0, bytesRead);
                    }
                }
                System.out.println("Success: " + localFile.getPath());
                return true; // Download was successful
            } else {
                // This is common, e.g., 404 Not Found
                System.err.println("Failed to download " + fileUrl + ". Server responded: " 
                    + fileConnection.getResponseCode());
                return false;
            }
        } catch (IOException e) {
            System.err.println("Error downloading " + fileUrl + ": " + e.getMessage());
            return false;
        }
    }
}