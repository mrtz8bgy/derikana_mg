<?php
// admin/comments.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== تایید نظر =====
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
    $stmt->execute([$id]);
    $message = '✅ نظر با موفقیت تایید شد!';
}

// ===== رد نظر =====
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$id]);
    $message = '❌ نظر با موفقیت رد شد!';
}

// ===== حذف نظر =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $message = '🗑️ نظر با موفقیت حذف شد!';
}

// ===== پاسخ به نظر =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $id = (int)$_POST['comment_id'];
    $reply = cleanInput($_POST['reply_text'] ?? '');
    
    if (empty($reply)) {
        $error = '❌ لطفاً متن پاسخ را وارد کنید!';
    } else {
        // ذخیره پاسخ در دیتابیس (در صورت وجود ستون reply)
        // اگر ستون reply وجود ندارد، یک جدول جداگانه برای پاسخ‌ها در نظر بگیرید
        // برای سادگی، پاسخ را در admin_note یا یک فیلد دیگر ذخیره می‌کنیم
        try {
            $stmt = $pdo->prepare("UPDATE comments SET admin_note = ? WHERE id = ?");
            $stmt->execute([$reply, $id]);
            $message = '✅ پاسخ با موفقیت ارسال شد!';
        } catch (PDOException $e) {
            // اگر ستون admin_note وجود ندارد، آن را اضافه کنید
            $pdo->exec("ALTER TABLE comments ADD COLUMN admin_note TEXT DEFAULT NULL");
            $stmt = $pdo->prepare("UPDATE comments SET admin_note = ? WHERE id = ?");
            $stmt->execute([$reply, $id]);
            $message = '✅ پاسخ با موفقیت ارسال شد! ستون admin_note به دیتابیس اضافه شد.';
        }
    }
}

