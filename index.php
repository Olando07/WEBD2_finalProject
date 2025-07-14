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
$userSearch = $_GET['search-bar'] ?? '';

// Handle filter and search functionality
if(!empty($selectedCategories)){
    if(!empty($_GET['search-bar'])){
        // Both category filter and search 
        $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
        $stmt = "SELECT * FROM posts WHERE category IN ($placeholders) AND title LIKE ? ORDER BY time_created DESC";
        $posts=$db->prepare($stmt);

        $searchPattern = '%' . $userSearch . '%';
        $params=array_merge($selectedCategories, [$searchPattern]);
        $posts->execute($params); 
    }else{
        // Category filter only
        $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
        $stmt = "SELECT * FROM posts WHERE category IN ($placeholders) ORDER BY time_created DESC";
        $posts=$db->prepare($stmt);
        $posts->execute($selectedCategories);      
    }
}else{
    if(isset($_GET['search-btn']) && !empty($_GET['search-bar'])){
        // Search only 
        $stmt = $db->prepare('SELECT  * FROM posts WHERE title LIKE ? ORDER BY time_created DESC');
        $searchPattern = '%' . $userSearch . '%';
        $posts = $stmt;
        $posts->execute([$searchPattern]);
    }else{
        // No category filter or search
        $posts=$db->query('SELECT * FROM posts ORDER BY time_created DESC');
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
        <div class='nav-bar'>
                <nav>
                <div class="search-div">
                    <input type="text" placeholder="Search" value="<?= isset($_GET['search-bar']) ? htmlspecialchars($userSearch) : '' ?>" id="search-bar" name="search-bar">
                    <input type="submit" value="Search" id="search-btn" name="search-btn">
                </div>
                <!-- TODO: make a guess account where users can only view things -->
            </form>

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
                            <?= htmlspecialchars($row['title'])?>
                        </h3>
                        <p><?= htmlspecialchars($row['report'])?></p>
                        <a href="fullpost.php?id=<?= $row['post_id']?>" class="fullpost">Read the full news post →</a>
                    </div>
                <?php endif?>
            <?php endwhile?>
        </div>
    </div>

    <!-- Javascript to handle category checkbox clearing -->
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
            document.querySelectorAll('input[name="selected_categories[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            document.querySelector('form').submit();
        }
    </script>
</body>
</html>