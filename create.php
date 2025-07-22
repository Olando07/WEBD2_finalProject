<?php

require('connect.php');
require('header.php'); // add require once
include_once 'sessionHandler.php';
requireLogin(); // Make sure user is logged in

require './php-image-resize-master/lib/ImageResize.php';
require './php-image-resize-master/lib/ImageResizeException.php';

$errors = [];
$post = ['title'=>'', 'subtitle'=>'', 'category_id'=>'', 'report'=>''];

try{
    $stmt = $db->query("SELECT * FROM categories ORDER BY category_id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $errors['general'] = "Errors loading categories:" . $e->getMessage();
    $categories = [];
}

// if(){

// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: Create a post</title>
</head>
<body>
    <div class="create-main">
        <div class="create-form">
            <h2>Edit this post</h2>

            <form action="" method="POST" id="createPostForm">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?= isset($_POST['title']) ? htmlspecialchars( $_POST['title']) : ''?>" required>
                    <span class="error"><?= $errors['title']?></span>
                </div>
                <div class="form-group">
                    <label for="subtitle">Subtitle:</label>
                    <input type="text" id="subtitle" name="subtitle" value="<?=isset( $_POST['subtitle']) ? htmlspecialchars( $_POST['subtitle']) : '' ?>" required>
                    <span class="error"><?= $errors['subtitle']?></span>
                </div>
                <div class="form-group">
                    <label for="category">Select a Category:</label>
                    <select name="category" id="category" size="8" required>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['category_id']?>" <?= $category['category_id'] == $post['category_id'] ? 'selected' : ''?>>
                                <?= $category['category_name']?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="report">Content</label>
                    <textarea name="report" id="report" rows="10" required><?= $post['report']?></textarea>
                </div>

                <div class="form-actions">
                    <input type="button" name="create" value="Create Post" class="create-btn">
                    <a href="index.php" class="cancel-btn">cancel</a>
                </div>
            </form>
        </div>
    </div>

 <!-- TODO: only admins can create posts -->
 <!-- TODO: add comments button which shows a pop with comments from other users. users can create, edit and delete comments  -->

</body>
</html>