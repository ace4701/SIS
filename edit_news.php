<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'public') {
    die("Access Denied.");
}

$message = "";
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = mysqli_query($conn, "SELECT * FROM news WHERE id = $news_id");
$post = mysqli_fetch_assoc($query);

if (!$post) { die("Post not found."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    
    // 1. Gather Existing Images
    $existing_path = $post['image_path'];
    $image_paths = [];
    if (!empty($existing_path)) {
        $decoded = json_decode($existing_path, true);
        $image_paths = is_array($decoded) ? $decoded : [$existing_path];
    }

    // 2. Handle Deletions of OLD images
    if (isset($_POST['delete_existing'])) {
        foreach ($_POST['delete_existing'] as $del_path) {
            // Find the image in our array and remove it
            if (($key = array_search($del_path, $image_paths)) !== false) {
                unset($image_paths[$key]);
                if (file_exists($del_path)) {
                    unlink($del_path); // Delete the physical file!
                }
            }
        }
        $image_paths = array_values($image_paths); // Re-index the array cleanly
    }

    // 3. Generate Fingerprints of whatever old images are left (to prevent duplicate new uploads)
    $existing_hashes = [];
    foreach ($image_paths as $path) {
        if (file_exists($path)) {
            $existing_hashes[] = md5_file($path);
        }
    }

    // 4. Handle NEW uploads
    if (isset($_FILES['news_images']) && !empty($_FILES['news_images']['name'][0])) {
        $target_dir = "uploads/";
        
        foreach ($_FILES['news_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['news_images']['error'][$key] == 0) {
                
                $new_hash = md5_file($tmp_name);
                
                // REDUNDANCY CHECK: Compare new fingerprint against existing fingerprints
                if (in_array($new_hash, $existing_hashes)) {
                    continue; // Skip this duplicate!
                }
                
                $ext = pathinfo($_FILES["news_images"]["name"][$key], PATHINFO_EXTENSION);
                $new_filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_paths[] = $target_file; 
                    $existing_hashes[] = $new_hash; // Add to hashes so we don't upload it twice in this batch
                }
            }
        }
    }

    $image_path_json = !empty($image_paths) ? json_encode($image_paths) : '';

    $update_query = "UPDATE news SET title='$title', content='$content', image_path='$image_path_json' WHERE id=$news_id";
    if (mysqli_query($conn, $update_query)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "<div style='color:red;'>Error updating database.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Announcement - SIS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 600px; }
        .file-upload { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 4px; margin-bottom: 15px; background: #f8f9fa;}
        textarea { resize: vertical; width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        #preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; justify-content: center; }
        .preview-item { position: relative; width: 80px; height: 80px; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .remove-btn { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        
        .old-image-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        .old-image-wrapper { position: relative; width: 80px; height: 80px; }
    </style>
</head>
<body>

<div class="box">
    <h2 style="text-align: center; margin-top: 0; color: #0056b3;">Edit Announcement</h2>
    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Headline / Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        
        <label>Manage Photos</label>
        <div class="file-upload">
            
            <?php if(!empty($post['image_path'])): ?>
                <div style="font-size: 13px; color: #666; margin-bottom: 10px;"><strong>Currently Attached:</strong></div>
                <div class="old-image-container" id="old-images-container">
                    <?php 
                    $decoded = json_decode($post['image_path'], true);
                    $imgs = is_array($decoded) ? $decoded : [$post['image_path']];
                    foreach($imgs as $idx => $img) {
                    ?>
                        <div class="old-image-wrapper" id="old-img-<?php echo $idx; ?>">
                            <img src="<?php echo $img; ?>" style="width:100%; height:100%; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                            <button type="button" class="remove-btn" onclick="markOldImageForDeletion('<?php echo $idx; ?>', '<?php echo $img; ?>')">X</button>
                        </div>
                    <?php } ?>
                </div>
                <hr style="border-top: 1px dashed #ccc; margin: 15px 0;">
            <?php endif; ?>
            
            <div style="font-size: 13px; color: #666; margin-bottom: 10px;"><strong>Add New Photos:</strong></div>
            <input type="file" id="image-input" name="news_images[]" accept="image/png, image/jpeg, image/jpg" multiple>
            <div id="preview-container"></div>
            
            <div id="delete-inputs-container"></div>
        </div>
        
        <label>Announcement Details</label>
        <textarea name="content" rows="8" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        
        <button type="submit" class="btn-submit" style="background: #0056b3;">Save Changes</button>
        <a href="dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel</a>
    </form>
</div>

<script>
    // --- LOGIC FOR DELETING EXISTING (OLD) IMAGES ---
    function markOldImageForDeletion(index, imagePath) {
        // 1. Hide the image visually
        document.getElementById('old-img-' + index).style.display = 'none';
        
        // 2. Create a hidden input that tells PHP to delete this file
        let hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'delete_existing[]';
        hiddenInput.value = imagePath;
        
        document.getElementById('delete-inputs-container').appendChild(hiddenInput);
    }

    // --- LOGIC FOR PREVIEWING/DELETING NEW IMAGES ---
    const imageInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('preview-container');
    let dataTransfer = new DataTransfer();

    imageInput.addEventListener('change', function() {
        for(let i = 0; i < this.files.length; i++) {
            dataTransfer.items.add(this.files[i]);
        }
        imageInput.files = dataTransfer.files;
        updatePreviews();
    });

    function updatePreviews() {
        previewContainer.innerHTML = ''; 
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
            reader.readAsDataURL(file);
        });
    }

    function removeFile(indexToRemove) {
        let newDataTransfer = new DataTransfer();
        Array.from(imageInput.files).forEach((file, index) => {
            if (index !== indexToRemove) { newDataTransfer.items.add(file); }
        });
        dataTransfer = newDataTransfer;
        imageInput.files = dataTransfer.files;
        updatePreviews();
    }
</script>

</body>
</html>