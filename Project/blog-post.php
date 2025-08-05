<?php
include 'includes/db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$post = null;
$comments = [];
$page_error_message = '';
$post_id_for_js = null;

if (empty($slug)) {
    header("Location: blog.php");
    exit();
}

try {
    // Fetch post details
    $sql_post = "SELECT * FROM blog_posts WHERE slug = ?";
    $stmt_post = $conn->prepare($sql_post);
    if (!$stmt_post) throw new Exception("Prepare failed for post: " . $conn->error);
    $stmt_post->bind_param("s", $slug);
    $stmt_post->execute();
    $result_post = $stmt_post->get_result();

    if ($result_post->num_rows === 0) {
        header("Location: blog.php?error=notfound");
        exit();
    }
    $post = $result_post->fetch_assoc();
    $post_id_for_js = $post['id'];
    $stmt_post->close();

    // Update views
    $update_views = "UPDATE blog_posts SET views = views + 1 WHERE id = ?";
    $stmt_views = $conn->prepare($update_views);
    if (!$stmt_views) throw new Exception("Prepare failed for views: " . $conn->error);
    $stmt_views->bind_param("i", $post['id']);
    $stmt_views->execute();
    $stmt_views->close();
    $post['views']++;

    // Fetch comments
    $sql_comments = "SELECT * FROM blog_comments WHERE post_id = ? AND approved = 1 ORDER BY date_posted DESC";
    $stmt_comments = $conn->prepare($sql_comments);
    if (!$stmt_comments) throw new Exception("Prepare failed for comments: " . $conn->error);
    $stmt_comments->bind_param("i", $post['id']);
    $stmt_comments->execute();
    $comments_result = $stmt_comments->get_result();
    while ($comment_row = $comments_result->fetch_assoc()) {
        $comments[] = $comment_row;
    }
    $stmt_comments->close();

} catch (mysqli_sql_exception $e) {
    $page_error_message = "Database error loading post: " . $e->getMessage();
    error_log("Blog-post page DB error for slug {$slug}: " . $e->getMessage());
} catch (Exception $e) {
    $page_error_message = "Error loading post: " . $e->getMessage();
    error_log("Blog-post page error for slug {$slug}: " . $e->getMessage());
}

// AJAX Handler for Comments and Likes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!$conn || $conn->connect_error) {
        include 'includes/db_connect.php';
    }

    $response = ['status' => 'error', 'message' => 'Unknown action.'];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $action_post_id = isset($_POST['post_id']) ? filter_var($_POST['post_id'], FILTER_VALIDATE_INT) : null;
        if (!$action_post_id) {
            throw new Exception("Post ID is missing or invalid for action.");
        }

        if ($_POST['action'] === 'submit_comment') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $comment_text = trim($_POST['comment']);

            if (empty($name) || empty($email) || empty($comment_text)) {
                throw new Exception("All comment fields are required.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }

            $sql_insert = "INSERT INTO blog_comments (post_id, name, email, comment, date_posted, approved) VALUES (?, ?, ?, ?, NOW(), 1)";
            $stmt_insert = $conn->prepare($sql_insert);
            if (!$stmt_insert) throw new Exception("Prepare failed for comment insert: " . $conn->error);
            $stmt_insert->bind_param("isss", $action_post_id, $name, $email, $comment_text);
            
            if ($stmt_insert->execute()) {
                $new_comment_id = $stmt_insert->insert_id;
                $response = [
                    'status' => 'success', 
                    'message' => 'Thank you for your comment!',
                    'comment' => [
                        'id' => $new_comment_id,
                        'name' => htmlspecialchars($name),
                        'comment' => nl2br(htmlspecialchars($comment_text)),
                        'date_posted' => date("F d, Y"),
                        'avatar_char' => strtoupper(substr($name, 0, 1))
                    ]
                ];
            } else {
                throw new Exception("Error submitting comment: " . $stmt_insert->error);
            }
            $stmt_insert->close();

        } elseif ($_POST['action'] === 'like_post') {
            $update_likes = "UPDATE blog_posts SET likes = likes + 1 WHERE id = ?";
            $stmt_like = $conn->prepare($update_likes);
            if (!$stmt_like) throw new Exception("Prepare failed for like: " . $conn->error);
            $stmt_like->bind_param("i", $action_post_id);
            
            if ($stmt_like->execute()) {
                $result_likes = $conn->query("SELECT likes FROM blog_posts WHERE id = {$action_post_id}");
                $new_likes_count = $result_likes->fetch_assoc()['likes'];
                $response = ['status' => 'success', 'message' => 'Post liked!', 'new_likes_count' => $new_likes_count];
            } else {
                throw new Exception("Error liking post: " . $stmt_like->error);
            }
            $stmt_like->close();
        } else {
            throw new Exception("Invalid action specified.");
        }

    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        $response = ['status' => 'error', 'message' => 'Database error during action: ' . $e->getMessage()];
        error_log('Blog-post AJAX DB error: ' . $e->getMessage());
    } catch (Exception $e) {
        http_response_code(400);
        $response = ['status' => 'error', 'message' => $e->getMessage()];
        error_log('Blog-post AJAX error: ' . $e->getMessage());
    } finally {
        if ($conn && ($_POST['action'] ?? false)) {
             $conn->close();
        }
    }
    echo json_encode($response);
    exit();
}

