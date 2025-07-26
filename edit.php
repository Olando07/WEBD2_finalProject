<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin(); // Make sure user is logged in

$errors = [];

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

// get categories
try{
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY category_id");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $errors['general'] = "Errors loading categories: " . $e->getMessage();
    $categories = [];
}

// get post to edit
try{
    $posts = $db->prepare("SELECT * FROM posts WHERE post_id = :id");
    $posts->execute([':id'=>$post_id]);
    $post = $posts->fetch(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $errors['general'] = "Error fetching post: " . $e->getMessage();
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
    $report = filter_input(INPUT_POST, 'hidden-editor', FILTER_UNSAFE_RAW);
    $category = filter_input(INPUT_POST, 'category', FILTER_UNSAFE_RAW);
    
    // validate required fields
    if (empty($title)) $errors['title'] = 'The title is required'; 
    if (empty($subtitle)) $errors['subtitle'] = 'The subtitle is required'; 
    if (empty($report)) $errors['hidden-editor'] = 'The report is required'; 
    if (empty($category)) $errors['category'] = 'Please select a category';

    // keep old image id as a default
    $imageId = $post['image_id'];

    // image upload
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
        // filename sanitization
        $imageFileName = preg_replace('/[^a-zA-Z0-9._-]/', '',basename($_FILES['image']['name']));
        $tempImagePath = $_FILES['image']['tmp_name'];

        // Check file size (limit to 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors['image'] = "File size too large. Maximum 5MB allowed.";
        }elseif (!validFile($tempImagePath, $imageFileName)) {
            $errors['image'] = "Invalid file type. Only GIF, PNG, JPG and JPEG files are allowed.";
        }else{
            $fileExtension = pathinfo($imageFileName, PATHINFO_EXTENSION);
            $uniqueFileName = time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
            try{
                // get original image info
                $imageInfo = getimagesize($tempImagePath);
                $imageWidth = $imageInfo[0];
                $imageHeight = $imageInfo[1];
                $imageType = $imageInfo[2];
                
                // set max dimensions
                $maxWidth = 800;
                $maxHeight = 600;

                // calculate new dimensions
                $ratio = min($maxWidth/$imageWidth, $maxHeight/$imageHeight);
                $newWidth = (int)($imageWidth * $ratio);
                $newHeight = (int)($imageHeight * $ratio);

                switch($imageType) {
                    case IMAGETYPE_JPEG:
                        $sourceImage = imagecreatefromjpeg($tempImagePath);
                        break;
                    case IMAGETYPE_PNG:
                        $sourceImage = imagecreatefrompng($tempImagePath);
                        break;
                    case IMAGETYPE_GIF:
                        $sourceImage = imagecreatefromgif($tempImagePath);
                        break;
                    default:
                        throw new Exception("Your image is not supported. Sorry");
                }

                // create image with resized dimensions
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // resized image
                imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);

                // capture resized image data
                ob_start();
                switch($imageType) {
                    case IMAGETYPE_JPEG:
                        imagejpeg($resizedImage, null, 85);
                        break;
                    case IMAGETYPE_PNG:
                        imagepng($resizedImage);
                        break;
                    case IMAGETYPE_GIF:
                        imagegif($resizedImage);
                        break;
                }
                $resizedImageData = ob_get_contents();
                ob_end_clean();

                // clear memory
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);

                // save resized image to database
                $imgStmt = $db->prepare("INSERT INTO images (image_name, image_data) VALUES (:name, :data)");
                $imgStmt->execute([':name'=> basename($uniqueFileName), ':data'=> $resizedImageData]);

                $imageId = $db->lastInsertId();

            }catch(Exception $e){
                $errors['image'] = "There was an error processing the image: " . $e->getMessage();
            }catch(PDOException $e){
                $errors['image'] = "There was an error saving the image: " . $e->getMessage();
            } 
        }    
    }elseif(isset($_FILES['image']) && $_FILES['image']['error'] > 0 && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE){
        $errors['image'] = 'Error uploading file';
    }
    
    if(empty($errors) && !empty($title) && !empty($subtitle) && !empty($report)){
        $posts = $db->prepare("UPDATE posts SET title = :title, subtitle = :subtitle, report = :report, category_id = :category, image_id = :image WHERE post_id = :id");
        $result = $posts->execute([':title'=>$title, ':subtitle'=>$subtitle, ':report'=>$report, ':category'=>$category, ':id'=>$post_id, ':image'=> $imageId]);

        try {
            if($result){
                header('Location: index.php');
                exit();
            }else{
                $errors['general'] = "Failed to create post. Try again";
            }
        }catch(PDOException $e){
            $errors['general'] = "Database error: " . $e->getMessage();
        }
    }
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
                        <span class="error"><?= isset($errors['title']) ? $errors['title'] : ''?></span>
                    </div>
                    <div class="form-group">
                        <label for="subtitle">Subtitle:</label>
                        <input type="text" id="subtitle" name="subtitle" value="<?= htmlspecialchars($post['subtitle'], ENT_QUOTES | ENT_HTML5)?>">
                        <span class="error"><?= isset($errors['subtitle']) ? $errors['subtitle'] : ''?></span>
                    </div>
                    <div class="form-group">
                        <label for="category">Select a Category:</label>
                        <select name="category" id="category" size="8" required>
                            <option value="" id="placeholder">
                                Select a category
                            </option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['category_id']?>" <?= $cat['category_id'] == $post['category_id'] ? 'selected' : ''?>>
                                    <?= $cat['category_name']?>
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
                        <p>Report:</p>
                        <div name="editor" id="editor" value="<?= isset($_POST['hidden-editor']) ? $_POST['hidden-editor'] : ''?>"></div>
                        <input type="hidden" name="hidden-editor" id="hidden-editor" value="<?= isset($_POST['hidden-editor']) ? $_POST['hidden-editor'] : ''?>">
                        <span class="error"><?= isset($errors['hidden-editor']) ? $errors['hidden-editor']: ''?></span>
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
                document.addEventListener('DOMContentLoaded', function(){
                    const toolbarOptions = [
                        ['bold', 'italic', 'underline', 'strike'],  
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
                        [{ 'script': 'sub'}, { 'script': 'super' }], 
                        [{ 'indent': '-1'}, { 'indent': '+1' }],    
                        ['clean']  
                    
                    ];
                    const quill = new Quill('#editor', {
                        placeholder: 'Enter your report here',
                        theme: 'snow',
                        modules: { toolbar: toolbarOptions }
                    });
                    
                    let currentReport = '';
                    let prevData = document.getElementById('hidden-editor').value;
                    let postContent = <?= json_encode($post['report'] ?? '')?>
                    // Load previosuly entered input
                    if(prevData || prevData.trim() !== ''){
                        quill.root.innerHTML = prevData;
                    }else{
                        quill.root.innerHTML = postContent;
                        document.getElementById('hidden-editor').value = postContent;
                    }

                    // Update hidden input when changed 
                    quill.on('text-change', function(){
                        document.getElementById('hidden-editor').value = quill.root.innerHTML;
                    });
                    
                    // handle input and form submission
                    // retrieves user input and adds it to hidden input
                    document.getElementById('editPostForm').addEventListener('submit', function(e){
                        // update hidden input
                        document.getElementById('hidden-editor').value = quill.root.innerHTML;

                        // Check if there is content
                        let textContent = quill.getText().trim();
                        if(!textContent || textContent === 0){
                            e.preventDefault();
                            return false;
                        }
                    });
                })

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