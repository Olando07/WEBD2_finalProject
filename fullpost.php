<?php

require('connect.php');


$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT); 

if((int)$id == 0){
    header('Location: index.php');
}else{
    $posts = $db->query("SELECT * FROM posts WHERE post_id = '$id'");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News</title>
</head>
<body>
    <div class="comments-page">
        <?php while($row = $posts->fetch(PDO::FETCH_ASSOC)):?>
        <div class="posts">
            <h3 class="title">
                <?= $row['title']?>
            </h3>
            <p><?= $row['report']?></p>
        </div>
        <?php endwhile?>
        <div class="comments-overlay">
            <div class="comments-main">
                <div class="comments-head">

                </div>
                <div class="comments">

                </div>
            </div>
        </div>
    </div>
</body>
</html>