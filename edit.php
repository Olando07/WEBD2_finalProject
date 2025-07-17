<?php

require('connect.php');
require('header.php');
include 'sessionHandler.php';
requireLogin(); // Make sure user is logged in

// Array of categories
$categories = [
    'Technology',
    'Entertainment',
    'Education',
    'Content',
    'Cybersecurity',
    'Business',
    'Infrastructure',
    'Climate',
    'Research',
    'Innovation',
    'Health',
    'Transportation',
    'Energy',
    'Government',
    'Local News',
    'Science',
    'Automotive',
    'Food & Beverage',
    'Retail',
    'Gaming'
];

$titleError = 0;
$subtitleError = 0;
$reportError = 0;

$stmt=$db->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username'=>$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// check if the user is an admin
if($user['is_admin'] != 1){
    header('Location: index.php');
    exit();
}

$post_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT); 

if(!(int)$post_id){
    header('Location: index.php');
    exit();
}

// get post to edit
$posts = $db->prepare("SELECT * FROM posts WHERE post_id = :id");
$posts->execute([':id'=>$post_id]);
$post = $posts->fetch(PDO::FETCH_ASSOC);

if(!$post){
    header('Location: index.php');
    exit();
}

// form submission
// delete post
if(isset($_POST['delete'])){
    $posts = $db->prepare("DELETE FROM posts WHERE post_id = :id");
    $posts->execute([':id'=>$post_id]);
    header('Location: index.php');
    exit();
}

// update post
if(isset($_POST['update'])){
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $subtitle = filter_input(INPUT_POST, 'subtitle', FILTER_SANITIZE_SPECIAL_CHARS);
    $report = filter_input(INPUT_POST, 'report', FILTER_SANITIZE_SPECIAL_CHARS);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if($title && $subttitle && $report){
        $posts = $db->prepare("UPDATE posts SET title = :title, subtitle = :subtitle, report = :report, category = :category WHERE post_id = :id");
        $posts->execute([':title'=>$title, ':subtitle'=>$subtitle, ':report'=>$report, ':category'=>$category, ':id'=>$post_id]);
        header('Location: index.php');
        exit();
    }

    // empty($title) ? $titleError = 'This field is required' : $titleError = ''; 
    // empty($subtitle) ? $subtitleError = 'This field is required' : $titleError = ''; 
    // empty($report) ? $reportError = 'This field is required' : $titleError = ''; 
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: Edit Post</title>
</head>
<body>
    <div class="main">
        
            <div class="edit-form">
                <h2>Edit this post</h2>

                <form action="" method="POST">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'])?>" required>
                    </div>
                    <div class="form-group">
                        <label for="subtitle">Subtitle</label>
                        <input type="text" id="subtitle" name="subtitle" value="<?= htmlspecialchars($post['subtitle'])?>">
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category?>" <?= $post['category'] == $category ? 'selected' : ''?>>
                                    <?= $category?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report">Content</label>
                        <textarea name="report" id="report" rows="10" required><?= htmlspecialchars($post['report'])?></textarea>
                    </div>

                    <div class="form-actions">
                        <input type="submit" name="update" value="Update Post" class="update-btn" onclick="showDeleteOverlay()">
                        <input type="submit" name="delete" value="Delete Post" class="delete-btn" onclick="return confirm('Are you sure you want to delete this post?')">
                        <a href="index.php" class="cancel-btn">cancel</a>
                    </div>
                </form>
            </div>

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
                function showDeleteOverlay() {
                    document.getElementById('deleteOverlay').style.display = 'flex';
                }

                function hideDeleteOverlay() {
                    document.getElementById('deleteOverlay').style.display = 'none';
                }

                function confirmDelete() {
                    // Create a hidden form to submit the delete request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete';
                    deleteInput.value = '1';
                    
                    form.appendChild(deleteInput);
                    document.body.appendChild(form);
                    form.submit();
                }

                // Close overlay when clicking outside the modal
                document.getElementById('deleteOverlay').addEventListener('click', function(e) {
                    if (e.target === this) {
                        hideDeleteOverlay();
                    }
                });

                // Close overlay with Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        hideDeleteOverlay();
                    }
                });
                </script>
            <!-- TODO: use qil js for form - https://quilljs.com/docs/quickstart -->
            
            <p>This works edit page works so W</p>
    </div>
</body>
</html>