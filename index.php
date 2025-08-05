<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin();

// Logout handle
if(isset($_GET['action']) && $_GET['action'] === 'logout'){
    $_SESSION = array();
    
    if(isset($_COOKIE[session_name()])){
        setcookie(session_name(), '', time()-3600, '/');
    }

    session_destroy();
    
    header('Location: login.php');
    exit();
}    

// Fetches categories
$categoryQuery = $db->query('SELECT * FROM categories');
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

$stmt = null;
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$userSearch = (filter_input(INPUT_GET, 'search-bar', FILTER_SANITIZE_SPECIAL_CHARS)) ?? '';
$editBtn = null;

// Handle filter and search functionality
if(!empty($selectedCategory)){
    if($_GET['search-bar']){
        // Both category filter and search 
        $stmt = $db->prepare("SELECT p.*, c.category_name, i.image_id FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id WHERE p.category_id = ? AND p.title LIKE ? ORDER BY p.time_created DESC, p.title, p.subtitle");
        $searchPattern = '%' . $userSearch . '%';
        $posts=$stmt;
        $posts->execute([$selectedCategory, $searchPattern]); 
    }else{
        // Category filter only
        $stmt = $db->prepare("SELECT p.*, c.category_name, i.image_id FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id WHERE p.category_id = ? ORDER BY p.time_created DESC, p.title, p.subtitle");
        $posts=$stmt;
        $posts->execute([$selectedCategory]);      
    }
}else{
    if(!empty($_GET['search-bar']) && $_GET['search-bar']){
        // Search only 
        $stmt = $db->prepare("SELECT p.*, c.category_name, i.image_id FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id WHERE p.title LIKE ? ORDER BY p.time_created DESC, p.title, p.subtitle");
        $searchPattern = '%' . $userSearch . '%';
        $posts = $stmt;
        $posts->execute([$searchPattern]);
    }else{
        // No category filter or search
        $posts=$db->query("SELECT p.*, c.category_name, i.image_id FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id ORDER BY p.time_created DESC, p.title, p.subtitle");
    }
}   

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: Home</title>
</head>
<body>
    <div class='main'>
        <div class="category-list">
            <form method="GET" action="">
                <div class="category-filters">
                    <h3>Filter by category</h3>

                    <!-- Categories users can select -->
                     <select name="category" id="category">
                        <option value="" id="placeholder">
                            Select a category
                        </option>
                         <?php foreach($categories as $category): ?>
                             <option value="<?= $category['category_id']?>" <?= $selectedCategory == $category['category_id'] ? 'selected' : ''?>>
                                 <?= $category['category_name']?>
                             </option>
                         <?php endforeach ?>
                     </select>
                     <!-- TODO: prevent category from being reset -->

                    <div class="form-actions">
                        <!-- Submit button to apply the filters -->
                        <input type="submit" value="Apply Filter" class="filter-btn" name="search-btn" onclick="applyFilter()">
                        <!-- Clear button to remove filters -->
                        <input type="submit" value="Clear Filter" onclick="clearAll()">
                    </div>
                </div>
        </div>
                <?php include_once 'header.php'?>
            </form>

        <?php 
            // Check if user is an admin
            $stmt=$db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([':username'=>$_SESSION['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        ?>
        <div class="news-posts">
             <!-- PHP for fetching posts -->
            <?php while($row = $posts->fetch(PDO::FETCH_ASSOC)):?>
                <!-- Check if the current post has a category assigned -->
                <?php if($row['category_id']):?>
                    <!-- Container div for each individual post -->
                    <div class="posts">
                        <h3 class="title">
                            <?= htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML5)?>
                            <p>
                                <?= htmlspecialchars($row['subtitle'], ENT_QUOTES | ENT_HTML5)?>
                                <?php 
                                    if($user['is_admin'] == 1){
                                        $editBtn = "<a href='edit.php?id=" . $row['post_id'] . "' class='edit-btn'>Edit post</a>";
                                    }
                                ?>
                                <?= $editBtn?>
                            </p>
                        </h3>

                        <?php if(!empty($row['image_id'])): ?>                               
                            <img src="serve_image.php?id=<?= $row['image_id']?>" alt="<?= $row['title']?>" class="thumbnail">
                        <?php endif ?>

                        <p><?= $row['report']?></p>
                        <div class="post-bottom">
                            <a href="fullpost.php?id=<?= $row['post_id']?>" class="fullpost">Read the full news post →</a>
                        </div>
                        <div class="date_div">
                            <p class="date">Created on: <?= date_format(new DateTime($row['time_created']), "F d Y h:i a") ?></p>
                            <p class="update_date">Last Updated on: <?= date_format(new DateTime($row['updated_at']), "F d Y h:i a") ?></p>
                        </div>
                        <!-- TODO: show date when post was last update -->
                    </div>
                <?php endif?>
            <?php endwhile?>
        </div>
    </div>

    <script>
        // functionality to apply filter and keep previous input in search bar
        function applyFilter(){
            const searchValue = document.getElementById('search-bar');

            let hiddenValue = document.createElement('input');
            hiddenValue.type = hidden;
            hiddenValue.name = 'search-btn';
            hiddenValue.value = searchValue;

            document.querySelector('form').appendChild(hiddenValue);
            document.querySelector('form').submit();
        }

        function clearAll(){
            let category = document.getElementById('category');
            category.value = '';

            document.querySelector('form').submit();
        }
    </script>
</body>
</html>