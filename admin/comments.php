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
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت نظرات</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* ===== استایل‌های ادمین ===== */
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-gradient: linear-gradient(135deg, #6C63FF 0%, #3F3D9E 100%);
            --bg: #f0f2f8;
            --bg-card: #ffffff;
            --text: #1a1a2e;
            --text-light: #6c6c8a;
            --border: #e2e6f0;
            --shadow: rgba(108, 99, 255, 0.12);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --radius: 20px;
            --radius-sm: 12px;
            --transition: 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            --danger: #FF5252;
            --success: #00E676;
            --gold: #F9A825;
            --glass-bg: rgba(255,255,255,0.08);
            --orange: #FF9800;
            --info: #448AFF;
        }
        [data-theme="dark"] {
            --bg: #0a0a1a;
            --bg-card: #16162e;
            --text: #e8e8f0;
            --text-light: #9090b0;
            --border: #2a2a4a;
            --glass-bg: rgba(255,255,255,0.05);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: var(--bg); color: var(--text); transition: all var(--transition); min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* Admin Header */
        .admin-header {
            background: var(--bg-card);
            border-bottom: 2px solid var(--border);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-header .logo { font-size: 24px; font-weight: 900; display: flex; align-items: center; gap: 8px; }
        .admin-header .logo .brand-en { font-family: 'Playfair Display', serif; font-weight: 900; font-style: italic; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .admin-header .logo .badge { font-size: 12px; background: var(--primary-gradient); color: #fff; padding: 2px 12px; border-radius: 20px; -webkit-text-fill-color: #fff; }
        .admin-header .user-info { display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .admin-header .user-info .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-gradient); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; }
        .admin-header .back-btn { background: var(--glass-bg); border: 1px solid var(--border); padding: 6px 16px; border-radius: 30px; cursor: pointer; color: var(--text); transition: all var(--transition); font-family: 'Vazirmatn', sans-serif; font-size: 13px; }
        .admin-header .back-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        .admin-content { max-width: 1400px; margin: 0 auto; padding: 24px; }
        .admin-content .page-title { font-size: 22px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .admin-content .page-title i { color: var(--primary); }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all var(--transition);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 6px; }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: var(--text); }
        .stat-card .stat-label { font-size: 13px; color: var(--text-light); }
        .stat-card.total { border-right: 4px solid var(--primary); }
        .stat-card.pending { border-right: 4px solid var(--orange); }
        .stat-card.approved { border-right: 4px solid var(--success); }
        .stat-card.rejected { border-right: 4px solid var(--danger); }

        /* Table */
        .admin-table-container {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .admin-table-container .table-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .admin-table th { text-align: right; padding: 12px 16px; background: var(--bg); color: var(--text-light); font-weight: 600; border-bottom: 2px solid var(--border); font-size: 12px; }
        .admin-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
        .admin-table tr:hover td { background: var(--glass-bg); }

        .status-badge {
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.pending { background: #FFF3E0; color: #E65100; }
        .status-badge.approved { background: #E8F5E9; color: #2E7D32; }
        .status-badge.rejected { background: #FFEBEE; color: #C62828; }
        [data-theme="dark"] .status-badge.pending { background: #E65100; color: #FFE0B2; }
        [data-theme="dark"] .status-badge.approved { background: #1B5E20; color: #A5D6A7; }
        [data-theme="dark"] .status-badge.rejected { background: #B71C1C; color: #EF9A9A; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btns button {
            border: none;
            padding: 4px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 11px;
            transition: all var(--transition);
            font-weight: 500;
        }
        .btn-approve { background: #E8F5E9; color: #2E7D32; }
        .btn-approve:hover { background: #2E7D32; color: #fff; }
        .btn-reject { background: #FFEBEE; color: #C62828; }
        .btn-reject:hover { background: #C62828; color: #fff; }
        .btn-delete { background: #FFEBEE; color: #C62828; }
        .btn-delete:hover { background: #C62828; color: #fff; }
        .btn-reply { background: var(--info); color: #fff; }
        .btn-reply:hover { background: #0D47A1; color: #fff; }
        .btn-view { background: var(--glass-bg); color: var(--primary); border: 1px solid var(--primary) !important; }
        .btn-view:hover { background: var(--primary); color: #fff; }

        .message { padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 16px; }
        .message.success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .message.error { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }
        .message.info { background: #E3F2FD; color: #0D47A1; border: 1px solid #90CAF9; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            z-index: 2000;
            padding: 20px;
            overflow-y: auto;
        }
        .modal-overlay.active { display: block; }
        .modal-box {
            max-width: 700px;
            margin: 40px auto;
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow-lg);
            position: relative;
            border: 1px solid var(--border);
            animation: modal-slide 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modal-slide {
            from { transform: translateY(-30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .modal-close {
            position: absolute;
            top: 14px;
            left: 18px;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            transition: all var(--transition);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover {
            color: var(--primary);
            transform: rotate(90deg) scale(1.1);
            background: var(--glass-bg);
        }
        .modal-box .form-group { margin-bottom: 14px; }
        .modal-box .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 4px;
            color: var(--text);
        }
        .modal-box .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text);
            font-family: 'Vazirmatn', sans-serif;
            font-size: 13px;
            transition: all var(--transition);
            min-height: 120px;
            resize: vertical;
        }
        .modal-box .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .modal-box .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .modal-box .form-actions button {
            padding: 10px 28px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: all var(--transition);
        }
        .modal-box .form-actions .btn-submit { background: var(--primary-gradient); color: #fff; }
        .modal-box .form-actions .btn-submit:hover { transform: scale(1.03); }
        .modal-box .form-actions .btn-cancel { background: var(--glass-bg); color: var(--text); border: 1px solid var(--border); }
        .modal-box .form-actions .btn-cancel:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        .comment-detail {
            background: var(--bg);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 12px;
        }
        .comment-detail p { margin: 4px 0; font-size: 14px; }
        .comment-detail .label { color: var(--text-light); font-weight: 600; }

        @media (max-width: 768px) {
            .admin-table { font-size: 12px; }
            .admin-table th, .admin-table td { padding: 8px 10px; }
            .admin-header { padding: 10px 16px; }
            .admin-header .logo { font-size: 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .modal-box { margin: 20px; padding: 20px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .admin-table th, .admin-table td { display: block; width: 100%; }
            .admin-table thead { display: none; }
            .admin-table tr { display: block; border-bottom: 2px solid var(--border); padding: 8px 0; }
            .admin-table td { display: flex; justify-content: space-between; padding: 4px 8px; border: none; }
            .admin-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-light); }
            .action-btns { justify-content: flex-end; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo">
            <span class="brand-en">Anosha</span>
            <span class="badge">مدیریت نظرات</span>
        </div>
        <div class="user-info">
            <span style="font-weight:500;"><?php echo $_SESSION['user_name']; ?></span>
            <div class="avatar"><?php echo mb_substr($_SESSION['user_name'], 0, 1); ?></div>
            <button class="back-btn" onclick="window.location.href='index.php'">داشبورد</button>
            <button class="back-btn" onclick="window.location.href='logout.php'" style="background:var(--danger); color:#fff; border-color:var(--danger);">خروج</button>
        </div>
    </header>

    <!-- Content -->
    <div class="admin-content">
        <div class="page-title"><i class="fas fa-comments"></i> مدیریت نظرات</div>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : (strpos($message, '❌') !== false ? 'error' : 'info'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- آمار -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">💬</div>
                <div class="stat-number"><?php echo $total_comments; ?></div>
                <div class="stat-label">مجموع نظرات</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?php echo count($pending_comments); ?></div>
                <div class="stat-label">در انتظار تایید</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo count($approved_comments); ?></div>
                <div class="stat-label">تایید شده</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-icon">❌</div>
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
                <p><strong>📝 نظر:</strong></p>
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
                <h3>📋 لیست نظرات</h3>
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
                                'pending' => '⏳ در انتظار تایید',
                                'approved' => '✅ تایید شده',
                                'rejected' => '❌ رد شده'
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
                                    <?php if ($c['admin_note']): ?>
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
                                                ✅ تایید
                                            </button>
                                            <button class="btn-reject" onclick="window.location.href='comments.php?reject=<?php echo $c['id']; ?>'">
                                                ❌ رد
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
                <li>📌 <strong>وضعیت‌ها</strong>: 
                    <span class="status-badge pending" style="font-size:11px;">در انتظار تایید</span> | 
                    <span class="status-badge approved" style="font-size:11px;">تایید شده</span> | 
                    <span class="status-badge rejected" style="font-size:11px;">رد شده</span>
                </li>
                <li>📌 <strong>تایید نظر</strong>: با تایید، نظر در صفحه مقاله نمایش داده می‌شود</li>
                <li>📌 <strong>رد نظر</strong>: نظر رد می‌شود و در سایت نمایش داده نمی‌شود</li>
                <li>📌 <strong>پاسخ به نظر</strong>: می‌توانید پاسخی برای کاربر ارسال کنید</li>
                <li>📌 <strong>حذف نظر</strong>: نظر به طور کامل از دیتابیس حذف می‌شود</li>
            </ul>
        </div>
    </div>

    <script>
        console.log('💬 مدیریت نظرات');
        console.log('📊 تعداد کل: <?php echo $total_comments; ?>');
        console.log('⏳ در انتظار: <?php echo count($pending_comments); ?>');
        console.log('✅ تایید شده: <?php echo count($approved_comments); ?>');
        console.log('❌ رد شده: <?php echo count($rejected_comments); ?>');
    </script>
</body>
</html>