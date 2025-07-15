<?php

require('connect.php');
include 'sessionHandler.php';

$stmt=$db->prepare("SELECT * FROM users WHERE username = :username AND is_admin = :isAdmin");
$stmt->execute([':username'=>$_SESSION['username'], ':isAdmin'=>1]);
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

    <?php if($user):?>
        
    <?php else:?>
        <p class="noCreationAccess">Only admins can create news posts. If you have news that you want to report please send email it to the province.
        <br>Please go back to the home page</p>
    <?php endif?>  

 <!-- TODO: only admins can create posts -->
 <!-- TODO: make the page fetch info from database based on selected category or input in the search field -->
 <!-- TODO: add comments button which shows a pop with comments from other users. users can create, edit and delete comments  -->




</body>
</html>