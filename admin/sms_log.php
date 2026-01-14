<?php
session_start();
require_once "../connect.php";

// Handle Delete action
if (isset($_POST['delete_sms_id'])) {
    $deleteId = $conn->real_escape_string($_POST['delete_sms_id']);
    $conn->query("DELETE FROM sms_logs WHERE sms_id='$deleteId'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="bg-blur"></div>

    <div class="container admin-wrapper my-4">
        <div class="row">
            <div class="col-12">
                <div class="card-admin">
                    <h2>SMS Logs</h2>
                    <a href="dashboard.php" class="btn btn-primary mb-3">Back to Dashboard</a>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>SMS ID</th>
                                    <th>Phone</th>
                                    <th>Booking Ref</th>
                                    <th>Direction</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM sms_logs ORDER BY created_at DESC");
                                while ($row = $res->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$row['sms_id']}</td>
                                        <td>{$row['phone']}</td>
                                        <td>{$row['booking_ref']}</td>
                                        <td>{$row['sms_direction']}</td>
                                        <td>{$row['sms_message']}</td>
                                        <td>{$row['status']}</td>
                                        <td>{$row['created_at']}</td>
                                        <td>
                                            <form method='POST' class='d-inline'>
                                                <input type='hidden' name='delete_sms_id' value='{$row['sms_id']}'>
                                                <button type='submit' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this SMS log?')\">Delete</button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div> <!-- table-responsive -->
                </div> <!-- card-admin -->
            </div> <!-- col-12 -->
        </div> <!-- row -->
    </div> <!-- container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
