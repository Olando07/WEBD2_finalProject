<?php

require('connect.php');

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(isset($_GET['action']) && $_GET['action'] === 'logout'){
    session_unset();
    session_destroy();
    
    if(isset($_COOKIE[session_name()])){
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    header('Location: login.php');
    exit();
}

$selectedCategories = $_GET['selected_categories'] ?? [];

if(!empty($selectedCategories)){
    $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
    $stmt = "SELECT * FROM posts WHERE category IN ($placeholders) ORDER BY time_created DESC";
    $posts=$db->prepare($stmt);
    $posts->execute($selectedCategories);      
}else{
    $posts=$db->query('SELECT * FROM posts ORDER BY time_created DESC');
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
                        <input type="submit" value="Apply Filters" class="filter-btn">
                        <!-- Clear button to remove filters -->
                        <input type="submit" value="Clear All" onclick="clearAll()">
                    </div>
                </div>
            </form>
        </div>
        <div class='nav-bar'>
            <nav>
                <div class="search-div">
                    <input type="text" placeholder="Search" id="search-bar">
                    <input type="submit" value="Search" id="search-btn">
                </div>
                <!-- Link to home page -->
                <a href="index.php" id="homepage">Home</a>
                <!-- Log out button -->
                <a href="login.php" id="loginStatus" onclick="return confirm('Are you sure you want to logout?')">Log out</a>
            </nav>
        </div>
        <div class="news-posts">
             <!-- PHP for fetching posts -->
            <?php while($row = $posts->fetch(PDO::FETCH_ASSOC)):?>
                <!-- Check if the current post has a category assigned -->
                <?php if($row['category']):?>
                    <!-- Container div for each individual post -->
                    <div class="posts">
                        <h3 class="title">
                            <?= $row['title']?>
                        </h3>
                        <p><?= $row['report']?></p>
                        <a href="fullpost.php?id=<?= $row['post_id']?>" class="fullpost">Read the full news post →</a>
                    </div>
                <?php endif?>
            <?php endwhile?>
        </div>
    </div>

    <!-- Javascript to handle category checkbox clearing -->
    <script>
        function clearAll(){
            document.querySelectorAll('input[name="selected_categories[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            document.querySelector('form').submit();
        }
    </script>
</body>
</html>