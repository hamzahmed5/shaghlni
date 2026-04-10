<?php
/**
 * Database setup script - run once via CLI:
 * php database/setup.php
 */
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$name = 'jobpilot';

$pdo = new PDO("mysql:host=$host;port=3306;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");

$tables = [
'email_verifications' => "CREATE TABLE IF NOT EXISTS email_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'password_resets' => "CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'employer_profiles' => "CREATE TABLE IF NOT EXISTS employer_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    company_name VARCHAR(160) NOT NULL DEFAULT '',
    industry VARCHAR(100) DEFAULT NULL,
    company_size VARCHAR(60) DEFAULT NULL,
    founded_year YEAR DEFAULT NULL,
    description TEXT DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    banner VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(40) DEFAULT NULL,
    setup_complete TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'employer_social_links' => "CREATE TABLE IF NOT EXISTS employer_social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    platform VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'candidate_profiles' => "CREATE TABLE IF NOT EXISTS candidate_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    current_position VARCHAR(120) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    experience_level VARCHAR(60) DEFAULT NULL,
    education_level VARCHAR(60) DEFAULT NULL,
    work_experience TEXT DEFAULT NULL,
    education TEXT DEFAULT NULL,
    preferred_field VARCHAR(100) DEFAULT NULL,
    expected_salary_min INT UNSIGNED DEFAULT 0,
    expected_salary_max INT UNSIGNED DEFAULT 0,
    website VARCHAR(255) DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'candidate_skills' => "CREATE TABLE IF NOT EXISTS candidate_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id INT UNSIGNED NOT NULL,
    skill_name VARCHAR(80) NOT NULL,
    proficiency VARCHAR(30) DEFAULT NULL,
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'candidate_social_links' => "CREATE TABLE IF NOT EXISTS candidate_social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    platform VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'candidate_cvs' => "CREATE TABLE IF NOT EXISTS candidate_cvs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id INT UNSIGNED NOT NULL,
    label VARCHAR(100) NOT NULL DEFAULT 'My Resume',
    file_path VARCHAR(255) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'jobs' => "CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    job_type VARCHAR(60) NOT NULL DEFAULT 'Full Time',
    location VARCHAR(100) DEFAULT NULL,
    experience_level VARCHAR(60) DEFAULT NULL,
    education_level VARCHAR(60) DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    requirements TEXT DEFAULT NULL,
    responsibilities TEXT DEFAULT NULL,
    salary_min INT UNSIGNED DEFAULT 0,
    salary_max INT UNSIGNED DEFAULT 0,
    salary_currency VARCHAR(10) DEFAULT 'JOD',
    salary_type VARCHAR(30) DEFAULT 'Monthly',
    vacancy_count TINYINT UNSIGNED DEFAULT 1,
    expires_at DATE DEFAULT NULL,
    status ENUM('active','expired','draft') DEFAULT 'active',
    is_featured TINYINT(1) DEFAULT 0,
    is_urgent TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status(status),
    INDEX idx_job_type(job_type),
    INDEX idx_location(location),
    INDEX idx_employer(employer_id),
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'job_benefits' => "CREATE TABLE IF NOT EXISTS job_benefits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    benefit VARCHAR(200) NOT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'applications' => "CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    cover_letter TEXT DEFAULT NULL,
    resume_path VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application (candidate_profile_id, job_id),
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'saved_jobs' => "CREATE TABLE IF NOT EXISTS saved_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    saved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_job (candidate_profile_id, job_id),
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'saved_candidates' => "CREATE TABLE IF NOT EXISTS saved_candidates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_profile_id INT UNSIGNED NOT NULL,
    candidate_profile_id INT UNSIGNED NOT NULL,
    saved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_cand (employer_profile_id, candidate_profile_id),
    FOREIGN KEY (employer_profile_id) REFERENCES employer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'job_alerts' => "CREATE TABLE IF NOT EXISTS job_alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    keyword VARCHAR(120) DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    job_type VARCHAR(60) DEFAULT NULL,
    frequency VARCHAR(20) DEFAULT 'daily',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'plans' => "CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    price DECIMAL(8,2) NOT NULL DEFAULT 0,
    max_jobs INT UNSIGNED DEFAULT 5,
    features JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB",

'employer_subscriptions' => "CREATE TABLE IF NOT EXISTS employer_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    starts_at DATE NOT NULL,
    ends_at DATE DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'invoices' => "CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    status VARCHAR(20) DEFAULT 'paid',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'blog_posts' => "CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    content LONGTEXT DEFAULT NULL,
    excerpt TEXT DEFAULT NULL,
    featured_img VARCHAR(255) DEFAULT NULL,
    author_id INT UNSIGNED DEFAULT NULL,
    status ENUM('published','draft') DEFAULT 'draft',
    views INT UNSIGNED DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB",

'contact_messages' => "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB",

'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB",

'recommendations' => "CREATE TABLE IF NOT EXISTS recommendations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    score FLOAT DEFAULT 0,
    method VARCHAR(30) DEFAULT 'tfidf',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rec (candidate_profile_id, job_id),
    FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB",
];

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "  OK: $name\n";
    } catch (Exception $e) {
        echo "  ERR [$name]: " . $e->getMessage() . "\n";
    }
}

// Seed plans
$pdo->exec("INSERT IGNORE INTO plans (name, price, max_jobs, features) VALUES
    ('Basic',    19.00, 5,   '[\"5 active job posts\",\"Standard visibility\",\"Email support\"]'),
    ('Standard', 39.00, 15,  '[\"15 active job posts\",\"Featured visibility\",\"Priority support\",\"Advanced analytics\"]'),
    ('Premium',  59.00, 999, '[\"Unlimited job posts\",\"Featured + Urgent\",\"Dedicated manager\",\"AI matching\"]')
");

echo "\nDatabase setup complete!\n";
echo "Tables created: " . count($tables) . "\n";
$cnt = $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn();
echo "Plans seeded: $cnt\n";
