<?php
// admin/users.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// ===== تغییر نقش کاربر =====
if (isset($_GET['role'])) {
    $id = (int)$_GET['role'];
    $new_role = $_GET['set'] ?? 'user';
    
    // جلوگیری از تغییر نقش خودش
    if ($id == $_SESSION['user_id']) {
        $error = '❌ نمی‌توانید نقش خودتان را تغییر دهید!';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $id]);
        $message = '✅ نقش کاربر با موفقیت تغییر کرد!';
    }
}

// ===== فعال/غیرفعال کردن کاربر =====
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    
    // جلوگیری از غیرفعال کردن خودش
    if ($id == $_SESSION['user_id']) {
        $error = '❌ نمی‌توانید خودتان را غیرفعال کنید!';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$id]);
        $message = '✅ وضعیت کاربر با موفقیت تغییر کرد!';
    }
}

// ===== حذف کاربر =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // جلوگیری از حذف خودش
    if ($id == $_SESSION['user_id']) {
        $error = '❌ نمی‌توانید خودتان را حذف کنید!';
    } else {
        // بررسی اینکه کاربر مقاله دارد یا خیر
        $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ?");
        $check->execute([$id]);
        $article_count = $check->fetchColumn();
        
        if ($article_count > 0) {
            $error = "❌ این کاربر {$article_count} مقاله دارد! ابتدا مقالات را به کاربر دیگری منتقل کنید.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = '🗑️ کاربر با موفقیت حذف شد!';
        }
    }
}

// ===== ویرایش کاربر =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = '❌ لطفاً نام و ایمیل را وارد کنید!';
    } elseif (!validateEmail($email)) {
        $error = '❌ ایمیل وارد شده معتبر نیست!';
    } else {
        if (!empty($password)) {
            // با تغییر رمز عبور
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $hashed, $id]);
        } else {
            // بدون تغییر رمز عبور
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $id]);
        }
        $message = '✅ اطلاعات کاربر با موفقیت به‌روزرسانی شد!';
    }
}

// ===== دریافت لیست کاربران =====
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();

// ===== دریافت کاربر برای ویرایش =====
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}

