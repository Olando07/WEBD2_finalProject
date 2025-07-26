<?php
require_once 'connect.php';

// Turn off error display to prevent any output before headers
ini_set('display_errors', 0);

// Clean any output buffer
if (ob_get_level()) {
    ob_clean();
}

$imageFound = "Image Not Found";
$databaseError = "There was a server error";
$idError = "The image id is invalid";

if(isset($_GET['id']) && is_numeric($_GET['id'])){
    $imageId = (int)$_GET['id'];

    try{
        $stmt = $db->prepare("SELECT image_data FROM images WHERE image_id = :id");
        $stmt->execute([':id'=>$imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if($image && $image['image_data']){

            // get image type from data
            $imageInfo = getimagesizefromstring($image['image_data']);
            if($imageInfo){
                header('Content-Type: ' . $imageInfo['mime']); // Generic header
            }
            // else{
            //     // header('Content-Type: image/jpeg');
            // }

            header('Content-Length: ' . strlen($image['image_data'])); 
            header('Cache-Control: public, max-age=3600'); 
            
            echo $image['image_data'];
            exit;
        }else{
            header('HTTP/1.0 404 Not Found'); // Image not found
            echo $imageFound;
            exit;
        }
    }catch(PDOException $e){
        header('HTTP/1.0 500 Internal Server Error');
        echo $databaseError;
        exit;
    }
}else{
    header('HTTP/1.0 400 Bad Request');
    exit;
}    


?>