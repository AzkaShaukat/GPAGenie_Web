<?php
include 'includes/db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$blog_posts = [];
$fetch_error_message = null;

try {
    $sql = "SELECT id, title, slug, excerpt, image, date_published, views, likes FROM blog_posts ORDER BY date_published DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $blog_posts[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    $fetch_error_message = "Error fetching blog posts: " . $e->getMessage();
    error_log("Blog listing page DB error: " . $e->getMessage());
}

if ($conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - GPA Genie</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/blog.css">
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
                    <form id="searchFormDesktop" class="navbar-form navbar-left" onsubmit="return false;">
                        <div class="form-group">
                            <input id="searchInputDesktop" type="text" class="form-control search-input" placeholder="Search..." autocomplete="off">
                        </div>
                    </form>
                    <ul class="nav navbar-nav">
                        <li><a href="login.html" class="about">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1 class="main-heading">Read Informative Blogs</h1>
                <p class="hero-description">Explore captivating stories and historical mysteries that have shaped our world. Our blogs delve into compelling narratives, intriguing events, and thought-provoking phenomena, offering insights into fascinating aspects of history and human experience. Join us on a journey through the past and uncover tales that continue to intrigue and inspire.</p>
            </div>
            <div class="col-md-4">
                <div class="hero-image-container">
                    <img src="images/blog.png" alt="Admin Illustration" class="img-responsive hero-image"/>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="main-content">
    <div class="container">
        <?php if ($fetch_error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetch_error_message); ?></div>
        <?php elseif (empty($blog_posts)): ?>
            <div class='no-posts'>No blog posts found at the moment. Check back soon!</div>
        <?php else: ?>
            <?php foreach ($blog_posts as $row): ?>
                <?php $date = date("F d, Y", strtotime($row["date_published"])); ?>
                <div class="blog-item">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="blog-image">
                                <img src="images/blog/<?php echo htmlspecialchars($row["image"]); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?>" class="img-responsive">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="blog-content">
                                <div class="blog-date"><?php echo $date; ?></div>
                                <h2 class="blog-title"><?php echo htmlspecialchars($row["title"]); ?></h2>
                                <p class="blog-excerpt"><?php echo htmlspecialchars($row["excerpt"]); ?></p>
                                <a href="blog-post.php?slug=<?php echo htmlspecialchars($row["slug"]); ?>" class="read-more">Read blog <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
<script src="js/script.js"></script>
</body>
</html>
