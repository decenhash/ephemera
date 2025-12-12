import javax.swing.*;
import javax.swing.event.HyperlinkEvent;
import java.awt.*;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.InetSocketAddress;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.nio.file.*;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.*;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

import com.sun.net.httpserver.HttpServer;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpExchange;

public class E2EPHEMERA extends JFrame {

    private final JEditorPane resultPane;
    private final JTextField searchField;
    private final JButton btnRank;
    private final JButton btnDownload;
    private final JButton btnServersDownload;
    private final JButton btnStartServer;
    private final JButton btnCleanUp;
    private final JButton btnGuide;
    private final JButton btnSearch;
    private final JButton btnView;
    private final ExecutorService executor;
    private final StringBuilder htmlContent = new StringBuilder();
    private HttpServer httpServer;

    // --- New HttpHandler for serving files ---
    private class FileServerHandler implements HttpHandler {
        @Override
        public void handle(HttpExchange exchange) throws IOException {
            String path = exchange.getRequestURI().getPath();
            File requestedFile = null;
            
            // 1. Handle files.txt request
            if (path.equals("/files.txt")) {
                requestedFile = new File("files.txt");
            } 

            // 2. Handle servers.txt request
            if (path.equals("/servers.txt")) {
                requestedFile = new File("servers.txt");
            }

            // 3. Handle /files/ directory requests
            else if (path.startsWith("/files/") && path.length() > 7) {
                String filename = path.substring("/files/".length());
                
                // Security check: Prevent directory traversal (e.g., /files/../secrets.txt)
                if (filename.contains("..")) {
                    sendResponse(exchange, 403, "Forbidden");
                    return;
                }
                requestedFile = new File("files", filename);
            }

            if (requestedFile != null && requestedFile.exists() && requestedFile.isFile()) {
                // Serve the file
                try {
                    byte[] fileBytes = Files.readAllBytes(requestedFile.toPath());
                    
                    // Determine Content-Type
                    String contentType = Files.probeContentType(requestedFile.toPath());
                    if (contentType == null) {
                        contentType = "application/octet-stream"; // Default binary stream
                    }
                    
                    exchange.getResponseHeaders().set("Content-Type", contentType);
                    exchange.sendResponseHeaders(200, fileBytes.length);
                    
                    OutputStream os = exchange.getResponseBody();
                    os.write(fileBytes);
                    os.close();
                } catch (IOException e) {
                    sendResponse(exchange, 500, "Internal Server Error reading file.");
                }
            } else if (path.equals("/") || exchange.getRequestURI().getQuery() != null) {
                // 3. Handle base URL or propagation query (?server=...)
                // These requests receive a generic success message
                sendResponse(exchange, 200, "Server is online.");
            } else {
                // 4. Handle 404 Not Found
                sendResponse(exchange, 404, "Not Found");
            }
        }
        
        private void sendResponse(HttpExchange exchange, int statusCode, String response) throws IOException {
            byte[] responseBytes = response.getBytes(StandardCharsets.UTF_8);
            exchange.sendResponseHeaders(statusCode, responseBytes.length);
            OutputStream os = exchange.getResponseBody();
            os.write(responseBytes);
            os.close();
        }
    }

