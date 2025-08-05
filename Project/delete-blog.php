<?php
include 'includes/db_connect.php';

// Check if user is logged in (you may want to add proper authentication)
// This is a placeholder - implement proper authentication in production
$is_admin = true; // For demo purposes

if (!$is_admin) {
    header("Location: login.html");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: admin.php");
    exit();
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
        
        // Redirect back to admin page with success message
        header("Location: admin.php?deleted=success");
    } else {
        // Redirect back to admin page with error message
        header("Location: admin.php?deleted=error");
    }
} else {
    // Blog post not found
    header("Location: admin.php?deleted=notfound");
}

// Close connection
$conn->close();
?>
