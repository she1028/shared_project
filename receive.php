<?php
// Set the default timezone
date_default_timezone_set('Asia/Manila'); // Set your timezone

// Gmail IMAP configuration
$mailbox = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = 'kristineannmaglinao@gmail.com'; // use your Gmail address
$password = 'cxqqvmwrbrblurys';    // use your Gmail App Password

// Connect to mailbox
$inbox = imap_open($mailbox, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());

// Search for emails received today or later
$today = date("d-M-Y");
$emails = imap_search($inbox, 'SINCE "' . $today . '"');

$replies = [];
if ($emails) {
    rsort($emails);

    // Pagination setup
    $per_page = 2;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $start = ($page - 1) * $per_page;
    $total = count($emails);

    $emails = array_slice($emails, $start, $per_page);

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
        $subject = isset($overview->subject) ? $overview->subject : '(No Subject)';
        $from = $overview->from;
        $date = date("D, d M Y H:i:s O", strtotime($overview->date));
        $body = imap_fetchbody($inbox, $email_number, 1);

        // Clean up the "From" field to extract name and email
        if (preg_match('/(.*)<(.*)>/', $from, $matches)) {
            $from_name = trim($matches[1]);
            $from_email = trim($matches[2]);
        } else {
            $from_name = $from;
            $from_email = '';
        }

        $replies[] = [
            'subject' => $subject,
            'from_name' => $from_name,
            'from_email' => $from_email,
            'date' => $date,
            'reply' => $body
        ];
    }
}
imap_close($inbox);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recent Emails</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(to bottom, #fff6f6 0%, #8d314a 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 900px;
            width: 100%;
            margin: 48px auto;
            background: transparent;
            border-radius: 16px;
            box-sizing: border-box;
            padding: 0;
        }
        h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 32px;
            color: #222;
            letter-spacing: 1px;
        }
        .cards-row {
            display: flex;
            gap: 40px;
            justify-content: center;
        }
        .reply-card {
            background: rgba(255, 255, 255, 0.35); /* semi-transparent for glass effect */
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(140, 49, 74, 0.18); /* stronger shadow */
            padding: 28px 22px 22px 22px;
            width: 340px;
            min-height: 380px;
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
            backdrop-filter: blur(8px); /* glass blur effect */
            -webkit-backdrop-filter: blur(8px); /* Safari support */
            border: 1px solid rgba(255,255,255,0.25); /* subtle border */
        }
        .subject {
            font-weight: 600;
            color: #1a73e8;
            font-size: 20px;
            margin-bottom: 8px;
        }
        .from {
            font-weight: 500;
            color: #222;
            font-size: 16px;
            margin-bottom: 2px;
        }
        .from-email {
            color: #666;
            font-size: 14px;
        }
        .date {
            font-size: 13px;
            color: #888;
            margin-bottom: 12px;
        }
        .reply-text {
            font-size: 15px;
            color: #222;
            background: #fff;
            border-radius: 6px;
            padding: 10px;
            white-space: pre-wrap;
            flex: 1;
        }
        .back-arrow {
            position: absolute;
            top: 32px;
            left: 32px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            z-index: 10;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .back-arrow svg {
            display: block;
            width: 28px;
            height: 28px;
            transition: stroke 0.2s;
        }
        .back-arrow:hover svg path {
            stroke: #b03a5b;
        }
        .pagination {
            text-align: center;
            margin-top: 32px;
        }
        .pagination a {
            display: inline-block;
            margin: 0 6px;
            padding: 6px 12px;
            background: #8d314a;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 15px;
            transition: background 0.2s;
        }
        .pagination a.active,
        .pagination a:hover {
            background: #b03a5b;
        }
        @media (max-width: 800px) {
            .cards-row {
                flex-direction: column;
                gap: 24px;
                align-items: center;
            }
            .reply-card {
                width: 95vw;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-arrow" title="Back">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
            <path d="M18 6L10 14L18 22" stroke="#8d314a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </a>
    <div class="container">
        <h2>RECENT EMAILS</h2>
        <div class="cards-row">
            <?php if (count($replies) > 0): ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="reply-card">
                        <div class="subject">Re: <?php echo htmlspecialchars($reply['subject']); ?></div>
                        <div class="from">
                            <?php echo htmlspecialchars($reply['from_name']); ?>
                            <?php if ($reply['from_email']): ?>
                                <span class="from-email">&lt;<?php echo htmlspecialchars($reply['from_email']); ?>&gt;</span>
                            <?php endif; ?>
                        </div>
                        <div class="date"><?php echo htmlspecialchars($reply['date']); ?></div>
                        <div class="reply-text"><?php echo nl2br(htmlspecialchars($reply['reply'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="reply-card" style="text-align:center;justify-content:center;">
                    <span>No replies found for today.</span>
                </div>
            <?php endif; ?>
        </div>
        <!-- Pagination -->
        <div class="pagination">
            <?php
            $total_pages = isset($total) ? ceil($total / $per_page) : 1;
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo "<a class='$active' href='receive.php?page=$i'>$i</a>";
            }
            ?>
        </div>
    </div>
</body>
</html>