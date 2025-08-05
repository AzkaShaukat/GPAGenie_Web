<?php
// AJAX handler for adding blog posts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_blog_ajax'])) {
    header('Content-Type: application/json');
    include 'includes/db_connect.php';

    $response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

    try {
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $excerpt = trim($_POST['excerpt']);
        $content = trim($_POST['content']);
        $author = trim($_POST['author']);

        if (empty($title) || empty($slug) || empty($excerpt) || empty($content) || empty($author)) {
            throw new Exception("Please fill in all required fields.");
        }

        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            $filename = $_FILES["image"]["name"];
            $filetype = $_FILES["image"]["type"];
            $filesize = $_FILES["image"]["size"];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!array_key_exists($ext, $allowed)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG and GIF types are accepted.");
            }
            
            $maxsize = 5 * 1024 * 1024;
            if ($filesize >= $maxsize) {
                throw new Exception("File size is larger than the allowed limit (5MB).");
            }

            $image_name = uniqid() . "." . $ext;
            $target = "images/blog/" . $image_name;

            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
                throw new Exception("Error uploading file. Check permissions or path.");
            }
        } else {
            throw new Exception("Please select an image for the blog post.");
        }

        $sql = "INSERT INTO blog_posts (title, slug, excerpt, content, author, image, date_published, views, likes)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 0, 0)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ssssss", $title, $slug, $excerpt, $content, $author, $image_name);

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Blog post added successfully!', 'redirect' => 'admin.php'];
        } else {
            throw new Exception("Error adding blog post: " . $stmt->error);
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
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

$success_message = '';
$error_message = '';
$title_val = $slug_val = $excerpt_val = $content_val = $author_val = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Blog Post - GPA Genie</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/blog.css">
    <style>
        .form-container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: 600;
        }
        .btn-container {
            margin-top: 20px;
        }
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
        <div class="row">
            <div class="col-md-12">
                <h1 class="main-heading">Add New Blog Post</h1>
                <p class="hero-description">Create a new blog post by filling out the form below.</p>
            </div>
        </div>
    </div>
</section>

<main class="main-content">
    <div class="container">
        <div id="message-container"></div>
        
        <div class="form-container">
            <form id="addBlogForm" method="post" action="add-blog.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title_val); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug (URL-friendly version of title)</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($slug_val); ?>" required readonly>
                </div>
                
                <div class="form-group">
                    <label for="excerpt">Excerpt (Short description)</label>
                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3" required><?php echo htmlspecialchars($excerpt_val); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($content_val); ?></textarea>
                    <p class="help-block">You can use HTML tags like &lt;h3&gt;, &lt;strong&gt;, &lt;p&gt;, &lt;br&gt;, etc.</p>
                </div>
                
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($author_val); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="image">Featured Image</label>
                    <input type="file" class="form-control" id="image" name="image" required>
                    <p class="help-block">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB.</p>
                </div>
                
                <div class="btn-container">
                    <button type="submit" name="add_blog" class="btn btn-success">Add Blog Post</button>
                    <a href="admin.php" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
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
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
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
    $('#addBlogForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('add_blog_ajax', 'true');

        const messageContainer = $('#message-container');
        messageContainer.empty();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Adding...');

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
                    $('#addBlogForm')[0].reset();
                    $('#slug').val('');
                    
                    // Redirect to admin page after 2 seconds
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 2000);
                    }
                } else {
                    messageContainer.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                messageContainer.html('<div class="alert alert-danger">An unexpected error occurred. Please try again.</div>');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Add Blog Post');
            }
        });
    });
});
</script>
</body>
</html>
