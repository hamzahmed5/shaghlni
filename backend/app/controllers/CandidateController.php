<?php
/**
 * CandidateController
 *
 * GET  /api/candidate/dashboard   → dashboard()
 * GET  /api/candidate/favorites   → favorites()
 * POST /api/candidate/upload-cv   → uploadCv()
 */

class CandidateController
{
    private CandidateProfileModel $cpModel;
    private ApplicationModel      $appModel;

    public function __construct()
    {
        $this->cpModel  = new CandidateProfileModel();
        $this->appModel = new ApplicationModel();
    }

    // ── GET /api/candidate/dashboard ──────────────────────────────────────────
    // Frontend reads: d.applied_jobs, d.favorite_jobs, d.profile_completion
    // (from: const d = res.data || {})

    public function dashboard(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);

        if (!$candidate) {
            Response::error('Candidate profile not found.', 404);
        }

        $cpId = $candidate['id'];

        $appsCount = $this->appModel->countByCandidate($cpId);
        $favCount  = $this->cpModel->countFavorites($cpId);
        $cvsCount  = $this->cpModel->countCvs($cpId);

        // Profile completion score (simple heuristic)
        $filled = 0;
        foreach (['primary_skills', 'location', 'education_level', 'preferred_job_field', 'headline', 'bio'] as $f) {
            if (!empty($candidate[$f])) $filled++;
        }
        $profileCompletion = (int) round(($filled / 6) * 100);