// Function to safely render HTML content
function renderSafeHTML($content) {
    // Allow specific HTML tags that are commonly used in blog posts
    $allowed_tags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre>';
    return strip_tags($content, $allowed_tags);
}

$display_date = $post ? date("F d, Y", strtotime($post["date_published"])) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post ? htmlspecialchars($post['title']) : 'Blog Post'; ?> - GPA Genie</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/blog.css">
    <style>
        .avatar-placeholder { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #007bff; color: white; border-radius: 50%; font-weight: bold; margin-right: 10px; }
        .comment { display: flex; margin-bottom: 15px; }
        .comment-content { flex-grow: 1; }
        #comment-message-container { margin-bottom: 15px; }
    </style>
</head>
<body>
<marquee>© 2025 GPA Genei — Empowering Students with Smarter Academic Tools to Track, Improve, and Own Their GPA Journey. All rights reserved.</marquee>
<header class="header container-fluid">
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbarNav" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand logo-area" href="index.html">
                    <img src="images/logo.png" alt="Logo" class="logo">
                </a>
            </div>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="nav navbar-nav main-nav">
                    <li><a href="homepage.html">Home</a></li>
                    <li><a href="sgpa.html">SGPA</a></li>
                    <li><a href="cgpa.html">CGPA</a></li>
                    <li><a href="converter.html">Converter</a></li>
                    <li><a href="percentage.html">Percentage</a></li>
                    <li class="active"><a href="blog.php">Blog</a></li>
                    <li><a href="admin.php">Admin</a></li>
                    <li><a href="about.html">About</a></li>
                </ul>
                <div class="nav navbar-nav navbar-right hidden-xs hidden-sm header-right">
                    <ul class="nav navbar-nav">
                        <li><a href="login.html" class="about">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<?php if ($page_error_message): ?>
<main class="main-content"><div class="container"><div class="alert alert-danger"><?php echo htmlspecialchars($page_error_message); ?></div></div></main>
<?php elseif (!$post): ?>
<main class="main-content"><div class="container"><div class="alert alert-warning">The blog post could not be found. <a href="blog.php">Return to blog list</a>.</div></div></main>
<?php else: ?>
<section class="hero-section">
    <div class="container">
        <h1 class="main-heading"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="post-meta">
            <span class="post-date"><i class="far fa-calendar-alt"></i> <?php echo $display_date; ?></span>
            <span class="post-author"><i class="far fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
        </div>
        <p class="hero-description"><?php echo htmlspecialchars($post['excerpt']); ?></p>
    </div>
</section>
<main class="main-content">
    <div class="container">
        <div class="blog-post-container">
            <div class="blog-post-image">
                <img src="images/blog/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-responsive" style="height:auto; max-height:500px; width:100%; object-fit: cover;">
            </div>
            <div class="blog-post-content">
                <?php echo renderSafeHTML($post['content']); ?>
            </div>
        </div>
    </div>
</main>
<section class="engagement-section">
    <div class="container">
        <div class="engagement-wrapper">
            <div class="engagement-stats">
                <span class="comments-toggle" id="comments-toggle" style="cursor:pointer;">
                    <i class="far fa-comment"></i> <span id="comments-count"><?php echo count($comments); ?></span>
                </span>
                <span class="views-count">
                    <i class="far fa-eye"></i> <?php echo $post['views']; ?>
                </span>
                <button type="button" id="likeButton" class="like-button" data-post-id="<?php echo $post['id']; ?>">
                    <i class="far fa-heart"></i> <span id="likes-count"><?php echo $post['likes']; ?></span>
                </button>
            </div>
            <div class="back-to-blog">
                <a href="blog.php"><i class="fas fa-arrow-left"></i> Back to Blog</a>
            </div>
        </div>
    </div>
</section>
<section class="comments-section" id="comments-section" style="display: none;">
    <div class="container">
        <div class="comments-container">
            <h3 class="comments-title">Comments (<span id="comments-title-count"><?php echo count($comments); ?></span>)</h3>
            <div id="comments-list" class="comments-list">
                <?php if (empty($comments)): ?>
                    <p class="no-comments" id="no-comments-message">No comments yet. Be the first to comment!</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-avatar">
                                <div class="avatar-placeholder"><?php echo strtoupper(substr(htmlspecialchars($comment['name']), 0, 1)); ?></div>
                            </div>
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['name']); ?></span>
                                    <span class="comment-date"><?php echo date("F d, Y", strtotime($comment['date_posted'])); ?></span>
                                </div>
                                <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<section class="comment-form-section">
    <div class="container">
        <div class="comment-form-container">
            <h3 class="comment-form-title">Leave a Reply</h3>
            <div id="comment-message-container"></div>
            <form id="commentForm" class="comment-form" method="post" action="blog-post.php?slug=<?php echo htmlspecialchars($slug); ?>">
                 <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="comment">Comment *</label>
                    <textarea id="comment" name="comment" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" name="submit_comment_btn" class="btn btn-primary">Post Comment</button>
            </form>
        </div>
    </div>
