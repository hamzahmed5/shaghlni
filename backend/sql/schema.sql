CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('candidate','employer') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE candidate_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    person_code VARCHAR(50) NULL UNIQUE,
    age INT NULL,
    education_level VARCHAR(100) NULL,
    years_of_experience INT DEFAULT 0,
    primary_skills TEXT NULL,
    employment_status VARCHAR(100) NULL,
    preferred_job_field VARCHAR(150) NULL,
    salary_expectation_jod DECIMAL(10,2) NULL,
    location VARCHAR(150) NULL,
    willing_to_relocate ENUM('Yes','No') NULL,
    headline VARCHAR(255) NULL,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE employer_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    business_code VARCHAR(50) NULL UNIQUE,
    company_name VARCHAR(191) NOT NULL,
    business_type VARCHAR(100) NULL,
    industry VARCHAR(150) NULL,
    about TEXT NULL,
    location VARCHAR(150) NULL,
    website VARCHAR(255) NULL,
    logo_path VARCHAR(255) NULL,
    company_size VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employer_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_profile_id BIGINT UNSIGNED NOT NULL,
    job_title VARCHAR(191) NOT NULL,
    industry VARCHAR(150) NULL,
    description TEXT NULL,
    requirements TEXT NULL,
    required_experience_years INT DEFAULT 0,
    required_skills TEXT NULL,
    salary_min_jod DECIMAL(10,2) NULL,
    salary_max_jod DECIMAL(10,2) NULL,
    job_type VARCHAR(100) NULL,
    location VARCHAR(150) NULL,
    status ENUM('draft','active','closed') DEFAULT 'active',
    application_deadline DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_job_employer FOREIGN KEY (employer_profile_id) REFERENCES employer_profiles(id) ON DELETE CASCADE
);

CREATE TABLE cvs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cv_candidate FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
);

CREATE TABLE applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    candidate_profile_id BIGINT UNSIGNED NOT NULL,
    cv_id BIGINT UNSIGNED NULL,
    cover_letter TEXT NULL,
    status ENUM('pending','reviewing','shortlisted','rejected','hired') DEFAULT 'pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_candidate_job (job_id, candidate_profile_id),
    CONSTRAINT fk_application_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_candidate FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_cv FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE SET NULL
);

CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (candidate_profile_id, job_id),
    CONSTRAINT fk_favorite_candidate FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorite_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

CREATE TABLE job_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    keywords VARCHAR(255) NULL,
    location VARCHAR(150) NULL,
    job_type VARCHAR(100) NULL,
    frequency VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alert_candidate FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE
);

CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price_jod DECIMAL(10,2) DEFAULT 0,
    job_post_limit INT DEFAULT 0,
    candidate_view_limit INT DEFAULT 0,
    features TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE billing_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_profile_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    amount_jod DECIMAL(10,2) NOT NULL,
    status ENUM('paid','pending','failed') DEFAULT 'pending',
    invoice_number VARCHAR(100) NULL UNIQUE,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_billing_employer FOREIGN KEY (employer_profile_id) REFERENCES employer_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_billing_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

CREATE TABLE recommendations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_profile_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    score DECIMAL(8,4) NOT NULL,
    reason_text VARCHAR(255) NULL,
    generated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_recommendation (candidate_profile_id, job_id),
    CONSTRAINT fk_reco_candidate FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_reco_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);
