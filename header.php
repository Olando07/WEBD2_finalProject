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
                <form action="index.php" method="GET">
                    <input type="text" placeholder="Search" value="<?= htmlspecialchars($userSearch)?>" id="search-bar" name="search-bar">
                    <input type="submit" value="Search" id="search-btn" name="search-btn">
                </form>
            </div>
            <!-- Link to home page -->
            <a href="index.php" id="homepage">Home</a>

            <!-- Link to create page -->
            <?php if($isAdmin): ?>
                <a href="create.php" id="createpost">Create</a>
                <a href="admin_posts.php" id="manageposts">Manage Posts</a> 
            <?php endif ?>

            <a href="index.php" id="userInfo"><?= $displayUser?></a>

            <!-- Log out button -->
            <a href="index.php?action=logout" id="loginStatus" onclick="return confirm('Are you sure you want to logout?')">Log out</a>

            <!-- Mode toggler -->
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark/light mode">
                <img class="light-icon" src="./light_dark_icons/sun.svg" alt="Light mode" width="24" height="24">
                <img class="dark-icon" src="./light_dark_icons/moon.svg" alt="Dark mode" width="24" height="24">
            </button>
        </nav>
    </div>

    <!-- Javascript to handle category checkbox clearing -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            themeToggle.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent form submission when toggle button is clicked
                toggleTheme();
            });
        });

        // Theme toggle functionality
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Initialize theme on page load
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        }

        // Initialize theme when page loads
        document.addEventListener('DOMContentLoaded', initTheme);

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