    public E2EPHEMERA() {
        super("E2EPHEMERA");
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setSize(1250, 700);
        setLayout(new BorderLayout());

        // Top Panel for Inputs
        JPanel topPanel = new JPanel(new FlowLayout());
        topPanel.setBackground(new Color(0, 128, 128)); // Bright cyan
        
        btnRank = new JButton("Rank");
        btnDownload = new JButton("Download");
        btnServersDownload = new JButton("Servers Download");
        btnStartServer = new JButton("Start Server");
        btnCleanUp = new JButton("Clean Up");
        btnGuide = new JButton("Guide");
        
        JLabel lblSearch = new JLabel("User:");
        searchField = new JTextField(12);
        btnSearch = new JButton("Search");
        btnView = new JButton("View");

        topPanel.add(btnRank);
        topPanel.add(btnDownload);
        topPanel.add(btnServersDownload);
        topPanel.add(btnStartServer);
        topPanel.add(btnCleanUp);
        topPanel.add(btnGuide);
        
        topPanel.add(new JSeparator(SwingConstants.VERTICAL));
        topPanel.add(lblSearch);
        topPanel.add(searchField);
        topPanel.add(btnSearch);
        topPanel.add(btnView);

        add(topPanel, BorderLayout.NORTH);

        // Center Area
        resultPane = new JEditorPane();
        resultPane.setEditable(false);
        resultPane.setContentType("text/html");
        resultPane.addHyperlinkListener(e -> {
            if (e.getEventType() == HyperlinkEvent.EventType.ACTIVATED) {
                try {
                    Desktop.getDesktop().browse(e.getURL().toURI());
                } catch (Exception ex) {
                    JOptionPane.showMessageDialog(this, "Error opening link: " + ex.getMessage());
                }
            }
        });
        add(new JScrollPane(resultPane), BorderLayout.CENTER);

        executor = Executors.newCachedThreadPool();

        // Action Listeners
        btnRank.addActionListener(e -> executor.submit(this::processRank));
        btnDownload.addActionListener(e -> executor.submit(this::processDownload));
        btnServersDownload.addActionListener(e -> executor.submit(this::processServersDownload));
        btnStartServer.addActionListener(e -> executor.submit(this::processStartServer));
        btnCleanUp.addActionListener(e -> executor.submit(this::processCleanUp));
        btnGuide.addActionListener(e -> executor.submit(this::processGuide));
        btnSearch.addActionListener(e -> {
            String input = searchField.getText().trim();
            if (!input.isEmpty()) executor.submit(() -> processSearch(input));
            else log("Please enter a username.");
        });
        btnView.addActionListener(e -> {
            String input = searchField.getText().trim();
            if (!input.isEmpty()) executor.submit(() -> processView(input));
            else log("Please enter a username to view.");
        });
        
        processGuide(); 
    }

    // --- LOGIC: START SERVER (REVISED) ---
    private void processStartServer() {
        clearScreen();
        log("<b>--- Starting Server Process (Serving files.txt, servers.txt and /files/) ---</b>");

        // 1. Start HTTP Server on port 52525
        try {
            if (httpServer == null) {
                httpServer = HttpServer.create(new InetSocketAddress(52525), 0);
                // Use the new handler for all requests
                httpServer.createContext("/", new FileServerHandler()); 
                httpServer.setExecutor(null); 
                httpServer.start();
                log("<span style='color:green'>HTTP Server successfully started on port 52525.</span>");
                log("Accessible paths: <b>/files.txt</b> and files inside <b>/files/</b>");
            } else {
                log("Server is already running on port 52525.");
            }
        } catch (IOException e) {
            log("<span style='color:red'>Error starting server: " + e.getMessage() + "</span>");
            return;
        }

        // 2. Prepare Requests (Propagation Logic remains)
        File serversFile = new File("servers.txt");
        if (!serversFile.exists()) {
            log("servers.txt not found. Cannot perform propagation requests.");
            return;
        }

        List<String> serverList = new ArrayList<>();
        try (BufferedReader br = new BufferedReader(new FileReader(serversFile))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (!line.isEmpty() && isValidURL(line)) {
                    serverList.add(line);
                }
            }
        } catch (IOException e) {
            log("Error reading servers.txt: " + e.getMessage());
            return;
        }

        if (serverList.isEmpty()) {
            log("No valid URLs found in servers.txt for propagation.");
            return;
        }

        // 3. Determine "My Identity"
        String myIdentity = "";
        File myServerFile = new File("my_server.txt");
        if (myServerFile.exists()) {
            try {
                myIdentity = new String(Files.readAllBytes(myServerFile.toPath())).trim();
            } catch (IOException e) { /* Ignore */ }
        }

        if (myIdentity.isEmpty()) {
            log("my_server.txt missing or empty. Fetching IP...");
            myIdentity = getPublicIpAddress();
            log("Using Identity (IP): " + myIdentity);
        } else {
            log("Using Identity (File): " + myIdentity);
        }

        // 4. Perform Random Requests
        Random rand = new Random();

        // Task A: 5 Random Checks (Domain only)
        log("<br><b>Performing 5 Random Health Checks:</b>");
        for (int i = 0; i < 5; i++) {
            String randomUrl = serverList.get(rand.nextInt(serverList.size()));
            try {
                URL url = new URL(randomUrl);
                String domainRoot = url.getProtocol() + "://" + url.getHost();
                if (url.getPort() != -1) domainRoot += ":" + url.getPort();
                
                log("Checking: " + domainRoot);
                boolean status = checkUrlExists(domainRoot);
                log("-> " + (status ? "<span style='color:green'>Online</span>" : "<span style='color:red'>Offline</span>"));
            } catch (Exception e) {
                log("Invalid URL: " + randomUrl);
            }
        }

