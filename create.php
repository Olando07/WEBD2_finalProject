<?php

require('connect.php');
require_once('header.php');
include_once 'sessionHandler.php';
requireLogin(); // Make sure user is logged in

$title = '';
$subtitle = '';
$report = '';
$category = '';

function uploadPath($fileName, $uploadFolder = 'uploads'){
    $currentFolder = dirname(__FILE__);
    $path = [$currentFolder, $uploadFolder, basename($fileName)];
    
    return join(DIRECTORY_SEPARATOR, $path);
}

function validFile($tempPath, $newPath){
    $validFileTypes = ['gif', 'jpg', 'jpeg', 'png'];

    // Check if file exists and is readable
    if (!is_uploaded_file($tempPath)) {
        return false;
    }

    // Checks file type
    $fileType = pathinfo($newPath, PATHINFO_EXTENSION);
    $isValidFileType = in_array($fileType, $validFileTypes);
    
    // Additional MIME type check for security
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tempPath);
    finfo_close($finfo);
    
    $validMimeTypes = ['image/gif', 'image/jpeg', 'image/png'];
    $isValidMimeType = in_array($mimeType, $validMimeTypes);

    return $isValidFileType && $isValidMimeType;
}

// Create uploads directory if it doesn't exist
$uploadsDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        $errors['general'] = "Could not create uploads directory";
    }
}

// Fetch categories
try{
    $stmt = $db->query("SELECT * FROM categories ORDER BY category_id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $errors['general'] = "Errors loading categories:" . $e->getMessage();
    $categories = [];
}

// checks if form is submitted
if($_POST && !empty($_POST['create'])){
    // filter unsafe to preserve " and '
    $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
    $subtitle = filter_input(INPUT_POST, 'subtitle', FILTER_UNSAFE_RAW);
    $report = filter_input(INPUT_POST, 'report', FILTER_UNSAFE_RAW);
    $category = filter_input(INPUT_POST, 'category', FILTER_UNSAFE_RAW);

    // validate required fields
    if (empty($title)) $errors['title'] = 'The title is required'; 
    if (empty($subtitle)) $errors['subtitle'] = 'This subtitle is required'; 
    if (empty($report)) $errors['report'] = 'This report is required'; 
    if (empty($category)) $errors['category'] = 'Please select a category';

    $imageId = null;

    // handle image upload
    $uploadImage = isset($_FILES['image']) && ($_FILES['image']['error'] === 0);
    $uploadError = isset($_FILES['image']) && ($_FILES['image']['error'] > 0);
    $imageFileName = '';

    // checks if image is uploaded
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
        // filename sanitization
        $imageFileName = preg_replace('/[^a-zA-Z0-9._-]/', '',basename($_FILES['image']['name']));
        $tempImagePath = $_FILES['image']['tmp_name'];
        $newPath = uploadPath($imageFileName);
        $imageRelPath = 'uploads/' . $imageFileName;

        // Check file size (limit to 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors['image'] = "File size too large. Maximum 5MB allowed.";
        }elseif (!validFile($tempImagePath, $newPath)) {
            $errors['image'] = "Invalid file type. Only GIF, PNG, JPG and JPEG files are allowed.";
        }
        // Check if file already exists and rename if necessary
        else {
            $counter = 1;
            $originalPath = $newPath;
            $originalRelPath = $imageRelPath;
            
            // Generate unique filename to avoid conflicts 
            $fileExtension = pathinfo($imageFileName, PATHINFO_EXTENSION);
            $uniqueFileName = time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
            $newPath = uploadPath($uniqueFileName);
            $imageRelPath = 'uploads/' . $uniqueFileName;

            if(move_uploaded_file($tempImagePath, $newPath)){
                try{
                    // add image to database
                    $imgStmt = $db->prepare("INSERT INTO images (image_name, image_path) VALUES (:name, :path)");
                    $imgStmt->execute([':name'=> basename($newPath), ':path'=> $imageRelPath]);
                    
                    $imageId = $db->lastInsertId();
                }catch(PDOException $e){
                    $errors['image'] = "There was an errror in saving the image: " . $e->getMessage();
                    // Clean up uploaded file if database insert fails
                    if (file_exists($newPath)) {
                        unlink($newPath);
                    }
                } 
            }else{
                $errors['image'] = "Failed to upload image. Check directory permissions.";
            }
        }     
    }elseif($uploadError){
        $errors['image'] = 'Error uploading file';
    }
    
    // proceed if there are no validation errors
    if(empty($errors) && $title && $subtitle && $report){
        $posts = $db->prepare("INSERT INTO posts(title, subtitle, report, category_id, creator_id, image_id) VALUES(:title, :subtitle, :report, :category, :creator, :image_id)");
        // checks if there is an image or image error
        if (!$uploadImage || isset($errors['images'])) {
            $imageId = null;
        }

        $posts->execute([':title'=>$title, ':subtitle'=>$subtitle, ':report'=>$report, ':category'=>$category, ':creator'=>$_SESSION['user_id'], ':image_id'=>$imageId]);

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
    <title>Winnipeg News: Create a post</title>
</head>
<body>
    <div class="create-main">
        <div class="create-form">
            <h2>Create a post</h2>

            <form action="" method="POST" id="createPostForm" enctype="multipart/form-data">
                <?php if (isset($errors['general'])): ?>
                    <div class="error-message"><?= $errors['general'] ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?= isset($_POST['title']) ? htmlspecialchars( $_POST['title']) : ''?>" required placeholder="Enter post title">
                    <span class="error"><?= isset($errors['title']) ? $errors['title']: ''?></span>
                </div>
                <div class="form-group">
                    <label for="subtitle">Subtitle:</label>
                    <input type="text" id="subtitle" name="subtitle" value="<?= isset($_POST['subtitle']) ? htmlspecialchars( $_POST['subtitle']) : '' ?>" required placeholder="Enter post subtitle">
                   <span class="error"><?= isset($errors['subtitle']) ? $errors['subtitle']: ''?></span>
                </div>
                <div class="form-group">
                    <label for="category">Select a Category:</label>
                    <select name="category" id="category" size="8" required>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['category_id']?>" <?= isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id'] ? 'selected' : ''?>>
                                <?= $category['category_name']?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group image-upload">
                    <label for="image">Upload Image (Optional):</label>
                    <input type="file" id="image" name="image" accept='image/*'>
                    <small>Allowed formats: GIF, JPG, JPEG, PNG</small>
                    <span class="error"><?= isset($errors['images']) ? $errors['images']: ''?></span>
                </div>
                <div class="form-group">
                    <label for="report">Content</label>
                    <textarea name="report" id="report" rows="10" required><?= isset($_POST['report']) ? htmlspecialchars( $_POST['report']) : ''?></textarea>
                    <span class="error"><?= isset($errors['report']) ? $errors['report']: ''?></span>
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