<?php



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
                        <input type="text" name="username" id="username" placeholder="username">
                    </div>
                    <div class="input-fields">
                        <label for="password">Password:</label>
                        <input type="text" name="password" id="password" placeholder="password">
                    </div>
                    <div class="input-btns">
                        <button><a href="index.php">Login</a></button>
                        <button><a href="signup.php">Sign Up</a></button>
                        <!-- <button>Login</button>
                        <button>Sign Up</button> -->
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>