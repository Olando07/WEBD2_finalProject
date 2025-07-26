<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin(); // Make sure user is logged in

// // image resize library
// require './php-image-resize-master/lib/ImageResize.php';
// require './php-image-resize-master/lib/ImageResizeException.php';

$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT); 
$post = null;

if((int)$id == 0){
    header('Location: index.php');
    exit();
}else{
    $postQuery = $db->prepare("SELECT p.*, i.image_name, i.image_data FROM posts p LEFT JOIN images i ON p.image_id = i.image_id WHERE post_id = :id");
    $postQuery->execute([':id'=>$id]);
    $post = $postQuery->fetch(PDO::FETCH_ASSOC);

    if(!$post){
        header('Location: index.php');
        exit();
    }
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
            <?php if(!empty($post['image_id'])): ?>                            
                <img src="serve_image.php?id=<?= $post['image_id']?>" alt="<?= $post['title']?>" class="fullsize">
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