<?php

require('connect.php');
include 'sessionHandler.php';

$stmt=$db->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username'=>$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require('header.php');

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
    <div class="main">
        <?php if($user['is_admin'] == 1):?>
            <p>This works create page works so W</p>
        <?php else:?>
            <p class="noCreationAccess">Only admins can create news posts. If you have news that you want to report please send email it to the province.
            <br>Please go back to the home page</p>
        <?php endif?>  
    </div>

 <!-- TODO: only admins can create posts -->
 <!-- TODO: make the page fetch info from database based on selected category or input in the search field -->
 <!-- TODO: add comments button which shows a pop with comments from other users. users can create, edit and delete comments  -->




</body>
</html>