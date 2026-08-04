<div id="News" class="tab-content">
    <div class="generic-container" style="max-width: 800px; margin: 0 auto; background: transparent; box-shadow: none; padding: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #da251d;">Official News Feed</h3>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                <a href="add_news.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">+ Publish News</a>
            <?php endif; ?>
        </div>

        <?php 
        mysqli_data_seek($news_result, 0);
        if(mysqli_num_rows($news_result) > 0) {
            while($news = mysqli_fetch_assoc($news_result)) { 
                $news_id = $news['id'];
                
                $like_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes WHERE news_id = $news_id"))['total'];
                
                $user_id = $_SESSION['user_id'];
                $has_liked = (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM likes WHERE news_id = $news_id AND user_id = $user_id")) > 0);
                $like_btn_color = $has_liked ? "#0056b3" : "#6c757d";

                $is_hidden = ($news['status'] == 'hidden');
                $opacity = $is_hidden ? "0.6" : "1";
        ?>
            <div id="post-<?php echo $news_id; ?>" style="background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 25px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); opacity: <?php echo $opacity; ?>; transition: opacity 0.3s;">
                
                <div style="padding: 15px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin: 0 0 5px 0; color: #333; font-size: 18px;"><?php echo $news['title']; ?></h4>
                        <div style="font-size: 13px; color: #777;">
                            <strong><?php echo $news['author']; ?></strong> &bull; <?php echo date('d M Y, H:i', strtotime($news['created_at'])); ?>
                        </div>
                    </div>

                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                        <div class="dropdown">
                            <button onclick="toggleDropdown(<?php echo $news_id; ?>)" class="dropdown-btn">⋮</button>
                            <div id="dropdown-<?php echo $news_id; ?>" class="dropdown-content">
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                    <button onclick="toggleHide(<?php echo $news_id; ?>, '<?php echo $news['status']; ?>')" id="hide-btn-<?php echo $news_id; ?>">
                                        <?php echo $is_hidden ? "👁️‍🗨️ Unhide Post" : "👁️ Hide Post"; ?>
                                    </button>
                                <?php endif; ?>
                                <a href="edit_news.php?id=<?php echo $news_id; ?>">✏️ Edit</a>
                                <button onclick="openDeleteModal(<?php echo $news_id; ?>)" style="color: #dc3545;">🗑️ Delete</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php 
                if (!empty($news['image_path'])) {
                    // Try to decode it to see if it is a new array
                    $images = json_decode($news['image_path'], true);
                    
                    echo '<div class="image-grid">';
                    
                    if (is_array($images) && count($images) > 0) {
                        // NEW POST: Loop through the array of images
                        foreach ($images as $img) {
                            echo '<img src="' . $img . '" alt="News Image">';
                        }
                    } else {
                        // OLD POST: It's just a single text string, print it directly
                        echo '<img src="' . $news['image_path'] . '" alt="News Image">';
                    }
                    
                    echo '</div>';
                }
                ?>

                <div style="padding: 15px;">
                    <p style="margin: 0 0 15px 0; font-size: 15px; color: #444; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </p>

                    <div style="display: flex; gap: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                        <button onclick="toggleLike(<?php echo $news_id; ?>)" id="like-btn-<?php echo $news_id; ?>" style="background: none; border: none; color: <?php echo $like_btn_color; ?>; cursor: pointer; font-weight: bold; font-size: 14px; padding: 0;">
                            👍 Like (<span id="like-count-<?php echo $news_id; ?>"><?php echo $like_count; ?></span>)
                        </button>
                        <button onclick="sharePost('<?php echo addslashes($news['title']); ?>', '<?php echo $news_id; ?>')" style="background: none; border: none; color: #6c757d; cursor: pointer; font-weight: bold; font-size: 14px; padding: 0;">
                            ↗️ Share
                        </button>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-top: 1px solid #eee;">
                    <h5 style="margin: 0 0 10px 0; color: #555;">Comments</h5>
                    
                    <div id="comment-list-<?php echo $news_id; ?>" style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
                        <?php 
                        $comments_query = mysqli_query($conn, "SELECT username, comment_text, created_at FROM comments WHERE news_id = $news_id ORDER BY created_at ASC");
                        if(mysqli_num_rows($comments_query) > 0) {
                            while($comment = mysqli_fetch_assoc($comments_query)) {
                        ?>
                            <div style="margin-bottom: 8px; font-size: 13px;">
                                <strong style="color: #0056b3;"><?php echo $comment['username']; ?>:</strong> 
                                <span style="color: #444;"><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                <span style="color: #aaa; font-size: 11px; margin-left: 5px;"><?php echo date('d M, H:i', strtotime($comment['created_at'])); ?></span>
                                <button onclick="replyTo('<?php echo $comment['username']; ?>', <?php echo $news_id; ?>)" style="background: none; border: none; color: #da251d; font-size: 11px; cursor: pointer; margin-left: 5px;">Reply</button>
                            </div>
                        <?php } } else { echo "<div id='no-comment-{$news_id}' style='font-size: 13px; color: #777;'>Be the first to comment!</div>"; } ?>
                    </div>

                    <div style="display: flex; gap: 10px; margin: 0;">
                        <input type="text" id="comment-input-<?php echo $news_id; ?>" placeholder="Write a comment..." style="flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 20px; outline: none; margin: 0;">
                        <button onclick="postComment(<?php echo $news_id; ?>)" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-weight: bold; margin: 0;">Post</button>
                    </div>
                </div>

            </div>
        <?php } } else { echo "<p style='text-align:center; color:#777;'>No news available.</p>"; } ?>
    </div>
</div>