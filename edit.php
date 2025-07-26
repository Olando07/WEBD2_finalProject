<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin(); // Make sure user is logged in

// image resize library
require './php-image-resize-master/lib/ImageResize.php';
require './php-image-resize-master/lib/ImageResizeException.php';

$errors = ['title'=>'', 'subtitle'=>'', 'report'=>''];

$stmt=$db->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username'=>$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// check if the user is an admin
if($user['is_admin'] != 1){
    header('Location: index.php');
    exit();
}

$post_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT); 

if(!(int)$post_id){
    header('Location: index.php');
    exit();
}

$categoryQuery = $db->query('SELECT * FROM categories');
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// get post to edit
$posts = $db->prepare("SELECT * FROM posts WHERE post_id = :id");
$posts->execute([':id'=>$post_id]);
$post = $posts->fetch(PDO::FETCH_ASSOC);

if(!$post){
    header('Location: index.php');
    exit();
}

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

// form submission
// delete post
if(isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'true'){
    $posts = $db->prepare("DELETE FROM posts WHERE post_id = :id");
    $posts->execute([':id'=>$post_id]);
    header('Location: index.php');
    exit();
}

// update post
if(isset($_POST['update'])){
    $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
    $subtitle = filter_input(INPUT_POST, 'subtitle', FILTER_UNSAFE_RAW);
    $report = filter_input(INPUT_POST, 'report', FILTER_UNSAFE_RAW);
    $category = filter_input(INPUT_POST, 'category', FILTER_UNSAFE_RAW);
    
    // keep old image id as a default
    $imageId = $post['image_id'];

    // image upload
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
        // filename sanitization
        $imageFileName = preg_replace('/[^a-zA-Z0-9._-]/', '',basename($_FILES['image']['name']));
        $tempImagePath = $_FILES['image']['tmp_name'];
        $fileExtension = pathinfo($imageFileName, PATHINFO_EXTENSION);
        $uniqueFileName = time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
        $newPath = uploadPath($uniqueFileName);
        $imageRelPath = 'uploads/' . $uniqueFileName;

        if (validFile($tempImagePath, $newPath)) {
            if(move_uploaded_file($tempImagePath, $newPath)){
                // remove image
                if($post['image_id']){
                    // get old umage path
                    $imgStmt = $db->prepare("SELECT image_path FROM images WHERE image_id = :id");
                    $imgStmt->execute([':id'=> $post['image_id']]);
                    
                    $oldImage = $imgStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // remove old image path
                    if($oldImage && file_exists($oldImage['image_path'])){
                        unlink($oldImage['image_path']);
                    }
                    
                    $deleteImage = $db->prepare('DELETE FROM images WHERE image_id = :id');
                    $deleteImage->execute([':id' => $post['image_id']]);
                }

                // add image to database
                $imgStmt = $db->prepare("INSERT INTO images (image_name, image_path) VALUES (:name, :path)");
                $imgStmt->execute([':name'=> basename($newPath), ':path'=> $imageRelPath]);
                
                $imageId = $db->lastInsertId();
            }else{
                $errors['image'] = "Failed to upload new image";
            }
        }else{
            $errors['image'] = "Invalid image file";
        }
    }
    
    if($title && $subtitle && $report){
        $posts = $db->prepare("UPDATE posts SET title = :title, subtitle = :subtitle, report = :report, category_id = :category, image_id = :image WHERE post_id = :id");
        $posts->execute([':title'=>$title, ':subtitle'=>$subtitle, ':report'=>$report, ':category'=>$category, ':id'=>$post_id, ':image'=> $imageId]);

        header('Location: index.php');
        exit();
    }


    if (empty($title)) $errors['title'] = 'The title is required'; 
    if (empty($subtitle)) $errors['subtitle'] = 'This subtitle is required'; 
    if (empty($report)) $errors['report'] = 'This report is required'; 
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: Edit Post</title>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <div class="edit-main">
            <div class="edit-form">
                <h2>Edit this post</h2>

                <form action="" method="POST" id="editPostForm" enctype="multipart/form-data">
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
                            <option value="" id="placeholder">
                                Select a category
                            </option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category['category_id']?>" <?= $category['category_id'] == $post['category_id'] ? 'selected' : ''?>>
                                    <?= $category['category_name']?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group image-upload">
                        <label for="image">Upload Image (Optional):</label>
                        <input type="file" id="image" name="image" accept='image/*'>
                        <small>Allowed formats: GIF, JPG, JPEG, PNG</small>
                        <span class="error"><?= isset($errors['image']) ? $errors['image']: ''?></span>
                    </div>
                    <div class="form-group">
                        <label for="report">Content</label>
                        <textarea name="report" id="report" rows="10" required><?= $post['report']?></textarea>
                    </div>

                    <input type="hidden" name="confirm_delete" id="deleteFlag" value="">
                    <div class="form-actions">
                        <input type="submit" name="update" value="Update Post" class="update-btn">
                        <input type="button" name="delete" value="Delete Post" class="delete-btn" onclick="showDeleteOverlay()">
                        <a href="index.php" class="cancel-btn">cancel</a>
                    </div>
                </form>
            </div>

            <!-- Delete Confirmation Overlay -->
            <div class="delete-overlay" id="deleteOverlay">
                <div class="delete-modal">
                    <h3>⚠️ Delete Post</h3>
                    <p>Are you sure you want to delete this post?<br>
                    <strong>This action cannot be undone.</strong></p>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                        <button type="button" class="btn btn-secondary" onclick="hideDeleteOverlay()">Cancel</button>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
            <script>
                const quill = new Quill('#report-editor', {
                    theme: 'snow',
                    placeholder: 'Enter your report here'
                });

                function showDeleteOverlay() {
                    document.getElementById('deleteOverlay').style.display = 'flex';
                }

                function hideDeleteOverlay() {
                    document.getElementById('deleteOverlay').style.display = 'none';
                }

                function confirmDelete() {
                    document.getElementById('deleteFlag').value = 'true';
                    document.getElementById('editPostForm').submit();
                }

                // Close overlay with Escape key
                window.addEventListener('keydown', e=>e.key=="Escape" && hideDeleteOverlay());
                // Close overlay when clicking outside the modal
                document.getElementById('deleteOverlay').addEventListener('click', e => {
                    if (e.target === e.currentTarget) { hideDeleteOverlay(); }
                });

            </script>
              <!-- TODO: add input for picture upload and use qil js for form - https://quilljs.com/docs/quickstart  -->
    </div>
</body>
</html>