<?php
/**
 * Jobpilot Expanded Seed Script
 * Run: php database/seed.php
 * Populates: 15 employers, 35 candidates, 75+ jobs across all categories and cities
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$db = get_db();
$db->exec('SET FOREIGN_KEY_CHECKS=0');

echo "[INFO] Clearing old seed data...\n";
$tables = [
    'applications', 'saved_jobs', 'saved_candidates', 'candidate_skills',
    'candidate_social_links', 'employer_social_links', 'job_benefits', 'job_alerts',
    'notifications', 'candidate_cvs', 'recommendations', 'blog_posts', 'jobs',
    'candidate_profiles', 'employer_profiles', 'employer_subscriptions', 'plans', 'users',
];
foreach ($tables as $t) {
    $db->exec("DELETE FROM `$t`");
    $db->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
}
$db->exec('SET FOREIGN_KEY_CHECKS=1');
echo "[INFO] Cleared.\n";

$hash = fn($p) => password_hash($p, PASSWORD_BCRYPT);

// ─── EMPLOYERS (15) ───────────────────────────────────────────────────────────
// [full_name, username, email, company_name, industry, city, size, about]
$employers_data = [
    ['Ahmad Al-Rashid',  'ahmad',    'ahmad@techcorp.jo',      'TechCorp Jordan',          'IT',                'Amman', 120, 'Leading software house in Jordan specializing in enterprise solutions and fintech applications.'],
    ['Sara Khalil',      'sara',     'sara@designhub.jo',      'DesignHub MENA',            'Design',            'Amman', 45,  'Award-winning creative agency serving top brands across the MENA region since 2015.'],
    ['Omar Mansour',     'omar',     'omar@financeplus.jo',    'FinancePlus',               'Finance',           'Irbid', 80,  'Trusted financial services and accounting firm operating in Jordan and the Gulf region.'],
    ['Lina Haddad',      'lina',     'lina@hcjordan.jo',       'HealthCare Jordan',         'Health Care',       'Amman', 200, 'Network of private clinics, labs, and diagnostic centers across Jordan since 2008.'],
    ['Khalid Nasser',    'khalid',   'khalid@buildpro.jo',     'BuildPro Construction',     'Engineering',       'Zarqa', 350, 'Major contractor for residential, commercial, and infrastructure projects since 2005.'],
    ['Tariq Obeidat',    'tariq',    'tariq@royalbank.jo',     'Royal Bank Jordan',         'Finance',           'Amman', 900, 'Full-service commercial bank offering retail, corporate, and investment banking across Jordan.'],
    ['Reem Bsharat',     'reem',     'reem@edunation.jo',      'EduNation Jordan',          'Education',         'Amman', 130, 'Innovative EdTech platform connecting students with quality tutors and online courses in Arabic.'],
    ['Faris Suleiman',   'faris',    'faris@mawdoo3.jo',       'Mawdoo3',                   'Media & Communication', 'Amman', 180, 'Largest Arabic content platform on the web, reaching 80 million monthly readers across the Arab world.'],
    ['Nadia Qasim',      'nadia',    'nadia@zain.jo',          'Zain Jordan',               'IT',                'Amman', 1500,'Leading telecom operator in Jordan providing mobile, broadband, and enterprise digital services.'],
    ['Sameer Khalaf',    'sameer',   'sameer@petrahotels.jo',  'Petra Hotels & Resorts',    'Administration',    'Aqaba', 420, 'Luxury hospitality group operating five-star properties in Aqaba, Petra, and Amman.'],
    ['Mais Habibi',      'mais',     'mais@alfuttaim.jo',      'Al-Futtaim Jordan',         'Sales',             'Amman', 600, 'Regional retail and automotive conglomerate with flagship brands in Jordan since 1995.'],
    ['Yousef Daoud',     'yousef',   'yousef@jordanenergy.jo', 'Jordan Energy Solutions',   'Engineering',       'Amman', 95,  'Renewable energy and solar power systems provider serving public and private sector clients.'],
    ['Hala Sabbagh',     'hala',     'hala@legaledge.jo',      'LegalEdge Jordan',          'Legal',             'Amman', 35,  'Full-service law firm specializing in corporate, commercial, and IP law for Jordanian and international clients.'],
    ['Bilal Khouri',     'bilal',    'bilal@medexpress.jo',    'MedExpress Clinics',        'Health Care',       'Zarqa', 160, 'Chain of outpatient clinics providing affordable healthcare services across Zarqa and surroundings.'],
    ['Sana Taha',        'sana',     'sana@greenlogistics.jo', 'Green Logistics Jordan',    'Engineering',       'Amman', 210, 'Modern supply chain and logistics company serving retail, manufacturing, and e-commerce clients in Jordan.'],
];

$employer_user_ids    = [];
$employer_profile_ids = [];

foreach ($employers_data as [$name, $uname, $email, $company, $industry, $city, $size, $about]) {
    $db->prepare("INSERT INTO users (full_name,username,email,password,role,is_verified,created_at) VALUES (?,?,?,?,?,1,DATE_SUB(NOW(), INTERVAL ? DAY))")
       ->execute([$name, $uname, $email, $hash('Password1!'), 'employer', rand(30, 365)]);
    $uid = (int)$db->lastInsertId();
    $employer_user_ids[] = $uid;

    $db->prepare("INSERT INTO employer_profiles (user_id,company_name,industry,company_size,description,city,website,setup_complete) VALUES (?,?,?,?,?,?,?,1)")
       ->execute([$uid, $company, $industry, $size, $about, $city, 'https://www.example.jo']);
    $employer_profile_ids[] = (int)$db->lastInsertId();
}
echo "[INFO] Created " . count($employers_data) . " employers.\n";

// ─── CANDIDATES (35) ──────────────────────────────────────────────────────────
// [full_name, username, email, position, field, city, exp_level, edu, sal_min, sal_max, bio, skills[]]
$candidates_data = [
    // IT
    ['Rami Yousef',       'rami',      'rami@mail.com',      'Senior PHP Developer',       'IT',                'Amman', 'Senior Level', 'Bachelor', 1200, 1800, 'Backend developer with 6 years of experience in PHP, Laravel, and MySQL. Passionate about scalable APIs and clean code.', ['PHP', 'Laravel', 'MySQL', 'REST API', 'Git', 'Docker']],
    ['Zaid Musa',         'zaid',      'zaid@mail.com',      'Full Stack Developer',       'IT',                'Zarqa', 'Mid Level',    'Bachelor', 1000, 1500, 'MERN stack developer building modern web applications. Open to remote and hybrid work opportunities.', ['JavaScript', 'React', 'Node.js', 'MongoDB', 'Express', 'TypeScript']],
    ['Karim Salah',       'karimsal',  'karim.s@mail.com',   'DevOps Engineer',            'IT',                'Amman', 'Mid Level',    'Bachelor', 1100, 1600, 'Infrastructure engineer focused on CI/CD, containerization, and cloud migrations across AWS and Azure.', ['Docker', 'Kubernetes', 'AWS', 'CI/CD', 'Linux', 'Terraform']],
    ['Rania Barakat',     'rania',     'rania@mail.com',     'Mobile Developer (Flutter)', 'IT',                'Amman', 'Mid Level',    'Bachelor', 900,  1400, 'Cross-platform mobile developer with a portfolio of 8 published apps on iOS and Android.', ['Flutter', 'Dart', 'Firebase', 'REST APIs', 'Git', 'Android']],
    ['Jad Nassar',        'jadnas',    'jad.n@mail.com',     'Cybersecurity Analyst',      'IT',                'Amman', 'Mid Level',    'Master',   1200, 1700, 'Information security professional with 4 years specializing in penetration testing and vulnerability assessment.', ['Penetration Testing', 'SIEM', 'Python', 'Networking', 'OWASP', 'Linux']],
    ['Dana Rimawi',       'dana',      'dana@mail.com',      'Data Scientist',             'IT',                'Amman', 'Senior Level', 'Master',   1400, 2000, 'Applied ML engineer with experience deploying NLP and recommendation models in production. 5 years in data science.', ['Python', 'TensorFlow', 'SQL', 'Pandas', 'Scikit-learn', 'Spark']],
    ['Fadi Khalil',       'fadikh',    'fadi.kh@mail.com',   'React Developer',            'IT',                'Irbid', 'Entry Level',  'Bachelor', 600,  900,  'Front-end developer passionate about building responsive, accessible web UIs with React and Tailwind CSS.', ['React', 'JavaScript', 'CSS', 'HTML', 'Git', 'Figma']],
    // Finance
    ['Bassam Qasim',      'bassam',    'bassam@mail.com',    'Financial Analyst',          'Finance',           'Irbid', 'Mid Level',    'Master',   900,  1400, 'CFA Level II candidate with 4 years in financial modeling, budgeting, and investment analysis for regional funds.', ['Excel', 'Financial Modeling', 'SAP', 'Budgeting', 'PowerBI', 'Python']],
    ['Nour Alami',        'nouralami', 'nour.a@mail.com',    'Risk Analyst',               'Finance',           'Amman', 'Mid Level',    'Bachelor', 950,  1350, 'Banking professional with 3 years in credit risk assessment and portfolio analysis for a Jordanian commercial bank.', ['Risk Assessment', 'Excel', 'SQL', 'Basel III', 'PowerBI', 'Bloomberg']],
    ['Tarek Mansour',     'tarekm',    'tarek.m@mail.com',   'Investment Analyst',         'Finance',           'Amman', 'Entry Level',  'Bachelor', 700,  1000, 'Recent finance graduate with internship experience at ASE and strong quantitative and research skills.', ['Excel', 'Financial Modeling', 'Bloomberg', 'Research', 'PowerPoint']],
    // Accounting
    ['Samir Awad',        'samiraw',   'samir.a@mail.com',   'Senior Accountant',          'Accounting',        'Amman', 'Senior Level', 'Bachelor', 1000, 1400, 'CPA-certified accountant with 7 years managing financial statements, audits, and tax compliance for mid-size firms.', ['IFRS', 'ERP', 'Excel', 'Auditing', 'Tax Compliance', 'SAP']],
    ['Lara Shahin',       'laras',     'lara.s@mail.com',    'Payroll Specialist',         'Accounting',        'Amman', 'Mid Level',    'Bachelor', 700,  1000, 'Experienced payroll professional ensuring accurate monthly processing for 400+ employee organizations.', ['Payroll Systems', 'Excel', 'HRMS', 'Labor Law', 'Accounting']],
    // Design
    ['Nour Saleh',        'nour',      'nour@mail.com',      'UI/UX Designer',             'Design',            'Amman', 'Mid Level',    'Bachelor', 800,  1200, 'Creative designer focused on user-centered experiences for web and mobile. Proficient in Figma and design systems.', ['Figma', 'Adobe XD', 'UI Design', 'CSS', 'Prototyping', 'Sketch']],
    ['Leen Barakat',      'leen',      'leen@mail.com',      'Graphic Designer',           'Design',            'Amman', 'Entry Level',  'Bachelor', 500,  750,  'Visual communicator with a strong portfolio in brand identity, print, and digital design for SMEs.', ['Adobe Illustrator', 'Photoshop', 'InDesign', 'Brand Identity', 'Canva']],
    ['Anas Turki',        'anasturki', 'anas.t@mail.com',    'Motion Designer',            'Design',            'Amman', 'Mid Level',    'Bachelor', 800,  1200, 'After Effects specialist creating animations for social media, apps, and TV campaigns across the Arab world.', ['After Effects', 'Premiere Pro', 'Animation', 'Storyboarding', 'Illustrator']],
    // Marketing
    ['Hana Tawfiq',       'hana',      'hana@mail.com',      'Marketing Manager',          'Marketing',         'Amman', 'Senior Level', 'Bachelor', 1100, 1600, 'Digital marketing expert with 7 years growing brands in MENA. Specialist in SEO, SEM, and performance campaigns.', ['SEO', 'Google Ads', 'Social Media', 'Content Strategy', 'Analytics', 'Email Marketing']],
    ['Yara Naseem',       'yara',      'yara@mail.com',      'Content Strategist',         'Marketing',         'Amman', 'Mid Level',    'Bachelor', 700,  1000, 'Bilingual content professional creating Arabic and English digital campaigns that drive engagement and conversions.', ['Content Writing', 'SEO', 'Social Media', 'Copywriting', 'Arabic', 'WordPress']],
    ['Majd Obeidat',      'majdob',    'majd.o@mail.com',    'Performance Marketing Analyst', 'Marketing',      'Amman', 'Mid Level',    'Bachelor', 750,  1100, 'Data-driven marketer managing JOD 50K+ monthly ad budgets on Meta and Google with proven ROAS results.', ['Google Ads', 'Meta Ads', 'Analytics', 'A/B Testing', 'Excel', 'Looker Studio']],
    // HR
    ['Maya Ibrahim',      'maya',      'maya@mail.com',      'HR Specialist',              'HR',                'Amman', 'Entry Level',  'Bachelor', 500,  800,  'Recent HR graduate with internship experience in recruitment, onboarding, and employee engagement programs.', ['Recruitment', 'HRMS', 'Employee Relations', 'MS Office', 'Communication']],
    ['Rana Tabbaa',       'ranat',     'rana.t@mail.com',    'HR Business Partner',        'HR',                'Amman', 'Senior Level', 'Master',   1200, 1700, 'Strategic HRBP with 8 years partnering with business leaders to drive talent strategy, engagement, and performance.', ['Talent Management', 'OD', 'HRIS', 'Coaching', 'Labor Law', 'Analytics']],
    ['Samer Hijazi',      'samerh',    'samer.h@mail.com',   'Recruitment Specialist',     'HR',                'Amman', 'Mid Level',    'Bachelor', 700,  1000, 'Talent acquisition specialist with 3 years sourcing tech and finance talent for Jordanian and GCC firms.', ['LinkedIn Recruiter', 'ATS', 'Interviewing', 'Boolean Search', 'HR Metrics']],
    // Engineering
    ['Murad Bawab',       'muradb',    'murad.b@mail.com',   'Civil Engineer',             'Engineering',       'Zarqa', 'Mid Level',    'Bachelor', 900,  1300, 'Site engineer with 4 years overseeing residential and infrastructure construction in Zarqa and East Amman.', ['AutoCAD', 'MS Project', 'Soil Testing', 'Construction Management', 'Revit']],
    ['Tariq Khalil',      'tariqkh',   'tariq.kh@mail.com',  'Electrical Engineer',        'Engineering',       'Amman', 'Mid Level',    'Bachelor', 950,  1350, 'Electrical systems engineer with 3 years on industrial and commercial projects. PLC and SCADA certified.', ['PLC', 'SCADA', 'AutoCAD Electrical', 'ETAP', 'Project Management']],
    ['Sari Haddad',       'sarihad',   'sari.h@mail.com',    'Mechanical Engineer',        'Engineering',       'Amman', 'Entry Level',  'Bachelor', 650,  950,  'Mechanical engineering graduate with hands-on experience in HVAC design and maintenance for commercial buildings.', ['HVAC', 'AutoCAD', 'SolidWorks', 'ASHRAE', 'Maintenance']],
    // Health Care
    ['Rim Shalhoub',      'rimshal',   'rim.s@mail.com',     'Medical Lab Specialist',     'Health Care',       'Amman', 'Mid Level',    'Bachelor', 750,  1100, 'Clinical lab professional with 4 years in hematology, microbiology, and PCR testing in Jordanian hospitals.', ['PCR', 'Hematology', 'Microbiology', 'LIMS', 'Quality Control']],
    ['Ahmad Salti',       'ahmadsal',  'ahmad.sal@mail.com', 'Pharmacist',                 'Health Care',       'Amman', 'Entry Level',  'Bachelor', 700,  1000, 'Licensed pharmacist seeking clinical or retail position with a strong background in patient counseling and medication management.', ['Dispensing', 'Drug Interactions', 'Patient Counseling', 'Inventory', 'PharmD']],
    ['Lina Atallah',      'linaatal',  'lina.a@mail.com',    'Physiotherapist',            'Health Care',       'Irbid', 'Mid Level',    'Bachelor', 700,  1050, 'Registered physiotherapist with 3 years treating musculoskeletal and neurological conditions in outpatient clinics.', ['Manual Therapy', 'Exercise Prescription', 'Electrotherapy', 'Patient Assessment']],
    // Legal
    ['Kareem Suleiman',   'kareems',   'kareem.s@mail.com',  'Corporate Lawyer',           'Legal',             'Amman', 'Senior Level', 'Master',   1400, 2000, 'Jordanian Bar Association member with 6 years in corporate transactions, M&A, and commercial contract drafting.', ['Corporate Law', 'Contract Drafting', 'M&A', 'Due Diligence', 'Arbitration']],
    ['Hiam Masri',        'hiamm',     'hiam.m@mail.com',    'Legal Counsel',              'Legal',             'Amman', 'Mid Level',    'Bachelor', 900,  1400, 'In-house legal professional with 4 years advising on employment law, regulatory compliance, and IP matters.', ['Employment Law', 'IP', 'Compliance', 'Contract Review', 'Research']],
    // Education
    ['Suha Hamdan',       'suhaham',   'suha.h@mail.com',    'English Language Trainer',   'Education',         'Amman', 'Mid Level',    'Bachelor', 600,  900,  'CELTA-certified English trainer with 5 years teaching general, business, and IELTS preparation courses.', ['CELTA', 'IELTS Prep', 'Business English', 'Lesson Planning', 'E-learning']],
    ['Osama Kurdi',       'osamak',    'osama.k@mail.com',   'Instructional Designer',     'Education',         'Amman', 'Mid Level',    'Master',   850,  1250, 'EdTech professional designing engaging online courses and blended learning programs for corporate and academic clients.', ['Articulate Storyline', 'LMS', 'Instructional Design', 'E-learning', 'Curriculum']],
    // Sales
    ['Wafa Rimawi',       'wafarim',   'wafa.r@mail.com',    'B2B Sales Executive',        'Sales',             'Amman', 'Mid Level',    'Bachelor', 800,  1400, 'Results-driven sales professional with 4 years closing enterprise deals for SaaS and tech companies in Jordan.', ['CRM', 'B2B Sales', 'Negotiation', 'Lead Generation', 'Salesforce', 'Presentation']],
    ['Nizar Khatib',      'nizarkh',   'nizar.k@mail.com',   'Sales Manager',              'Sales',             'Amman', 'Senior Level', 'Bachelor', 1200, 1800, 'Sales leader who built and managed a 12-person team achieving 140% of annual target for a regional FMCG company.', ['Team Leadership', 'Sales Strategy', 'CRM', 'KPI Management', 'Retail', 'FMCG']],
    // Customer Service
    ['Wissam Haddad',     'wissamh',   'wissam.h@mail.com',  'Customer Service Team Lead', 'Customer Service',  'Amman', 'Mid Level',    'Bachelor', 700,  1000, 'CX professional with 5 years managing multi-channel support teams (phone, chat, email) in banking and telecom.', ['Customer Support', 'CRM', 'Team Leadership', 'SLA Management', 'Arabic', 'English']],
    ['Abir Nablsi',       'abirnab',   'abir.n@mail.com',    'Customer Success Specialist','Customer Service',  'Amman', 'Entry Level',  'Bachelor', 500,  750,  'Proactive problem-solver passionate about delivering excellent customer experiences in fast-paced digital environments.', ['Communication', 'CRM', 'Problem Solving', 'Zendesk', 'Arabic', 'English']],
];

$candidate_user_ids    = [];
$candidate_profile_ids = [];

foreach ($candidates_data as [$name, $uname, $email, $position, $field, $city, $exp, $edu, $smin, $smax, $bio, $skills]) {
    $db->prepare("INSERT INTO users (full_name,username,email,password,role,is_verified,created_at) VALUES (?,?,?,?,?,1,DATE_SUB(NOW(), INTERVAL ? DAY))")
       ->execute([$name, $uname, $email, $hash('Password1!'), 'candidate', rand(10, 200)]);
    $uid = (int)$db->lastInsertId();
    $candidate_user_ids[] = $uid;

    $db->prepare("INSERT INTO candidate_profiles (user_id,current_position,bio,preferred_field,city,experience_level,education_level,expected_salary_min,expected_salary_max,is_public) VALUES (?,?,?,?,?,?,?,?,?,1)")
       ->execute([$uid, $position, $bio, $field, $city, $exp, $edu, $smin, $smax]);
    $cpid = (int)$db->lastInsertId();
    $candidate_profile_ids[] = $cpid;

    foreach ($skills as $skill) {
        $db->prepare("INSERT INTO candidate_skills (candidate_profile_id, skill_name) VALUES (?,?)")->execute([$cpid, $skill]);
    }
}
echo "[INFO] Created " . count($candidates_data) . " candidates.\n";

// ─── JOBS (75+) ───────────────────────────────────────────────────────────────
// employer_user_ids index: 0=TechCorp, 1=DesignHub, 2=FinancePlus, 3=HealthCare Jordan,
//   4=BuildPro, 5=Royal Bank, 6=EduNation, 7=Mawdoo3, 8=Zain Jordan,
//   9=Petra Hotels, 10=Al-Futtaim, 11=Jordan Energy, 12=LegalEdge, 13=MedExpress, 14=Green Logistics
// Format: [emp_idx, title, category, job_type, location, exp_level, edu, sal_min, sal_max, vacancies, featured, description, requirements, responsibilities, benefits[]]

$jobs_data = [

    // ── TechCorp Jordan (IT) ──────────────────────────────────────────────────
    [0, 'Senior PHP Developer', 'IT', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1200, 1800, 2, 1,
     'TechCorp Jordan is seeking a Senior PHP Developer to lead backend development of our flagship SaaS products used by hundreds of enterprise clients.',
     '6+ years PHP experience. Laravel or Symfony expertise. Strong MySQL and Redis. REST API design. Git workflow.',
     'Build and maintain scalable backend services. Review code and mentor junior developers. Collaborate with frontend and DevOps teams.',
     ['Health insurance', 'Remote work options', 'Annual bonus', 'Training budget']],

    [0, 'DevOps Engineer', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 1100, 1600, 1, 0,
     'TechCorp Jordan is hiring a DevOps Engineer to strengthen our infrastructure pipeline and ensure 99.9% uptime for client-facing services.',
     'Docker, Kubernetes, AWS or Azure. Linux systems administration. CI/CD pipelines. Monitoring with Grafana or Datadog.',
     'Manage CI/CD pipelines and container orchestration. Monitor production systems. Optimize cloud costs.',
     ['Health insurance', 'Flexible hours', 'Annual bonus']],

    [0, 'Mobile Developer (React Native)', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 1000, 1500, 1, 1,
     'TechCorp Jordan is building a new consumer mobile product and needs a React Native Developer to own the cross-platform experience.',
     '3+ years React Native. iOS and Android deployment. Redux or Zustand. REST API integration.',
     'Develop cross-platform apps. Integrate REST APIs and push notifications. Write unit tests and collaborate with QA.',
     ['Health insurance', 'Remote work', 'Latest MacBook']],

    [0, 'Data Engineer', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 1100, 1600, 1, 0,
     'We are building a modern data platform and need a Data Engineer to design pipelines that power our analytics and ML systems.',
     'Python, SQL, Apache Airflow or similar. Cloud data warehouses (BigQuery, Redshift). ETL pipeline experience.',
     'Build and maintain data pipelines. Collaborate with data scientists. Ensure data quality and governance.',
     ['Health insurance', 'Flexible schedule', 'Stock options']],

    [0, 'QA Engineer', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 900, 1300, 2, 0,
     'TechCorp Jordan is looking for a QA Engineer to ensure the quality of our web and mobile products through automated and manual testing.',
     '3+ years QA experience. Selenium or Cypress. API testing (Postman). Familiarity with Agile/Scrum.',
     'Write and maintain automated test suites. Report and track bugs. Participate in sprint reviews.',
     ['Health insurance', 'Remote-friendly', 'Training budget']],

    [0, 'UI/UX Designer (Tech)', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 900, 1300, 1, 0,
     'Join TechCorp Jordan\'s product team as a UI/UX Designer, shaping the experience of enterprise SaaS tools used by thousands of users daily.',
     'Figma expertise. 3+ years in product design. Understanding of design systems. User research experience.',
     'Design wireframes, prototypes, and UI assets. Conduct user testing. Maintain and evolve the design system.',
     ['Health insurance', 'MacBook Pro', 'Creative environment']],

    [0, 'Junior Backend Developer', 'IT', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 600, 900, 3, 0,
     'TechCorp Jordan is growing its engineering team and seeking motivated junior backend developers ready to learn and contribute.',
     'PHP basics or Node.js. Understanding of databases. Git proficiency. Strong learning attitude.',
     'Assist in feature development. Write unit tests. Participate in code reviews and daily standups.',
     ['Health insurance', 'Mentorship program', 'Training budget']],

    // ── DesignHub MENA (Design) ───────────────────────────────────────────────
    [1, 'Senior UI/UX Designer', 'Design', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1100, 1600, 1, 1,
     'DesignHub MENA seeks a Senior UI/UX Designer to lead design on major regional brand projects and mentor a growing design team.',
     '5+ years UI/UX experience. Expert in Figma. Strong portfolio. HTML/CSS understanding a plus. Arabic-speaking preferred.',
     'Lead UX research and design sprints. Create wireframes, prototypes, and final assets. Present work to clients.',
     ['Creative environment', 'Flexible hours', 'MacBook provided', 'Annual bonus']],

    [1, 'Graphic Designer', 'Design', 'Part Time', 'Amman', 'Entry Level', 'Bachelor', 400, 650, 2, 0,
     'DesignHub MENA is looking for a part-time Graphic Designer to support client campaigns with high-quality visual content.',
     'Adobe Illustrator and Photoshop proficiency. Creative eye. Ability to meet tight deadlines.',
     'Design social media content, banners, brochures, and marketing materials. Follow brand guidelines.',
     ['Flexible schedule', 'Portfolio growth']],

    [1, 'Brand Strategist', 'Marketing', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1100, 1700, 1, 0,
     'DesignHub MENA is looking for a Brand Strategist to lead identity and positioning projects for top clients across the region.',
     '6+ years in branding or integrated marketing. Excellent presentation skills. MENA market experience preferred.',
     'Develop brand strategies. Lead client workshops. Oversee campaign execution. Mentor junior team members.',
     ['Creative office', 'Health insurance', 'Performance bonus']],

    [1, 'Motion Designer', 'Design', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1200, 1, 0,
     'We are looking for a Motion Designer to bring our client campaigns to life through stunning animations and video content.',
     'After Effects proficiency. Experience with Premiere Pro. Strong sense of timing and visual storytelling.',
     'Create animated content for social media, TV, and digital ads. Collaborate with creative directors.',
     ['Creative environment', 'Health insurance', 'Flexible hours']],

    [1, 'Copywriter', 'Marketing', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 700, 1000, 1, 0,
     'DesignHub MENA needs a bilingual Copywriter (Arabic/English) to craft compelling copy for brand campaigns and digital content.',
     'Excellent Arabic and English writing skills. 2+ years copywriting or content creation. Brand voice adaptability.',
     'Write copy for ads, websites, social media, and branding materials. Collaborate with designers and strategists.',
     ['Creative team', 'Health insurance', 'Portfolio growth']],

    // ── FinancePlus (Finance) ─────────────────────────────────────────────────
    [2, 'Financial Analyst', 'Finance', 'Full Time', 'Irbid', 'Mid Level', 'Master', 900, 1300, 1, 0,
     'FinancePlus is seeking a Financial Analyst to support our investment advisory division with modeling and market research.',
     'Bachelor in Finance or Accounting. CFA Level I or II preferred. Advanced Excel and financial modeling. IFRS knowledge.',
     'Prepare financial models, forecasts, and investment reports. Analyze market trends. Support client presentations.',
     ['Medical insurance', 'Performance bonus', 'Professional development']],

    [2, 'Senior Accountant', 'Accounting', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1000, 1400, 1, 0,
     'Join FinancePlus as a Senior Accountant managing key client accounts, financial reporting, and compliance.',
     '5+ years accounting experience. CPA or JCPA certification. ERP systems knowledge. Jordanian tax law.',
     'Oversee accounting operations. Prepare financial statements. Ensure compliance. Coordinate with auditors.',
     ['Health insurance', 'Annual bonus', 'Retirement plan']],

    [2, 'Risk Analyst', 'Finance', 'Full Time', 'Irbid', 'Mid Level', 'Bachelor', 950, 1350, 1, 0,
     'FinancePlus is expanding its risk management team and seeks a Risk Analyst to support credit and market risk assessments.',
     '2+ years in banking or financial services. Knowledge of Basel III. Excel and SQL proficiency. Analytical mindset.',
     'Conduct credit risk analysis. Monitor portfolio performance. Prepare risk reports for management and regulators.',
     ['Medical insurance', 'Performance bonus']],

    [2, 'Data Analyst', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 900, 1300, 1, 0,
     'FinancePlus seeks a Data Analyst to turn complex financial datasets into actionable business insights and executive dashboards.',
     'Python or R. SQL proficiency. PowerBI or Tableau. Strong analytical and communication skills.',
     'Build interactive dashboards. Analyze large datasets. Present findings to management. Maintain data pipelines.',
     ['Flexible hours', 'Health insurance', 'Bonus']],

    // ── HealthCare Jordan (Health Care) ───────────────────────────────────────
    [3, 'Medical Representative', 'Health Care', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 600, 900, 3, 0,
     'HealthCare Jordan is expanding its field sales team and needs Medical Representatives to promote our product portfolio.',
     'Science-related degree. Strong communication skills. Valid driving license. Medical background a plus.',
     'Visit hospitals, clinics, and pharmacies. Achieve sales targets. Build relationships with healthcare professionals.',
     ['Company car', 'Commission', 'Health insurance']],

    [3, 'HR Coordinator', 'HR', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 500, 750, 2, 0,
     'HealthCare Jordan seeks an HR Coordinator to support our growing human resources team across three clinics.',
     'Degree in HRM or related field. Good communication skills. HRMS experience a plus. Arabic and English fluency.',
     'Assist in recruitment and onboarding. Maintain HR records. Coordinate employee engagement. Support payroll.',
     ['Health insurance', 'Training opportunities']],

    [3, 'Clinical Lab Technician', 'Health Care', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 600, 850, 2, 0,
     'We are hiring Clinical Lab Technicians to support our diagnostic laboratories in Amman.',
     'Bachelor in Medical Lab Sciences or equivalent. MOH license preferred. Attention to detail.',
     'Perform laboratory tests. Maintain equipment and reagents. Report results accurately.',
     ['Health insurance', 'Medical benefits', 'Stable hours']],

    [3, 'Radiologist Technician', 'Health Care', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 700, 1050, 1, 0,
     'HealthCare Jordan is seeking a Radiologist Technician to operate imaging equipment in our diagnostics centers.',
     'Bachelor in Radiologic Technology. 2+ years experience. MOH licensed. Knowledge of MRI and CT protocols.',
     'Operate X-ray, CT, and MRI machines. Ensure patient safety. Collaborate with radiologists.',
     ['Health insurance', 'Medical benefits', 'Annual bonus']],

    [3, 'Pharmacist', 'Health Care', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 700, 1000, 3, 0,
     'Join HealthCare Jordan\'s growing pharmacy network as a licensed Pharmacist serving patients across our clinics.',
     'PharmD or equivalent Jordanian license. Strong patient communication. Arabic and English fluency.',
     'Dispense medications. Counsel patients on drug use. Manage inventory and expiry dates.',
     ['Health insurance', 'Medical benefits', 'Stable schedule']],

    // ── BuildPro Construction (Engineering) ───────────────────────────────────
    [4, 'Civil Engineer', 'Engineering', 'Full Time', 'Zarqa', 'Mid Level', 'Bachelor', 900, 1300, 2, 0,
     'BuildPro Construction is looking for Civil Engineers to join active residential and infrastructure projects in Zarqa.',
     'B.Sc. in Civil Engineering. 3+ years on-site experience. AutoCAD proficiency. Knowledge of Jordanian construction codes.',
     'Oversee construction activities. Review drawings. Ensure quality and safety. Coordinate with subcontractors.',
     ['Site allowance', 'Transportation', 'Health insurance', 'Annual leave']],

    [4, 'Site Safety Officer', 'Engineering', 'Full Time', 'Zarqa', 'Mid Level', 'Bachelor', 800, 1100, 1, 0,
     'BuildPro Construction requires a Site Safety Officer to enforce HSE standards across active construction sites.',
     'HSE certification (NEBOSH preferred). 3+ years in construction safety. Knowledge of Jordanian safety regulations.',
     'Conduct daily safety inspections. Investigate incidents. Train site workers on safety procedures.',
     ['Site allowance', 'Transportation', 'Health insurance']],

    [4, 'Procurement Officer', 'Administration', 'Full Time', 'Zarqa', 'Mid Level', 'Bachelor', 750, 1050, 1, 0,
     'BuildPro Construction needs a Procurement Officer to manage vendor relationships and material sourcing for large-scale projects.',
     '3+ years in procurement or supply chain. Negotiation skills. ERP knowledge. Engineering material background a plus.',
     'Source materials and subcontractors. Negotiate contracts. Track deliveries and manage supplier database.',
     ['Transportation', 'Health insurance', 'Annual bonus']],

    [4, 'AutoCAD Draftsman', 'Engineering', 'Full Time', 'Zarqa', 'Entry Level', 'Bachelor', 550, 800, 2, 0,
     'We are hiring AutoCAD Draftsmen to support our engineering team in preparing technical drawings and shop drawings.',
     'Diploma or Bachelor in Civil or Architectural Drafting. Proficiency in AutoCAD 2D. Attention to detail.',
     'Prepare technical and shop drawings under engineer supervision. Revise drawings based on field conditions.',
     ['Transportation', 'Health insurance', 'Stable hours']],

    // ── Royal Bank Jordan (Finance) ───────────────────────────────────────────
    [5, 'Relationship Manager (Corporate)', 'Finance', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1300, 1900, 2, 1,
     'Royal Bank Jordan is seeking experienced Corporate Relationship Managers to manage and grow a portfolio of business clients.',
     '5+ years corporate banking experience. Strong financial analysis. CFA or MBA preferred. Excellent communication.',
     'Manage corporate client portfolios. Structure loan facilities. Cross-sell banking products. Achieve revenue targets.',
     ['Health insurance', 'Performance bonus', 'Retirement plan', 'Company car']],

    [5, 'Retail Banking Officer', 'Finance', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 600, 850, 5, 0,
     'Royal Bank Jordan is expanding its branch network and needs Retail Banking Officers for front-line client service.',
     'Bachelor in Business, Finance, or related. Good communication in Arabic and English. Customer service orientation.',
     'Open accounts, process transactions, cross-sell products. Handle customer inquiries and complaints.',
     ['Health insurance', 'Annual bonus', 'Career growth']],

    [5, 'Credit Analyst', 'Finance', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 1000, 1400, 2, 0,
     'We are looking for Credit Analysts to evaluate loan applications and support the credit committee decision process.',
     '3+ years credit analysis. Knowledge of IFRS 9 and banking regulations. Strong Excel skills. CFA a plus.',
     'Analyze financial statements and credit requests. Prepare credit memos. Monitor portfolio risk indicators.',
     ['Health insurance', 'Performance bonus', 'Training']],

    [5, 'IT Support Specialist (Banking)', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1100, 1, 0,
     'Royal Bank Jordan needs an IT Support Specialist to ensure smooth day-to-day IT operations across branch and head office.',
     '2+ years IT support. Windows Server, Active Directory, networking basics. Banking systems exposure a plus.',
     'Provide first and second level support. Manage hardware/software inventory. Coordinate with vendors.',
     ['Health insurance', 'Annual bonus']],

    // ── EduNation Jordan (Education) ──────────────────────────────────────────
    [6, 'Online English Tutor', 'Education', 'Part Time', 'Remote', 'Mid Level', 'Bachelor', 400, 600, 10, 0,
     'EduNation Jordan is looking for qualified English tutors to teach live online sessions to students aged 8-18 across the Arab world.',
     'CELTA or equivalent teaching certification. Strong command of English. Online teaching experience preferred.',
     'Deliver one-on-one and group online sessions. Prepare lesson plans. Provide feedback reports to parents.',
     ['Flexible schedule', 'Work from home', 'Monthly bonuses']],

    [6, 'Curriculum Developer', 'Education', 'Full Time', 'Amman', 'Mid Level', 'Master', 900, 1300, 1, 0,
     'EduNation Jordan is seeking a Curriculum Developer to design engaging Arabic-language learning materials for K-12 students.',
     'Master in Education or Curriculum. 3+ years curriculum design. Familiarity with e-learning authoring tools.',
     'Develop course content, assessments, and learning objectives. Review and update existing curricula. Collaborate with tutors.',
     ['Health insurance', 'Training budget', 'Flexible hours']],

    [6, 'Student Support Advisor', 'Customer Service', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 500, 750, 3, 0,
     'EduNation Jordan needs Student Support Advisors to guide learners and parents through enrolment and ongoing learning journeys.',
     'Strong communication in Arabic and English. Customer-oriented mindset. CRM experience a plus.',
     'Handle enrolment enquiries. Support students and parents. Track attendance and escalate issues.',
     ['Health insurance', 'Training', 'Career growth']],

    // ── Mawdoo3 (Media) ───────────────────────────────────────────────────────
    [7, 'Senior Content Writer (Arabic)', 'Media & Communication', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1200, 2, 0,
     'Mawdoo3 is seeking Senior Arabic Content Writers to produce high-quality, SEO-optimized articles for our 80M monthly readers.',
     'Excellent Modern Standard Arabic writing. 3+ years content writing. SEO knowledge. Research skills.',
     'Write and edit long-form Arabic articles. Optimize content for search engines. Meet daily publishing targets.',
     ['Health insurance', 'Flexible hours', 'Annual bonus']],

    [7, 'SEO Specialist', 'Marketing', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1200, 1, 0,
     'Mawdoo3 is looking for an SEO Specialist to drive organic traffic growth for one of the largest Arabic websites in the world.',
     '3+ years SEO experience. Proficiency in SEMrush, Ahrefs, or Moz. Understanding of Arabic search behavior.',
     'Conduct keyword research. Optimize on-page content. Analyze traffic data. Develop link-building strategies.',
     ['Health insurance', 'Flexible hours', 'Annual bonus']],

    [7, 'Video Producer', 'Media & Communication', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1200, 1, 0,
     'Mawdoo3 is expanding into video content and needs a Video Producer to manage production of educational and lifestyle videos.',
     '3+ years video production. Premiere Pro expertise. Experience with YouTube optimization. Arabic narration a plus.',
     'Plan, film, and edit video content. Manage production timelines. Optimize content for YouTube.',
     ['Health insurance', 'Creative environment', 'Annual bonus']],

    [7, 'Social Media Manager', 'Marketing', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 750, 1100, 1, 0,
     'Mawdoo3 is looking for a Social Media Manager to grow and engage our Arabic-language communities across all platforms.',
     '3+ years social media management. Excellent Arabic writing. Experience with Meta and TikTok analytics.',
     'Create content calendars. Publish and monitor content. Respond to community comments. Report on KPIs.',
     ['Health insurance', 'Flexible schedule', 'Annual bonus']],

    // ── Zain Jordan (IT/Telecom) ───────────────────────────────────────────────
    [8, 'Network Engineer', 'IT', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 1100, 1600, 2, 0,
     'Zain Jordan is looking for Network Engineers to support and optimize our nationwide telecommunications infrastructure.',
     'CCNA or CCNP. 3+ years network engineering. IP networking, MPLS, BGP. Transmission systems experience a plus.',
     'Configure and maintain network equipment. Troubleshoot connectivity issues. Support network expansion projects.',
     ['Health insurance', 'Annual bonus', 'Transportation']],

    [8, 'Cybersecurity Specialist', 'IT', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1300, 1900, 1, 1,
     'Zain Jordan is strengthening its cybersecurity posture and needs a senior Cybersecurity Specialist to protect critical infrastructure.',
     '5+ years in information security. CISSP or CEH certified. SIEM, SOC, and incident response experience.',
     'Monitor and respond to security incidents. Conduct vulnerability assessments. Develop security policies.',
     ['Health insurance', 'Annual bonus', 'Training budget']],

    [8, 'Customer Experience Analyst', 'Customer Service', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1200, 1, 0,
     'Zain Jordan seeks a Customer Experience Analyst to analyze NPS and satisfaction data and drive service improvements.',
     '2+ years CX or market research. Data analysis skills. Excel and PowerBI proficiency. Telecom sector a plus.',
     'Analyze customer feedback and churn data. Identify pain points. Recommend process improvements.',
     ['Health insurance', 'Annual bonus', 'Stable schedule']],

    [8, 'Marketing Specialist (Digital)', 'Marketing', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 900, 1300, 1, 0,
     'Zain Jordan needs a Digital Marketing Specialist to manage performance campaigns and drive subscriber acquisition.',
     '3+ years digital marketing. Google Ads and Meta Ads certified. Analytics and A/B testing experience.',
     'Plan and execute digital campaigns. Optimize ad spend. Report on ROI and KPIs.',
     ['Health insurance', 'Annual bonus', 'Flexible hours']],

    // ── Petra Hotels & Resorts (Hospitality) ─────────────────────────────────
    [9, 'Front Office Manager', 'Administration', 'Full Time', 'Aqaba', 'Senior Level', 'Bachelor', 1100, 1600, 1, 0,
     'Petra Hotels & Resorts is seeking a Front Office Manager to lead guest services at our five-star Aqaba property.',
     '5+ years hotel management. PMS experience (Opera preferred). Excellent guest relations. Arabic and English fluency.',
     'Manage front desk operations. Ensure VIP guest experiences. Lead and train front office team. Handle escalations.',
     ['Accommodation', 'Meals', 'Health insurance', 'Annual bonus']],

    [9, 'F&B Supervisor', 'Administration', 'Full Time', 'Aqaba', 'Mid Level', 'Bachelor', 750, 1100, 2, 0,
     'Petra Hotels & Resorts needs an experienced F&B Supervisor to manage restaurant and banqueting operations in Aqaba.',
     '3+ years F&B supervisory experience. Knowledge of food safety standards. Team leadership. Arabic and English.',
     'Oversee daily F&B operations. Train service staff. Manage stock and minimize wastage. Ensure guest satisfaction.',
     ['Accommodation', 'Meals', 'Health insurance']],

    [9, 'Housekeeping Supervisor', 'Administration', 'Full Time', 'Aqaba', 'Mid Level', 'High School', 600, 850, 2, 0,
     'We are hiring a Housekeeping Supervisor to maintain the highest cleanliness standards at our luxury hotel.',
     '3+ years in hotel housekeeping. Supervisory experience. Attention to detail. OSHA standards awareness.',
     'Inspect rooms and public areas. Manage housekeeping team schedules. Order supplies. Report maintenance issues.',
     ['Accommodation', 'Meals', 'Health insurance', 'Annual leave']],

    [9, 'Sales Executive (Reservations)', 'Sales', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 550, 850, 2, 0,
     'Petra Hotels & Resorts needs Sales Executives in Amman to drive room bookings, event sales, and corporate accounts.',
     'Good communication skills. Sales or hospitality background preferred. Arabic and English fluency.',
     'Handle inbound booking enquiries. Develop corporate client relationships. Achieve monthly revenue targets.',
     ['Commission', 'Health insurance', 'Annual bonus']],

    // ── Al-Futtaim Jordan (Retail/Sales) ──────────────────────────────────────
    [10, 'Retail Store Manager', 'Sales', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1200, 1800, 2, 0,
     'Al-Futtaim Jordan is looking for experienced Retail Store Managers to lead flagship brand stores in Amman malls.',
     '5+ years retail management. P&L accountability. Team leadership. KPI-driven mindset.',
     'Manage day-to-day store operations. Drive sales and customer experience. Lead and develop team of 15+.',
     ['Health insurance', 'Performance bonus', 'Staff discount', 'Annual bonus']],

    [10, 'Automotive Sales Advisor', 'Sales', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 700, 1300, 3, 0,
     'Al-Futtaim Jordan is hiring Automotive Sales Advisors to sell premium vehicle brands at our Amman showrooms.',
     '2+ years automotive or high-value sales experience. Customer service orientation. Valid driving license.',
     'Welcome showroom visitors. Understand customer needs. Present and demonstrate vehicles. Close deals and achieve targets.',
     ['Commission', 'Health insurance', 'Annual bonus', 'Staff discount']],

    [10, 'Supply Chain Coordinator', 'Administration', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 800, 1150, 1, 0,
     'Al-Futtaim Jordan seeks a Supply Chain Coordinator to manage inventory flow and logistics for retail operations.',
     '2+ years supply chain or logistics. ERP knowledge (SAP preferred). Excel proficiency. Analytical skills.',
     'Coordinate with suppliers on orders and delivery. Monitor inventory levels. Resolve supply issues.',
     ['Health insurance', 'Annual bonus', 'Staff discount']],

    // ── Jordan Energy Solutions (Engineering) ─────────────────────────────────
    [11, 'Solar Energy Engineer', 'Engineering', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 1000, 1450, 1, 1,
     'Jordan Energy Solutions is hiring a Solar Energy Engineer to design and oversee rooftop and utility-scale solar installations.',
     'B.Sc. Electrical or Mechanical Engineering. Solar PV system design (PVsyst). Knowledge of Jordanian NEPCO requirements.',
     'Design solar PV systems. Conduct site surveys. Liaise with clients and contractors. Ensure project delivery.',
     ['Health insurance', 'Project bonus', 'Training', 'Annual leave']],

    [11, 'Project Manager (Renewable Energy)', 'Engineering', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1300, 1900, 1, 0,
     'We need an experienced Project Manager to lead end-to-end delivery of renewable energy projects across Jordan.',
     'PMP certification preferred. 5+ years project management in engineering or construction. MS Project.',
     'Lead project planning and execution. Manage budgets, timelines, and stakeholders. Report to senior management.',
     ['Health insurance', 'Performance bonus', 'Company car']],

    [11, 'Electrical Technician', 'Engineering', 'Full Time', 'Amman', 'Entry Level', 'High School', 500, 750, 4, 0,
     'Jordan Energy Solutions is hiring Electrical Technicians to support installation and maintenance of solar systems.',
     'Vocational certificate in Electrical Work. Basic understanding of solar PV. Ability to work at heights.',
     'Install solar panels and electrical components. Perform routine maintenance. Follow safety protocols.',
     ['Transportation', 'Health insurance', 'Training']],

    // ── LegalEdge Jordan (Legal) ──────────────────────────────────────────────
    [12, 'Corporate Associate Lawyer', 'Legal', 'Full Time', 'Amman', 'Mid Level', 'Master', 1000, 1500, 1, 0,
     'LegalEdge Jordan is expanding its corporate team and seeks an Associate Lawyer with M&A and commercial law experience.',
     'Jordanian Bar Association membership. 3+ years corporate law. Contract drafting and due diligence experience.',
     'Draft and review commercial contracts. Support M&A transactions. Conduct legal research. Advise clients.',
     ['Health insurance', 'Annual bonus', 'Professional development']],

    [12, 'Legal Researcher', 'Legal', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 600, 900, 1, 0,
     'LegalEdge Jordan is looking for a Legal Researcher to support our attorneys with case law research and document preparation.',
     'LLB degree. Strong legal research skills. Arabic and English bilingual. Attention to detail.',
     'Conduct legal research using online databases. Summarize case law and legislation. Prepare legal memos.',
     ['Health insurance', 'Training', 'Career growth']],

    [12, 'Office Manager (Law Firm)', 'Administration', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 700, 1000, 1, 0,
     'LegalEdge Jordan needs a professional Office Manager to handle administrative operations and client coordination.',
     '3+ years office management. Excellent organizational skills. Proficiency in MS Office. Discreet and professional.',
     'Manage scheduling, filing, and correspondence. Coordinate client meetings. Oversee office supplies and billing.',
     ['Health insurance', 'Stable environment']],

    // ── MedExpress Clinics (Health Care) ──────────────────────────────────────
    [13, 'General Practitioner', 'Health Care', 'Full Time', 'Zarqa', 'Mid Level', 'Bachelor', 1200, 1800, 2, 0,
     'MedExpress Clinics is hiring General Practitioners to provide outpatient consultations in our Zarqa clinics.',
     'MBBCh or equivalent. Valid Jordanian Medical Council license. 2+ years post-internship. Patient-focused.',
     'Conduct outpatient consultations. Diagnose and treat common conditions. Refer complex cases. Maintain records.',
     ['Health insurance', 'Malpractice cover', 'Annual bonus']],

    [13, 'Dental Surgeon', 'Health Care', 'Full Time', 'Zarqa', 'Mid Level', 'Bachelor', 1100, 1700, 1, 0,
     'MedExpress Clinics is looking for a licensed Dental Surgeon to join our growing dental department in Zarqa.',
     'BDS degree. Jordanian license. 2+ years clinical experience. General dentistry and preventive care.',
     'Provide dental consultations, fillings, extractions, and cleaning. Refer complex cases to specialists.',
     ['Health insurance', 'Malpractice cover', 'Annual bonus']],

    [13, 'Nursing Coordinator', 'Health Care', 'Full Time', 'Zarqa', 'Mid Level', 'Bachelor', 700, 1000, 2, 0,
     'MedExpress Clinics needs a Nursing Coordinator to manage nursing staff schedules and ensure clinical quality.',
     'RN license. 3+ years nursing experience. Supervisory skills. Knowledge of JCI or JCIA standards.',
     'Coordinate nursing team shifts. Conduct patient assessments. Maintain clinical protocols. Train junior nurses.',
     ['Health insurance', 'Medical benefits', 'Annual bonus']],

    [13, 'Receptionist (Medical)', 'Customer Service', 'Full Time', 'Zarqa', 'Entry Level', 'High School', 400, 600, 3, 0,
     'MedExpress Clinics is hiring Medical Receptionists to provide front-desk service at our outpatient clinics.',
     'Good communication in Arabic. Computer literacy. Friendly, patient-oriented manner. Previous clinic experience a plus.',
     'Register patients. Schedule appointments. Handle phone inquiries. Manage patient files and billing.',
     ['Health insurance', 'Medical benefits', 'Stable hours']],

    // ── Green Logistics Jordan (Engineering/Logistics) ────────────────────────
    [14, 'Logistics Coordinator', 'Engineering', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 750, 1100, 2, 0,
     'Green Logistics Jordan is looking for Logistics Coordinators to manage day-to-day freight and delivery operations.',
     '2+ years in logistics or supply chain. TMS experience preferred. Strong attention to detail. Valid driving license.',
     'Coordinate with carriers and warehouses. Track shipments and resolve delays. Maintain delivery documentation.',
     ['Transportation', 'Health insurance', 'Annual bonus']],

    [14, 'Fleet Manager', 'Administration', 'Full Time', 'Amman', 'Senior Level', 'Bachelor', 1100, 1600, 1, 0,
     'Green Logistics Jordan needs a Fleet Manager to oversee our growing fleet of 120+ commercial vehicles.',
     '5+ years fleet management. Strong knowledge of vehicle maintenance schedules. GPS tracking systems. Team leadership.',
     'Manage fleet maintenance, fuel, and driver assignments. Optimize route efficiency. Ensure regulatory compliance.',
     ['Company car', 'Health insurance', 'Performance bonus']],

    [14, 'Warehouse Supervisor', 'Administration', 'Full Time', 'Amman', 'Mid Level', 'Bachelor', 700, 1000, 1, 0,
     'Green Logistics Jordan seeks a Warehouse Supervisor to manage operations and inventory accuracy in our main warehouse.',
     '3+ years warehouse operations. WMS experience. Team leadership. Forklift license a plus.',
     'Supervise receiving, storage, and dispatch. Manage warehouse staff. Maintain inventory accuracy. Ensure safety standards.',
     ['Transportation', 'Health insurance', 'Annual bonus']],

    [14, 'Customer Service Executive (Logistics)', 'Customer Service', 'Full Time', 'Amman', 'Entry Level', 'Bachelor', 500, 750, 3, 0,
     'Green Logistics Jordan needs Customer Service Executives to handle client enquiries and shipment tracking for our corporate accounts.',
     'Good communication in Arabic and English. Customer service mindset. Computer literacy. Logistics background a plus.',
     'Handle client calls and emails. Track shipments. Coordinate with operations team on delivery issues.',
     ['Transportation', 'Health insurance', 'Stable schedule']],
];

$job_ids = [];
foreach ($jobs_data as [$emp_idx, $title, $cat, $type, $loc, $exp, $edu, $smin, $smax, $vac, $featured, $desc, $req, $resp, $benefits]) {
    $emp_uid = $employer_user_ids[$emp_idx];
    $db->prepare("INSERT INTO jobs (employer_id,title,description,requirements,responsibilities,category,job_type,location,experience_level,education_level,salary_min,salary_max,salary_type,vacancy_count,status,is_featured,expires_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Monthly',?,'active',?,DATE_ADD(NOW(), INTERVAL ? DAY),DATE_SUB(NOW(), INTERVAL ? DAY))")
       ->execute([$emp_uid, $title, $desc, $req, $resp, $cat, $type, $loc, $exp, $edu, $smin, $smax, $vac, $featured, rand(30, 90), rand(0, 30)]);
    $jid = (int)$db->lastInsertId();
    $job_ids[] = $jid;

    foreach ($benefits as $b) {
        $db->prepare("INSERT INTO job_benefits (job_id, benefit) VALUES (?,?)")->execute([$jid, $b]);
    }
}
echo "[INFO] Created " . count($jobs_data) . " jobs.\n";

// ─── SAMPLE APPLICATIONS ──────────────────────────────────────────────────────
// Candidate profile IDs are 1-indexed in order of $candidates_data
// [cp_idx (0-based), job_idx (0-based), status, cover_letter_snippet]
$applications_data = [
    [0, 0,  'reviewed',    'My 6 years of Laravel and MySQL experience make me an ideal fit for your senior PHP role.'],
    [0, 2,  'pending',     'React Native is my secondary stack. I have shipped 3 production apps on iOS and Android.'],
    [1, 2,  'shortlisted', 'I have 4 years building cross-platform MERN apps and would love to grow with TechCorp.'],
    [2, 1,  'interviewed', 'Infrastructure automation and cloud migrations are my core strengths — Docker and K8s daily.'],
    [3, 2,  'pending',     'Flutter development across 8 published apps demonstrates my mobile product experience.'],
    [4, 29, 'pending',     'Cybersecurity operations and vulnerability management are where I excel.'],
    [5, 3,  'reviewed',    'My ML and data pipeline expertise would directly support your data platform initiative.'],
    [6, 6,  'pending',     'I am excited to grow as a junior developer and contribute under strong technical mentorship.'],
    [7, 12, 'hired',       'My CFA studies and 4 years in financial modeling align perfectly with this analyst role.'],
    [8, 26, 'reviewed',    'Three years in bank credit risk with Basel III knowledge is exactly what this role requires.'],
    [9, 25, 'pending',     'As a finance graduate with ASE internship experience, I am eager to start my investment career.'],
    [10, 13,'shortlisted', 'Seven years of IFRS accounting and CPA certification match your senior accountant needs.'],
    [12, 7, 'shortlisted', 'My Figma-first approach and 4 years in product UX make me a strong fit for your senior designer role.'],
    [13, 8, 'pending',     'I have a strong portfolio in brand identity and print design ready to share.'],
    [14, 9, 'reviewed',    'After Effects motion work for TV and digital campaigns is my core specialization.'],
    [15, 15,'reviewed',    'Seven years in digital marketing for MENA brands translates directly to your Brand Strategist role.'],
    [16, 10,'pending',     'Bilingual Arabic-English content strategy for digital platforms is exactly what I do.'],
    [18, 17,'pending',     'HR coordination experience and HRMS proficiency make me a strong candidate for your team.'],
    [19, 19,'shortlisted', 'Eight years as a strategic HRBP with talent management and labor law expertise.'],
    [20, 18,'pending',     'Three years sourcing tech talent in Jordan means I understand your recruitment challenges.'],
    [21, 20,'reviewed',    'Four years on residential construction sites in Zarqa, strong AutoCAD and site management.'],
    [22, 21,'pending',     'PLC and SCADA certified with 3 years on commercial electrical projects.'],
    [27, 43,'pending',     'Six years in Jordanian corporate law including M&A transactions and commercial drafting.'],
    [30, 30,'pending',     'CELTA certified with 5 years teaching English to Arab students online and in-person.'],
    [32, 38,'pending',     'Four years B2B SaaS sales closing enterprise deals. Salesforce CRM power user.'],
    [33, 39,'shortlisted', '140% of annual target with a 12-person team — I bring proven sales leadership to Al-Futtaim.'],
];

foreach ($applications_data as [$cp_idx, $job_idx, $status, $cover]) {
    if (!isset($candidate_profile_ids[$cp_idx]) || !isset($job_ids[$job_idx])) continue;
    $cpid = $candidate_profile_ids[$cp_idx];
    $jid  = $job_ids[$job_idx];
    $db->prepare("INSERT IGNORE INTO applications (candidate_profile_id,job_id,cover_letter,status,applied_at) VALUES (?,?,?,?,DATE_SUB(NOW(), INTERVAL ? DAY))")
       ->execute([$cpid, $jid, $cover, $status, rand(1, 25)]);
}
echo "[INFO] Created sample applications.\n";

// ─── SAVED JOBS ───────────────────────────────────────────────────────────────
$saved_jobs = [
    [0,1],[0,4],[1,2],[1,5],[5,0],[5,3],[7,12],[7,13],[8,26],
];
foreach ($saved_jobs as [$cp_idx, $job_idx]) {
    if (!isset($candidate_profile_ids[$cp_idx]) || !isset($job_ids[$job_idx])) continue;
    $db->prepare("INSERT IGNORE INTO saved_jobs (candidate_profile_id,job_id) VALUES (?,?)")
       ->execute([$candidate_profile_ids[$cp_idx], $job_ids[$job_idx]]);
}
echo "[INFO] Created saved jobs.\n";

// ─── BLOG POSTS ───────────────────────────────────────────────────────────────
$posts = [
    ['How to Write a Winning Resume in 2025', 'how-to-write-winning-resume-2025', 'Career Tips',
     'Your resume is your first impression. These key elements make recruiters stop and take notice.',
     "## Start With a Strong Summary\n\nYour professional summary is the first thing recruiters read. Keep it to 3–4 lines highlighting your years of experience, key skills, and what makes you unique.\n\n## Use Action Verbs\n\nBegin every bullet point with a strong action verb: *developed*, *managed*, *led*, *reduced*, *increased*.\n\n## Quantify Your Achievements\n\nNumbers grab attention. Instead of \"improved sales\", write \"increased sales by 40% in Q3 2024\".\n\n## Tailor for Every Application\n\nRead each job description and mirror its keywords. ATS systems filter resumes based on keyword matches.\n\n## Keep It to One Page\n\nFor candidates with fewer than 10 years of experience, one page is the standard.", 0],

    ['Top 10 In-Demand Skills for Tech Jobs in Jordan', 'top-10-indemand-skills-jordan', 'Industry Insights',
     'The tech industry in Jordan is evolving fast. These are the skills employers are actively hiring for right now.',
     "## 1. Cloud Computing (AWS / Azure)\n\nCloud skills are in high demand across all sectors.\n\n## 2. React and React Native\n\nJavaScript frameworks dominate frontend and mobile development.\n\n## 3. Python for Data and AI\n\nWith AI adoption accelerating, Python remains the top language for data science.\n\n## 4. DevOps and CI/CD\n\nCompanies want engineers who can build and deploy. Docker and Kubernetes are core.\n\n## 5. Cybersecurity\n\nAs digital threats grow, so does demand for security professionals.", 0],

    ['How to Ace Your Next Job Interview', 'how-to-ace-job-interview', 'Interview Tips',
     'Preparation is the key to confidence. Here are proven strategies to help you perform your best.',
     "## Research the Company\n\nSpend at least an hour researching the company before your interview.\n\n## Prepare STAR Stories\n\nThe STAR method (Situation, Task, Action, Result) structures your answers to behavioral questions.\n\n## Ask Smart Questions\n\nAlways have 3–5 questions ready. Ask about team structure, success metrics, and challenges.\n\n## Follow Up\n\nSend a thank-you email within 24 hours.", 2],

    ['Salary Negotiation: Get What You Are Worth', 'salary-negotiation-tips', 'Career Tips',
     'Negotiating your salary does not have to be uncomfortable. Use these data-backed tactics to ask for more.',
     "## Research the Market Rate\n\nBefore negotiating, know what similar roles pay in Jordan.\n\n## Let Them Offer First\n\nWhenever possible, let the employer state a number first.\n\n## Counter Confidently\n\n\"Based on my 5 years of experience and the market rate for this role in Amman, I was expecting closer to JOD 1,400.\"\n\n## Negotiate the Full Package\n\nSalary is only part of compensation. Health insurance, annual leave, and remote work all have real value.", 1],

    ['Employer Branding: Why Top Talent Chooses You', 'employer-branding-guide', 'For Employers',
     'Your company culture and reputation directly impact the quality of candidates you attract.',
     "## What Is Employer Branding?\n\nEmployer branding is how your company is perceived as a place to work.\n\n## Why It Matters\n\n75% of job seekers research a company before applying. A strong brand reduces time-to-hire significantly.\n\n## How to Build Your Brand\n\n1. Define your Employee Value Proposition\n2. Showcase your culture on social media\n3. Highlight internal growth stories\n4. Offer competitive, transparent benefits", 0],

    ['Remote Work in Jordan: Opportunities and Challenges', 'remote-work-jordan', 'Work Life',
     'Remote work is reshaping how Jordanians work. Discover how to maximize productivity and find remote opportunities.',
     "## The Rise of Remote Work\n\nRemote work has gone from exception to expectation in Jordan's tech and creative sectors.\n\n## Where to Find Remote Jobs\n\nJobpilot lists remote-friendly positions under the Remote location filter.\n\n## The Opportunity\n\nSenior Jordanian developers and designers can access international salaries while living in Jordan.", 2],
];

foreach ($posts as $i => [$title, $slug, $cat, $excerpt, $content, $author_idx]) {
    $author_uid = $candidate_user_ids[$author_idx] ?? $candidate_user_ids[0];
    $db->prepare("INSERT INTO blog_posts (author_id,title,slug,excerpt,category,content,status,created_at) VALUES (?,?,?,?,?,?,?,DATE_SUB(NOW(), INTERVAL ? DAY))")
       ->execute([$author_uid, $title, $slug, $excerpt, $cat, $content, 'published', ($i + 1) * 6]);
}
echo "[INFO] Created " . count($posts) . " blog posts.\n";

// ─── PLANS ────────────────────────────────────────────────────────────────────
$plans = [
    ['Basic',    19, 1, '{"urgent_featured":false,"highlights":false,"candidates_access":0,"support":false}'],
    ['Standard', 39, 5, '{"urgent_featured":true,"highlights":true,"candidates_access":10,"support":true}'],
    ['Premium',  59, 8, '{"urgent_featured":true,"highlights":true,"candidates_access":20,"support":true}'],
];
$plan_ids = [];
foreach ($plans as [$name, $price, $max_jobs, $features]) {
    $db->prepare("INSERT INTO plans (name,price,max_jobs,features,is_active) VALUES (?,?,?,?,1)")
       ->execute([$name, $price, $max_jobs, $features]);
    $plan_ids[] = (int)$db->lastInsertId();
}

// Give first employer a Premium subscription
$db->prepare("INSERT INTO employer_subscriptions (employer_id,plan_id,starts_at,ends_at,status) VALUES (?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 30 DAY),'active')")
   ->execute([$employer_user_ids[0], $plan_ids[2]]);

echo "[INFO] Plans and subscriptions seeded.\n";

echo "\n[SUCCESS] Database seeded!\n";
echo "=== Stats ===\n";
echo "Employers: " . count($employers_data) . "\n";
echo "Candidates: " . count($candidates_data) . "\n";
echo "Jobs: " . count($jobs_data) . "\n";
echo "\n=== Demo Accounts (password: Password1!) ===\n";
echo "Employer:  ahmad@techcorp.jo    -> TechCorp Jordan (IT, Amman)\n";
echo "Employer:  tariq@royalbank.jo   -> Royal Bank Jordan (Finance, Amman)\n";
echo "Candidate: rami@mail.com        -> Senior PHP Developer (IT, Amman)\n";
echo "Candidate: bassam@mail.com      -> Financial Analyst (Finance, Irbid)\n";
echo "Candidate: nour@mail.com        -> UI/UX Designer (Design, Amman)\n";
echo "Candidate: hana@mail.com        -> Marketing Manager (Marketing, Amman)\n";
echo "Candidate: kareem.s@mail.com    -> Corporate Lawyer (Legal, Amman)\n";
