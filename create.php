<?php

require('connect.php');
require_once('header.php');
include_once 'sessionHandler.php';
requireLogin(); // Make sure user is logged in

require './php-image-resize-master/lib/ImageResize.php';
require './php-image-resize-master/lib/ImageResizeException.php';

$title = '';
$subtitle = '';
$report = '';
$category = '';

$titleError = '';
$subtitleError = '';
$reportError = '';
$post = ['title'=>'', 'subtitle'=>'', 'category_id'=>'', 'report'=>''];

function uploadPath($fileName, $uploadFolder = 'uploads'){
    $currentFolder = dirname(__FILE__);
    $path = [$currentFolder, $uploadFolder, basename($fileName)];
    
    return join(DIRECTORY_SEPARATOR, $path);
}

function validFile($tempPath, $newPath){
    $validFileTypes = ['gif', 'jpg', 'jpeg', 'png'];

    $fileType = pathinfo($newPath, PATHINFO_EXTENSION);
    
    $isValidFileType = in_array($fileType, $validFileTypes);
    
    return $isValidFileType;
}

try{
    $stmt = $db->query("SELECT * FROM categories ORDER BY category_id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $errors['general'] = "Errors loading categories:" . $e->getMessage();
    $categories = [];
}

if($_POST && !empty($_POST['create'])){
    $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
    $subtitle = filter_input(INPUT_POST, 'subtitle', FILTER_UNSAFE_RAW);
    $report = filter_input(INPUT_POST, 'report', FILTER_UNSAFE_RAW);
    $category = filter_input(INPUT_POST, 'category', FILTER_UNSAFE_RAW);

    $uploadImage = isset($_FILES['image']) && ($_FILES['image']['error'] === 0);
    $uploadError = isset($_FILES['image']) && ($_FILES['image']['error'] > 0);
    $uploadedFile = '';
    $resized50Path = '';

    if($uploadImage){
        $imageFileName = $_FILES['image']['name'];
        $tempImagePath = $_FILES['image']['tmp_name'];
        $newPath = uploadPath($imageFileName);

        if(validFile($tempImagePath, $newPath)){
            if(move_uploaded_file($tempImagePath, $newPath)){
                $uploadedFile = $newPath;
                
                try{
                    // Resize to 50px width
                    $image50 = new \Gumlet\ImageResize($newPath);
                    $image50->resizeToWidth(50);
                    $resized50Path = uploadPath('50px_' . $imageFileName);
                    $image50->save($resized50Path);
                }catch(Exception $e){
                    $errors['images'] = "Error processing the image" . $e->getMessage();
                }
            }else{
                $errors['images'] = "Failed to upload image";
            }
        }else{
            $errors['images'] = "Invalid file type. Only gif, png, jpg and jpeg file types are allowed.";    
        }
    }elseif($uploadError){
        $errors['image'] = 'Error uploading file';
    }
    


}

if($title && $subtitle && $report){
    $posts = $db->prepare("INSERT INTO posts(title, subtitle, report, category_id, creator_id) VALUES(:title, :subtitle, :report, :category, :creator)");
    $posts->execute([':title'=>$title, ':subtitle'=>$subtitle, ':report'=>$report, ':category'=>$category, ':creator'=>$_SESSION['user_id']]);
    header('Location: index.php');
    exit();
}

if (empty($title)) $errors['title'] = 'The title is required'; 
if (empty($subtitle)) $errors['subtitle'] = 'This subtitle is required'; 
if (empty($report)) $errors['report'] = 'This report is required'; 

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
                    <input type="text" id="title" name="title" value="<?= isset($post['title']) ? htmlspecialchars( $post['title']) : ''?>" required placeholder="Enter post title">
                    <span class="error"><?= $titleError?></span>
                </div>
                <div class="form-group">
                    <label for="subtitle">Subtitle:</label>
                    <input type="text" id="subtitle" name="subtitle" value="<?= isset($post['subtitle']) ? htmlspecialchars( $post['subtitle']) : '' ?>" required placeholder="Enter post subtitle">
                    <span class="error"><?= $subtitleError?></span>
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
                    <input type="submit" name="create" value="Create Post" class="create-btn">
                    <a href="index.php" class="cancel-btn">cancel</a>
                </div>
            </form>
        </div>
    </div>

 <!-- TODO: add comments button which shows a pop with comments from other users. users can create, edit and delete comments  -->

</body>
</html>