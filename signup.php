<?php

require('connect.php');
$fNameError = null;
$lNameError = null;
$ageError = null;
$emailError = null;
$usernameError = null;
$passwordError = null;
$passwordConfirmError = null;
$noError = true;
$accountCreated = false;

$fName = null;
$lName = null;
$email = null;
$age = null;
$username = null;
$password = null;
$reenterPassword = null;
$user=$db->prepare("INSERT INTO users(username, password, first_name, last_name, age, email)VALUES(:username, :password, :firstName, :lastName, :age, :email)");

if($_POST && !empty($_POST['signup'])){
    // Start of error checking //
    if(empty($_POST['first_name'])){
        $fNameError = "Please enter your first name.";
        $noError = false;
    }
    
    if(empty($_POST['last_name'])){
        $lNameError = "Please enter your last name.";
        $noError = false;
    }
    
    if(empty($_POST['age']) || $_POST['age'] < 16 || $_POST['age'] > 75){
        $ageError = "Please enter a valid age. <br/> You must be atleast 16 years old.";
        $noError = false;
    }
    
    if(empty($_POST['email'])){
        $emailError = "Please enter your email.";
        $noError = false;
    }
    
    if(empty($_POST['username'])){
        $usernameError = "Please enter a username.";
        $noError = false;
    }
    
    if(empty($_POST['password'])){
        $passwordError = "Please enter a password.";
        $noError = false;
    }
    
    if(!$usernameError && !$passwordError){  
        $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password'];
        $reenterPassword = $_POST['password-confirm'];
    }

    if($_POST['password'] != $_POST['password-confirm']){
        $passwordConfirmError = "Your passwords do not match.";
        $noError = false;
    }
    // End of error checking //
    
    // Start of variable assignment //
    $fName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $lName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password']; 
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if($noError){
        $user->execute([':username'=>$username, ':password'=>$hashedPassword, ':firstName'=>$fName, ':lastName'=>$lName, ':age'=>$age, ':email'=>$email]);
        $accountCreated = true;

        $_POST["username"] = '';
        $_POST["password"] = '';
        $_POST["first_name"] = '';
        $_POST["last_name"] = '';
        $_POST["age"] = '';
        $_POST["email"] = '';
    }
    /* End of variable assignment */
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Winnipeg News: Sign Up</title>
</head>
<body>
    <div class='main-overlay'>
        <div class='input'>
            <form action="signup.php" method="POST">
                <h1>Sign Up</h1>
                <div class="input-div">
                    <div class="input-fields">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" placeholder="First name" value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']): '' ?>">
                        <p class="error"><?= $fNameError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" placeholder="Last name" value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']): '' ?>">
                        <p class="error"><?= $lNameError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="age">Age:</label>
                        <input type="text" name="age" id="age" placeholder="Age" value="<?= isset($_POST['age']) ? htmlspecialchars($_POST['age']): '' ?>">
                        <p class="error"><?= $ageError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="username">Email:</label>
                        <input type="text" name="email" id="email" placeholder="Email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']): '' ?>">
                        <p class="error"><?= $emailError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" placeholder="Username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']): '' ?>">
                        <p class="error"><?= $usernameError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" placeholder="Password" value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']): '' ?>">
                        <p class="error"><?= $passwordError?></p>
                    </div>
                    <div class="input-fields">
                        <label for="password">Reenter password:</label>
                        <input type="password" name="password-confirm" id="password-confirm" placeholder="Password" value="<?= isset($_POST['password-confirm']) ? htmlspecialchars($_POST['password']): '' ?>">
                        <p class="error"><?= $passwordConfirmError?></p>
                    </div>
                    <?php if($accountCreated):?>
                        <div class="input-fields">
                            <p id="account">Your account has been created successfully</p>
                            <a href="index.php" id="newUser">Click here to go to the home page</a>
                        </div>
                    <?php endif?>
                    <div class="input-btns">
                        <a href="login.php">Login</a>
                        <input type="submit" name="signup" id="signup" value="Sign Up">
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>