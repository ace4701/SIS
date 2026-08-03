<?php
require_once 'auth_guard.php';

if ($_SESSION['role'] == 'public') {
    die("Access Denied.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $author = $_SESSION['username'];
    
    $image_paths = []; 
    $uploaded_hashes = []; // To prevent duplicates within the same upload batch

    // AFTER (Patched):
    if (isset($_FILES['news_images']) && !empty($_FILES['news_images']['name'][0])) {
        $target_dir = "uploads/";
        
        // Define strict whitelists
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Initialize PHP's finfo extension to read the actual file signature
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        
        foreach ($_FILES['news_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['news_images']['error'][$key] == 0) {
                
                // SECURITY CHECK 1: Verify actual MIME type from file contents
                $mime_type = finfo_file($finfo, $tmp_name);
                if (!in_array($mime_type, $allowed_mime_types)) {
                    continue; // Silently reject malicious or invalid files
                }
                
                // SECURITY CHECK 2: Verify and sanitize the extension
                $file_extension = strtolower(pathinfo($_FILES["news_images"]["name"][$key], PATHINFO_EXTENSION));
                if (!in_array($file_extension, $allowed_extensions)) {
                    continue; // Silently reject invalid extensions
                }

                // DUPLICATE PREVENTION: Create a digital fingerprint of the file
                $file_hash = md5_file($tmp_name);
                if(in_array($file_hash, $uploaded_hashes)) {
                    continue; // Skip this file, we already uploaded it!
                }
                
                $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_paths[] = $target_file;
                    $uploaded_hashes[] = $file_hash; // Remember this fingerprint
                }
            }
        }
        finfo_close($finfo); // Clean up memory
    }

    $image_path_json = !empty($image_paths) ? json_encode($image_paths) : '';

    $insert_query = "INSERT INTO news (title, content, author, image_path) VALUES ('$title', '$content', '$author', '$image_path_json')";
    if (mysqli_query($conn, $insert_query)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "<div style='color:red;'>Error saving to database.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publish Announcement - SIS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 600px; }
        .file-upload { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 4px; margin-bottom: 15px; background: #f8f9fa;}
        textarea { resize: vertical; width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        /* New Styles for Dynamic Previews */
        #preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; justify-content: center; }
        .preview-item { position: relative; width: 80px; height: 80px; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .remove-btn { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    </style>
</head>
<body>

<div class="box">
    <h2 style="text-align: center; margin-top: 0; color: #da251d;">Compose Announcement</h2>
    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Headline / Title</label>
        <input type="text" name="title" required placeholder="e.g., Opening Ceremony Details">
        
        <label>Attach Photos (Optional)</label>
        <div class="file-upload">
            <input type="file" id="image-input" name="news_images[]" accept="image/png, image/jpeg, image/jpg" multiple>
            <div id="preview-container"></div>
        </div>
        
        <label>Announcement Details</label>
        <textarea name="content" rows="6" required placeholder="Type the full details here..."></textarea>
        
        <button type="submit" class="btn-submit">Publish News</button>
        <a href="dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel</a>
    </form>
</div>

<script>
    const imageInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('preview-container');
    
    // Create a virtual container to hold our selected files
    let dataTransfer = new DataTransfer();

    imageInput.addEventListener('change', function() {
        // Add newly selected files to our virtual container
        for(let i = 0; i < this.files.length; i++) {
            dataTransfer.items.add(this.files[i]);
        }
        
        // Update the actual input to match our virtual container
        imageInput.files = dataTransfer.files;
        updatePreviews();
    });

    function updatePreviews() {
        previewContainer.innerHTML = ''; // Clear old previews
        
        Array.from(imageInput.files).forEach((file, index) => {
            let reader = new FileReader();
            reader.onload = function(e) {
                let div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" title="${file.name}">
                    <button type="button" class="remove-btn" onclick="removeFile(${index})">X</button>
                `;
                previewContainer.appendChild(div);
            }
            reader.readAsDataURL(file); // Convert image to displayable thumbnail
        });
    }

    function removeFile(indexToRemove) {
        let newDataTransfer = new DataTransfer();
        
        // Copy all files EXCEPT the one we want to delete into a new virtual container
        Array.from(imageInput.files).forEach((file, index) => {
            if (index !== indexToRemove) {
                newDataTransfer.items.add(file);
            }
        });

        dataTransfer = newDataTransfer;
        imageInput.files = dataTransfer.files; // Update the real input
        updatePreviews(); // Redraw the thumbnails
    }
</script>

</body>
</html>