<?php
require_once 'sessionHandler.php';
require_once 'connect.php';
requireLogin(); // Make sure user is logged in

$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT); 
$post = null;
$error = '';
$success = '';

if((int)$id == 0){
    header('Location: index.php');
    exit();
}else{
    $postQuery = $db->prepare("SELECT p.*, i.image_name, i.image_data FROM posts p LEFT JOIN images i ON p.image_id = i.image_id WHERE post_id = :id");
    $postQuery->execute([':id'=>$id]);
    $post = $postQuery->fetch(PDO::FETCH_ASSOC);

    $commentsQuery = $db->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.user_id WHERE c.post_id = :commentPostId ORDER BY c.created_at DESC");
    $commentsQuery->execute([':commentPostId'=>$id]);
    $comments = $commentsQuery->fetchAll(PDO::FETCH_ASSOC);

    if(!$post){
        header('Location: index.php');
        exit();
    }
}

$userId = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newComment'])){
    $comment = filter_input(INPUT_POST, 'newComment', FILTER_UNSAFE_RAW);
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
    
    if(empty($comment)){
        $error = 'Comment cannot be empty.';
    }
    elseif(strlen($comment) > 1000){
        $error = 'Comment is too long (maximum 1000 characters).';
    }else{
        try{
            $submitComment = $db->prepare("INSERT INTO comments(user_id, post_id, comment) VALUES(:userId, :postId, :comment)");
            $submitComment->execute([':userId'=>$userId, ':postId'=>$post['post_id'], ':comment'=>$comment]);
            $success = 'Comment posted successfully!';

            $commentId = $db->lastInsertId();
            $newCommentQuery = $db->prepare("SELECT c.*, u.username, c.created_at FROM comments c JOIN users u ON c.user_id = u.user_id WHERE c.comment_id = :commentId");
            $newCommentQuery->execute([':commentId'=>$commentId]);
            $newComment = $newCommentQuery->fetch(PDO::FETCH_ASSOC);

            // Refresh comments list
            $commentsQuery = $db->prepare("SELECT c.*, u.username, c.created_at FROM comments c JOIN users u ON c.user_id = u.user_id WHERE c.post_id = :commentPostId ORDER BY c.created_at DESC");
            $commentsQuery->execute([':commentPostId'=>$id]);
            $comments = $commentsQuery->fetchAll(PDO::FETCH_ASSOC);

            if($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $success,
                    'comment' => [
                        'id' => $newComment['comment_id'],
                        'username' => htmlspecialchars($newComment['username']),
                        'comment' => htmlspecialchars($newComment['comment']),
                        'created_at' => date('M j, Y g:i A', strtotime($newComment['created_at']))
                    ],
                    'comment_count' => count($comments)
                ]);
                exit();
            }
        }catch(PDOException $e){
            $error = 'Failed to post comment. Please try again.';

            if($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $error
                ]);
                exit();
            }
        }
    }

        // If this is an AJAX request and we have validation errors, return JSON
    if($isAjax && $error) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
        exit();
    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Fullpost</title>
