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
            <p>This works edit page works so W</p>
        <?php else:?>
            <p class="noCreationAccess">Only admins can edit news posts.
            <br>Please go back to the home page</p>
        <?php endif?>  
    </div>
</body>
</html>