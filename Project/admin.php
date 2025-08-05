<?php
// AJAX handler for delete operations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_blog') {
    header('Content-Type: application/json');
    include 'includes/db_connect.php';
    
    $response = ['status' => 'error', 'message' => 'Unknown error occurred.'];
    
    try {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception("Invalid blog post ID.");
        }
        
        // First, get the image filename to delete the file
        $sql = "SELECT image FROM blog_posts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $image_path = "images/blog/" . $row['image'];
            
            // Delete the image file if it exists
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            
            // Delete the blog post from the database
            $sql_delete = "DELETE FROM blog_posts WHERE id = ?";
            $stmt = $conn->prepare($sql_delete);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Also delete any comments associated with this post
                $sql_delete_comments = "DELETE FROM blog_comments WHERE post_id = ?";
                $stmt = $conn->prepare($sql_delete_comments);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $response = ['status' => 'success', 'message' => 'Blog post deleted successfully!'];
            } else {
                throw new Exception("Error deleting blog post: " . $stmt->error);
            }
        } else {
            throw new Exception("Blog post not found.");
        }
        
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
    
    echo json_encode($response);
    exit();
}

include 'includes/db_connect.php';

// Check if user is logged in (you may want to add proper authentication)
// This is a placeholder - implement proper authentication in production
$is_admin = true; // For demo purposes

if (!$is_admin) {
    header("Location: login.html");
    exit();
}

$sql = "SELECT id, title, slug, excerpt, image, date_published, views, likes FROM blog_posts ORDER BY date_published DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Blog Management - GPA Genie</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/blog.css">
    <style>
        .admin-actions {
            margin-top: 15px;
        }
        .admin-actions .btn {
            margin-right: 10px;
        }
        .add-blog-btn {
            margin: 20px 0;
            text-align: center;
        }
        .add-blog-btn .btn {
            padding: 10px 30px;
            font-size: 18px;
        }
        #message-container {
            margin-bottom: 20px;
        }
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
                        <li><a href="login.php" class="about">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Hero Section with Light Green Background -->
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1 class="main-heading">Blog Administration</h1>
                <p class="hero-description">Manage your blog posts - create, update, and delete content from this admin dashboard.Join us on a journey through the past and uncover tales that continue to intrigue and inspire.</p>
            </div>
            <div class="col-md-4">
                <div class="hero-image-container">
                    <img src="images/blog.png" alt="Admin Illustration" class="img-responsive hero-image"/>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content with White Background -->
<main class="main-content">
    <div class="container">
        <div id="message-container"></div>
        
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $date = date("F d, Y", strtotime($row["date_published"]));
                ?>
                <div class="blog-item" id="blog-item-<?php echo $row["id"]; ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="blog-image">
                                <img src="images/blog/<?php echo $row["image"]; ?>" alt="<?php echo $row["title"]; ?>" class="img-responsive">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="blog-content">
                                <div class="blog-date"><?php echo $date; ?></div>
                                <h2 class="blog-title"><?php echo $row["title"]; ?></h2>
                                <p class="blog-excerpt"><?php echo $row["excerpt"]; ?></p>
                                <div class="admin-actions">
                                    <a href="update-blog.php?id=<?php echo $row["id"]; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Update
                                    </a>
                                    <button type="button" class="btn btn-danger delete-blog-btn" data-id="<?php echo $row["id"]; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<div class='no-posts'>No blog posts found.</div>";
        }
        ?>
        
        <div class="add-blog-btn">
            <a href="add-blog.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Blog Post
            </a>
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
<script src="js/script.js"></script>
<script>
$(document).ready(function() {
    // AJAX Delete functionality
    $('.delete-blog-btn').on('click', function() {
        if (!confirm('Are you sure you want to delete this blog post?')) {
            return;
        }
        
        const blogId = $(this).data('id');
        const blogItem = $('#blog-item-' + blogId);
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
        
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: {
                action: 'delete_blog',
                id: blogId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                    blogItem.fadeOut(500, function() {
                        $(this).remove();
                    });
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                    button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred while deleting the blog post.</div>');
                button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
            }
        });
    });
});
</script>
</body>
</html>
<?php
// Close connection
$conn->close();
?>
