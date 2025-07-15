<?php

$userSearch = $_GET['search-bar'] ?? '';

if(isset($_GET['search-bar']) && !empty($_GET['search-bar'])) {
    $_SESSION['last_search'] = $_GET['search-bar'];
}

$userSearch = $_GET['search-bar'] ?? $_SESSION['last_search'] ?? '';

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
            <a href="create.php" id="createpost">Create</a>

            <!-- Link to edit page  -->
            <a href="edit.php" id="editpost">Edit</a>
            <!-- Log out button -->
            <a href="login.php" id="loginStatus" onclick="return confirm('Are you sure you want to logout?')">Log out</a>
        </nav>
    </div>

    <!-- Javascript to handle category checkbox clearing -->
    <script>
        window.addEventListener('load', function() {
            if (performance.navigation.type === 1) { // 1 = reload
                document.getElementById('search-bar').value = '';
                // Also clear the URL if you want
                // window.history.replaceState({}, document.title, window.location.pathname);
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
            document.querySelectorAll('input[name="selected_categories[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            document.querySelector('form').submit();
        }
    </script>
</body>
</html>