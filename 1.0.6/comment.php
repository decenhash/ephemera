<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHA-256 Hash Comment System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.5;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 1.5rem;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        h2 {
            margin-bottom: 15px;
            font-weight: 500;
            font-size: 1.2rem;
        }
        
        .input-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        input, textarea, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        button {
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        button:hover {
            background-color: #555;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-button {
            background-color: #f0f0f0;
            color: #333;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            border: 1px solid #ddd;
        }
        
        .file-input-button:hover {
            background-color: #e8e8e8;
        }
        
        .hash-display {
            margin-top: 10px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
            word-break: break-all;
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        .comment {
            background-color: #f8f8f8;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }
        
        .comment-user {
            font-weight: 500;
        }
        
        .comment-date {
            color: #666;
        }
        
        .comment-text {
            color: #333;
        }
        
        .no-comments {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
        }
        
        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        
        .error {
            color: #c33;
            background-color: #ffeaea;
        }
        
        .success {
            color: #363;
            background-color: #eaffea;
        }
    </style>
</head>
<body>
    <h1>Comment</h1>
    <div class="section">        
        <div class="input-group">
            <label for="fileInput">Select a file:</label>
            <div class="file-input-wrapper">
                <div class="file-input-button">Choose File</div>
                <input type="file" id="fileInput">
            </div>
            <div id="fileName" style="margin-top: 8px; font-size: 0.85rem;"></div>
        </div>
        
        <div class="input-group">
            <label for="hashInput">Or enter SHA-256 hash:</label>
            <input type="text" id="hashInput" placeholder="64-character SHA-256 hash">
        </div>
        
        <div id="hashDisplay" class="hash-display" style="display: none;"></div>
        <div id="hashError" class="message error"></div>
    </div>
    
    <div class="section">
        <h2>Add Comment</h2>
        
        <div class="input-group">
            <label for="userInput">User:</label>
            <input type="text" id="userInput" placeholder="Your name">
        </div>
        
        <div class="input-group">
            <label for="commentInput">Comment:</label>
            <textarea id="commentInput" placeholder="Your comment"></textarea>
        </div>
        
        <button id="submitComment">Submit Comment</button>
        <div id="commentError" class="message error"></div>
        <div id="commentSuccess" class="message success"></div>
    </div>
    
    <div class="section">
        <h2>Comments</h2>
        <div id="commentsContainer">
            <div class="no-comments">No comments yet. Select a file or enter a hash to see comments.</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('fileInput');
            const hashInput = document.getElementById('hashInput');
            const hashDisplay = document.getElementById('hashDisplay');
            const hashError = document.getElementById('hashError');
            const fileName = document.getElementById('fileName');
            const userInput = document.getElementById('userInput');
            const commentInput = document.getElementById('commentInput');
            const submitComment = document.getElementById('submitComment');
            const commentError = document.getElementById('commentError');
            const commentSuccess = document.getElementById('commentSuccess');
            const commentsContainer = document.getElementById('commentsContainer');
            
            let currentHash = '';
            
            // Handle file selection
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileName.textContent = `Selected: ${file.name}`;
                    hashInput.value = '';
                    hashError.style.display = 'none';
                    
                    // Calculate SHA-256 hash
                    calculateSHA256(file).then(hash => {
                        currentHash = hash;
                        hashDisplay.textContent = `SHA-256: ${hash}`;
                        hashDisplay.style.display = 'block';
                        loadComments(hash);
                    }).catch(error => {
                        hashError.textContent = 'Error calculating hash: ' + error.message;
                        hashError.style.display = 'block';
                    });
                }
            });
            
            // Handle manual hash input
            hashInput.addEventListener('input', function() {
                const hash = hashInput.value.trim();
                if (hash.length === 64 && /^[a-fA-F0-9]+$/.test(hash)) {
                    currentHash = hash;
                    hashDisplay.textContent = `SHA-256: ${hash}`;
                    hashDisplay.style.display = 'block';
                    hashError.style.display = 'none';
                    loadComments(hash);
                } else if (hash.length > 0) {
                    hashError.textContent = 'Please enter a valid 64-character SHA-256 hash';
                    hashError.style.display = 'block';
                    hashDisplay.style.display = 'none';
                } else {
                    hashError.style.display = 'none';
                    hashDisplay.style.display = 'none';
                }
            });
            
            // Handle comment submission
            submitComment.addEventListener('click', function() {
                const user = userInput.value.trim();
                const comment = commentInput.value.trim();
                
                // Reset messages
                commentError.style.display = 'none';
                commentSuccess.style.display = 'none';
                
                // Validate inputs
                if (!currentHash) {
                    commentError.textContent = 'Please select a file or enter a hash first';
                    commentError.style.display = 'block';
                    return;
                }
                
                if (!user) {
                    commentError.textContent = 'Please enter your name';
                    commentError.style.display = 'block';
                    return;
                }
                
                if (!comment) {
                    commentError.textContent = 'Please enter a comment';
                    commentError.style.display = 'block';
                    return;
                }
                
                // Submit comment
                submitCommentToServer(currentHash, user, comment);
            });
            
            // Function to calculate SHA-256 hash
            function calculateSHA256(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;
                        
                        crypto.subtle.digest('SHA-256', arrayBuffer)
                            .then(hashBuffer => {
                                const hashArray = Array.from(new Uint8Array(hashBuffer));
                                const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                                resolve(hashHex);
                            })
                            .catch(reject);
                    };
                    
                    reader.onerror = reject;
                    reader.readAsArrayBuffer(file);
                });
            }
            
            // Function to load comments for a hash
            function loadComments(hash) {
                // In a real application, this would be an AJAX call to the server
                // For this demo, we'll simulate loading from localStorage
                
                // Clear previous comments
                commentsContainer.innerHTML = '';
                
                // Try to get comments from localStorage
                const commentsKey = `comments_${hash}`;
                const commentsJSON = localStorage.getItem(commentsKey);
                
                if (commentsJSON) {
                    try {
                        const comments = JSON.parse(commentsJSON);
                        
                        if (comments.length === 0) {
                            commentsContainer.innerHTML = '<div class="no-comments">No comments yet for this file/hash.</div>';
                        } else {
                            comments.forEach(comment => {
                                const commentElement = document.createElement('div');
                                commentElement.className = 'comment';
                                
                                const commentHeader = document.createElement('div');
                                commentHeader.className = 'comment-header';
                                
                                const userElement = document.createElement('div');
                                userElement.className = 'comment-user';
                                userElement.textContent = comment.user;
                                
                                const dateElement = document.createElement('div');
                                dateElement.className = 'comment-date';
                                dateElement.textContent = new Date(comment.timestamp).toLocaleString();
                                
                                commentHeader.appendChild(userElement);
                                commentHeader.appendChild(dateElement);
                                
                                const textElement = document.createElement('div');
                                textElement.className = 'comment-text';
                                textElement.textContent = comment.comment;
                                
                                commentElement.appendChild(commentHeader);
                                commentElement.appendChild(textElement);
                                
                                commentsContainer.appendChild(commentElement);
                            });
                        }
                    } catch (e) {
                        commentsContainer.innerHTML = '<div class="no-comments">Error loading comments.</div>';
                    }
                } else {
                    commentsContainer.innerHTML = '<div class="no-comments">No comments yet for this file/hash.</div>';
                }
            }
            
            // Function to submit comment to server
            function submitCommentToServer(hash, user, comment) {
                // In a real application, this would be an AJAX call to the server
                // For this demo, we'll simulate saving to localStorage
                
                const commentsKey = `comments_${hash}`;
                let comments = [];
                
                // Get existing comments
                const existingCommentsJSON = localStorage.getItem(commentsKey);
                if (existingCommentsJSON) {
                    try {
                        comments = JSON.parse(existingCommentsJSON);
                    } catch (e) {
                        console.error('Error parsing existing comments:', e);
                    }
                }
                
                // Add new comment
                const newComment = {
                    user: user,
                    comment: comment,
                    timestamp: new Date().toISOString()
                };
                
                comments.push(newComment);
                
                // Save back to localStorage
                localStorage.setItem(commentsKey, JSON.stringify(comments));
                
                // Clear form
                userInput.value = '';
                commentInput.value = '';
                
                // Show success message
                commentSuccess.textContent = 'Comment submitted successfully!';
                commentSuccess.style.display = 'block';
                
                // Reload comments
                loadComments(hash);
            }
        });
    </script>
</body>
</html>