<?php


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Job Postings: Sign Up</title>
</head>
<body>
    <div class='main-overlay'>
        <div class='input'>
            <form action="index.php" method="POST">
                <h1>Sign Up</h1>
                <div class="input-div">
                    <div class="input-fields">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" placeholder="First name">
                    </div>
                    <div class="input-fields">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" placeholder="Last name">
                    </div>
                    <div class="input-fields">
                        <label for="age">Select your age:</label>
                        <select name="age" id="age">
                            <option value="placeholder" selected>Click to select</option>
                            <option value="option">16-20</option>
                            <option value="option">21-30</option>
                            <option value="option">31-40</option>
                            <option value="option">41-50</option>
                            <option value="option">51-60</option>
                            <option value="option">61-70</option>                            
                        </select>
                    </div>
                    <div class="input-fields">
                        <label for="address">Address:</label>
                        <input type="text" name="address" id="address" placeholder="Address">
                    </div>
                    <div class="input-fields">
                        <label for="education">Select your education level:</label>
                        <select name="education" id="education">
                            <option value="placeholder" selected>Click to select</option>
                            <option value="option">No Certificate, Diploma or Degree</option>
                            <option value="option">High School Diploma</option>
                            <option value="option">College/University</option>
                            <option value="option">Certificate/Diploma</option>
                            <option value="option">Bachelor's Degree</option>
                            <option value="option">Master's Degree</option>
                            <option value="option">Doctoral Degree</option>
                            <option value="option">Professional Degree</option>
                        </select>
                    </div>
                    <div class="input-fields">
                        <label for="Skills">List your skills:</label>
                        <textarea name="Skills" id="Skills" placeholder="Skills"></textarea>
                    </div>
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