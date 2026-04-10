<?php
// Site configuration
define('SITE_NAME',    'Jobpilot');
define('SITE_URL',     (
                           (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                           (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                           ? 'https' : 'http'
                       ) . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/jobpilot');
define('SITE_EMAIL',   'support@jobpilot.jo');
define('SITE_PHONE',   '+962 6 500 0000');
define('SITE_ADDRESS', 'Abdali Boulevard, Amman 11180, Jordan');

// Database
define('DB_HOST',     '127.0.0.1');
define('DB_NAME',     'jobpilot');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// Upload limits
define('MAX_FILE_SIZE',   5 * 1024 * 1024); // 5 MB
define('UPLOAD_PATH',     __DIR__ . '/../uploads/');
define('UPLOAD_AVATARS',  __DIR__ . '/../uploads/avatars/');
define('UPLOAD_LOGOS',    __DIR__ . '/../uploads/logos/');
define('UPLOAD_BANNERS',  __DIR__ . '/../uploads/banners/');
define('UPLOAD_CVS',      __DIR__ . '/../uploads/cvs/');

// Session
define('SESSION_LIFETIME', 86400); // 24 hours

// Pagination
define('JOBS_PER_PAGE',       12);
define('CANDIDATES_PER_PAGE', 12);
define('EMPLOYERS_PER_PAGE',  12);
define('BLOG_PER_PAGE',       9);

// ML recommendations path
define('ML_RECS_TABLE', 'recommendations'); // stored in DB after offline run

// Password reset token lifetime (minutes)
define('RESET_TOKEN_LIFETIME', 60);
define('VERIFY_TOKEN_LIFETIME', 1440);

// Jordan cities (from dataset)
define('JO_CITIES', [
    'Amman', 'Irbid', 'Zarqa', 'Aqaba', 'Mafraq',
    'Ajloun', 'Madaba', 'Salt', 'Karak', 'Jerash', 'Remote'
]);

// Job types
define('JOB_TYPES', ['Full Time', 'Part Time', 'Internship', 'Temporary', 'Contract', 'Freelance']);

// Experience levels
define('EXP_LEVELS', ['Entry Level', 'Mid Level', 'Senior Level', 'Manager', 'Director', 'VP or Above']);

// Education levels
define('EDU_LEVELS', ['High School', 'Associate', 'Bachelor', 'Master', 'PhD', 'Self-taught']);

// Salary types
define('SALARY_TYPES', ['Monthly', 'Hourly', 'Yearly']);

// Job categories (from dataset industries)
define('JOB_CATEGORIES', [
    'IT', 'Finance', 'HR', 'Marketing', 'Design', 'Engineering', 'Sales',
    'Customer Service', 'Manufacturing', 'Education', 'Health Care',
    'Accounting', 'Legal', 'Media & Communication', 'Administration'
]);

// Plans
define('PLANS', [
    'basic'    => ['name' => 'Basic',    'price' => 19, 'active_jobs' => 1,  'urgent_featured' => false, 'highlights' => false, 'candidates_access' => 0,  'resume_visibility' => 30,  'support' => false],
    'standard' => ['name' => 'Standard', 'price' => 39, 'active_jobs' => 5,  'urgent_featured' => true,  'highlights' => true,  'candidates_access' => 10, 'resume_visibility' => 60,  'support' => true],
    'premium'  => ['name' => 'Premium',  'price' => 59, 'active_jobs' => 8,  'urgent_featured' => true,  'highlights' => true,  'candidates_access' => 20, 'resume_visibility' => 90,  'support' => true],
]);
