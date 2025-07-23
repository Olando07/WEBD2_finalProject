<?php

// admin account pass: greatnesswithGod
// regular account pass: itisnotclockingtoyou

require('connect.php');
include_once 'sessionHandler.php';

$userNameError = null;
$passwordError = null;
$loggedIn = null;
$invalidCredentials = null;

if($_POST && !empty($_POST['login'])){
    if(empty($_POST['username'])){
        $userNameError = "Please enter a username.";
    }
    
    if(empty($_POST['password'])){
        $passwordError = "Please enter a password.";
    }
    
    if(!$userNameError && !$passwordError){  
        $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password'];

        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username'=>$username]);
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username'=>$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($password, $user['password'])){
            if(!$user){
                $invalidCredentials = "<p id='failed-login'>Failed login. Check your login info</p>";
            }else if(!password_verify($password, $user['password'])){
                $invalidCredentials = "<p id='failed-login'>Failed login. Check your login info</p>";
            }else{
                $loggedIn = true;

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;

                // header('Location: index.php');
                // exit();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: Login</title>
</head>
<body>
    <div class="main-overlay">
        <div class="input">
            <form action="login.php" method="POST">
                <h1>Login</h1>
                <div class="input-div">
                    <div class="input-fields">

                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" placeholder="Username" value="<?= isset($_POST["username"]) ? htmlspecialchars($_POST["username"]) : '' ?>">
                        <p class="error"><?= $userNameError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" placeholder="Password" value="<?= isset($_POST["password"]) ? htmlspecialchars($_POST["password"]) : '' ?>">
                        <p class="error"><?= $passwordError?></p>
                        <div class="password-div">
                            <input type="checkbox" onclick="showPassword()"><p>Show Password</p>
                        </div>
                        <?php if($loggedIn):?>
                            <div>
                                <p id="logged-in">Successfully logged in</p>
                                <a href="index.php" id="index-link">Click here to see news posts</a>
                            </div>
                        <?php else: ?>
                                <?= $invalidCredentials ?>
                        <?php endif ?>
                    </div>
                    <div class="input-btns" >
                        <input type="submit" name="login" id="login" value="Login">
                        <a href="signup.php">Sign Up</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showPassword(){
            var password = document.getElementById("password");   
            if(password.type === "password"){
                password.type = "text";
            }else{
                password.type = "password";
            }     
        }
    </script>
</body>
</html>