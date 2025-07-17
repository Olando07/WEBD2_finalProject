<?php

require('connect.php');
include 'sessionHandler.php';
requireLogin();

// Logout handle
if(isset($_GET['action']) && $_GET['action'] === 'logout'){
    session_unset();
    session_destroy();
    
    if(isset($_COOKIE[session_name()])){
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    header('Location: login.php');
    exit();
}    

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

$stmt = null;
$selectedCategories = $_GET['selected_categories'] ?? [];
$userSearch = (filter_input(INPUT_GET, 'search-bar', FILTER_SANITIZE_SPECIAL_CHARS)) ?? '';
$editBtn = null;

// Handle filter and search functionality
if(!empty($selectedCategories)){
    if($_GET['search-bar']){
        // Both category filter and search 
        $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
        $stmt = "SELECT * FROM posts WHERE category IN ($placeholders) AND title LIKE ? ORDER BY time_created, title, subtitle DESC";
        $posts=$db->prepare($stmt);

        $searchPattern = '%' . $userSearch . '%';
        $params=array_merge($selectedCategories, [$searchPattern]);
        $posts->execute($params); 
    }else{
        // Category filter only
        $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
        $stmt = "SELECT * FROM posts WHERE category IN ($placeholders) ORDER BY time_created, title, subtitle DESC";
        $posts=$db->prepare($stmt);
        $posts->execute($selectedCategories);      
    }
}else{
    if(isset($_GET['search-btn']) && $_GET['search-bar']){
        // Search only 
        $stmt = $db->prepare('SELECT  * FROM posts WHERE title LIKE ? ORDER BY time_created, title, subtitle DESC');
        $searchPattern = '%' . $userSearch . '%';
        $posts = $stmt;
        $posts->execute([$searchPattern]);
    }else{
        // No category filter or search
        $posts=$db->query('SELECT * FROM posts ORDER BY time_created, title, subtitle DESC');
    }
}   

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: View News Posts</title>
</head>
<body>
    <div class='main'>
        <div class="category-list">
            <form method="GET" action="">
                <div class="category-filters">
                    <h3>Filter by category</h3>

                    <!-- Categories users can select -->
                    <?php foreach($categories as $category):?>
                        <!-- Individual category checkbox container -->
                        <div class="category">
                            <input type="checkbox" name="selected_categories[]" value="<?= $category?>" id="<?= $category?>" <?= in_array($category, $selectedCategories) ? 'checked' : '' ?>>
                            <label for="<?= $category?>"><?= $category?></label>
                        </div>
                    <?php endforeach ?>

                    <div class="form-actions">
                        <!-- Submit button to apply the filters -->
                        <input type="submit" value="Apply Filters" class="filter-btn" name="search-btn" onclick="applyFilter()">
                        <!-- Clear button to remove filters -->
                        <input type="submit" value="Clear All" onclick="clearAll()">
                    </div>
                </div>
        </div>
                <?php include 'header.php'?>
                <!-- TODO: make a guess account where users can only view things -->
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
                <?php if($row['category']):?>
                    <!-- Container div for each individual post -->
                    <div class="posts">
                        <h3 class="title">
                            <?= htmlspecialchars($row['title'])?>
                            <?php 
                            
                                if($user['is_admin'] == 1){
                                    $editBtn = "<a href='edit.php?id=" . $row['post_id'] . "' class='edit-btn'>Edit post</a>";
                                }

                            ?>
                            <?= $editBtn?>
                        </h3>
                        <p><?= htmlspecialchars($row['report'])?></p>
                        <div class="post-bottom">
                            <a href="fullpost.php?id=<?= $row['post_id']?>" class="fullpost">Read the full news post →</a>
                            <p class="date"><?= date_format(new DateTime($row['time_created']), "F d Y h:i a") ?></p>
                        </div>
                    </div>
                <?php endif?>
            <?php endwhile?>
        </div>
    </div>
</body>
</html>