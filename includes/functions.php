<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// ─── Session ─────────────────────────────────────────────────────────────────
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function restore_from_remember_cookie(): void {
    if (isset($_SESSION['user_id'])) return; // already logged in
    $token = $_COOKIE['remember_token'] ?? '';
    if (!$token) return;
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT rt.user_id, u.id, u.full_name, u.username, u.email, u.role, u.avatar
         FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.token = ? AND rt.expires_at > NOW()'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        setcookie('remember_token', '', time() - 3600, '/');
        return;
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['user']    = [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'username'  => $user['username'],
        'email'     => $user['email'],
        'role'      => $user['role'],
        'avatar'    => $user['avatar'],
    ];
}

function is_logged_in(): bool {
    start_session();
    if (!isset($_SESSION['user_id'])) restore_from_remember_cookie();
    return isset($_SESSION['user_id']);
}
function current_user(): ?array     { start_session(); return $_SESSION['user'] ?? null; }
function current_user_id(): ?int    { start_session(); return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; }
function current_role(): ?string    { start_session(); return $_SESSION['role'] ?? null; }
function is_candidate(): bool       { return current_role() === 'candidate'; }
function is_employer(): bool        { return current_role() === 'employer'; }

function require_login(string $redirect = '/auth/login.php'): void {
    if (!is_logged_in()) { redirect(SITE_URL . $redirect); }
}
function require_role(string $role): void {
    require_login();
    if (current_role() !== $role) { redirect(SITE_URL . '/index.php'); }
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf(string $token): bool {
    return hash_equals(csrf_token(), $token);
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// ─── Input ───────────────────────────────────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
function sanitize_email(string $email): string {
    return strtolower(trim(filter_var($email, FILTER_SANITIZE_EMAIL)));
}
function post(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}
function get_param(string $key, string $default = ''): string {
    return trim($_GET[$key] ?? $default);
}
function post_int(string $key, int $default = 0): int {
    return (int)post($key, (string)$default);
}

// ─── Flash messages ──────────────────────────────────────────────────────────
function flash(string $key, string $msg): void {
    start_session();
    $_SESSION['flash'][$key] = $msg;
}
function get_flash(string $key): ?string {
    start_session();
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
function render_flash(): void {
    foreach (['success', 'error', 'info'] as $k) {
        if ($m = get_flash($k)) {
            $cls = ($k === 'error') ? 'danger' : $k;
            echo '<div class="alert alert-' . $cls . '">' . clean($m) . '</div>';
        }
    }
}

// ─── Slug ─────────────────────────────────────────────────────────────────────
function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function unique_slug(string $table, string $column, string $base): string {
    $db   = get_db();
    $slug = slugify($base);
    $i    = 0;
    do {
        $try  = $i === 0 ? $slug : $slug . '-' . $i;
        $stmt = $db->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = ?");
        $stmt->execute([$try]);
        $exists = (int)$stmt->fetchColumn();
        $i++;
    } while ($exists);
    return $try;
}

// ─── Pagination ───────────────────────────────────────────────────────────────
function paginate(int $total, int $per_page, int $current): array {
    $pages = max(1, (int)ceil($total / $per_page));
    $current = max(1, min($current, $pages));
    return [
        'total'   => $total,
        'per_page'=> $per_page,
        'current' => $current,
        'pages'   => $pages,
        'offset'  => ($current - 1) * $per_page,
    ];
}

function render_pagination(array $p, string $base_url, array $extra_params = []): string {
    if ($p['pages'] <= 1) return '';
    $qs = $extra_params ? '&' . http_build_query($extra_params) : '';
    $html = '<nav class="pagination">';
    if ($p['current'] > 1) {
        $html .= '<div class="page-item"><a href="' . $base_url . '?page=' . ($p['current'] - 1) . $qs . '">&lsaquo;</a></div>';
    }
    $range = 2;
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($i === 1 || $i === $p['pages'] || abs($i - $p['current']) <= $range) {
            if ($i == $p['current']) {
                $html .= '<div class="page-item active"><span>' . $i . '</span></div>';
            } else {
                $html .= '<div class="page-item"><a href="' . $base_url . '?page=' . $i . $qs . '">' . $i . '</a></div>';
            }
        } elseif (abs($i - $p['current']) === $range + 1) {
            $html .= '<div class="page-item"><span>...</span></div>';
        }
    }
    if ($p['current'] < $p['pages']) {
        $html .= '<div class="page-item"><a href="' . $base_url . '?page=' . ($p['current'] + 1) . $qs . '">&rsaquo;</a></div>';
    }
    $html .= '</nav>';
    return $html;
}

// ─── Job type ────────────────────────────────────────────────────────────────
function job_type_class(string $type): string {
    $map = [
        'Full Time'  => 'full-time',
        'Part Time'  => 'part-time',
        'Internship' => 'internship',
        'Temporary'  => 'temporary',
        'Contract'   => 'contract',
        'Freelance'  => 'freelance',
    ];
    return $map[$type] ?? 'full-time';
}

// ─── Email ───────────────────────────────────────────────────────────────────
function send_mail(string $to, string $subject, string $body_html): bool {
    $from_name  = addslashes(SITE_NAME);
    $from_email = SITE_EMAIL;
    $headers    = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    $wrapper = '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:Inter,Arial,sans-serif;color:#474C54;background:#F1F2F4;margin:0;padding:20px}
    .box{max-width:520px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
    .hdr{background:#0A65CC;padding:28px 32px}.hdr .logo{color:#fff;font-size:18px;font-weight:700;text-decoration:none}
    .body{padding:32px}.body h2{color:#18191C;margin-bottom:12px}.btn{display:inline-block;margin:20px 0;padding:12px 28px;background:#0A65CC;color:#fff;border-radius:6px;text-decoration:none;font-weight:600}
    .footer{padding:16px 32px;background:#F1F2F4;font-size:12px;color:#767F8C;text-align:center}
    </style></head><body><div class="box">
    <div class="hdr"><span class="logo">' . SITE_NAME . '</span></div>
    <div class="body">' . $body_html . '</div>
    <div class="footer">&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.<br>' . SITE_ADDRESS . '</div>
    </div></body></html>';
    return mail($to, $subject, $wrapper, $headers);
}

// ─── Salary ──────────────────────────────────────────────────────────────────
function format_salary(int $min, int $max, string $currency = 'JOD', string $type = 'Monthly'): string {
    if ($min > 0 && $max > 0) {
        return $currency . ' ' . number_format($min) . ' - ' . number_format($max) . '/' . strtolower($type);
    }
    if ($max > 0) return $currency . ' ' . number_format($max) . '/' . strtolower($type);
    if ($min > 0) return 'From ' . $currency . ' ' . number_format($min) . '/' . strtolower($type);
    return 'Negotiable';
}

// ─── Time ────────────────────────────────────────────────────────────────────
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return (int)($diff / 60) . ' min ago';
    if ($diff < 86400)   return (int)($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return (int)($diff / 86400) . ' days ago';
    if ($diff < 31536000)return (int)($diff / 2592000) . ' months ago';
    return date('M j, Y', strtotime($datetime));
}

function days_remaining(string $date): int {
    return max(0, (int)round((strtotime($date) - time()) / 86400));
}

// ─── URLs ─────────────────────────────────────────────────────────────────────
function asset(string $path): string   { return SITE_URL . '/assets/' . ltrim($path, '/'); }
function upload_url(string $path): string { return SITE_URL . '/uploads/' . ltrim($path, '/'); }
function site_url(string $path = ''): string { return SITE_URL . '/' . ltrim($path, '/'); }

// ─── Token ────────────────────────────────────────────────────────────────────
function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

// ─── Redirect ────────────────────────────────────────────────────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ─── File upload ─────────────────────────────────────────────────────────────
function handle_upload(
    string $input_name,
    string $upload_dir,
    array  $allowed_types = ['image/jpeg', 'image/png', 'image/webp']
): ?string {
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$input_name];
    if ($file['size'] > MAX_FILE_SIZE) {
        flash('error', 'File too large. Maximum size is 5 MB.');
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_types)) {
        flash('error', 'Invalid file type.');
        return null;
    }
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('', true) . '.' . $ext;
    $dest     = rtrim($upload_dir, '/') . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('error', 'Upload failed. Please try again.');
        return null;
    }
    return $filename;
}

// ─── Profiles ────────────────────────────────────────────────────────────────
function get_candidate_profile(int $user_id): ?array {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT cp.*, u.full_name, u.email, u.avatar, u.username
         FROM candidate_profiles cp
         JOIN users u ON u.id = cp.user_id
         WHERE cp.user_id = ?'
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

function get_employer_profile(int $user_id): ?array {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT ep.*, u.full_name, u.email, u.avatar, u.username
         FROM employer_profiles ep
         JOIN users u ON u.id = ep.user_id
         WHERE ep.user_id = ?'
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

// ─── Notifications ───────────────────────────────────────────────────────────
function count_notifications(int $user_id): int {
    $db   = get_db();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function add_notification(int $user_id, string $type, string $message, string $link = ''): void {
    $db   = get_db();
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $type, $message, $link]);
}

// ─── ML Recommendations ──────────────────────────────────────────────────────
function get_recommended_jobs(int $candidate_id, int $limit = 6): array {
    $db = get_db();

    // Try pre-computed recommendations first
    $stmt = $db->prepare(
        'SELECT j.*, r.score, ep.company_name, ep.logo
         FROM recommendations r
         JOIN jobs j ON j.id = r.job_id
         JOIN employer_profiles ep ON ep.user_id = j.employer_id
         WHERE r.candidate_profile_id = ? AND j.status = "active"
         ORDER BY r.score DESC
         LIMIT ?'
    );
    $stmt->execute([$candidate_id, $limit]);
    $recs = $stmt->fetchAll();
    if (count($recs) >= 3) return $recs;

    // Fallback: simple matching by preferred_field + location
    $cp = $db->prepare(
        'SELECT preferred_field, city FROM candidate_profiles WHERE id = ?'
    );
    $cp->execute([$candidate_id]);
    $profile = $cp->fetch();
    if (!$profile) return [];

    $stmt2 = $db->prepare(
        'SELECT j.*, ep.company_name, ep.logo, 0.5 AS score
         FROM jobs j
         JOIN employer_profiles ep ON ep.user_id = j.employer_id
         WHERE j.status = "active"
           AND (j.category = ? OR j.location = ?)
         ORDER BY j.created_at DESC
         LIMIT ?'
    );
    $stmt2->execute([$profile['preferred_field'] ?? '', $profile['city'] ?? '', $limit]);
    return $stmt2->fetchAll();
}

// ─── Job helpers ─────────────────────────────────────────────────────────────
function is_job_saved(int $candidate_profile_id, int $job_id): bool {
    $db   = get_db();
    $stmt = $db->prepare('SELECT 1 FROM saved_jobs WHERE candidate_profile_id = ? AND job_id = ?');
    $stmt->execute([$candidate_profile_id, $job_id]);
    return (bool)$stmt->fetchColumn();
}

function has_applied(int $candidate_profile_id, int $job_id): bool {
    $db   = get_db();
    $stmt = $db->prepare('SELECT 1 FROM applications WHERE candidate_profile_id = ? AND job_id = ?');
    $stmt->execute([$candidate_profile_id, $job_id]);
    return (bool)$stmt->fetchColumn();
}

// ─── Render job card (shared) ─────────────────────────────────────────────────
function render_job_card(array $job, bool $show_bookmark = true, ?int $candidate_id = null): string {
    $saved   = ($show_bookmark && $candidate_id) ? is_job_saved($candidate_id, $job['id']) : false;
    $logo    = !empty($job['logo']) ? upload_url('logos/' . $job['logo']) : asset('images/company-placeholder.png');
    $type_cl = job_type_class($job['job_type'] ?? 'Full Time');
    $salary  = format_salary((int)($job['salary_min'] ?? 0), (int)($job['salary_max'] ?? 0), $job['salary_currency'] ?? 'JOD');
    $days    = !empty($job['expires_at']) ? days_remaining($job['expires_at']) : null;

    ob_start();
    ?>
    <div class="job-card">
        <?php if ($show_bookmark): ?>
        <button class="bookmark-btn <?= $saved ? 'saved' : '' ?>"
                data-job-id="<?= (int)$job['id'] ?>"
                title="<?= $saved ? 'Remove from favorites' : 'Save job' ?>">
            <svg width="18" height="18" fill="<?= $saved ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>
            </svg>
        </button>
        <?php endif; ?>

        <div class="job-card-top">
            <span class="badge-type badge-<?= $type_cl ?>"><?= clean($job['job_type'] ?? 'Full Time') ?></span>
        </div>

        <div class="job-card-body">
            <div class="job-card-meta-row">
                <img src="<?= $logo ?>" alt="<?= clean($job['company_name'] ?? '') ?>" class="company-logo">
                <div>
                    <a href="<?= site_url('jobs/job-detail.php?id=' . (int)$job['id']) ?>" class="job-title">
                        <?= clean($job['title']) ?>
                    </a>
                    <div class="company-name"><?= clean($job['company_name'] ?? '') ?></div>
                </div>
            </div>
            <div class="job-meta">
                <span class="meta-item">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/></svg>
                    <?= clean($job['location'] ?? '') ?>
                </span>
                <span class="meta-item">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    <?= clean($salary) ?>
                </span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ─── Breadcrumb helper ───────────────────────────────────────────────────────
function breadcrumb(array $items): string {
    $html = '<nav class="breadcrumb">';
    $last = array_key_last($items);
    foreach ($items as $k => $item) {
        if ($k === $last) {
            $html .= '<span class="current">' . clean($item['label']) . '</span>';
        } else {
            $html .= '<a href="' . clean($item['url']) . '">' . clean($item['label']) . '</a>';
            $html .= '<span class="breadcrumb-sep">&#62;</span>';
        }
    }
    $html .= '</nav>';
    return $html;
}
