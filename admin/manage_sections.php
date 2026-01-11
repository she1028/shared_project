<?php
session_start();
include("../connect.php");

// Only admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}


if (isset($_POST['ajax_update']) && $_POST['ajax_update'] == 1) {
    $section_id = $_POST['section_id'];
    $title = $_POST['section_title'];
    $subtitle = $_POST['section_subtitle'];
    $content = $_POST['section_content'];

    $stmt = $conn->prepare("UPDATE sections SET section_title=?, section_subtitle=?, section_content=? WHERE section_id=?");
    $stmt->bind_param("ssss", $title, $subtitle, $content, $section_id);
    if ($stmt->execute()) {
        echo "Updated";
    } else {
        http_response_code(500);
        echo "Error updating";
    }
    $stmt->close();
    exit();
}

// ==========================
// Fetch all sections
// ==========================
$sections = $conn->query("
    SELECT * FROM sections 
    ORDER BY FIELD(section_id, 
        'home',
        'about',
        'packages',
        'food_menu',
        'rentals',
        'how_it_works',
        'why_choose_us',
        'testimonials',
        'contact',
        'footer'
    )
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Landing Page Sections</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css"> <!-- your external CSS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- for AJAX -->
</head>

<body>
    <div class="bg-blur"></div>
    <div class="container mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
        <div class="admin-wrapper">
            <div class="container">
                <h1 class="mb-4 text-center">Landing Page Sections</h1>
                <div class="row">
                    <?php while ($sec = $sections->fetch_assoc()): ?>
                        <div class="col-12 mb-4">
                            <div class="card-admin p-4" id="section-<?= $sec['section_id'] ?>">
                                <form class="section-form" data-id="<?= $sec['section_id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Section Name:</strong></label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($sec['section_name']) ?>" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="section_title" value="<?= htmlspecialchars($sec['section_title']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Subtitle / Link</label>
                                        <input type="text" class="form-control" name="section_subtitle" value="<?= htmlspecialchars($sec['section_subtitle']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Content</label>
                                        <textarea class="form-control" name="section_content" rows="5"><?= htmlspecialchars($sec['section_content']) ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary save-btn">Save Changes</button>
                                    <span class="text-success ms-2 save-msg" style="display:none;">Saved!</span>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                $(".section-form").on("submit", function(e) {
                    e.preventDefault(); // prevent default form submit

                    var form = $(this);
                    var sectionId = form.data("id");
                    var title = form.find("input[name='section_title']").val();
                    var subtitle = form.find("input[name='section_subtitle']").val();
                    var content = form.find("textarea[name='section_content']").val();

                    $.ajax({
                        url: "", // submit to same page
                        method: "POST",
                        data: {
                            ajax_update: 1,
                            section_id: sectionId,
                            section_title: title,
                            section_subtitle: subtitle,
                            section_content: content
                        },
                        success: function(response) {
                            form.find(".save-msg").fadeIn().delay(1500).fadeOut();
                        },
                        error: function() {
                            alert("Error saving changes!");
                        }
                    });
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>