        // Task B: 5 Random Propagation Requests (with ?server=)
        log("<br><b>Performing 5 Random Propagation Requests:</b>");
        for (int i = 0; i < 5; i++) {
            String randomUrl = serverList.get(rand.nextInt(serverList.size()));
            try {
                String targetUrl = randomUrl;
                if (targetUrl.contains("?")) targetUrl += "&server=" + myIdentity;
                else targetUrl += "?server=" + myIdentity;

                log("Sending to: " + targetUrl);
                int code = sendGetRequest(targetUrl);
                log("-> Response Code: " + code);

            } catch (Exception e) {
                log("Request failed: " + e.getMessage());
            }
        }
        
        log("<br>Server start and propagation tasks completed.");
    }
    
    // --- LOGIC: GUIDE ---
    private void processGuide() {
        clearScreen();
        StringBuilder sb = new StringBuilder();
        sb.append("<h2>Application Guide</h2>");
        sb.append("<p>Welcome to the P2P File Processor. Below is a description of each function:</p>");
        
        sb.append("<hr>");
        
        sb.append("<h3>1. Rank</h3>");
        sb.append("<ul>");
        sb.append("<li>Reads <b>servers.txt</b>.</li>");
        sb.append("<li>Calculates the frequency of filenames found in the list.</li>");
        sb.append("<li>Displays the <b>TOP 100</b> most repeated files.</li>");
        sb.append("</ul>");

        sb.append("<h3>2. Download</h3>");
        sb.append("<ul>");
        sb.append("<li>Reads URLs from <b>servers.txt</b>.</li>");
        sb.append("<li>Downloads the files to the local folder <b>/files/</b>.</li>");
        sb.append("<li>Attempts to find and download corresponding JSON metadata to <b>/json/</b>.</li>");
        sb.append("</ul>");

        sb.append("<h3>3. Servers Download</h3>");
        sb.append("<ul>");
        sb.append("<li>Reads <b>servers.txt</b> to find server domains.</li>");
        sb.append("<li>Connects to those servers to look for their specific <b>/servers.txt</b> list.</li>");
        sb.append("<li>Consolidates the remote file lists and downloads the files and metadata to your local folders.</li>");
        sb.append("</ul>");

        sb.append("<h3>4. Start Server</h3>");
        sb.append("<ul>");
        sb.append("<li>Starts a lightweight HTTP server on <b>Port 52525</b>.</li>");
        sb.append("<li>The server is configured to serve <b>files.txt</b>, <b>servers.txt</b> and any files inside the <b>/files/</b> directory (e.g., <code>http://localhost:52525/files/hash.ext</code>).</li>");
        sb.append("<li>Performs a propagation sequence: Health checks on 5 random servers and sends an announcement (<i>?server=YOUR_IDENTITY</i>) to 5 random servers.</li>");
        sb.append("<li>Identity is read from <b>my_server.txt</b> or defaults to your Public IP.</li>");
        sb.append("</ul>");

        sb.append("<h3>5. Clean Up</h3>");
        sb.append("<ul>");
        sb.append("<li><b>Local Files:</b> Scans the <b>/files/</b> directory. Renames every file to its <b>SHA-256 hash</b> (preserving extension).</li>");
        sb.append("<li><b>files.txt:</b> Completely overwrites this file with the new list of local hashed filenames.</li>");
        sb.append("<li><b>servers.txt:</b> Checks every URL. Removes invalid URLs or servers that are currently <b>offline</b>.</li>");
        sb.append("</ul>");

        sb.append("<h3>6. Search & View</h3>");
        sb.append("<ul>");
        sb.append("<li><b>Search:</b> Enter a username. The app checks servers in <b>servers.txt</b> for <i>/json_search/[user].json</i> and saves matches locally.</li>");
        sb.append("<li><b>View:</b> Parses the downloaded search results and displays clickable links to the content.</li>");
        sb.append("</ul>");

        sb.append("<h3>7. Donate</h3>");
        sb.append("<ul>");
        sb.append("<li><b>BTC:</b> bc1ql0l8hsnaqgamlz2cvs53hs7ntszwrthera00sz</li>");
        sb.append("<li><b><a href='https://www.paypal.com/donate/?hosted_button_id=P7QXZJ3X7SVSE'>https://www.paypal.com/donate/?hosted_button_id=P7QXZJ3X7SVSE</a></li>");
        sb.append("</ul>");

        sb.append("<h3>8. Web</h3>");
        sb.append("<ul>");
        sb.append("<li><b><a href='https://3gp.neocities.org/'>https://3gp.neocities.org/</a></li>");
        sb.append("<li><b><a href='https://geocities.ws/decenhash/'>http://geocities.ws/decenhash/</a></li>");
        sb.append("<li><b><a href='https://sourceforge.net/projects/decenhash/'>https://sourceforge.net/projects/decenhash/</a></li>");
        sb.append("<li><b><a href='https://github.com/decenhash/'>https://github.com/decenhash/</a></li>");
        sb.append("</ul>");
        
        
        appendHtml(sb.toString());
    }
    
    // --- LOGIC: CLEAN UP ---
    private void processCleanUp() {
        clearScreen();
        log("<b>--- Starting Clean Up Process ---</b>");

        // Part 1: Process 'files' folder
        File filesDir = new File("files");
        List<String> validFilenames = new ArrayList<>();

        if (filesDir.exists() && filesDir.isDirectory()) {
            File[] files = filesDir.listFiles();
            if (files != null) {
                log("Processing " + files.length + " files in /files/...");
                for (File f : files) {
                    if (f.isFile()) {
                        try {
                            String hash = calculateSHA256(f);
                            String originalName = f.getName();
                            String extension = "";
                            int i = originalName.lastIndexOf('.');
                            if (i >= 0) {
                                extension = originalName.substring(i);
                            }

                            String nameWithoutExt = (i >= 0) ? originalName.substring(0, i) : originalName;
                            String newFilename = hash + extension;

                            if (!nameWithoutExt.equalsIgnoreCase(hash)) {
                                File dest = new File(filesDir, newFilename);
                                if (f.renameTo(dest)) {
                                    log("Renamed: " + originalName + " -> " + newFilename);
                                    validFilenames.add(newFilename);
                                } else {
                                    log("Failed to rename: " + originalName);
                                    validFilenames.add(newFilename); 
                                }
                            } else {
                                log("Skipped (Already Hash): " + originalName);
                                validFilenames.add(originalName);
                            }

                        } catch (Exception e) {
                            log("Error processing file " + f.getName() + ": " + e.getMessage());
                        }
                    }
                }
            }
        } else {
            log("Directory 'files' does not exist.");
        }

        // Part 2: Overwrite files.txt
        try (BufferedWriter bw = new BufferedWriter(new FileWriter("files.txt"))) {
            for (String name : validFilenames) {
                bw.write(name);
                bw.newLine();
            }
            log("files.txt updated with " + validFilenames.size() + " entries.");
        } catch (IOException e) {
            log("Error writing files.txt: " + e.getMessage());
        }

        // Part 3: Clean servers.txt
        log("<br>Cleaning servers.txt...");

        File serversFile = new File("servers.txt");
        List<String> validServers = new ArrayList<>();
        Set<String> seenServers = new HashSet<>(); // 1. Create a Set to track duplicates

        if (serversFile.exists()) {
            try (BufferedReader br = new BufferedReader(new FileReader(serversFile))) {
                String line;
                while ((line = br.readLine()) != null) {
                    line = line.trim();
                    if (line.isEmpty()) continue;

                    // 2. Check for duplicates immediately
                    if (seenServers.contains(line)) {
                        log("Duplicate skipped: " + line);
                        continue; // Skip the rest of the loop for this line
                    }

                    seenServers.add(line); // Mark this URL as seen

                    if (isValidURL(line)) {
                        log("Checking: " + line);
                        if (checkUrlExists(line)) {
                            validServers.add(line);
                            log("-> <span style='color:green'>Online</span>");
                        } else {
                            log("-> <span style='color:red'>Offline (Removed)</span>");
                        }
                    } else {
                        log("Invalid URL removed: " + line);
                    }
                }
            } catch (IOException e) {
                log("Error reading servers.txt: " + e.getMessage());
            }

            try (BufferedWriter bw = new BufferedWriter(new FileWriter(serversFile))) {
                for (String server : validServers) {
                bw.write(server);
                bw.newLine();
                }
                log("servers.txt updated. Retained: " + validServers.size());
            } catch (IOException e) {
                log("Error writing servers.txt: " + e.getMessage());
            }
        } else {
            log("servers.txt not found.");
        }
        log("<br>Clean Up Finished.");
    }
    
    // --- LOGIC: RANK ---
    private void processRank() {
        clearScreen();
        log("<b>--- Starting Ranking Process ---</b>");
        File file = new File("servers.txt");
        if (!file.exists()) { log("servers.txt not found."); return; }

        Map<String, Integer> frequencyMap = new HashMap<>();
        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty()) continue;
                String filename = getFilenameFromPath(line);
                frequencyMap.put(filename, frequencyMap.getOrDefault(filename, 0) + 1);
            }
            
            List<Map.Entry<String, Integer>> sortedList = frequencyMap.entrySet().stream()
                    .sorted((e1, e2) -> e2.getValue().compareTo(e1.getValue()))
                    .limit(100)
                    .collect(Collectors.toList());

            SwingUtilities.invokeLater(() -> {
                appendHtml("<h3>TOP 100 REPEATED FILES:</h3><table border='1' cellpadding='5' style='border-collapse:collapse;'><tr><th>Filename</th><th>Count</th></tr>");
                for (Map.Entry<String, Integer> entry : sortedList) {
                    appendHtml("<tr><td>" + entry.getKey() + "</td><td>" + entry.getValue() + "</td></tr>");
                }
                appendHtml("</table><br>Done.");
            });
        } catch (IOException e) { log("Error: " + e.getMessage()); }
    }

    // --- LOGIC: DOWNLOAD ---
    private void processDownload() {
        clearScreen();
        log("<b>--- Starting Download Process ---</b>");
        File file = new File("files.txt");
        if (!file.exists()) { log("files.txt not found."); return; }
        
        createDirectory("files");
        createDirectory("json");

        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (!line.isEmpty() && isValidURL(line)) {
                    processSingleDownloadUrl(line);
                }
            }
            log("Download process finished.");
        } catch (IOException e) { log("Error: " + e.getMessage()); }
    }

