<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin();

$stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username'=>$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['is_admin'] === 0){
    header('Location: index.php');
    exit();
}

// handle sorting
$sortBy = $_GET['sort'] ?? 'time_created';
$sortOrder = $_GET['order'] ?? 'DESC';

// sorting columns allowed
$allowedSorts = ['title', 'time_created', 'updated_at'];
if(!in_array($sortBy, $allowedSorts)){
    $sortBy = 'time_created';
}

// toggle sorting order
$newOrder = ($sortOrder === 'ASC') ? 'DESC' : 'ASC';

// fetch posts with new order
try{
    $orderByClause = "p.$sortBy $sortOrder";
    $sql = "SELECT p.*, c.category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY $orderByClause";
    $stmt = $db->prepare($sql);    
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $error = "Error fetching posts: " . $e->getMessage();
    $posts = [];
}

// delete post
if(isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'true'){
    $posts = $db->prepare("DELETE FROM posts WHERE post_id = :id");
    $posts->execute([':id'=>$_POST['post_id']]);
    header('Location: admin_posts.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Manage Posts</title>
</head>
<body>
    <?php require_once 'header.php' ?>
    <div class="admin_posts">
        <h2>Manage Posts</h2>

        <div class="sort-info">
            <strong>Currently sorting by:</strong> 
            <?php  $sortNames = ['title' => 'Title', 'time_created' => 'Created Date', 'updated_at' => 'Updated Date'];
            echo $sortNames[$sortBy] . ' (' . $sortOrder . ')';
            ?>
        </div>

        <table class="posts_table">
            <thead>
                <tr>
                    <th class="<?= $sortBy === 'title' ? 'current_sort' : '' ?>">
                        <a href="?sort=title&order=<?= $sortBy === 'title' ? $newOrder : 'ASC' ?>" class="sort-link">
                            Title
                            <?php if($sortBy === 'title'): ?>
                                <span class="sort_indicator"><?= $sortOrder === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif ?>
                        </a>
                    </th>
                    <th class="<?= $sortBy === 'time_created' ? 'current_sort' : '' ?>">
                        <a href="?sort=time_created&order=<?= $sortBy === 'time_created' ? $newOrder : 'ASC' ?>" class="sort-link">
                            Created
                            <?php if($sortBy === 'time_created'): ?>
                                <span class="sort_indicator"><?= $sortOrder === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif ?>
                        </a>
                    </th>
                    <th class="<?= $sortBy === 'updated_at' ? 'current_sort' : '' ?>">
                        <a href="?sort=updated_at&order=<?= $sortBy=== 'updated_at' ? $newOrder : 'ASC' ?>" class="sort-link">
                            Updated
                            <?php if($sortBy === 'updated_at'): ?>
                                <span class="sort_indicator"><?= $sortOrder === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif ?>
                        </a>
                    </th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($posts) && is_array($posts)):?>
                    <?php foreach($posts as $post): ?>
                        <tr>
                            <td><?= $post['title']?></td>
                            <td><?= date('Y-m-d H:i', strtotime($post['time_created']))?></td>
                            <td><?= date('Y-m-d H:i', strtotime($post['updated_at']))?></td>
                            <td><?= $post['category_name'] ?? 'Uncategorized'?></td>
                            <td>
                                <a href="edit.php?id=<?= urlencode($post['post_id'])?>" class="action-btn edit-btn">Edit</a>
                                <input type="button" name="delete" value="Delete" class="action-btn delete-btn" onclick="showDeleteOverlay(<?= (int)$post['post_id']?>)"/>  
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php else: ?>
                    <tr>
                        <td class="no-posts">No posts found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
                
    <!-- hidden form to submit post id of form to delete -->
    <form method="POST" id="deleteForm" enctype="multipart/form-data">
        <input type="hidden" name="confirm_delete" value="true">
        <input type="hidden" name="post_id" id="postIdToDelete">
    </form>
            
    <!-- Delete Confirmation Overlay -->
    <div class="delete-overlay" id="deleteOverlay">
        <div class="delete-modal">
            <h3>⚠️ Delete Post</h3>
            <p>Are you sure you want to delete this post?<br>
            <strong>This action cannot be undone.</strong></p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                <button type="button" class="btn btn-secondary" onclick="hideDeleteOverlay()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let currentPostId = null;

        function showDeleteOverlay(postId) {
            currentPostId = postId;
            document.getElementById('postIdToDelete').value = postId;
            document.getElementById('deleteOverlay').style.display = 'flex';
        }

        function hideDeleteOverlay() {
            currentPostId = null;
            document.getElementById('deleteOverlay').style.display = 'none';
        }

        function confirmDelete() {
            if(currentPostId){
                document.getElementById('deleteForm').submit();
            }
        }

        // Close overlay with Escape key
        window.addEventListener('keydown', e=>e.key=="Escape" && hideDeleteOverlay());
        // Close overlay when clicking outside the modal
        document.getElementById('deleteOverlay').addEventListener('click', e => {
            if (e.target === e.currentTarget) { hideDeleteOverlay(); }
        });
    </script>

</body>
</html>