// ===== دریافت لیست نظرات =====
$comments = $pdo->query("SELECT c.*, 
                         u.name as user_name, 
                         a.title as article_title, 
                         a.slug as article_slug 
                         FROM comments c 
                         LEFT JOIN users u ON c.user_id = u.id 
                         LEFT JOIN articles a ON c.article_id = a.id 
                         ORDER BY c.created_at DESC")->fetchAll();

// ===== آمار =====
$total_comments = count($comments);
$pending_comments = array_filter($comments, function($c) { return $c['status'] == 'pending'; });
$approved_comments = array_filter($comments, function($c) { return $c['status'] == 'approved'; });
$rejected_comments = array_filter($comments, function($c) { return $c['status'] == 'rejected'; });

// ===== دریافت نظر برای ویرایش/پاسخ =====
$reply_comment = null;
if (isset($_GET['reply'])) {
    $stmt = $pdo->prepare("SELECT c.*, u.name as user_name, a.title as article_title 
                           FROM comments c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           LEFT JOIN articles a ON c.article_id = a.id 
                           WHERE c.id = ?");
    $stmt->execute([$_GET['reply']]);
    $reply_comment = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت نظرات</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <script>(function(){try{var t=localStorage.getItem("theme");document.documentElement.setAttribute("data-theme",t||"dark");}catch(e){}})();</script>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <button class="sidebar-toggle" onclick="toggleAdminSidebar()" aria-label="منو"><i class="fas fa-bars"></i></button>
        <div class="logo">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="gmark" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse"><stop stop-color="#EAD6A6"/><stop offset=".45" stop-color="#D6B36A"/><stop offset="1" stop-color="#A9853E"/></linearGradient></defs><path d="M14 8h20l8 10-18 22L6 18 14 8z" fill="url(#gmark)"/><path d="M6 18h36M14 8l4 10 6-10 6 10 4-10M18 18l6 22 6-22" stroke="#0B0D12" stroke-opacity=".38" stroke-width="1.6"/></svg></span>
            <span class="brand-en">دریکانا</span>
            <span class="badge">مدیریت نظرات</span>
        </div>
        <button class="theme-btn" onclick="toggleAdminTheme()" aria-label="تغییر پوسته"><i class="fas fa-sun i-sun"></i><i class="fas fa-moon i-moon"></i></button>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $_SESSION['user_name']; ?></span>
            <div class="avatar"><?php echo mb_substr($_SESSION['user_name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='index.php'">داشبورد</button>
            <button class="back-btn danger" onclick="window.location.href='logout.php'">خروج</button>
        </div>
    </header>

    <!-- Content -->
    <!-- ===== ADMIN LAYOUT ===== -->
    <div class="admin-layout">
        <?php $current_page = 'comments.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-comments"></i> مدیریت نظرات</div>

        <?php if ($message): ?>
            <?php $msg_type = strpos($message, '✅') !== false ? 'success' : (strpos($message, '❌') !== false ? 'error' : 'info'); $msg_icon = ['success' => 'fa-circle-check', 'error' => 'fa-circle-xmark', 'info' => 'fa-circle-info'][$msg_type]; ?>
            <div class="message <?php echo $msg_type; ?>"><i class="fas <?php echo $msg_icon; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-circle-xmark"></i> <?php echo preg_replace('/[\x{2705}\x{274C}\x{26A0}\x{FE0F}\x{23F3}\x{26D4}]\s*/u', '', $error); ?></div>
        <?php endif; ?>

        <!-- آمار -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-comments" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo $total_comments; ?></div>
                <div class="stat-label">مجموع نظرات</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-hourglass-half" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($pending_comments); ?></div>
                <div class="stat-label">در انتظار تایید</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon"><i class="fas fa-circle-check" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($approved_comments); ?></div>
                <div class="stat-label">تایید شده</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-icon"><i class="fas fa-circle-xmark" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($rejected_comments); ?></div>
                <div class="stat-label">رد شده</div>
            </div>
        </div>

        <!-- فرم پاسخ به نظر -->
        <?php if ($reply_comment): ?>
        <div class="admin-form" id="replyForm">
            <h3 style="margin-bottom:16px;">
                <i class="fas fa-reply" style="color:var(--primary);"></i>
                پاسخ به نظر: <?php echo $reply_comment['user_name'] ?? 'کاربر مهمان'; ?>
            </h3>
            <div style="background:var(--bg); padding:12px 16px; border-radius:var(--radius-sm); margin-bottom:16px;">
                <p><strong><i class="fas fa-newspaper" aria-hidden="true"></i> نظر:</strong></p>
                <p style="margin-top:4px; font-size:14px; line-height:1.8;"><?php echo nl2br($reply_comment['text']); ?></p>
                <p style="margin-top:4px; font-size:12px; color:var(--text-light);">
                    <i class="fas fa-file-alt"></i> مقاله: <?php echo $reply_comment['article_title'] ?? 'حذف شده'; ?>
                </p>
            </div>
            <form method="POST">
                <input type="hidden" name="comment_id" value="<?php echo $reply_comment['id']; ?>" />
                <div class="form-group">
                    <label>متن پاسخ *</label>
                    <textarea name="reply_text" required placeholder="پاسخ خود را برای کاربر بنویسید..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="send_reply" class="btn-submit"><i class="fas fa-send"></i> ارسال پاسخ</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='comments.php'">لغو</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- لیست نظرات -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3><i class="fas fa-file-lines" aria-hidden="true"></i> لیست نظرات</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo $total_comments; ?> نظر</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>مقاله</th>
                        <th>متن نظر</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $index => $c): 
                            $status_text = [
                                'pending' => 'در انتظار تایید',
                                'approved' => 'تایید شده',
                                'rejected' => 'رد شده'
                            ];
                            $status_class = $c['status'];
                            $user_name = $c['user_name'] ?? $c['name'] ?? 'کاربر مهمان';
                            $comment_text = strlen($c['text']) > 60 ? mb_substr($c['text'], 0, 60) . '...' : $c['text'];
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <div style="width:30px; height:30px; border-radius:50%; background:var(--primary-gradient); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;">
                                            <?php echo mb_substr($user_name, 0, 1); ?>
                                        </div>
                                        <span style="font-weight:500;"><?php echo $user_name; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($c['article_title']): ?>
                                        <a href="/article.php?slug=<?php echo $c['article_slug']; ?>" target="_blank" style="color:var(--primary);">
                                            <?php echo strlen($c['article_title']) > 20 ? mb_substr($c['article_title'], 0, 20) . '...' : $c['article_title']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--text-light);">مقاله حذف شده</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        <?php echo $comment_text; ?>
                                    </div>
                                    <?php if (!empty($c['admin_note'])): ?>
                                        <div style="font-size:10px; color:var(--info); margin-top:2px;">
                                            <i class="fas fa-reply"></i> پاسخ ارسال شده
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getPersianDateTime(strtotime($c['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text[$c['status']] ?? $c['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($c['status'] == 'pending'): ?>
                                            <button class="btn-approve" onclick="window.location.href='comments.php?approve=<?php echo $c['id']; ?>'">
                                                <i class="fas fa-circle-check" aria-hidden="true"></i> تایید
                                            </button>
                                            <button class="btn-reject" onclick="window.location.href='comments.php?reject=<?php echo $c['id']; ?>'">
                                                <i class="fas fa-circle-xmark" aria-hidden="true"></i> رد
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-reply" onclick="window.location.href='comments.php?reply=<?php echo $c['id']; ?>'">
                                            <i class="fas fa-reply"></i> پاسخ
                                        </button>
                                        <button class="btn-delete" onclick="if(confirm('آیا از حذف این نظر مطمئن هستید؟')) window.location.href='comments.php?delete=<?php echo $c['id']; ?>'">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:var(--text-light);">
                                <i class="fas fa-comment-slash" style="font-size:48px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                                هیچ نظری وجود ندارد
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- راهنما -->
        <div style="background:var(--bg-card); border-radius:var(--radius); padding:20px; border:1px solid var(--border);">
            <h4 style="margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> راهنمای نظرات</h4>
            <ul style="color:var(--text-light); font-size:13px; line-height:2; padding-right:20px;">
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>وضعیت‌ها</strong>: 
                    <span class="status-badge pending" style="font-size:11px;">در انتظار تایید</span> | 
                    <span class="status-badge approved" style="font-size:11px;">تایید شده</span> | 
                    <span class="status-badge rejected" style="font-size:11px;">رد شده</span>
                </li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>تایید نظر</strong>: با تایید، نظر در صفحه مقاله نمایش داده می‌شود</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>رد نظر</strong>: نظر رد می‌شود و در سایت نمایش داده نمی‌شود</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>پاسخ به نظر</strong>: می‌توانید پاسخی برای کاربر ارسال کنید</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>حذف نظر</strong>: نظر به طور کامل از دیتابیس حذف می‌شود</li>
            </ul>
        </div>
    </div>

    <script>
        console.log('<i class="fas fa-comments" aria-hidden="true"></i> مدیریت نظرات');
        console.log('<i class="fas fa-chart-pie" aria-hidden="true"></i> تعداد کل: <?php echo $total_comments; ?>');
        console.log('<i class="fas fa-hourglass-half" aria-hidden="true"></i> در انتظار: <?php echo count($pending_comments); ?>');
        console.log('<i class="fas fa-circle-check" aria-hidden="true"></i> تایید شده: <?php echo count($approved_comments); ?>');
        console.log('<i class="fas fa-circle-xmark" aria-hidden="true"></i> رد شده: <?php echo count($rejected_comments); ?>');
    </script>
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>