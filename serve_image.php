<?php
require_once 'connect.php';

$imageFound = "Image Not Found";
$databaseError = "There was a server error";
$idError = "The image id is invalid";

if(isset($_GET['id']) && is_numeric($_GET['id'])){
    $imageId = (int)$_GET;

    try{
        $stmt = $db->prepare("SELECT image_data FROM images WHERE image_id = :id");
        $stmt->execute([':id'=>$imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if($image && $image['image_data']){
            // set headers
            header('Content-Type: image/jpeg'); // Generic header
            header('Content-Length: ' . strlen($image['image_data'])); 
            // header('Content-Type: image/jpeg'); // Generic header
            
            echo $image['image_data'];
        }else{
            header('HTTP/1.0 404 Not Found'); // Image not found
            header('Content-Type: text/plain');
            echo $imageFound;
        }
    }catch(PDOException $e){
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain');
        echo $databaseError;
    }
}else{
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: text/plain');
}    


?>