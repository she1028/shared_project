<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
include("../connect.php");

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../adminsignin.php");
    exit();
}

$messages = [];
$loadError = null;

try {
    // Ensure the table exists (same schema used when logging from send-mail.php)
    $createSql = "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createSql);

    $result = $conn->query("SELECT id, user_id, name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    $loadError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        .message-cell { text-align: left; }
        .message-text { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
<div class="bg-blur"></div>
<div class="container admin-wrapper d-flex justify-content-start align-items-start py-5">
    <div class="row w-100 mb-4">
        <div class="col d-flex justify-content-between align-items-center">
            <h2 class="text-white mb-0">Contact Messages</h2>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="contact_messages.php" class="btn btn-primary">Refresh</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="row w-100">
        <div class="col-12">
            <div class="card card-admin shadow w-100">
                <?php if ($loadError): ?>
                    <div class="alert alert-danger" role="alert">
                        Unable to load messages: <?php echo htmlspecialchars($loadError, ENT_QUOTES); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($messages)): ?>
                    <p class="mb-0">No contact messages yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">User ID</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col" class="message-cell">Message</th>
                                    <th scope="col">Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td><?php echo (int)$msg['id']; ?></td>
                                        <td><?php echo htmlspecialchars($msg['name'], ENT_QUOTES); ?></td>
                                        <td><?php echo htmlspecialchars($msg['user_id'], ENT_QUOTES); ?></td>
                                        <td>
                                            <a class="text-white" href="mailto:<?php echo htmlspecialchars($msg['email'], ENT_QUOTES); ?>">
                                                <?php echo htmlspecialchars($msg['email'], ENT_QUOTES); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($msg['subject'], ENT_QUOTES); ?></td>
                                        <td class="message-cell"><div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'], ENT_QUOTES)); ?></div></td>
                                        <td><?php echo htmlspecialchars($msg['created_at'], ENT_QUOTES); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