</section>

<footer>
    <div class="container-fluid footer-container">
        <div class="row">
            <div class="col-sm-6 footer-links">
                <a href="about.html">About</a>
                <a href="privacy.html">Privacy Policy</a>
                <a href="terms.html">Terms of Service</a>
            </div>
            <div class="col-sm-6 social-icons text-right">
                <a href="#" class="social-icon"> <img src="images/facebook-logo.svg" alt="Facebook"> </a>
                <a href="#" class="social-icon"> <img src="images/instagram-logo.svg" alt="Instagram"> </a>
                <a href="#" class="social-icon"> <img src="images/linkedin-logo.svg" alt="LinkedIn"> </a>
            </div>
        </div>
    </div>
</footer>

<section class="more-converters-section">
   </section>

<?php endif; ?>

<footer></footer>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    const postId = <?php echo $post_id_for_js ? $post_id_for_js : 'null'; ?>;

    // Toggle comments section
    $('#comments-toggle').on('click', function() {
        $('#comments-section').toggle();
    });

    // AJAX for Comment Submission
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!postId) return;
        
        const formData = $(this).serialize() + '&action=submit_comment';
        const submitButton = $(this).find('button[type="submit"]');
        
        submitButton.prop('disabled', true).text('Posting...');
        $('#comment-message-container').empty();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#comment-message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                    $('#commentForm')[0].reset();
                    
                    if (response.comment) {
                        $('#no-comments-message').hide();
                        
                        const newComment = `
                            <div class="comment" id="comment-${response.comment.id}">
                                <div class="comment-avatar">
                                    <div class="avatar-placeholder">${response.comment.avatar_char}</div>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <span class="comment-author">${response.comment.name}</span>
                                        <span class="comment-date">${response.comment.date_posted}</span>
                                    </div>
                                    <div class="comment-text">${response.comment.comment}</div>
                                </div>
                            </div>`;
                        
                        $('#comments-list').prepend(newComment);
                        
                        const currentCount = parseInt($('#comments-count').text());
                        $('#comments-count').text(currentCount + 1);
                        $('#comments-title-count').text(currentCount + 1);
                    }
                } else {
                    $('#comment-message-container').html('<div class="alert alert-danger">' + (response.message || 'Error submitting comment.') + '</div>');
                }
            },
            error: function() {
                $('#comment-message-container').html('<div class="alert alert-danger">An unexpected error occurred.</div>');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Post Comment');
            }
        });
    });

    // AJAX for Like Button
    $('#likeButton').on('click', function() {
        if (!postId) return;
        
        const button = $(this);
        button.prop('disabled', true);

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'like_post',
                post_id: postId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && typeof response.new_likes_count !== 'undefined') {
                    $('#likes-count').text(response.new_likes_count);
                } else {
                    alert(response.message || 'Failed to like post.');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred while liking the post.');
                button.prop('disabled', false);
            }
        });
    });
});
</script>
</body>
</html>
<?php
if ($conn && $conn instanceof mysqli && !($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']))) {
    $conn->close();
}
?>
