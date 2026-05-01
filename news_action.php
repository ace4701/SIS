<?php
session_start();
require 'db_config.php';

header('Content-Type: application/json'); // Tell the browser we are sending JSON data back

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Read the raw JSON data sent by our new JavaScript fetch API
    $data = json_decode(file_get_contents("php://input"), true);
    
    $action = $data['action'];
    $news_id = (int)$data['news_id'];
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    // 1. Handle LIKES
    if ($action == 'like') {
        $check = mysqli_query($conn, "SELECT id FROM likes WHERE news_id = $news_id AND user_id = $user_id");
        $has_liked = false;
        
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM likes WHERE news_id = $news_id AND user_id = $user_id");
            $has_liked = false;
        } else {
            mysqli_query($conn, "INSERT INTO likes (news_id, user_id) VALUES ($news_id, $user_id)");
            $has_liked = true;
        }
        
        $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM likes WHERE news_id = $news_id");
        $like_count = mysqli_fetch_assoc($count_query)['total'];
        
        echo json_encode(['status' => 'success', 'likes' => $like_count, 'has_liked' => $has_liked]);
        exit();
    }

    // 2. Handle COMMENTS
    if ($action == 'comment') {
        $comment_text = mysqli_real_escape_string($conn, $data['comment_text']);
        if (!empty($comment_text)) {
            mysqli_query($conn, "INSERT INTO comments (news_id, username, comment_text) VALUES ($news_id, '$username', '$comment_text')");
            $time = date('d M, H:i');
            
            // Return the HTML for the new comment so JS can inject it instantly
            $comment_html = "
                <div style='margin-bottom: 8px; font-size: 13px; animation: fadeIn 0.5s;'>
                    <strong style='color: #0056b3;'>{$username}:</strong> 
                    <span style='color: #444;'>" . htmlspecialchars($comment_text) . "</span>
                    <span style='color: #aaa; font-size: 11px; margin-left: 5px;'>{$time}</span>
                </div>";
            
            echo json_encode(['status' => 'success', 'html' => $comment_html]);
            exit();
        }
    }

    // 3. Handle ADMIN HIDE/UNHIDE
    if ($action == 'toggle_status' && $_SESSION['role'] == 'admin') {
        $current_status = $data['current_status'];
        $new_status = ($current_status == 'visible') ? 'hidden' : 'visible';
        mysqli_query($conn, "UPDATE news SET status = '$new_status' WHERE id = $news_id");
        
        echo json_encode(['status' => 'success', 'new_status' => $new_status]);
        exit();
    }

    // 4. Handle STAFF/ADMIN DELETE POST
    if ($action == 'delete_post' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff')) {
        $file_query = mysqli_query($conn, "SELECT image_path FROM news WHERE id = $news_id");
        $post_data = mysqli_fetch_assoc($file_query);
        
        // Loop through the JSON array and delete every physical image
        if (!empty($post_data['image_path'])) {
            $images = json_decode($post_data['image_path'], true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    if (file_exists($img)) unlink($img); 
                }
            }
        }
        
        mysqli_query($conn, "DELETE FROM news WHERE id = $news_id");
        echo json_encode(['status' => 'success']);
        exit();
    }
}
?>