// ===== آمار =====
$total_users = count($users);
$admin_count = array_filter($users, function($u) { return $u['role'] == 'admin'; });
$active_count = array_filter($users, function($u) { return $u['status'] == 'active'; });
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت کاربران</title>
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
            <span class="badge">مدیریت کاربران</span>
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
        <?php $current_page = 'users.php'; require __DIR__ . '/partials/sidebar.php'; ?>

        <!-- CONTENT -->
        <div class="admin-content">
        <div class="page-title"><i class="fas fa-users"></i> مدیریت کاربران</div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- آمار -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">مجموع کاربران</div>
            </div>
            <div class="stat-card admins">
                <div class="stat-icon"><i class="fas fa-shield-halved" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($admin_count); ?></div>
                <div class="stat-label">مدیران</div>
            </div>
            <div class="stat-card active">
                <div class="stat-icon"><i class="fas fa-circle-check" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo count($active_count); ?></div>
                <div class="stat-label">کاربران فعال</div>
            </div>
            <div class="stat-card inactive">
                <div class="stat-icon"><i class="fas fa-ban" aria-hidden="true"></i></div>
                <div class="stat-number"><?php echo $total_users - count($active_count); ?></div>
                <div class="stat-label">کاربران غیرفعال</div>
            </div>
        </div>

        <!-- فرم ویرایش کاربر -->
        <?php if ($edit_user): ?>
        <div class="admin-form" id="userForm">
            <h3 style="margin-bottom:16px;">
                <i class="fas fa-edit" style="color:var(--primary);"></i>
                ویرایش کاربر: <?php echo $edit_user['name']; ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>" />
                
                <div class="form-row">
                    <div class="form-group">
                        <label>نام کامل</label>
                        <input type="text" name="name" value="<?php echo $edit_user['name']; ?>" required />
                    </div>
                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="email" value="<?php echo $edit_user['email']; ?>" required />
                    </div>
                </div>
                
                <div class="form-row three">
                    <div class="form-group">
                        <label>نقش</label>
                        <select name="role">
                            <option value="user" <?php echo $edit_user['role'] == 'user' ? 'selected' : ''; ?>>👤 کاربر</option>
                            <option value="editor" <?php echo $edit_user['role'] == 'editor' ? 'selected' : ''; ?>>✍️ نویسنده</option>
                            <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>🛡️ مدیر</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>وضعیت</label>
                        <select name="status">
                            <option value="active" <?php echo $edit_user['status'] == 'active' ? 'selected' : ''; ?>>✅ فعال</option>
                            <option value="inactive" <?php echo $edit_user['status'] == 'inactive' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>رمز عبور جدید (اختیاری)</label>
                        <input type="password" name="password" placeholder="برای تغییر وارد کنید..." />
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_user" class="btn-submit"><i class="fas fa-save"></i> ذخیره تغییرات</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='users.php'">لغو</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- لیست کاربران -->
        <div class="admin-table-container">
            <div class="table-header">
                <h3><i class="fas fa-users" aria-hidden="true"></i> لیست کاربران</h3>
                <span style="font-size:13px; color:var(--text-light);"><?php echo $total_users; ?> کاربر</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>نام کاربری</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $index => $u): 
                            $is_self = ($u['id'] == $_SESSION['user_id']);
                            $role_badge = [
                                'admin' => 'مدیر',
                                'editor' => 'نویسنده',
                                'user' => 'کاربر'
                            ];
                            $role_class = $u['role'];
                            
                            // بررسی وجود ستون registered_at یا registered
                            $register_date = $u['registered_at'] ?? $u['registered'] ?? null;
                            $date_display = $register_date ? getPersianDate(strtotime($register_date)) : '---';
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="avatar-small"><?php echo mb_substr($u['name'], 0, 1); ?></div>
                                        <span style="font-weight:500;"><?php echo $u['name']; ?></span>
                                        <?php if ($is_self): ?>
                                            <span style="font-size:10px; background:var(--primary); color:#fff; padding:0 8px; border-radius:10px;">شما</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $u['username']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $role_class; ?>">
                                        <?php echo $role_badge[$u['role']] ?? $u['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $u['status']; ?>">
                                        <?php echo $u['status'] == 'active' ? '<i class="fas fa-circle-check"></i> فعال' : '<i class="fas fa-ban"></i> غیرفعال'; ?>
                                    </span>
                                </td>
                                <td><?php echo $date_display; ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick="window.location.href='users.php?edit=<?php echo $u['id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if (!$is_self): ?>
                                            <!-- تغییر نقش -->
                                            <button class="btn-role" onclick="changeRole(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')">
                                                <i class="fas fa-user-tag"></i>
                                            </button>
                                            
                                            <!-- تغییر وضعیت -->
                                            <button class="btn-toggle <?php echo $u['status'] == 'active' ? 'active' : ''; ?>" onclick="window.location.href='users.php?toggle=<?php echo $u['id']; ?>'">
                                                <?php echo $u['status'] == 'active' ? 'غیرفعال' : 'فعال'; ?>
                                            </button>
                                            
                                            <!-- حذف -->
                                            <button class="btn-delete" onclick="if(confirm('آیا از حذف کاربر "<?php echo $u['name']; ?>" مطمئن هستید؟')) window.location.href='users.php?delete=<?php echo $u['id']; ?>'">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-light);">(خودتان)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:var(--text-light);">
                                <i class="fas fa-users" style="font-size:48px; display:block; margin-bottom:10px; opacity:0.3;"></i>
                                هیچ کاربری وجود ندارد
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- راهنما -->
        <div style="background:var(--bg-card); border-radius:var(--radius); padding:20px; border:1px solid var(--border);">
            <h4 style="margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> راهنمای کاربران</h4>
            <ul style="color:var(--text-light); font-size:13px; line-height:2; padding-right:20px;">
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>نقش‌ها</strong>: 
                    <span class="status-badge admin" style="font-size:11px;">مدیر</span> دسترسی کامل | 
                    <span class="status-badge editor" style="font-size:11px;">نویسنده</span> می‌تواند مقاله بنویسد | 
                    <span class="status-badge user" style="font-size:11px;">کاربر</span> فقط می‌تواند نظر دهد
                </li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>وضعیت</strong>: کاربران فعال می‌توانند وارد سایت شوند</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>تغییر نقش</strong>: با کلیک روی دکمه <i class="fas fa-user-tag"></i> می‌توانید نقش کاربر را تغییر دهید</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>امنیت</strong>: نمی‌توانید خودتان را حذف یا غیرفعال کنید</li>
                <li><i class="fas fa-thumbtack" aria-hidden="true"></i> <strong>تعداد کاربران</strong>: <?php echo $total_users; ?> کاربر در سیستم ثبت شده است</li>
            </ul>
        </div>
    </div>

    <!-- ===== MODAL: تغییر نقش ===== -->
    <div class="modal-overlay" id="roleModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('roleModal')">&times;</button>
            <h2 style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-user-tag" style="color:var(--primary);"></i>
                تغییر نقش کاربر
            </h2>
            <p style="margin-bottom:12px; color:var(--text-light);">نقش جدید را برای کاربر انتخاب کنید:</p>
            <div id="roleOptions" style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                <button onclick="setRole('user')" class="role-option" style="padding:10px 16px; border:2px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; background:var(--bg); text-align:right; font-family:'Vazirmatn', sans-serif; font-size:14px; transition:all var(--transition);">
                    <i class="fas fa-user" aria-hidden="true"></i> کاربر عادی
                </button>
                <button onclick="setRole('editor')" class="role-option" style="padding:10px 16px; border:2px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; background:var(--bg); text-align:right; font-family:'Vazirmatn', sans-serif; font-size:14px; transition:all var(--transition);">
                    <i class="fas fa-pen" aria-hidden="true"></i> نویسنده
                </button>
                <button onclick="setRole('admin')" class="role-option" style="padding:10px 16px; border:2px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; background:var(--bg); text-align:right; font-family:'Vazirmatn', sans-serif; font-size:14px; transition:all var(--transition);">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i> مدیر
                </button>
            </div>
            <input type="hidden" id="roleUserId" />
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn-cancel" onclick="closeModal('roleModal')" style="padding:10px 28px; border:none; border-radius:30px; cursor:pointer; background:var(--glass-bg); color:var(--text); border:1px solid var(--border); font-family:'Vazirmatn', sans-serif; font-size:14px; font-weight:600;">
                    لغو
                </button>
            </div>
        </div>
    </div>

    <script>
        // ===== تغییر نقش =====
        function changeRole(id, currentRole) {
            document.getElementById('roleUserId').value = id;
            
            // هایلایت نقش فعلی
            document.querySelectorAll('.role-option').forEach(btn => {
                btn.style.borderColor = 'var(--border)';
                btn.style.background = 'var(--bg)';
            });
            
            const roleMap = {
                'admin': '<i class="fas fa-shield-halved" aria-hidden="true"></i> مدیر',
                'editor': '<i class="fas fa-pen" aria-hidden="true"></i> نویسنده',
                'user': '<i class="fas fa-user" aria-hidden="true"></i> کاربر عادی'
            };
            
            document.querySelectorAll('.role-option').forEach(btn => {
                if (btn.textContent.trim() === roleMap[currentRole]) {
                    btn.style.borderColor = 'var(--primary)';
                    btn.style.background = 'rgba(108, 99, 255, 0.1)';
                }
            });
            
            openModal('roleModal');
        }

        function setRole(role) {
            const id = document.getElementById('roleUserId').value;
            if (id) {
                window.location.href = 'users.php?role=' + id + '&set=' + role;
            }
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

        console.log('<i class="fas fa-users" aria-hidden="true"></i> مدیریت کاربران');
        console.log('<i class="fas fa-chart-pie" aria-hidden="true"></i> تعداد کل: <?php echo $total_users; ?>');
        console.log('<i class="fas fa-shield-halved" aria-hidden="true"></i> مدیران: <?php echo count($admin_count); ?>');
        console.log('<i class="fas fa-circle-check" aria-hidden="true"></i> فعال: <?php echo count($active_count); ?>');
        console.log('<i class="fas fa-ban" aria-hidden="true"></i> غیرفعال: <?php echo $total_users - count($active_count); ?>');
    </script>
    </div><!-- /admin-layout -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>