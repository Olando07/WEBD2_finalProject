<?php

require('connect.php');
require('header.php');
include 'sessionHandler.php';
requireLogin(); // Make sure user is logged in



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News</title>
</head>
<body>
    <div class="main">
        <div class="edit-form">
            <h2>Edit this post</h2>

            <form action="" method="POST" id="editPostForm">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'], ENT_QUOTES | ENT_HTML5)?>" required>
                    <span class="error"><?= $errors['title']?></span>
                </div>
                <div class="form-group">
                    <label for="subtitle">Subtitle:</label>
                    <input type="text" id="subtitle" name="subtitle" value="<?= htmlspecialchars($post['subtitle'], ENT_QUOTES | ENT_HTML5)?>">
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
                    <input type="create" name="create" value="Create Post" class="create-btn">
                    <a href="index.php" class="cancel-btn">cancel</a>
                </div>
            </form>
        </div>
    </div>

 <!-- TODO: only admins can create posts -->
 <!-- TODO: make the page fetch info from database based on selected category or input in the search field -->
 <!-- TODO: add comments button which shows a pop with comments from other users. users can create, edit and delete comments  -->




</body>
</html>