// --- 1. REVISED: SERVERS DOWNLOAD ---
    private void processServersDownload() {
        clearScreen();
        log("<b>--- Starting Servers Download Process ---</b>");
        File file = new File("servers.txt");
        if (!file.exists()) { log("servers.txt not found."); return; }

        createDirectory("files");
        createDirectory("json");
        Set<String> processedDomains = new HashSet<>();

        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || !isValidURL(line)) continue;

                try {
                    URL url = new URL(line);
                    
                    // FIX #1: PRESERVE PORT NUMBER
                    // getAuthority() returns "host:port" (e.g. localhost:52525)
                    // getHost() only returns "localhost" (Port is lost)
                    String domainBase = url.getProtocol() + "://" + url.getAuthority();
                    
                    if (processedDomains.contains(domainBase)) continue;
                    processedDomains.add(domainBase);

                    String remoteFilesTxtUrl = domainBase + "/files.txt";
                    log("Checking remote list: " + remoteFilesTxtUrl);

                    // FIX #2: REMOVE "checkUrlExists"
                    // Directly attempt to download the string. If it fails, result is null.
                    String remoteContent = downloadToString(remoteFilesTxtUrl);

                    if (remoteContent != null && !remoteContent.isEmpty()) {
                        log("<span style='color:blue'>Found files.txt. Processing...</span>");
                        
                        // Parse the list
                        try (BufferedReader remoteReader = new BufferedReader(new StringReader(remoteContent))) {
                            String remoteLine;
                            while ((remoteLine = remoteReader.readLine()) != null) {
                                remoteLine = remoteLine.trim();
                                if (remoteLine.isEmpty()) continue;

                                String downloadUrl;
                                // FIX #3: Handle Absolute vs Relative URLs correcty
                                if (isValidURL(remoteLine)) {
                                    downloadUrl = remoteLine;
                                } else {
                                    // Ensure we don't double slash if not needed, though browsers usually handle it
                                    downloadUrl = domainBase + "/files/" + remoteLine;
                                }
                                
                                // Process the download (this method is updated below)
                                processSingleDownloadUrl(downloadUrl);
                            }
                        }
                    } else {
                        log("Could not retrieve file list from " + domainBase);
                    }

                } catch (Exception e) { 
                    log("Error processing server " + line + ": " + e.getMessage());
                }
            }
            log("Servers Download finished.");
        } catch (IOException e) { log("Error: " + e.getMessage()); }
    }

    

    // --- 3. REVISED: SINGLE DOWNLOAD LOGIC ---
    private void processSingleDownloadUrl(String urlString) {
        try {
            // Sanitize filename (remove URL params)
            String rawFilename = getFilenameFromPath(urlString);
            if (rawFilename.contains("?")) rawFilename = rawFilename.split("\\?")[0];
            String filename = java.net.URLDecoder.decode(rawFilename, StandardCharsets.UTF_8.name());

            File targetFile = new File("files", filename);
            
            // Always try to download (Removed exists() check to ensure we get valid files)
            log("Downloading: " + filename);
            if (downloadFile(urlString, targetFile)) {
                log("-> Success");
                
                // Only look for JSON if file download worked
                // Calculate JSON URL
                URL url = new URL(urlString);
                String host = url.getHost(); // Here getHost is fine (no port needed usually for relative logic if path is absolute)
                String protocol = url.getProtocol();
                int port = url.getPort();
                
                // Reconstruct base properly with port if needed
                StringBuilder jsonUrlBase = new StringBuilder(protocol + "://" + host);
                if (port != -1) jsonUrlBase.append(":").append(port);
                
                String jsonName = filename.contains(".") 
                        ? filename.substring(0, filename.lastIndexOf('.')) + ".json" 
                        : filename + ".json";
                        
                String jsonUrlString = jsonUrlBase + "/json/" + jsonName;
                File targetJson = new File("json", jsonName);

                if (!targetJson.exists()) {
                    downloadFile(jsonUrlString, targetJson);
                }

            } else {
                log("-> Failed");
            }
        } catch (Exception e) { 
             log("Error: " + e.getMessage());
        }
    }
    
    // --- NEW ROBUST DOWNLOADER ---
    private boolean downloadFile(String urlString, File destination) {
        HttpURLConnection conn = null;
        try {
            URL url = new URL(urlString);
            conn = (HttpURLConnection) url.openConnection();
            
            // A. Fake a Browser User-Agent (Critical for Neocities/Cloudflare)
            conn.setRequestProperty("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
            conn.setConnectTimeout(5000);
            conn.setReadTimeout(5000);
            conn.setInstanceFollowRedirects(true); // Attempt auto-redirect
            
            int status = conn.getResponseCode();

            // B. Handle Manual Redirects (Java sometimes fails HTTP->HTTPS auto-redirects)
            if (status == HttpURLConnection.HTTP_MOVED_TEMP || status == HttpURLConnection.HTTP_MOVED_PERM || status == 307 || status == 308) {
                String newUrl = conn.getHeaderField("Location");
                if (newUrl != null) {
                    // Recursively try the new URL
                    return downloadFile(newUrl, destination);
                }
            }

            // C. Only download if status is strictly 200 (OK)
            if (status == HttpURLConnection.HTTP_OK) {
                try (InputStream in = conn.getInputStream()) {
                    Files.copy(in, destination.toPath(), StandardCopyOption.REPLACE_EXISTING);
                    return true;
                }
            } else {
                // Log failure for non-200 codes (403, 404, 500)
                // log("Server returned code: " + status); // Optional debugging
                return false;
            }
        } catch (Exception e) {
            // log("Exception: " + e.getMessage()); // Optional debugging
            return false;
        } finally {
            if (conn != null) conn.disconnect();
        }
    }

    // --- LOGIC: SEARCH ---
    private void processSearch(String userInput) {
        clearScreen();
        log("<b>--- Search: " + userInput + " ---</b>");
        File file = new File("servers.txt");
        if (!file.exists()) { log("servers.txt not found."); return; }

        createDirectory("json_search");
        Set<String> testedDomains = new HashSet<>();
        File consolidatedFile = new File("json_search", userInput + ".json");
        Set<String> existingFilenames = loadExistingFilenames(consolidatedFile);

        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || !isValidURL(line)) continue;

                try {
                    URL url = new URL(line);
                    String domain = url.getHost();
                    String protocol = url.getProtocol();
                    if (testedDomains.contains(domain)) continue;
                    testedDomains.add(domain);

                    String searchUrl = protocol + "://" + domain + "/json_search/" + userInput + ".json";
                    log("Checking: " + searchUrl);

                    if (checkUrlExists(searchUrl)) {
                        log("<span style='color:green;'>Match found on: " + domain + "</span>");
                        String content = downloadToString(searchUrl);
                        if (content != null) {
                            int hashCode = content.hashCode();
                            File saveFile = new File("json_search", userInput.toLowerCase() + "_" + hashCode + ".json");
                            try (BufferedWriter w = new BufferedWriter(new FileWriter(saveFile))) { w.write(content); }
                            updateConsolidatedFile(consolidatedFile, content, existingFilenames);
                        }
                    }
                } catch (Exception e) { /* Ignore */ }
            }
            log("Search finished.");
        } catch (IOException e) { log("Error: " + e.getMessage()); }
    }

    // --- LOGIC: VIEW ---
    private void processView(String userInput) {
        clearScreen();
        log("<b>--- View Mode: " + userInput + " ---</b>");
        File file = new File("servers.txt");
        if (!file.exists()) { log("servers.txt not found."); return; }

        Set<String> testedDomains = new HashSet<>();
        boolean foundAny = false;

        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || !isValidURL(line)) continue;

                try {
                    URL url = new URL(line);
                    String domain = url.getHost();
                    String protocol = url.getProtocol();
                    String baseUrl = protocol + "://" + domain;

                    if (testedDomains.contains(domain)) continue;
                    testedDomains.add(domain);

                    String searchUrl = baseUrl + "/json_search/" + userInput + ".json";
                    log("Checking: " + searchUrl);

                    if (checkUrlExists(searchUrl)) {
                        foundAny = true;
                        String content = downloadToString(searchUrl);
                        if (content != null) {
                            appendHtml("<br><b>Results from " + domain + ":</b><ul>");
                            Pattern objectPattern = Pattern.compile("\\{\\s*\"title\"\\s*:\\s*\"([^\"]+)\"\\s*,\\s*\"filename\"\\s*:\\s*\"([^\"]+)\"\\s*\\}");
                            Matcher m = objectPattern.matcher(content);
                            while (m.find()) {
                                String title = m.group(1);
                                String hashFilename = m.group(2);
                                String redirectLink = baseUrl + "/redirect.html?hash=" + hashFilename;
                                appendHtml("<li><a href='" + redirectLink + "'>" + title + "</a></li>");
                            }
                            appendHtml("</ul>");
                        }
                    }
                } catch (Exception e) { /* Ignore */ }
            }
            if (!foundAny) log("No results found.");
            else log("<br>View process finished.");
        } catch (IOException e) { log("Error: " + e.getMessage()); }
    }

    // --- HELPERS ---
    private String calculateSHA256(File file) throws IOException, NoSuchAlgorithmException {
        MessageDigest digest = MessageDigest.getInstance("SHA-256");
        try (InputStream fis = new FileInputStream(file)) {
            byte[] byteArray = new byte[1024];
            int bytesCount; 
            while ((bytesCount = fis.read(byteArray)) != -1) {
                digest.update(byteArray, 0, bytesCount);
            }
        }
        byte[] bytes = digest.digest();
        StringBuilder sb = new StringBuilder();
        for (byte b : bytes) {
            sb.append(String.format("%02x", b));
        }
        return sb.toString();
    }

