<?php
require_once 'auth_guard.php';

// Check if we are viewing a specific article or the whole feed
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id > 0) {
    // 1. Fetch SINGLE article
    $query = "SELECT * FROM news WHERE id = '$article_id'";
    $result = mysqli_query($conn, $query);
    $article = mysqli_fetch_assoc($result);
} else {
    // 2. Fetch ALL articles for the main feed
    $query = "SELECT * FROM news ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tournament News - SIS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; background-color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; max-width: 800px; margin-left: auto; margin-right: auto; }
        .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        
        /* Feed Styling */
        .news-card { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .news-card:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .news-title { color: #0056b3; text-decoration: none; font-size: 20px; font-weight: bold; }
        .news-title:hover { text-decoration: underline; }
        .news-meta { font-size: 13px; color: #777; margin-top: 5px; margin-bottom: 15px; }
        .news-content { line-height: 1.6; color: #444; }
        
        .btn-add { background-color: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-back { background-color: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="header">
    <h2 style="margin: 0; color: #333;">Official Announcements</h2>
    <div>
        <a href="dashboard.php" style="color: #0056b3; text-decoration: none; margin-right: 15px;">&larr; Dashboard</a>
        
        <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff')): ?>
            <a href="add_news.php" class="btn-add">+ Publish News</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <?php if ($article_id > 0 && $article): ?>
        <a href="news.php" class="btn-back">&larr; Back to All News</a>
        <h1 style="margin-top: 0; color: #333;"><?php echo $article['title']; ?></h1>
        <div class="news-meta">
            Published by <strong><?php echo $article['author']; ?></strong> on <?php echo date('l, d F Y - H:i', strtotime($article['created_at'])); ?>
        </div>
        <div class="news-content" style="font-size: 16px;">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>

    <?php else: ?>
        <?php 
        if(mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) { 
        ?>
            <div class="news-card">
                <a href="news.php?id=<?php echo $row['id']; ?>" class="news-title"><?php echo $row['title']; ?></a>
                <div class="news-meta">By <?php echo $row['author']; ?> | <?php echo date('d M Y', strtotime($row['created_at'])); ?></div>
                <div class="news-content">
                    <?php echo substr(htmlspecialchars($row['content']), 0, 150); ?>...
                </div>
            </div>
        <?php 
            }
        } else {
            echo "<p style='text-align:center; color: #777;'>No announcements have been published yet.</p>";
        }
        ?>
    <?php endif; ?>
</div>

</body>
</html>