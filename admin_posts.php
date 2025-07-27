<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin();

$stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username'=>$post['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['is_admin'] !== 1){
    header('Location: index.php');
    exit();
}

// handle sorting
$sortBy = $_GET['sort'] ?? 'time_created';
$sortOrder = $_GET['order'] ?? 'DESC';

// sorting columns allowed
$allowedSorts = ['title', 'time_created', 'updated_at'];
if(!in_array($sortBy, $allowedSorts)){
    $sortBy = 'time_created';
}

// toggle sorting order
$newOrder = ($sortOrder === 'ASC') ? 'DESC' : 'ASC';

// fetch posts with new order
try{
    $stmt = $db->prepare('SELECT p.*, c.category_name FROM posts p LEFT JOIN categories c ON p.category_name = c.category_name ORDER BY {$sortBy} {$sortOrder}');
    $stmt->execute();
    $posts = $stmt->fetch(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    $error = "Error fetching posts: " . $e->getMessage();
    $posts = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Posts</title>
</head>
<body>
    <?= require_once 'header.php';?>

    <div class="admin_posts">
        <h2>Manage Posts</h2>

        <div class="sort-info">
            <strong>Currently sorting by:</strong> 
            <?php  $sortNames = ['title' => 'Title', 'time_created' => 'Created Date', 'updated_at' => 'Updated Date'];
            echo $sortNames[$sortBy] . ' (' . $sortOrder . ')';
            ?>
        </div>

        <table class="posts_table">
            <thead>
                <tr>
                    <th class="<?= $sortBy === 'title' ? 'current_sort' : '' ?>">
                        <a href="?sort=title&order=<?= $sortOrder === 'title' ? $newOrder : 'ASC' ?>" class="sort-link">
                            Title
                            <?php if($sortBy === 'title'): ?>
                                <span class="sort_indicator"><?= $sortOrder === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif ?>
                        </a>
                    </th>
                    <th class="<?= $sortBy === 'time_created' ? 'current_sort' : '' ?>">
                        <a href="?sort=time_created&order=<?= $sortOrder === 'time_created' ? $newOrder : 'DESC' ?>" class="sort-link">
                            Created
                            <?php if($sortBy === 'time_created'): ?>
                                <span class="sort_indicator"><?= $sortOrder === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif ?>
                        </a>
                    </th>
                    <th class="<?= $sortBy === 'created_at' ? 'current_sort' : '' ?>">
                        <a href="?sort=created_at&order=<?= $sortOrder === 'created_at' ? $newOrder : 'DESC' ?>" class="sort-link">
                            Updated
                            <?php if($sortBy === 'created_at'): ?>
                                <span class="sort_indicator"><?= $sortOrder === 'ASC' ? '▲' : '▼' ?></span>
                            <?php endif ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>