        Response::success([
            // ── Fields frontend dashboard.html reads directly ─────────────────
            'applied_jobs'        => $appsCount,        // d.applied_jobs
            'favorite_jobs'       => $favCount,          // d.favorite_jobs
            'profile_completion'  => $profileCompletion, // d.profile_completion

            // ── Extra data (available but not currently read by frontend) ──────
            'cvs_count'           => $cvsCount,
            'profile'             => $this->safeProfile($candidate, $user),
            'recent_applications' => array_slice($this->appModel->findByCandidate($cpId), 0, 5),
            'recent_favorites'    => array_slice($this->cpModel->getFavorites($cpId), 0, 5),
        ]);
    }

    // ── GET /api/candidate/favorites ──────────────────────────────────────────
    // Frontend: allFavorites = Array.isArray(res.data) ? res.data : []
    // Must return data as a flat array with normalized job field names.

    public function favorites(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);

        if (!$candidate) {
            Response::error('Candidate profile not found.', 404);
        }

        $rows = $this->cpModel->getFavorites($candidate['id']);

        // Add frontend-expected field aliases to each row
        $favorites = array_map(function (array $row): array {
            $row['title']   = $row['job_title']    ?? null;   // job.title
            $row['company'] = $row['company_name']  ?? null;   // job.company
            $row['type']    = $row['job_type']      ?? null;   // job.type

            // Build salary string for job.salary
            $min = (float) ($row['salary_min_jod'] ?? 0);
            $max = (float) ($row['salary_max_jod'] ?? 0);
            if ($min > 0 && $max > 0) {
                $row['salary'] = number_format($min, 0) . ' – ' . number_format($max, 0) . ' JOD';
            } elseif ($max > 0) {
                $row['salary'] = 'Up to ' . number_format($max, 0) . ' JOD';
            } else {
                $row['salary'] = null;
            }

            // Frontend: job.job_id || job.jobId || job.id — job_id already present ✓
            return $row;
        }, $rows);

        // Return as flat array — frontend does Array.isArray(res.data)
        Response::success($favorites);
    }

    // ── POST /api/candidate/favorites/{jobId} ─────────────────────────────────

    public function addFavorite(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Candidate profile not found.', 404);

        $jobId = (int) ($params['jobId'] ?? 0);
        if ($jobId < 1) Response::error('Invalid job ID.', 422);

        $this->cpModel->addFavorite($candidate['id'], $jobId);
        Response::success(null, 'Job added to favorites.');
    }

    // ── DELETE /api/candidate/favorites/{jobId} ────────────────────────────────

    public function removeFavorite(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Candidate profile not found.', 404);

        $jobId = (int) ($params['jobId'] ?? 0);
        if ($jobId < 1) Response::error('Invalid job ID.', 422);

        $this->cpModel->removeFavorite($candidate['id'], $jobId);
        Response::success(null, 'Job removed from favorites.');
    }

    // ── POST /api/candidate/upload-cv ─────────────────────────────────────────
    // Frontend: fd.append('cv', file) — field name 'cv' matches Request::file('cv') ✓

    public function uploadCv(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);

        if (!$candidate) {
            Response::error('Candidate profile not found.', 404);
        }

        $file = Request::file('cv');

        if (!$file) {
            Response::error('No file uploaded. Use field name: cv', 422);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('File upload error (code ' . $file['error'] . ').', 422);
        }

        $cfg     = require BASE_PATH . '/config/app.php';
        $allowed = $cfg['upload']['allowed_cv'];
        $maxSize = $cfg['upload']['max_size'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            Response::error('Invalid file type. Allowed: ' . implode(', ', $allowed), 422);
        }

        // Verify actual MIME type matches extension
        $allowedMime = [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!isset($allowedMime[$ext]) || $mimeType !== $allowedMime[$ext]) {
            Response::error('File type mismatch. Upload a real PDF, DOC, or DOCX file.', 422);
        }

        if ($file['size'] > $maxSize) {
            Response::error(
                'File too large. Maximum size: ' . ($maxSize / 1024 / 1024) . ' MB.',
                422
            );
        }

        $uploadDir   = $cfg['upload']['cv_path'];
        $safeName    = time() . '_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Response::error('Failed to save file. Check server write permissions.', 500);
        }

        $relativePath = 'storage/uploads/cvs/' . $safeName;
        $cvId = $this->cpModel->addCv($candidate['id'], $relativePath, $file['name']);

        Response::success(
            [
                'cv_id'         => $cvId,
                'file_path'     => $relativePath,
                'original_name' => $file['name'],
            ],
            'CV uploaded successfully.',
            201
        );
    }

    // ── GET /api/candidate/applications ──────────────────────────────────────

    public function applications(array $params = []): void
    {
        $user = AuthMiddleware::requireRole('candidate');
        $db   = \DB::get();
        $stmt = $db->prepare(
            'SELECT a.id, a.status, a.created_at,
                    j.id AS job_id, j.job_title, j.location, j.job_type,
                    ep.company_name
             FROM applications a
             JOIN jobs j               ON j.id  = a.job_id
             JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             WHERE a.candidate_profile_id = (SELECT id FROM candidate_profiles WHERE user_id = ?)
             ORDER BY a.created_at DESC'
        );
        $stmt->execute([$user['id']]);
        Response::success($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    // ── GET /api/candidate/job-alerts ────────────────────────────────────────

    public function jobAlerts(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::success([]);
        $db   = \DB::get();
        $stmt = $db->prepare('SELECT * FROM job_alerts WHERE candidate_profile_id = ? ORDER BY created_at DESC');
        $stmt->execute([$candidate['id']]);
        Response::success($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    // ── POST /api/candidate/job-alerts ───────────────────────────────────────

    public function createJobAlert(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Candidate profile not found.', 404);
        $body      = Request::json();
        $title     = trim($body['title']    ?? '');
        $keywords  = trim($body['keywords'] ?? $body['keyword'] ?? $title);
        if (!$title) Response::error('title is required.', 422);
        $db   = \DB::get();
        $stmt = $db->prepare(
            'INSERT INTO job_alerts (candidate_profile_id, title, keywords, location, job_type, frequency)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $candidate['id'],
            $title,
            $keywords,
            trim($body['location']  ?? ''),
            trim($body['job_type']  ?? ''),
            trim($body['frequency'] ?? 'daily'),
        ]);
        Response::success(['id' => $db->lastInsertId()], 'Alert created.', 201);
    }

    // ── DELETE /api/candidate/job-alerts/{id} ────────────────────────────────

    public function deleteJobAlert(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Profile not found.', 404);
        $id = (int) ($params['id'] ?? 0);
        $db = \DB::get();
        $db->prepare('DELETE FROM job_alerts WHERE id = ? AND candidate_profile_id = ?')
           ->execute([$id, $candidate['id']]);
        Response::success(null, 'Alert deleted.');
    }

    // ── GET /api/candidate/profile ───────────────────────────────────────────

    public function getProfile(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Profile not found.', 404);
        Response::success($this->safeProfile($candidate, $user));
    }

    // ── PUT /api/candidate/profile ───────────────────────────────────────────

    public function updateProfile(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Candidate profile not found.', 404);

        $body = Request::json();

        // full_name belongs to users table, not candidate_profiles
        if (!empty($body['full_name'])) {
            $db = \DB::get();
            $db->prepare('UPDATE users SET full_name = ? WHERE id = ?')
               ->execute([trim($body['full_name']), $user['id']]);
        }

        $this->cpModel->update($candidate['id'], $body);
        Response::success(null, 'Profile updated successfully.');
    }

    // ── GET /api/candidate/cvs ────────────────────────────────────────────────

    public function listCvs(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);
        if (!$candidate) Response::error('Candidate profile not found.', 404);
        Response::success($this->cpModel->getCvList($candidate['id']));
    }

    // ── GET /api/candidates (public) ─────────────────────────────────────────

    public function browsePublic(array $params = []): void
    {
        $db       = \DB::get();
        $page     = max(1, (int) ($_GET['page']     ?? 1));
        $limit    = min((int) ($_GET['limit']   ?? 20), 100);
        $search   = trim($_GET['search']   ?? '');
        $location = trim($_GET['location'] ?? '');
        $offset   = ($page - 1) * $limit;

        $where = ['u.status = "active"', 'u.role = "candidate"'];
        $binds = [];

        if ($search) {
            $where[] = '(u.full_name LIKE ? OR cp.headline LIKE ? OR cp.primary_skills LIKE ?)';
            $binds   = array_merge($binds, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($location) {
            $where[] = 'cp.location LIKE ?';
            $binds[] = "%$location%";
        }

        $sql  = 'FROM users u LEFT JOIN candidate_profiles cp ON cp.user_id = u.id WHERE ' . implode(' AND ', $where);
        $total = (int) $db->prepare("SELECT COUNT(*) $sql")->execute($binds) ? $db->prepare("SELECT COUNT(*) $sql")->execute($binds) && 0 : 0;

        // cleaner count query
        $countStmt = $db->prepare("SELECT COUNT(*) $sql");
        $countStmt->execute($binds);
        $total = (int) $countStmt->fetchColumn();

        $dataStmt = $db->prepare("SELECT u.id, u.full_name, cp.headline, cp.location, cp.primary_skills, cp.years_of_experience $sql ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
        $dataStmt->execute($binds);
        $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        Response::success([
            'data'       => $rows,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    // ── GET /api/candidates/{id} (public) ────────────────────────────────────

    public function showPublic(array $params = []): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $db   = \DB::get();
        $stmt = $db->prepare(
            'SELECT u.id, u.full_name, cp.headline, cp.location, cp.primary_skills, cp.years_of_experience, cp.bio
             FROM users u LEFT JOIN candidate_profiles cp ON cp.user_id = u.id
             WHERE u.id = ? AND u.role = "candidate" AND u.status = "active"'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) Response::error('Candidate not found.', 404);
        Response::success($row);
    }

    // ── GET /api/candidate/notifications ─────────────────────────────────────
    // Returns count of application status updates in the last 7 days.

    public function notifications(array $params = []): void
    {
        $user      = AuthMiddleware::requireRole('candidate');
        $candidate = $this->cpModel->findByUserId($user['id']);

        if (!$candidate) {
            Response::success(['count' => 0, 'items' => []]);
        }

        $db   = \DB::get();
        $stmt = $db->prepare(
            'SELECT a.id, j.job_title, ep.company_name, a.status, a.updated_at
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             LEFT JOIN employer_profiles ep ON ep.id = j.employer_profile_id
             WHERE a.candidate_profile_id = ?
               AND a.status != "pending"
               AND a.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY a.updated_at DESC
             LIMIT 10'
        );
        $stmt->execute([$candidate['id']]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        Response::success([
            'count' => count($items),
            'items' => $items,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function safeProfile(array $profile, array $user): array
    {
        $profile['full_name'] = $user['name'];
        $profile['role']      = $user['role'];
        return $profile;
    }
}
