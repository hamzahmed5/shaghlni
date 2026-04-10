# Jobpilot – AI-Powered Job Matching Platform

A full-stack web application for job matching in Jordan, built with PHP, MySQL, and vanilla JavaScript.

---

## Tech Stack

| Layer      | Technology                                      |
|------------|------------------------------------------------|
| Frontend   | HTML5, CSS3 (custom variables), JavaScript (ES6)|
| Backend    | PHP 8.x (PDO, sessions, CSRF, file uploads)    |
| Database   | MySQL 8.x                                      |
| ML Engine  | Python 3.10+ (SentenceTransformer, FAISS, etc.)|

---

## Project Structure

```
jobpilot/
├── assets/
│   ├── css/
│   │   ├── main.css           # Global styles
│   │   ├── auth.css           # Auth pages styles
│   │   └── dashboard.css      # Dashboard styles
│   ├── js/
│   │   ├── main.js            # Public site JS
│   │   └── dashboard.js       # Dashboard JS
│   └── uploads/               # User uploads (auto-created)
│       ├── avatars/
│       ├── logos/
│       ├── banners/
│       ├── cvs/
│       └── resumes/
├── config/
│   ├── config.php             # Site constants, DB credentials
│   └── db.php                 # PDO singleton
├── database/
│   ├── schema.sql             # Full MySQL schema
│   └── seed.php               # CSV import + seed script
├── includes/
│   ├── functions.php          # Core PHP utilities
│   ├── header.php             # Public site header
│   ├── footer.php             # Public site footer
│   ├── header_dashboard.php   # Dashboard header
│   ├── sidebar_candidate.php  # Candidate sidebar
│   └── sidebar_employer.php   # Employer sidebar
├── auth/                      # Login, Register, Verify, Password Reset
├── jobs/                      # find-job.php, job-detail.php
├── employers/                 # browse.php, employer-detail.php
├── candidates/                # browse.php, candidate-detail.php
├── candidate/                 # Candidate dashboard + settings/
├── employer/                  # Employer dashboard + settings/
├── pages/                     # about, contact, faq, blog, pricing, terms, privacy
├── actions/                   # AJAX handlers
├── ml/                        # Python ML pipeline
│   ├── recommend.py
│   └── requirements.txt
├── index.php                  # Homepage
├── 404.php                    # 404 error page
└── .htaccess                  # Apache configuration
```

---

## Setup Instructions

### 1. Requirements

- PHP 8.0+
- MySQL 8.0+
- Apache with `mod_rewrite` enabled
- Python 3.10+ (for ML pipeline)

### 2. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE jobpilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p jobpilot < database/schema.sql
```

### 3. Configure

Edit `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jobpilot');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('SITE_URL', 'http://localhost/jobpilot');
```

### 4. Create Upload Directories

```bash
mkdir -p assets/uploads/avatars assets/uploads/logos assets/uploads/banners
mkdir -p assets/uploads/cvs assets/uploads/resumes
chmod 755 assets/uploads/
```

### 5. Seed Data (Optional)

Place the CSV files in `The Data/`:
- `business_freelance_dataset.csv`
- `youth_coworkers_dataset.csv`

Then run:
```bash
php database/seed.php
```

This imports ~500 sample candidates and employers with basic recommendations.

### 6. Apache Virtual Host

```apache
<VirtualHost *:80>
    ServerName jobpilot.local
    DocumentRoot /path/to/The Final Project/jobpilot
    <Directory /path/to/The Final Project/jobpilot>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Or simply access via XAMPP: `http://localhost/jobpilot/`

---

## ML Pipeline

### Architecture

```
CSV Data → Preprocessing → TF-IDF skill weighting
                        → SentenceTransformer (all-mpnet-base-v2, 768-dim)
                        → FAISS ANN retrieval (top-50 per candidate)
                        → CrossEncoder reranking
                        → Stacked Ensemble score
                        → MySQL recommendations table
                        → PHP reads pre-computed scores
```

### Run ML Recommendations

```bash
cd ml
pip install -r requirements.txt

# Set DB credentials
export JOBPILOT_DB_HOST=localhost
export JOBPILOT_DB_NAME=jobpilot
export JOBPILOT_DB_USER=root
export JOBPILOT_DB_PASS=your_password

python recommend.py
```

**Recommended:** Run via cron daily at midnight:
```
0 0 * * * cd /path/to/jobpilot && python ml/recommend.py >> /var/log/jobpilot_ml.log 2>&1
```

### Fallback

If the ML pipeline hasn't been run yet (no data in `recommendations` table), the PHP `get_recommended_jobs()` function automatically falls back to SQL-based matching using field, location, and experience level.

---

## Color Palette

| Name      | Hex       |
|-----------|-----------|
| Primary   | `#0A65CC` |
| Text dark | `#18191C` |
| Text body | `#474C54` |
| Text muted| `#767F8C` |
| Border    | `#E4E5E8` |
| Background| `#F1F2F4` |
| Success   | `#0BA02C` |
| Danger    | `#E05151` |
| Warning   | `#FF6636` |
| Purple    | `#7C5FE0` |

---

## User Roles

| Role      | Capabilities                                                      |
|-----------|-------------------------------------------------------------------|
| Candidate | Browse jobs, apply, save jobs, set alerts, manage profile/CV      |
| Employer  | Post jobs, view applications (list/kanban), save candidates, billing|

---

## Key Features

- CSRF protection on all forms
- Secure password hashing (bcrypt)
- Email verification on registration
- File upload validation (type + size)
- AJAX bookmarking and status updates
- Responsive design (mobile-first breakpoints)
- AI job recommendations (offline ML + SQL fallback)
- Kanban application board for employers
- Subscription plans with invoice history

---

## Credits

Built for the Jobpilot project – AI-powered job matching for Jordan.
ML model: `sentence-transformers/all-mpnet-base-v2`
