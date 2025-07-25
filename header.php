<?php
require_once 'sessionHandler.php';
require_once 'connect.php';

$userId = $_SESSION['user_id'] ?? '';
$userSearch = $_GET['search-bar'] ?? $_SESSION['last_search'] ?? '';

// only update searchbar if new item is provided
if(isset($_GET['search-bar']) && !empty($_GET['search-bar'])) {
    $_SESSION['last_search'] = $_GET['search-bar'];
}

$userSearch = $_GET['search-bar'] ?? $_SESSION['last_search'] ?? '';

$loggedIn = false;
$isAdmin = false;
$displayUser = 'Guest';
$userinfo = null;

if(isset($_SESSION['user_id'])){
    $userId = $_SESSION['user_id'];
    try{
        $user = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $user->execute([':user_id'=>$userId]);
        $userinfo = $user->fetch(PDO::FETCH_ASSOC);

        if($userinfo){
            $loggedIn = true;
            $displayUser = $userinfo['username'];
            if($userinfo['is_admin'] == 1){
                $isAdmin = true;
            }
        }
    }catch(PDOException $e){
        error_log('Error fetching user: ' . $e->getMessage());
        $userinfo = null;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class='nav-bar'>
        <nav>
            <div class="search-div">
                <input type="text" placeholder="Search" value="<?= htmlspecialchars($userSearch)?>" id="search-bar" name="search-bar">
                <input type="submit" value="Search" id="search-btn" name="search-btn">
            </div>
            <!-- Link to home page -->
            <a href="index.php" id="homepage">Home</a>

            <!-- Link to create page -->
            <?php if($isAdmin): ?>
                <a href="create.php" id="createpost">Create</a>
            <?php endif ?>

            <a href="index.php" id="userInfo"><?= $displayUser?></a>

            <!-- Log out button -->
            <a href="index.php?action=logout" id="loginStatus" onclick="return confirm('Are you sure you want to logout?')">Log out</a>
        </nav>
    </div>

    <!-- Javascript to handle category checkbox clearing -->
    <script>
        window.addEventListener('load', function() {
            if (performance.navigation.type === 1) { // 1 = reload
                document.getElementById('search-bar').value = '';
            }
        });

        // functionality to apply filter and keep previous input in search bar
        function applyFilter(){
            const searchValue = document.getElementById('search-bar');

            let hiddenValue = document.createElement('input');
            hiddenValue.type = hidden;
            hiddenValue.name = 'search-btn';
            hiddenValue.value = searchValue;

            document.querySelector('form').appendChild(hiddenValue);
            document.querySelector('form').submit();
        }

        function clearAll(){
            let category = document.getElementById('category');
            category.value = '';

            document.querySelector('form').submit();
        }
    </script>
</body>
</html>