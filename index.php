<?php

$username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
$password = $_POST["username"];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Job Postings: Login</title>
</head>
<body>
    <div class="main-overlay">
        <div class="input">
            <form action="index.php" method="POST">
                <h1>Login</h1>
                <div class="input-div">
                    <div class="input-fields">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" placeholder="Username">
                    </div>
                    <div class="input-fields">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" placeholder="Password">
                        <div class="password-div">
                            <input type="checkbox" onclick="showPassword()"><p>Show Password</p>
                        </div>
                    </div>
                    <div class="input-btns">
                        <button><a href="index.php">Login</a></button>
                        <button><a href="signup.php">Sign Up</a></button>
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