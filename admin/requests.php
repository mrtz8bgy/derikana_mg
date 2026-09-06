<?php
// admin/requests.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== تایید درخواست =====
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    
    // دریافت اطلاعات درخواست
    $stmt = $pdo->prepare("SELECT * FROM article_requests WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if ($request) {
        // ایجاد مقاله از درخواست
        $title = $request['title'];
        $slug = slugify($title);
        $content = $request['description'];
        $category_id = $request['category_id'];
        $author_id = $_SESSION['user_id'];
        $status = 'published';
        
        // بررسی تکراری نبودن اسلاگ
        $check = $pdo->prepare("SELECT id FROM articles WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        try {
            // درج مقاله جدید
            $stmt = $pdo->prepare("INSERT INTO articles (title, slug, content, category_id, author_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $slug, $content, $category_id, $author_id, $status]);
            $article_id = $pdo->lastInsertId();
            
            // به‌روزرسانی وضعیت درخواست
            $stmt = $pdo->prepare("UPDATE article_requests SET status = 'approved', admin_note = CONCAT(admin_note, ' مقاله با ID #', ?, ' ایجاد شد.') WHERE id = ?");
            $stmt->execute([$article_id, $id]);
            
            $message = '✅ درخواست با موفقیت تایید و مقاله ایجاد شد! <a href="/article.php?slug=' . $slug . '" target="_blank">مشاهده مقاله</a>';
        } catch (PDOException $e) {
            $error = '❌ خطا در ایجاد مقاله: ' . $e->getMessage();
        }
    } else {
        $error = '❌ درخواست یافت نشد!';
    }
}

// ===== رد درخواست =====
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $pdo->prepare("UPDATE article_requests SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$id]);
    $message = '❌ درخواست با موفقیت رد شد!';
}

// ===== حذف درخواست =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM article_requests WHERE id = ?");
    $stmt->execute([$id]);
    $message = '🗑️ درخواست با موفقیت حذف شد!';
}

