<?php
// AJAX handler for updating blog posts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_blog_ajax'])) {
    header('Content-Type: application/json');
    include 'includes/db_connect.php';

    $response = ['status' => 'error', 'message' => 'An unknown error occurred during update.'];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception("Invalid blog post ID for update.");
        }

        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $excerpt = trim($_POST['excerpt']);
        $content = trim($_POST['content']);
        $author = trim($_POST['author']);
        $current_image = $_POST['current_image'];

        if (empty($title) || empty($slug) || empty($excerpt) || empty($content) || empty($author)) {
            throw new Exception("Please fill in all required fields for update.");
        }

        $image_name = $current_image;

        // Handle image upload if a new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            $filename = $_FILES["image"]["name"];
            $filetype = $_FILES["image"]["type"];
            $filesize = $_FILES["image"]["size"];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!array_key_exists($ext, $allowed)) {
                throw new Exception("Invalid new file type. Only JPG, JPEG, PNG and GIF types are accepted.");
            }
            
            $maxsize = 5 * 1024 * 1024;
            if ($filesize >= $maxsize) {
                throw new Exception("New file size is larger than the allowed limit (5MB).");
            }

            $new_image_name = uniqid() . "." . $ext;
            $target = "images/blog/" . $new_image_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
                if ($current_image && $current_image != $new_image_name) {
                    $old_image_path = "images/blog/" . $current_image;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image_name = $new_image_name;
            } else {
                throw new Exception("Error uploading new file. Check permissions or path.");
            }
        }

        $sql = "UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed for update: " . $conn->error);
        }
        $stmt->bind_param("ssssssi", $title, $slug, $excerpt, $content, $author, $image_name, $id);

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Blog post updated successfully!', 'updated_image' => $image_name, 'redirect' => 'admin.php'];
        } else {
            throw new Exception("Error updating blog post: " . $stmt->error);
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        $response = ['status' => 'error', 'message' => 'Database error during update: ' . $e->getMessage()];
    } catch (Exception $e) {
        http_response_code(400);
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        if ($conn) {
            $conn->close();
        }
    }

    echo json_encode($response);
    exit();
}

// Fetch existing post data for the form
include 'includes/db_connect.php';
$id_get = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$post = null;
$form_error_message = '';

if (!$id_get) {
    $form_error_message = "No blog post ID provided or invalid ID.";
} else {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $sql_fetch = "SELECT * FROM blog_posts WHERE id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        if (!$stmt_fetch) throw new Exception("Failed to prepare statement for fetching post: ".$conn->error);
        $stmt_fetch->bind_param("i", $id_get);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Blog post not found with ID: " . htmlspecialchars($id_get));
        }
        $post = $result->fetch_assoc();
        $stmt_fetch->close();
    } catch (Exception $e) {
        $form_error_message = "Error fetching blog post data: " . $e->getMessage();
        $post = null;
    }
}
if ($conn) $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Blog Post - GPA Genie</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/blog.css">
    <style>
        .form-container { background-color: #f9f9f9; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        .form-group label { font-weight: 600; }
        .current-image img { max-width: 200px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; margin-bottom:10px; }
        .btn-container { margin-top: 20px; }
        #message-container { margin-top: 15px; }
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
                    <li><a href="blog.php">Blog</a></li>
                    <li class="active"><a href="admin.php">Admin</a></li>
                    <li><a href="about.html">About</a></li>
                </ul>

                <div class="nav navbar-nav navbar-right hidden-xs hidden-sm header-right">
                    <ul class="nav navbar-nav">
                        <li><a href="logout.php" class="about">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>
<section class="hero-section">
    <div class="container">
        <h1 class="main-heading">Update Blog Post</h1>
        <p class="hero-description">Edit the content of your blog post below.</p>
    </div>
</section>
<main class="main-content">
    <div class="container">
        <div id="message-container">
            <?php if (!empty($form_error_message) && !$post): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($form_error_message); ?></div>
            <?php endif; ?>
        </div>
        
        <?php if ($post): ?>
        <div class="form-container">
            <form id="updateBlogForm" method="post" action="update-blog.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($post['id']); ?>">
                <input type="hidden" name="current_image" id="current_image_field" value="<?php echo htmlspecialchars($post['image']); ?>">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($post['slug']); ?>" required readonly>
                </div>
                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3" required><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    <p class="help-block">You can use HTML tags like &lt;h3&gt;, &lt;strong&gt;, &lt;p&gt;, &lt;br&gt;, etc.</p>
                </div>
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($post['author']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="current-image">
                        <img src="images/blog/<?php echo htmlspecialchars($post['image']); ?>" alt="Current Image" id="currentImagePreview">
                    </div>
                </div>
                <div class="form-group">
                    <label for="image">Upload New Image (Leave empty to keep current image)</label>
                    <input type="file" class="form-control" id="image" name="image">
                    <p class="help-block">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB.</p>
                </div>
                <div class="btn-container">
                    <button type="submit" name="update_blog" class="btn btn-primary">Update Blog Post</button>
                    <a href="admin.php" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
        <?php elseif (empty($form_error_message)): ?>
            <div class="alert alert-warning">Blog post not found or ID is invalid. <a href="admin.php">Return to Admin</a>.</div>
        <?php endif; ?>
    </div>
</main>
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Auto-generate slug from title
    $('#title').on('keyup', function() {
        const title = $(this).val();
        const slug = title.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        $('#slug').val(slug);
    });

    // AJAX form submission
    $('#updateBlogForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('update_blog_ajax', 'true');

        const messageContainer = $('#message-container');
        messageContainer.empty();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Updating...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    messageContainer.html('<div class="alert alert-success">' + response.message + '</div>');
                    if (response.updated_image) {
                        $('#currentImagePreview').attr('src', 'images/blog/' + response.updated_image + '?' + new Date().getTime());
                        $('#current_image_field').val(response.updated_image);
                    }
                    $('#image').val('');
                    
                    // Redirect to admin page after 2 seconds
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 2000);
                    }
                } else {
                    messageContainer.html('<div class="alert alert-danger">' + (response.message || 'An error occurred.') + '</div>');
                }
            },
            error: function() {
                messageContainer.html('<div class="alert alert-danger">An unexpected error occurred. Please try again.</div>');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Update Blog Post');
            }
        });
    });
});
</script>
</body>
</html>
