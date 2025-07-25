<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin();

// image resize library
require './php-image-resize-master/lib/ImageResize.php';
require './php-image-resize-master/lib/ImageResizeException.php';

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
        $stmt = $db->prepare("SELECT p.*, c.category_name, i.image_name, i.image_path, i.thumbnail_path, i.fullsize_path FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id WHERE p.category_id = ? AND p.title LIKE ? ORDER BY p.time_created DESC, p.title, p.subtitle");
        $searchPattern = '%' . $userSearch . '%';
        $posts=$stmt;
        $posts->execute([$selectedCategory, $searchPattern]); 
    }else{
        // Category filter only
        $stmt = $db->prepare("SELECT p.*, c.category_name, i.image_name, i.image_path, i.thumbnail_path, i.fullsize_path FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id WHERE p.category_id = ? ORDER BY p.time_created DESC, p.title, p.subtitle");
        $posts=$stmt;
        $posts->execute([$selectedCategory]);      
    }
}else{
    if(isset($_GET['search-btn']) && $_GET['search-bar']){
        // Search only 
        $stmt = $db->prepare("SELECT p.*, c.category_name, i.image_name, i.image_path, i.thumbnail_path, i.fullsize_path FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id WHERE p.title LIKE ? ORDER BY p.time_created DESC, p.title, p.subtitle");
        $searchPattern = '%' . $userSearch . '%';
        $posts = $stmt;
        $posts->execute([$searchPattern]);
    }else{
        // No category filter or search
        $posts=$db->query("SELECT p.*, c.category_name, i.image_name, i.image_path, i.thumbnail_path, i.fullsize_path FROM posts p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN images i ON p.image_id = i.image_id ORDER BY p.time_created DESC, p.title, p.subtitle");
    }
}   

// function to create resized images
function createResizedImages($db, $imageId, $originalPath){
    global $db;

    if(!$originalPath || !file_exists($originalPath)){
        return false;
    }

    try{
        // get file info
        $pathInfo = pathinfo($originalPath);
        $fileName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory =$pathInfo['dirname'];

        $thumbnailPath = $directory . '/' . $fileName . '_thumb.' . $extension;
        if(!file_exists($thumbnailPath)){
            $image = new Gumlet\ImageResize($originalPath);
            $image->resizeToBestFit(300, 200);
            $image->save($thumbnailPath);
        }
        
        $fullsizePath = $directory . '/' . $fileName . '_full.' . $extension;
        if(!file_exists($fullsizePath)){
            $image = new Gumlet\ImageResize($originalPath);
            $image->resizeToBestFit(800, 600);
            $image->save($fullsizePath);
        }
        
        $stmt = $db->prepare('UPDATE images SET thumbnail_path = ?, fullsize_path = ? WHERE image_id = ?');
        $stmt->execute([$thumbnailPath, $fullsizePath, $imageId]);

        return ['thumbnail'=>$thumbnailPath, 'fullsize'=>$fullsizePath];

    }catch(Exception $e){
        error_log("Image resize error: " . $e->getMessage());
        return false;
    }
}

// Function to get thumbnail image path
function getThumbnailPath($db, $imageId, $originalPath, $thumbnailPath){
    // return existing path 
    if(!empty($thumbnailPath) && file_exists($thumbnailPath)){
        return $thumbnailPath;
    }
    
    // return existing path 
    if(empty($thumbnailPath) && !file_exists($thumbnailPath)){
        return $originalPath;
    }
    
    // if thumbnail image does not exist create one
    $resized = createResizedImages($db, $imageId, $originalPath);
    if($resized && is_array($resized) && isset($resized['thumbnail'])){
        return $resized('thumbnail');
    }

    return $originalPath;
}

// Function to get fullsize image path
function getfullsizePath($db, $imageId, $originalPath, $fullsizePath){
    // return existing path
    if(!empty($fullsizePath) && file_exists($fullsizePath)){
        return $fullsizePath;
    }
    
    // return existing path
    if(empty($fullsizePath) && !file_exists($fullsizePath)){
        return $originalPath;
    }
    
    // if fullsize image does not exist create one
    $resized = createResizedImages($db, $imageId, $originalPath);
    if($resized && is_array($resized) && isset($resized['fullsize'])){
        return $resized('fullsize');
    }

    return $originalPath;
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
                        <option value="placeholder" id="placeholder">
                            All categories
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

                        <?php if(!empty($row['image_id']) && !empty($row['image_path'])): ?>
                            <?php
                                $imagePath = getThumbnailPath($db, $row['image_id'], $row['image_path'], $row['thumbnail_path']); 
                            ?>                                   
                            <img src="<?= $imagePath?>" alt="<?= $row['image_name']?>" class="thumbnail">
                        <?php endif ?>

                        <p><?= htmlspecialchars($row['report'], ENT_QUOTES | ENT_HTML5)?></p>
                        <div class="post-bottom">
                            <a href="fullpost.php?id=<?= $row['post_id']?>" class="fullpost">Read the full news post →</a>
                            <p class="date"><?= date_format(new DateTime($row['time_created']), "F d Y h:i a") ?></p>
                        </div>
                        <!-- TODO: show date when post was last update -->
                    </div>
                <?php endif?>
            <?php endwhile?>
        </div>
    </div>
</body>
</html>