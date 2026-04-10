<?php
/**
 * Route dispatcher.
 *
 * Pattern matching supports:
 *   - Exact segments:   /api/jobs
 *   - Named segments:   /api/jobs/{id}        → $params['id']
 *   - Sub-segments:     /api/jobs/{id}/apply
 *
 * Each route entry: [ METHOD, pattern, Controller::class, 'method' ]
 */

$routes = [
    // ── Public stats ──────────────────────────────────────────────────────
    ['GET', '/api/stats', StatsController::class, 'index'],

    // ── Auth ──────────────────────────────────────────────────────────────
    ['POST', '/api/auth/register', AuthController::class, 'register'],
    ['POST', '/api/auth/login',    AuthController::class, 'login'],
    ['POST', '/api/auth/logout',   AuthController::class, 'logout'],
    ['GET',  '/api/auth/me',       AuthController::class, 'me'],

    // ── Categories ────────────────────────────────────────────────────────
    ['GET',  '/api/categories',       JobController::class, 'categories'],

    // ── Public browse (employers + candidates lists) ───────────────────────
    ['GET',  '/api/employers',        EmployerController::class, 'browsePublic'],
    ['GET',  '/api/employers/{id}',   EmployerController::class, 'showPublic'],
    ['GET',  '/api/candidates',       CandidateController::class, 'browsePublic'],
    ['GET',  '/api/candidates/{id}',  CandidateController::class, 'showPublic'],

    // ── Recommendations (must come before /api/jobs/{id}) ─────────────────
    ['GET',  '/api/jobs/recommended', JobController::class, 'recommended'],

    // ── Jobs ──────────────────────────────────────────────────────────────
    ['GET',  '/api/jobs',             JobController::class, 'index'],
    ['GET',  '/api/jobs/{id}',        JobController::class, 'show'],
    ['POST', '/api/jobs/{id}/apply',  JobController::class, 'apply'],

    // ── Candidate ─────────────────────────────────────────────────────────
    ['GET',    '/api/candidate/dashboard',           CandidateController::class, 'dashboard'],
    ['GET',    '/api/candidate/applications',        CandidateController::class, 'applications'],
    ['GET',    '/api/candidate/job-alerts',          CandidateController::class, 'jobAlerts'],
    ['POST',   '/api/candidate/job-alerts',          CandidateController::class, 'createJobAlert'],
    ['DELETE', '/api/candidate/job-alerts/{id}',     CandidateController::class, 'deleteJobAlert'],
    ['GET',    '/api/candidate/favorites',           CandidateController::class, 'favorites'],
    ['POST',   '/api/candidate/favorites/{jobId}',   CandidateController::class, 'addFavorite'],
    ['DELETE', '/api/candidate/favorites/{jobId}',   CandidateController::class, 'removeFavorite'],
    ['POST',   '/api/candidate/upload-cv',           CandidateController::class, 'uploadCv'],
    ['GET',    '/api/candidate/profile',             CandidateController::class, 'getProfile'],
    ['PUT',    '/api/candidate/profile',             CandidateController::class, 'updateProfile'],
    ['GET',    '/api/candidate/cvs',                 CandidateController::class, 'listCvs'],
    ['GET',    '/api/candidate/notifications',       CandidateController::class, 'notifications'],

    // ── Employer ──────────────────────────────────────────────────────────
    ['GET',    '/api/employer/dashboard',            EmployerController::class, 'dashboard'],
    ['GET',    '/api/employer/applications',         EmployerController::class, 'applications'],
    ['GET',    '/api/employer/jobs',                 EmployerController::class, 'myJobs'],
    ['POST',   '/api/employer/jobs',                 EmployerController::class, 'createJob'],
    ['GET',    '/api/employer/profile',                        EmployerController::class, 'getProfile'],
    ['PUT',    '/api/employer/profile',                        EmployerController::class, 'updateProfile'],
    ['GET',    '/api/employer/applications/{id}',             EmployerController::class, 'showApplication'],
    ['PATCH',  '/api/employer/applications/{id}',             EmployerController::class, 'updateApplicationStatus'],
    ['GET',    '/api/employer/saved-candidates',              EmployerController::class, 'savedCandidates'],
    ['POST',   '/api/employer/saved-candidates/{candidateId}',EmployerController::class, 'saveCandidate'],
    ['DELETE', '/api/employer/saved-candidates/{candidateId}',EmployerController::class, 'unsaveCandidate'],
    ['GET',    '/api/employer/notifications',                  EmployerController::class, 'notifications'],

    // ── Account ───────────────────────────────────────────────────────────
    ['DELETE', '/api/account',                       AuthController::class, 'deleteAccount'],
];

// ── Dispatcher ────────────────────────────────────────────────────────────────

$method  = Request::method();
$path    = Request::path();
$matched = false;

foreach ($routes as [$routeMethod, $pattern, $controllerClass, $action]) {

    // Convert pattern to regex: /api/jobs/{id} → #^/api/jobs/(?P<id>[^/]+)$#
    $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';

    if (!preg_match($regex, $path, $matches)) {
        continue;
    }

    if ($method !== $routeMethod) {
        // Path matched but wrong method — collect for 405
        $matched = 'method_not_allowed';
        continue;
    }

    // Extract named params (filter out numeric keys from preg_match)
    $params = array_filter(
        $matches,
        fn($k) => is_string($k),
        ARRAY_FILTER_USE_KEY
    );

    // Instantiate controller and call action
    $controller = new $controllerClass();
    $controller->$action($params);
    exit;
}

// ── Fallback responses ────────────────────────────────────────────────────────

if ($matched === 'method_not_allowed') {
    Response::error('Method not allowed.', 405);
}

Response::notFound('Endpoint not found.');