</head>
<body>
    <?php require_once 'header.php'; ?>
    <div class="comments-page">
        <?php if($post):?>
            <?php if(!empty($post['image_id'])): ?>                            
                <img src="serve_image.php?id=<?= $post['image_id']?>" alt="<?= $post['title']?>" class="fullsize">
            <?php endif ?>
        <div class="posts">
            <h3 class="title">
                <?= $post['title']?>
            </h3>
            <p><?= $post['report']?></p>
        </div>
        <?php endif?>
        <div class="comments-overlay">
            <div class="comments-heading">
                <h2>Comments (<?= count($comments) ?>) </h2>
            </div>
            <?php if($error): ?>
                <div class="error-message" id="php-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message" id="php-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <div class="comments-main"> 
                <form method="POST" id="comment-editor" enctype="multipart/form-data">
                    <textarea name="newComment" id="newComment" class="content"></textarea>
                    <input type="submit" id="postComment" value="Post Comment">
                </form>
            </div>
        </div>
        <div class="all-comments">
            <?php if(empty($comments)): ?>
                <p class="no-comments">No comments yet. Be the first to comment!</p>
            <?php else: ?>
                <?php foreach($comments as $comment):?>
                     <div class="comment">
                        <div class="comment-header">
                            <strong><?= htmlspecialchars($comment['username']) ?></strong><br/>
                            <span class="comment-date"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <p class="comment-text">
                            <?= $comment['comment']?>
                        </p>
                    </div>
                <?php endforeach ?>
            <?php endif; ?>
        </div>
    </div> 
 
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('comment-editor');
            const textarea = document.getElementById('newComment');
            const charCount = document.querySelector('.char-count');
            const commentsContainer = document.querySelector('.all-comments');
            const commentsHeading = document.querySelector('.comments-heading h2');
            
            // Only add character counter if element exists
            if (charCount) {
                // Character counter
                textarea.addEventListener('input', function() {
                    const count = this.value.length;
                    charCount.textContent = count + '/1000';
                    
                    if(count > 1000) {
                        charCount.style.color = 'red';
                    } else {
                        charCount.style.color = '';
                    }
                });
            }
            
            // Auto-resize textarea
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
            
            // Hide PHP messages after a short delay
            setTimeout(() => {
                const phpError = document.getElementById('php-error');
                const phpSuccess = document.getElementById('php-success');
                if(phpError) phpError.style.display = 'none';
                if(phpSuccess) phpSuccess.style.display = 'none';
            }, 3000);
            
            // AJAX form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 
                
                const formData = new FormData(form);
                formData.append('ajax', '1'); // Flag for AJAX request
                
                const submitButton = document.getElementById('postComment');
                
                // Disable submit button to prevent double submission
                submitButton.disabled = true;
                submitButton.value = 'Posting...';
                
                // Clear any existing AJAX messages (keep PHP messages hidden)
                const existingMessages = document.querySelectorAll('.error-message:not(#php-error), .success-message:not(#php-success)');
                existingMessages.forEach(msg => msg.remove());
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        form.insertAdjacentHTML('beforebegin', 
                            '<div class="success-message">' + data.message + '</div>'
                        );
                        
                        // Add new comment to the top of comments list
                        const commentHtml = `
                            <div class="comment">
                                <div class="comment-header">
                                    <strong>${data.comment.username}</strong><br/>
                                    <span class="comment-date">${data.comment.created_at}</span>
                                </div>
                                <p class="comment-text">
                                    ${data.comment.comment.replace(/\n/g, '<br>')}
                                </p>
                            </div>
                        `;
                        
                        // Check if there are existing comments or "no comments" message
                        const noCommentsMsg = commentsContainer.querySelector('.no-comments');
                        if (noCommentsMsg) {
                            // Replace "no comments" message with the new comment
                            commentsContainer.innerHTML = commentHtml;
                        } else {
                            // Add to the beginning of existing comments
                            commentsContainer.insertAdjacentHTML('afterbegin', commentHtml);
                        }
                        
                        // Update comment count in heading
                        commentsHeading.textContent = `Comments (${data.comment_count})`;
                        
                        // Clear the form
                        form.reset();
                        textarea.style.height = 'auto';
                        
                        // Reset character counter if it exists
                        if (charCount) {
                            charCount.textContent = '0/1000';
                            charCount.style.color = '';
                        }
                        
                        // Auto-remove success message after 3 seconds
                        setTimeout(() => {
                            const successMsg = document.querySelector('.success-message:not(#php-success)');
                            if (successMsg) {
                                successMsg.remove();
                            }
                        }, 3000);
                        
                    } else {
                        // Show error message from server
                        form.insertAdjacentHTML('beforebegin', 
                            '<div class="error-message">' + data.error + '</div>'
                        );
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    form.insertAdjacentHTML('beforebegin', 
                        '<div class="error-message">Something went wrong. Please try again.</div>'
                    );
                })
                .finally(() => {
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.value = 'Post Comment';
                });
            });
        });
    </script>
</body>
</html>