// --- 2. REVISED: ROBUST LIST DOWNLOADER ---
    private String downloadToString(String urlString) {
        StringBuilder result = new StringBuilder();
        HttpURLConnection conn = null;
        try {
            URL url = new URL(urlString);
            conn = (HttpURLConnection) url.openConnection();
            
            // Fix: Add User-Agent and Redirects for the LIST download too
            conn.setRequestProperty("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
            conn.setConnectTimeout(5000);
            conn.setReadTimeout(5000);
            conn.setInstanceFollowRedirects(true);

            int status = conn.getResponseCode();
            
            // Handle Manual Redirects
            if (status == HttpURLConnection.HTTP_MOVED_TEMP || status == HttpURLConnection.HTTP_MOVED_PERM || status == 307 || status == 308) {
                String newUrl = conn.getHeaderField("Location");
                if (newUrl != null) return downloadToString(newUrl);
            }

            if (status == HttpURLConnection.HTTP_OK) {
                try (BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8))) {
                    String line;
                    while ((line = reader.readLine()) != null) {
                        result.append(line).append("\n"); // Append newline to keep list format
                    }
                }
                return result.toString();
            }
        } catch (Exception e) { 
            // log("Failed to fetch list: " + urlString); // Debugging
        } finally {
            if (conn != null) conn.disconnect();
        }
        return null; // Return null on failure
    }

    private Set<String> loadExistingFilenames(File file) {
        Set<String> filenames = new HashSet<>();
        if (!file.exists()) return filenames;
        try (BufferedReader br = new BufferedReader(new FileReader(file))) {
            StringBuilder sb = new StringBuilder();
            String line;
            while ((line = br.readLine()) != null) sb.append(line);
            Pattern p = Pattern.compile("\"filename\"\\s*:\\s*\"([^\"]+)\"");
            Matcher m = p.matcher(sb.toString());
            while (m.find()) filenames.add(m.group(1));
        } catch (IOException e) { /* */ }
        return filenames;
    }

    private void updateConsolidatedFile(File file, String newJsonContent, Set<String> existingFilenames) {
        Pattern objectPattern = Pattern.compile("\\{\\s*\"title\"\\s*:\\s*\"([^\"]+)\"\\s*,\\s*\"filename\"\\s*:\\s*\"([^\"]+)\"\\s*\\}");
        Matcher m = objectPattern.matcher(newJsonContent);
        try (FileWriter fw = new FileWriter(file, true); BufferedWriter bw = new BufferedWriter(fw)) {
            while (m.find()) {
                String title = m.group(1);
                String filename = m.group(2);
                if (!existingFilenames.contains(filename)) {
                    if (file.length() > 0) bw.write(",\n");
                    bw.write(String.format("{\n    \"title\": \"%s\",\n    \"filename\": \"%s\"\n}", title, filename));
                    existingFilenames.add(filename);
                    log("Added: " + title);
                }
            }
        } catch (IOException e) { log("Write error: " + e.getMessage()); }
    }

    private String getFilenameFromPath(String path) {
        if (path.contains("/")) return path.substring(path.lastIndexOf('/') + 1);
        return path;
    }

    private boolean isValidURL(String url) {
        return url.toLowerCase().startsWith("http");
    }

    private void createDirectory(String path) {
        File dir = new File(path);
        if (!dir.exists()) dir.mkdirs();
    }

    private boolean checkUrlExists(String urlString) {
        try {
            URL url = new URL(urlString);
            HttpURLConnection huc = (HttpURLConnection) url.openConnection();
            huc.setRequestMethod("HEAD");
            huc.setConnectTimeout(2000);
            huc.setReadTimeout(2000);
            return (huc.getResponseCode() == HttpURLConnection.HTTP_OK);
        } catch (Exception e) { return false; }
    }
    
    private int sendGetRequest(String urlString) {
        try {
            URL url = new URL(urlString);
            HttpURLConnection huc = (HttpURLConnection) url.openConnection();
            huc.setRequestMethod("GET");
            huc.setConnectTimeout(2000);
            huc.setReadTimeout(2000);
            return huc.getResponseCode();
        } catch (Exception e) { return -1; }
    }


    private String getPublicIpAddress() {
        try {
            return downloadToString("http://checkip.amazonaws.com").trim();
        } catch (Exception e) {
            try {
                return java.net.InetAddress.getLocalHost().getHostAddress();
            } catch (Exception ex) {
                return "127.0.0.1";
            }
        }
    }

    private void clearScreen() {
        htmlContent.setLength(0);
        htmlContent.append("<html><body style='font-family:monospaced; font-size:12px;'>");
        updatePane();
    }

    private void log(String message) {
        SwingUtilities.invokeLater(() -> {
            htmlContent.append(message).append("<br>");
            updatePane();
        });
    }

    private void appendHtml(String html) {
        SwingUtilities.invokeLater(() -> {
            htmlContent.append(html);
            updatePane();
        });
    }

    private void updatePane() {
        resultPane.setText(htmlContent.toString() + "</body></html>");
    }

    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> new E2EPHEMERA().setVisible(true));
    }
}