// ===== ارسال پاسخ به کاربر =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $id = (int)$_POST['request_id'];
    $reply = cleanInput($_POST['reply_text'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    
    if (empty($reply)) {
        $error = '❌ لطفاً متن پاسخ را وارد کنید!';
    } else {
        $stmt = $pdo->prepare("UPDATE article_requests SET admin_note = ?, status = ? WHERE id = ?");
        $stmt->execute([$reply, $status, $id]);
        $message = '✅ پاسخ با موفقیت ارسال شد!';
    }
}

// ===== دریافت لیست درخواست‌ها =====
$requests = $pdo->query("SELECT r.*, c.name as category_name 
                         FROM article_requests r 
                         LEFT JOIN categories c ON r.category_id = c.id 
                         ORDER BY r.created_at DESC")->fetchAll();

// ===== دریافت دسته‌بندی‌ها برای فرم =====
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// آمار
$total_requests = count($requests);
$pending_requests = array_filter($requests, function($r) { return $r['status'] == 'pending'; });
$approved_requests = array_filter($requests, function($r) { return $r['status'] == 'approved'; });
$rejected_requests = array_filter($requests, function($r) { return $r['status'] == 'rejected'; });
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت درخواست‌ها</title>
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
            <span class="brand-en">Derikana</span>
            <span class="badge">مدیریت درخواست‌ها</span>
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
        <?php $current_page = 'requests.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-file-alt"></i> مدیریت درخواست‌های مقاله</div>

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
                <div class="stat-icon"><i class="fas fa-file-lines" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo $total_requests; ?></div>
                <div class="stat-label">مجموع درخواست‌ها</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-hourglass-half" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($pending_requests); ?></div>
                <div class="stat-label">در انتظار تایید</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon"><i class="fas fa-circle-check" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($approved_requests); ?></div>
                <div class="stat-label">تایید شده</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-icon"><i class="fas fa-circle-xmark" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($rejected_requests); ?></div>
                <div class="stat-label">رد شده</div>
            </div>
        </div>

        <!-- لیست درخواست‌ها -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3><i class="fas fa-file-lines" aria-hidden="true"></i> لیست درخواست‌های مقاله</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo $total_requests; ?> درخواست</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>عنوان</th>
                        <th>دسته</th>
                        <th>کاربر</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $index => $r): 
                            $status_text = [
                                'pending' => 'در انتظار تایید',
                                'approved' => 'تایید شده',
                                'rejected' => 'رد شده'
                            ];
                            $status_class = $r['status'];
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td style="font-weight:500;"><?php echo $r['title']; ?></td>
                                <td><?php echo $r['category_name'] ?? 'بدون دسته'; ?></td>
                                <td><?php echo $r['user_name']; ?></td>
                                <td><?php echo getPersianDateTime(strtotime($r['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text[$r['status']] ?? $r['status']; ?>
                                    </span>
                                    <?php if ($r['admin_note']): ?>
                                        <div style="font-size:10px; color:var(--info); margin-top:2px;">
                                            <i class="fas fa-comment"></i> پاسخ ارسال شده
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" onclick="viewRequest(<?php echo $r['id']; ?>, '<?php echo addslashes($r['title']); ?>', '<?php echo addslashes($r['description']); ?>', '<?php echo $r['user_name']; ?>', '<?php echo $r['category_name'] ?? 'بدون دسته'; ?>', '<?php echo $r['status']; ?>', '<?php echo addslashes($r['admin_note'] ?? ''); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($r['status'] == 'pending'): ?>
                                            <button class="btn-approve" onclick="if(confirm('آیا از تایید این درخواست مطمئن هستید؟ مقاله به صورت خودکار ایجاد خواهد شد.')) window.location.href='requests.php?approve=<?php echo $r['id']; ?>'">
                                                <i class="fas fa-circle-check" aria-hidden="true"></i> تایید
                                            </button>
                                            <button class="btn-reject" onclick="if(confirm('آیا از رد این درخواست مطمئن هستید؟')) window.location.href='requests.php?reject=<?php echo $r['id']; ?>'">
                                                <i class="fas fa-circle-xmark" aria-hidden="true"></i> رد
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-reply" onclick="openReplyModal(<?php echo $r['id']; ?>, '<?php echo $r['status']; ?>')">
                                            <i class="fas fa-reply"></i> پاسخ
                                        </button>
                                        <button class="btn-delete" onclick="if(confirm('آیا از حذف این درخواست مطمئن هستید؟')) window.location.href='requests.php?delete=<?php echo $r['id']; ?>'">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:var(--text-light);">
                                <i class="fas fa-file-alt" style="font-size:48px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                                هیچ درخواستی وجود ندارد
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- راهنما -->
        <div style="background:var(--bg-card); border-radius:var(--radius); padding:20px; border:1px solid var(--border);">
            <h4 style="margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> راهنمای درخواست‌ها</h4>
            <ul style="color:var(--text-light); font-size:13px; line-height:2; padding-right:20px;">
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>درخواست جدید</strong>: کاربران می‌توانند موضوعات جدید را پیشنهاد دهند</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>تایید درخواست</strong>: با تایید، مقاله به صورت خودکار در سایت ایجاد می‌شود</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>رد درخواست</strong>: درخواست رد می‌شود و کاربر می‌تواند دوباره تلاش کند</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>پاسخ به کاربر</strong>: می‌توانید توضیحات خود را برای کاربر ارسال کنید</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>وضعیت‌ها</strong>: در انتظار تایید | تایید شده | رد شده</li>
            </ul>
        </div>
    </div>

    <!-- ===== MODAL: مشاهده جزئیات درخواست ===== -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            <h2 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-file-alt" style="color:var(--primary);"></i>
                جزئیات درخواست
            </h2>
            <div id="viewRequestContent"></div>
            <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn-submit" onclick="closeModal('viewModal')" style="padding:10px 28px; border:none; border-radius:30px; cursor:pointer; background:var(--primary-gradient); color:#fff; font-family:'Vazirmatn', sans-serif; font-size:14px; font-weight:600;">
                    بستن
                </button>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: پاسخ به درخواست ===== -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('replyModal')">&times;</button>
            <h2 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-reply" style="color:var(--primary);"></i>
                پاسخ به درخواست
            </h2>
            <form method="POST">
                <input type="hidden" name="request_id" id="replyRequestId" />
                <div class="form-group">
                    <label>وضعیت جدید</label>
                    <select name="status" id="replyStatus">
                        <option value="pending">⏳ در انتظار تایید</option>
                        <option value="approved">✅ تایید شده</option>
                        <option value="rejected">❌ رد شده</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>متن پاسخ *</label>
                    <textarea name="reply_text" id="replyText" placeholder="پاسخ خود را برای کاربر بنویسید..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="send_reply" class="btn-submit"><i class="fas fa-send"></i> ارسال پاسخ</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('replyModal')">لغو</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ===== مشاهده جزئیات درخواست =====
        function viewRequest(id, title, description, userName, category, status, adminNote) {
            const statusMap = {
                'pending': '<i class="fas fa-hourglass-half" aria-hidden="true"></i> در انتظار تایید',
                'approved': '<i class="fas fa-circle-check" aria-hidden="true"></i> تایید شده',
                'rejected': '<i class="fas fa-circle-xmark" aria-hidden="true"></i> رد شده'
            };
            const statusClass = {
                'pending': 'pending',
                'approved': 'approved',
                'rejected': 'rejected'
            };
            
            const content = `
                <div class="request-detail">
                    <p><span class="label"><i class="fas fa-thumbtack" aria-hidden="true"></i> عنوان:</span> ${title}</p>
                    <p><span class="label"><i class="fas fa-folder-open" aria-hidden="true"></i> دسته‌بندی:</span> ${category}</p>
                    <p><span class="label"><i class="fas fa-user" aria-hidden="true"></i> کاربر:</span> ${userName}</p>
                    <p><span class="label"><i class="fas fa-file-lines" aria-hidden="true"></i> وضعیت:</span> <span class="status-badge ${statusClass[status]}">${statusMap[status] || status}</span></p>
                </div>
                <div style="margin-top:12px;">
                    <p><strong><i class="fas fa-newspaper" aria-hidden="true"></i> توضیحات کاربر:</strong></p>
                    <div style="background:var(--bg); padding:12px 16px; border-radius:var(--radius-sm); margin-top:4px; line-height:1.8; font-size:14px;">
                        ${description}
                    </div>
                </div>
                ${adminNote ? `
                <div style="margin-top:12px;">
                    <p><strong><i class="fas fa-comments" aria-hidden="true"></i> پاسخ ادمین:</strong></p>
                    <div style="background:var(--bg); padding:12px 16px; border-radius:var(--radius-sm); margin-top:4px; line-height:1.8; font-size:14px; border-right:3px solid var(--primary);">
                        ${adminNote}
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('viewRequestContent').innerHTML = content;
            openModal('viewModal');
        }

        // ===== باز کردن مودال پاسخ =====
        function openReplyModal(id, status) {
            document.getElementById('replyRequestId').value = id;
            document.getElementById('replyStatus').value = status;
            document.getElementById('replyText').value = '';
            openModal('replyModal');
        }

        // ===== توابع مودال =====
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }

        // بستن مودال با کلیک روی overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        console.log('<i class="fas fa-file-lines" aria-hidden="true"></i> مدیریت درخواست‌های مقاله');
        console.log('<i class="fas fa-chart-pie" aria-hidden="true"></i> تعداد کل: <?php echo $total_requests; ?>');
        console.log('<i class="fas fa-hourglass-half" aria-hidden="true"></i> در انتظار: <?php echo count($pending_requests); ?>');
        console.log('<i class="fas fa-circle-check" aria-hidden="true"></i> تایید شده: <?php echo count($approved_requests); ?>');
        console.log('<i class="fas fa-circle-xmark" aria-hidden="true"></i> رد شده: <?php echo count($rejected_requests); ?>');
    </script>
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>