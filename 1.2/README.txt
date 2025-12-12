App Overview: The Decentralized Network Aggregator
This application is a Java-based Desktop GUI tool designed to manage, analyze, and aggregate files across multiple remote servers. It acts as a federated client that connects to various web servers (domains) listed in a local text file to perform tasks like ranking file popularity, mirroring content, discovery of remote file lists, and searching for specific user data across the network.
It operates entirely without external dependencies, using standard HTTP protocols to "talk" to servers.
________________________________________
Core Features & How They Work
1. Rank (Analytics)
•	What it does: Calculates which files are the most popular or most referenced in your input list.
•	How it works: It reads your local files.txt line by line. It strips away the domain (e.g., https://test.com/) to focus only on the filename (e.g., image.jpg). It counts how many times each filename appears and displays a "Top 100" leaderboard.
•	Utility: Useful for identifying trending content or finding duplicate assets spread across different servers.
2. Download (Asset Mirroring)
•	What it does: Downloads the files listed in files.txt to your local machine, but with a "smart twin" feature.
•	How it works:
1.	Direct Download: It downloads the URL found in the text file into a local files folder.
2.	JSON Twin Check: For every file (e.g., photo.jpg), it automatically assumes there might be a metadata file on the server in a specific /json/ folder (e.g., photo.json). It checks if that metadata file exists, and if so, downloads it to a json folder.
•	Utility: Great for backing up media libraries where every media file has an associated data file (metadata) that needs to be preserved together.
3. Servers Download (Crawler/Discovery)
•	What it does: This is a "discovery" mode. Instead of just downloading the specific URLs you have, it asks the servers for their list of files and downloads everything.
•	How it works:
1.	It extracts the unique domain names from your local list.
2.	It attempts to download a files.txt from the root of those domains.
3.	If successful, it parses that remote list and recursively performs the Download logic (including the "Twin JSON" check) for every file listed on the remote server.
•	Utility: Allows you to sync your local machine with the full contents of multiple remote servers without needing to know every specific URL beforehand.
4. Search (Data Aggregation)
•	What it does: Searches across all known servers for a specific user's data file and consolidates the results into a single local database.
•	How it works:
1.	You type a username (e.g., "bob").
2.	The app checks every domain in your list for a specific path: /json_search/bob.json.
3.	If found, it downloads the content.
4.	Consolidation: It parses the downloaded data and appends unique entries into a master file locally (json_search/bob.json). It ensures no duplicate file hashes are recorded.
•	Utility: This creates a decentralized search engine. If "Bob" has data split across 5 different servers, this tool merges them into one complete profile on your computer.
5. View (Browser Interface)
•	What it does: Allows you to browse the search results visually without downloading the files locally.
•	How it works:
1.	It performs the same network search as above.
2.	Instead of saving the data, it parses the JSON results in memory.
3.	It generates an HTML list of clickable links.
4.	Smart Redirection: The links don't point to the file directly; they point to a redirect.html script on the server, passing the file's hash as a parameter.
•	Utility: Turns the app into a web browser for your decentralized network, allowing you to access content on remote servers via a unified interface.
________________________________________
Why is this Useful?
1.	Decentralization: This tool removes the need for a central "master server." As long as you have a list of domains, the client (this app) does the work of stitching the network together.
2.	Resilience: If one server goes down, the "Servers Download" and "Search" features allow you to easily find and retrieve content from other servers in your list.
3.	Data Integrity: The "Twin Check" (downloading JSON alongside media) ensures that you don't just get the image/file, but also the context or metadata associated with it.
4.	Efficiency: The app checks if a file exists locally before downloading, saving bandwidth. It also checks if a file exists remotely (using HTTP HEAD requests) before trying to download, preventing errors.
Mental Model of the Architecture
Imagine you have a library card (the app) and a list of library addresses (files.txt).
•	Rank: You look at your list and see which book titles appear at the most libraries.
•	Download: You go to the libraries and borrow the specific books on your list, plus their index cards (JSON).
•	Servers Download: You go to the front desk of each library, ask for their master catalog, and borrow everything in it.
•	Search/View: You ask every library, "Do you have a folder for the author 'Bob'?" You then compile a master bibliography of Bob's work from every library combined.

