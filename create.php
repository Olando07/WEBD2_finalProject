<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin(); // Make sure user is logged in

$title = '';
$subtitle = '';
$report = null;
$category = '';
$errors = [];

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

// Fetch categories
try{
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY category_id");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $errors['general'] = "Errors loading categories:" . $e->getMessage();
    $categories = [];
}

// checks if form is submitted
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])){
    // filter unsafe to preserve " and '
    $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
    $subtitle = filter_input(INPUT_POST, 'subtitle', FILTER_UNSAFE_RAW);
    $category = filter_input(INPUT_POST, 'category', FILTER_UNSAFE_RAW);
    $report = filter_input(INPUT_POST, 'hidden-editor', FILTER_UNSAFE_RAW);

    // validate required fields
    if (empty($title)) $errors['title'] = 'The title is required'; 
    if (empty($subtitle)) $errors['subtitle'] = 'The subtitle is required'; 
    if (empty($report)) $errors['hidden-editor'] = 'The report is required'; 
    if (empty($category)) $errors['category'] = 'Please select a category';

    $imageId = null;

    // checks if image is uploaded
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
        // filename sanitization
        $imageFileName = preg_replace('/[^a-zA-Z0-9._-]/', '',basename($_FILES['image']['name']));
        $tempImagePath = $_FILES['image']['tmp_name'];

        // Check file size (limit to 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors['image'] = "File size too large. Maximum 5MB allowed.";
        }elseif (!validFile($tempImagePath, $imageFileName)) {
            $errors['image'] = "Invalid file type. Only GIF, PNG, JPG and JPEG files are allowed.";
        }
        // Check if file already exists and rename if necessary
        else {
            // Generate unique filename to avoid conflicts 
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
    
    // proceed if there are no validation errors
    if(empty($errors) && !empty($title) && !empty($subtitle) && !empty($report)){
        $posts = $db->prepare("INSERT INTO posts(title, subtitle, report, category_id, creator_id, image_id) VALUES(:title, :subtitle, :report, :category, :creator, :image_id)");
        $result = $posts->execute([':title'=>$title, ':subtitle'=>$subtitle, ':report'=>$report, ':category'=>$category, ':creator'=>$_SESSION['user_id'], ':image_id'=>$imageId]);

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
    <title>Create a post</title>
</head>
<body>
    <?php require_once 'header.php'; ?>
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
                        <option value="" id="placeholder">
                            Select a category
                        </option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['category_id']?>" <?= isset($_POST['category']) && $_POST['category'] == $cat['category_id'] ? 'selected' : ''?>>
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
                <div class="form-actions">
                    <input type="submit" name="create" value="Create Post" class="create-btn">
                    <a href="index.php" class="cancel-btn">cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>

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
            // Load previosuly entered input
            if(prevData || prevData.trim() !== ''){
                quill.root.innerHTML = prevData;
            }

            // Update hidden input when changed 
            quill.on('text-change', function(){
                document.getElementById('hidden-editor').value = quill.root.innerHTML;
            });
            
            // handle input and form submission
            // retrieves user input and adds it to hidden input
            document.getElementById('createPostForm').addEventListener('submit', function(e){
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
    </script>

</html>