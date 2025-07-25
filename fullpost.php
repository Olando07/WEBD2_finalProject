<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin(); // Make sure user is logged in

// image resize library
require './php-image-resize-master/lib/ImageResize.php';
require './php-image-resize-master/lib/ImageResizeException.php';

$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT); 
$post = null;

if((int)$id == 0){
    header('Location: index.php');
    exit();
}else{
    $postQuery = $db->prepare("SELECT p.*, i.image_path, i.thumbnail_path, i.fullsize_path FROM posts p LEFT JOIN images i ON p.image_id = i.image_id WHERE post_id = :id");
    $postQuery->execute([':id'=>$id]);
    $post = $postQuery->fetch(PDO::FETCH_ASSOC);

    if(!$post){
        header('Location: index.php');
        exit();
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
        return $resized['fullsize'];
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
    <title>Fullpost</title>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <div class="comments-page">
        <?php if($post):?>
            <?php if(!empty($post['image_id']) && !empty($post['image_path'])): ?>
                <?php
                    $imagePath = getfullsizePath($db, $post['image_id'], $post['image_path'], $post['fullsize_path']); 
                ?>                                   
                <img src="<?= $imagePath?>" alt="<?= $post['image_name']?>" class="fullsize">
            <?php endif ?>
        <div class="posts">
            <h3 class="title">
                <?= $post['title']?>
            </h3>
            <p><?= $post['report']?></p>
        </div>
        <?php endif?>
        <div class="comments-overlay">
            <div class="comments-heading">
                <h2>Comments</h2>
            </div>
            <div class="comments-main"> 
                <div class="comments-head">
                    
                </div>
                <div class="comments">
                    
                </div>
            </div>
        </div>
    </div>

    <!-- TODO: add comments button which shows a pop with comments from other users. users can create, edit and delete comments  -->

</body>
</html>