-- Jobpilot Database Schema
-- Run: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS jobpilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jobpilot;

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(120)  NOT NULL,
    username        VARCHAR(60)   NOT NULL UNIQUE,
    email           VARCHAR(180)  NOT NULL UNIQUE,
    password        VARCHAR(255)  NOT NULL,
    role            ENUM('candidate','employer') NOT NULL DEFAULT 'candidate',
    is_verified     TINYINT(1) NOT NULL DEFAULT 0,
    avatar          VARCHAR(255)  DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- EMAIL VERIFICATIONS
-- ─────────────────────────────────────────
CREATE TABLE email_verifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- PASSWORD RESETS
-- ─────────────────────────────────────────
CREATE TABLE password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- CANDIDATE PROFILES
-- ─────────────────────────────────────────
CREATE TABLE candidate_profiles (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,
    headline            VARCHAR(180) DEFAULT NULL,
    experience_years    TINYINT UNSIGNED DEFAULT 0,
    education_level     VARCHAR(60) DEFAULT NULL,
    location            VARCHAR(100) DEFAULT NULL,
    about               TEXT DEFAULT NULL,
    website             VARCHAR(255) DEFAULT NULL,
    preferred_job_field VARCHAR(100) DEFAULT NULL,
    salary_expectation  INT UNSIGNED DEFAULT 0,
    employment_status   VARCHAR(60) DEFAULT NULL,
    willing_to_relocate TINYINT(1) DEFAULT 0,
    age                 TINYINT UNSIGNED DEFAULT NULL,
    gender              ENUM('male','female','other') DEFAULT NULL,
    nationality         VARCHAR(80) DEFAULT NULL,
    profile_setup_done  TINYINT(1) DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE candidate_skills (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT UNSIGNED NOT NULL,
    skill        VARCHAR(100) NOT NULL,
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    INDEX idx_skill (skill)
) ENGINE=InnoDB;

CREATE TABLE candidate_social_links (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT UNSIGNED NOT NULL,
    platform     VARCHAR(40) NOT NULL,
    url          VARCHAR(255) NOT NULL,
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE candidate_cvs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT UNSIGNED NOT NULL,
    cv_name      VARCHAR(120) NOT NULL,
    file_path    VARCHAR(255) NOT NULL,
    file_size    INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- EMPLOYER PROFILES
-- ─────────────────────────────────────────
CREATE TABLE employer_profiles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL UNIQUE,
    company_name    VARCHAR(160) NOT NULL DEFAULT '',
    logo            VARCHAR(255) DEFAULT NULL,
    banner          VARCHAR(255) DEFAULT NULL,
    about           TEXT DEFAULT NULL,
    industry        VARCHAR(100) DEFAULT NULL,
    company_size    VARCHAR(60)  DEFAULT NULL,
    founded_year    YEAR         DEFAULT NULL,
    location        VARCHAR(100) DEFAULT NULL,
    website         VARCHAR(255) DEFAULT NULL,
    email           VARCHAR(180) DEFAULT NULL,
    phone           VARCHAR(40)  DEFAULT NULL,
    business_type   ENUM('Company','Freelancer','Restaurant','Salon','Workshop','Other') DEFAULT 'Company',
    setup_step      TINYINT DEFAULT 1,
    setup_done      TINYINT(1) DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employer_social_links (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id INT UNSIGNED NOT NULL,
    platform    VARCHAR(40) NOT NULL,
    url         VARCHAR(255) NOT NULL,
    FOREIGN KEY (employer_id) REFERENCES employer_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- JOBS
-- ─────────────────────────────────────────
CREATE TABLE jobs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id      INT UNSIGNED NOT NULL,
    title            VARCHAR(180) NOT NULL,
    slug             VARCHAR(220) NOT NULL,
    role             VARCHAR(100) DEFAULT NULL,
    tags             VARCHAR(255) DEFAULT NULL,
    industry         VARCHAR(100) DEFAULT NULL,
    min_salary       INT UNSIGNED DEFAULT 0,
    max_salary       INT UNSIGNED DEFAULT 0,
    salary_type      ENUM('Monthly','Hourly','Yearly') DEFAULT 'Monthly',
    currency         VARCHAR(10) DEFAULT 'JOD',
    education        VARCHAR(80) DEFAULT NULL,
    experience       TINYINT UNSIGNED DEFAULT 0,
    job_type         ENUM('Full Time','Part Time','Internship','Temporary','Contract','Freelance') DEFAULT 'Full Time',
    vacancies        TINYINT UNSIGNED DEFAULT 1,
    expiry_date      DATE DEFAULT NULL,
    job_level        VARCHAR(60) DEFAULT NULL,
    location         VARCHAR(100) DEFAULT NULL,
    is_remote        TINYINT(1) DEFAULT 0,
    description      LONGTEXT DEFAULT NULL,
    requirements     TEXT DEFAULT NULL,
    apply_on         ENUM('jobpilot','external','email') DEFAULT 'jobpilot',
    apply_url        VARCHAR(255) DEFAULT NULL,
    is_featured      TINYINT(1) DEFAULT 0,
    is_urgent        TINYINT(1) DEFAULT 0,
    status           ENUM('active','expired','draft') DEFAULT 'active',
    source           VARCHAR(30) DEFAULT 'manual',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employer_profiles(id) ON DELETE CASCADE,
    INDEX idx_title    (title),
    INDEX idx_status   (status),
    INDEX idx_job_type (job_type),
    INDEX idx_location (location),
    INDEX idx_employer (employer_id),
    FULLTEXT ft_search (title, tags, description)
) ENGINE=InnoDB;

CREATE TABLE job_benefits (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    name   VARCHAR(100) NOT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- APPLICATIONS
-- ─────────────────────────────────────────
CREATE TABLE applications (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id        INT UNSIGNED NOT NULL,
    candidate_id  INT UNSIGNED NOT NULL,
    cover_letter  TEXT DEFAULT NULL,
    cv_id         INT UNSIGNED DEFAULT NULL,
    status        ENUM('pending','shortlisted','rejected','hired') DEFAULT 'pending',
    applied_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application (job_id, candidate_id),
    FOREIGN KEY (job_id)       REFERENCES jobs(id)               ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id)  ON DELETE CASCADE,
    FOREIGN KEY (cv_id)        REFERENCES candidate_cvs(id)        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- SAVED JOBS (Candidate favourites)
-- ─────────────────────────────────────────
CREATE TABLE saved_jobs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT UNSIGNED NOT NULL,
    job_id       INT UNSIGNED NOT NULL,
    saved_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_job (candidate_id, job_id),
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id)       REFERENCES jobs(id)               ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- SAVED CANDIDATES (Employer bookmarks)
-- ─────────────────────────────────────────
CREATE TABLE saved_candidates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id  INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    saved_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_saved_candidate (employer_id, candidate_id),
    FOREIGN KEY (employer_id)  REFERENCES employer_profiles(id)  ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- JOB ALERTS
-- ─────────────────────────────────────────
CREATE TABLE job_alerts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id    INT UNSIGNED NOT NULL,
    alert_title     VARCHAR(180) DEFAULT NULL,
    keywords        VARCHAR(255) DEFAULT NULL,
    category        VARCHAR(100) DEFAULT NULL,
    location        VARCHAR(100) DEFAULT NULL,
    job_type        VARCHAR(60)  DEFAULT NULL,
    email_frequency ENUM('instant','daily','weekly') DEFAULT 'daily',
    is_active       TINYINT(1) DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- PLANS & BILLING
-- ─────────────────────────────────────────
CREATE TABLE plans (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(40)  NOT NULL UNIQUE,
    name         VARCHAR(80)  NOT NULL,
    price        DECIMAL(8,2) NOT NULL DEFAULT 0,
    billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
    active_jobs  TINYINT UNSIGNED DEFAULT 1,
    features     JSON DEFAULT NULL,
    is_active    TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE employer_subscriptions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id  INT UNSIGNED NOT NULL,
    plan_id      INT UNSIGNED NOT NULL,
    status       ENUM('active','cancelled','expired') DEFAULT 'active',
    starts_at    DATE NOT NULL,
    expires_at   DATE NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id)     REFERENCES plans(id)
) ENGINE=InnoDB;

CREATE TABLE invoices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id     INT UNSIGNED NOT NULL,
    subscription_id INT UNSIGNED DEFAULT NULL,
    invoice_no      VARCHAR(20) NOT NULL UNIQUE,
    plan_name       VARCHAR(80) NOT NULL,
    amount          DECIMAL(8,2) NOT NULL,
    currency        VARCHAR(10) DEFAULT 'USD',
    status          ENUM('paid','pending','failed') DEFAULT 'paid',
    invoice_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employer_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- BLOG
-- ─────────────────────────────────────────
CREATE TABLE blog_posts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    slug         VARCHAR(280) NOT NULL UNIQUE,
    excerpt      TEXT DEFAULT NULL,
    content      LONGTEXT DEFAULT NULL,
    author_id    INT UNSIGNED DEFAULT NULL,
    category     VARCHAR(80) DEFAULT NULL,
    image        VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    status       ENUM('published','draft') DEFAULT 'published',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- CONTACT MESSAGES
-- ─────────────────────────────────────────
CREATE TABLE contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(180) NOT NULL,
    subject    VARCHAR(255) NOT NULL,
    message    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────
CREATE TABLE notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(60) NOT NULL,
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) DEFAULT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- ML RECOMMENDATIONS (pre-computed offline)
-- ─────────────────────────────────────────
CREATE TABLE recommendations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id    INT UNSIGNED NOT NULL,
    job_id          INT UNSIGNED NOT NULL,
    score           FLOAT NOT NULL DEFAULT 0,
    generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rec (candidate_id, job_id),
    FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id)       REFERENCES jobs(id)               ON DELETE CASCADE,
    INDEX idx_candidate_score (candidate_id, score)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- DEFAULT PLANS SEED
-- ─────────────────────────────────────────
INSERT INTO plans (slug, name, price, billing_cycle, active_jobs, features) VALUES
('basic',    'Basic',    19.00, 'monthly', 1, '{"urgent_featured":false,"highlights_job":false,"highlights_with_colors":false,"candidates_access":0,"resume_visibility_days":30,"critical_support":false}'),
('standard', 'Standard', 39.00, 'monthly', 5, '{"urgent_featured":true,"highlights_job":true,"highlights_with_colors":true,"candidates_access":10,"resume_visibility_days":60,"critical_support":true}'),
('premium',  'Premium',  59.00, 'monthly', 8, '{"urgent_featured":true,"highlights_job":true,"highlights_with_colors":true,"candidates_access":20,"resume_visibility_days":90,"critical_support":true}');
