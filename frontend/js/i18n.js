/* ============================================================
   i18n.js — Jobpilot Internationalization System
   Supports: English (LTR) + Arabic (RTL)
   Usage:  Add data-i18n="key" to any translatable element.
           Add data-i18n-placeholder="key" for input placeholders.
           Add data-i18n-aria="key" for aria-label attributes.
   ============================================================ */

const TRANSLATIONS = {
  en: {
    /* ── Navigation (public) ─────────────────────────────── */
    'nav.home':            'Home',
    'nav.find_job':        'Find Job',
    'nav.find_employers':  'Find Employers',
    'nav.candidates':      'Candidates',
    'nav.blog':            'Blog',
    'nav.about':           'About',
    'nav.sign_in':         'Sign In',
    'nav.post_job':        'Post A Job',
    'nav.sign_out':        'Sign Out',
    'nav.my_profile':      'My Profile',
    'nav.settings':        'Settings',
    'nav.dashboard':       'Dashboard',

    /* ── Navigation (dashboard) ──────────────────────────── */
    'dash.nav.overview':          'Dashboard',
    'dash.nav.applied':           'Applied Jobs',
    'dash.nav.favorites':         'Favorite Jobs',
    'dash.nav.alerts':            'Job Alerts',
    'dash.nav.settings':          'Settings',
    'dash.nav.my_jobs':           'My Jobs',
    'dash.nav.applications':      'Applications',
    'dash.nav.saved_candidates':  'Saved Candidates',
    'dash.nav.post_job':          'Post a Job',
    'dash.nav.plans':             'Plans & Billing',
    'dash.nav.company_profile':   'Company Profile',

    /* ── Hero section ────────────────────────────────────── */
    'hero.eyebrow':             'Find Your Next Career Move',
    'hero.title_line1':         'Find a job that suits',
    'hero.title_line2':         'your',
    'hero.title_highlight':     'interest',
    'hero.title_line3':         '& skills.',
    'hero.subtitle':            'Search thousands of jobs across every industry. Connect with top employers and take the next step in your career today.',
    'hero.kw_placeholder':      'Job title, Keyword...',
    'hero.loc_placeholder':     'Your Location',
    'hero.find_job_btn':        'Find Job',
    'hero.popular_label':       'Popular Searches:',
    'hero.badge_verified':      '✓ Profile Verified',
    'hero.badge_alert':         '+ New Job Alert',

    /* ── Stats bar ───────────────────────────────────────── */
    'stats.live_jobs':   'Live Jobs',
    'stats.companies':   'Companies',
    'stats.candidates':  'Candidates',
    'stats.new_jobs':    'New Jobs',

    /* ── Homepage sections ───────────────────────────────── */
    'home.vacancies_title':      'Most Popular Vacancies',
    'home.categories_title':     'Popular Categories',
    'home.categories_sub':       'Explore jobs across the most in-demand fields',
    'home.featured_title':       'Featured Jobs',
    'home.featured_sub':         'Hand-picked opportunities from top employers',
    'home.companies_title':      'Top Companies',
    'home.companies_sub':        'Discover the best employers actively hiring',
    'home.testimonials_title':   'What Our Users Say',
    'home.testimonials_sub':     'Thousands of candidates and employers trust Shaghlni to make the right connections.',
    'home.cta_title':            'Ready to find your next opportunity?',
    'home.cta_sub':              'Join millions of professionals who found their dream job on Shaghlni.',
    'home.cta_create':           'Create Account',
    'home.cta_browse':           'Browse Jobs',
    'home.open_jobs':            'Open Jobs',
    'home.how_title':            'How Shaghlni Works',
    'home.how_sub':              'Job searching is an easy process with our platform. Just follow the steps below to find your perfect job.',

    /* ── Page headers ────────────────────────────────────── */
    'page.find_job':         'Find Job',
    'page.find_employers':   'Find Employers',
    'page.candidates':       'Candidates',
    'page.about':            'About Us',
    'page.contact':          'Contact Us',
    'page.blog':             'Blog & Articles',
    'page.faqs':             'FAQs',
    'page.terms':            'Terms & Conditions',

    /* ── Breadcrumbs ─────────────────────────────────────── */
    'bc.home':        'Home',
    'bc.find_job':    'Find Job',
    'bc.employers':   'Find Employers',
    'bc.candidates':  'Candidates',
    'bc.details':     'Details',

    /* ── Browse / Filter ─────────────────────────────────── */
    'filter.title':         'Filters',
    'filter.job_type':      'Job Type',
    'filter.salary':        'Salary Range',
    'filter.experience':    'Experience Level',
    'filter.category':      'Job Category',
    'filter.apply':         'Apply Filters',
    'filter.sort':          'Sort by',
    'filter.latest':        'Latest',
    'filter.oldest':        'Oldest',
    'filter.salary_high':   'Salary High to Low',
    'filter.salary_low':    'Salary Low to High',
    'filter.full_time':     'Full Time',
    'filter.part_time':     'Part Time',
    'filter.internship':    'Internship',
    'filter.remote':        'Remote',
    'filter.contract':      'Contract',
    'filter.entry':         'Entry Level',
    'filter.mid':           'Mid Level',
    'filter.senior':        'Senior Level',
    'filter.director':      'Director',
    'filter.search_job':    'Search by: Job Title, Position, Keyword...',
    'filter.search_loc':    'City, state or zip code',
    'filter.search_co':     'Company name or keyword',

    /* ── Job card / listing ──────────────────────────────── */
    'job.apply_now':      'Apply Now',
    'job.save':           'Save Job',
    'job.saved':          'Saved',
    'job.view':           'View Details',
    'job.jobs_found':     'Jobs found',
    'job.open_jobs':      'Open Jobs',
    'job.no_jobs':        'No jobs found',
    'job.no_jobs_sub':    'Try adjusting your search or filters.',
    'job.loading':        'Loading jobs...',
    'job.salary':         'Salary',
    'job.location':       'Location',
    'job.posted':         'Posted',
    'job.deadline':       'Deadline',
    'job.job_type':       'Job Type',
    'job.experience':     'Experience',
    'job.related':        'Related Jobs',

    /* ── Employer / Company ──────────────────────────────── */
    'emp.open_jobs':      'Open Jobs',
    'emp.follow':         'Follow',
    'emp.website':        'Website',
    'emp.industry':       'Industry',
    'emp.company_size':   'Company Size',
    'emp.founded':        'Founded',
    'emp.employees':      'Employees',
    'emp.about':          'About Company',
    'emp.no_employers':   'No employers found',
    'emp.companies':      'companies',
    'emp.showing':        'Showing',

    /* ── Candidate ───────────────────────────────────────── */
    'cand.hire':              'Hire',
    'cand.view_profile':      'View Profile',
    'cand.experience':        'Experience',
    'cand.education':         'Education',
    'cand.no_candidates':     'No candidates found',
    'cand.no_candidates_sub': 'Try adjusting your filters',
    'cand.failed':            'Failed to load candidates',
    'cand.search_ph':         'Name or title',
    'cand.location_ph':       'City or country',
    'cand.showing':           'Showing {n} candidates',
    'cand.sort_az':           'A – Z',
    'cand.find_candidates':   'Find Candidates',
    'filter.clear':           'Clear Filters',
    'filter.any_level':       'Any Level',
    'cat.design':             'Design',
    'cat.development':        'Development',
    'cat.marketing':          'Marketing',
    'cat.finance':            'Finance',
    'cat.healthcare':         'Healthcare',
    'cat.writing':            'Writing',

    /* ── Auth pages ──────────────────────────────────────── */
    'auth.sign_in':           'Sign In',
    'auth.create_account':    'Create an Account',
    'auth.email':             'Email Address',
    'auth.email_ph':          'Enter email address',
    'auth.password':          'Password',
    'auth.password_ph':       'Enter password',
    'auth.confirm_pw':        'Confirm Password',
    'auth.confirm_pw_ph':     'Confirm your password',
    'auth.remember_me':       'Remember me',
    'auth.forgot_pw':         'Forgot password?',
    'auth.no_account':        "Don't have an account?",
    'auth.have_account':      'Already have an account?',
    'auth.register_link':     'Create Account',
    'auth.login_link':        'Sign In',
    'auth.or':                'or',
    'auth.full_name':         'Full Name',
    'auth.full_name_ph':      'Enter your full name',
    'auth.i_am':              'I am a...',
    'auth.candidate':         'Candidate',
    'auth.employer':          'Employer',
    'auth.agree_terms':       'I agree to the',
    'auth.terms':             'Terms & Conditions',

    /* ── Dashboard common ────────────────────────────────── */
    'dash.welcome':           'Welcome back,',
    'dash.profile_complete':  'Profile Completion',
    'dash.quick_actions':     'Quick Actions',
    'dash.recent_activity':   'Recent Activity',
    'dash.notifications':     'Notifications',
    'dash.mark_all_read':     'Mark all as read',
    'dash.no_notifications':  'No new notifications',
    'dash.view_all':          'View All',

    /* ── Candidate dashboard ─────────────────────────────── */
    'cd.stats.applied':       'Applied Jobs',
    'cd.stats.favorites':     'Favorite Jobs',
    'cd.stats.alerts':        'Job Alerts',
    'cd.stats.profile_views': 'Profile Views',
    'cd.applied.title':       'Applied Jobs',
    'cd.applied.empty':       "You haven't applied to any jobs yet.",
    'cd.applied.empty_sub':   'Start browsing and apply to jobs that match your skills.',
    'cd.favorites.title':     'Favorite Jobs',
    'cd.favorites.empty':     "You haven't saved any jobs yet.",
    'cd.favorites.empty_sub': 'Browse jobs and click the bookmark icon to save them here.',
    'cd.alerts.title':        'Job Alerts',
    'cd.alerts.empty':        'No job alerts set up.',

    /* ── Employer dashboard ──────────────────────────────── */
    'ed.stats.posted':        'Posted Jobs',
    'ed.stats.applications':  'Applications',
    'ed.stats.saved':         'Saved Candidates',
    'ed.stats.views':         'Profile Views',
    'ed.jobs.title':          'My Jobs',
    'ed.jobs.post_btn':       'Post a Job',
    'ed.jobs.empty':          "You haven't posted any jobs yet.",
    'ed.jobs.empty_sub':      'Post your first job to start receiving applications.',
    'ed.apps.title':          'Applications',
    'ed.apps.empty':          'No applications received yet.',
    'ed.saved.title':         'Saved Candidates',
    'ed.saved.empty':         'No saved candidates yet.',

    /* ── Settings ────────────────────────────────────────── */
    'settings.account':       'Account Settings',
    'settings.personal':      'Personal Info',
    'settings.profile':       'Profile',
    'settings.social':        'Social Links',
    'settings.save':          'Save Changes',
    'settings.cancel':        'Cancel',

    /* ── Forms ───────────────────────────────────────────── */
    'form.submit':            'Submit',
    'form.cancel':            'Cancel',
    'form.save':              'Save Changes',
    'form.upload':            'Upload',
    'form.browse':            'Browse',
    'form.required':          'This field is required.',
    'form.invalid_email':     'Please enter a valid email address.',
    'form.name':              'Full Name',
    'form.name_ph':           'Your full name',
    'form.email':             'Email',
    'form.email_ph':          'your@email.com',
    'form.subject':           'Subject',
    'form.subject_ph':        'How can we help?',
    'form.message':           'Message',
    'form.message_ph':        'Tell us more about your inquiry...',
    'form.send':              'Send Message →',

    /* ── Contact page ────────────────────────────────────── */
    'contact.get_in_touch':   'Get in Touch',
    'contact.subtitle':       "Have a question or need help? We'd love to hear from you.",
    'contact.office':         'Our Office',
    'contact.office_val':     'Remote & available worldwide.',
    'contact.phone':          'Phone',
    'contact.phone_val':      'Available via email or contact form',
    'contact.email':          'Email',
    'contact.send_msg':       'Send a Message',

    /* ── About page ──────────────────────────────────────── */
    'about.mission_label':    'Our Mission',
    'about.mission_title':    'Connecting talent with opportunity — worldwide.',
    'about.values_title':     'Our Core Values',
    'about.values_sub':       'The principles that guide everything we do',
    'about.team_title':       'Meet Our Team',
    'about.team_sub':         'The passionate people building Shaghlni',

    /* ── Footer ──────────────────────────────────────────── */
    'footer.tagline':         'Find your dream job or discover the best candidates with Shaghlni.',
    'footer.quick_link':      'Quick Link',
    'footer.candidate':       'Candidate',
    'footer.employers':       'Employers',
    'footer.support':         'Support',
    'footer.about':           'About',
    'footer.contact':         'Contact',
    'footer.blog':            'Blog',
    'footer.browse_jobs':     'Browse Jobs',
    'footer.browse_emp':      'Browse Employers',
    'footer.browse_cand':     'Browse Candidates',
    'footer.dash_cand':       'Candidate Dashboard',
    'footer.saved_jobs':      'Saved Jobs',
    'footer.post_job':        'Post a Job',
    'footer.dash_emp':        'Employers Dashboard',
    'footer.faqs':            'FAQs',
    'footer.terms':           'Terms & Conditions',
    'footer.copy':            '© 2026 Shaghlni. All rights reserved.',

    /* ── Common UI ───────────────────────────────────────── */
    'ui.loading':             'Loading...',
    'ui.load_more':           'Load More',
    'ui.back':                'Back',
    'ui.close':               'Close',
    'ui.search':              'Search',
    'ui.apply':               'Apply',
    'ui.reset':               'Reset',
    'ui.confirm':             'Confirm',
    'ui.delete':              'Delete',
    'ui.edit':                'Edit',
    'ui.view':                'View',
    'ui.all':                 'All',
    'ui.none':                'None',
    'ui.yes':                 'Yes',
    'ui.no':                  'No',
    'ui.prev':                'Previous',
    'ui.next':                'Next',
    'ui.page':                'Page',
    'ui.of':                  'of',

    /* ── Quick search modal ──────────────────────────────── */
    'qs.placeholder':         'Search pages, jobs, employers...',
    'qs.hint':                'Press Esc to close',
    'qs.no_results':          'No results found.',
    'qs.nav_label':           'Navigation',
    'qs.jobs_label':          'Jobs',

    /* ── Toasts / notifications ──────────────────────────── */
    'toast.saved':            'Saved successfully.',
    'toast.removed':          'Removed.',
    'toast.error':            'Something went wrong. Please try again.',
    'toast.login_required':   'Please sign in to continue.',

    /* ── Theme / lang controls ───────────────────────────── */
    'theme.toggle_dark':      'Switch to dark mode',
    'theme.toggle_light':     'Switch to light mode',
    'lang.en':                'EN',
    'lang.ar':                'AR',
    'lang.switch_ar':         'Arabic',
    'lang.switch_en':         'English',
  },

  ar: {
    /* ── Navigation (public) ─────────────────────────────── */
    'nav.home':            'الرئيسية',
    'nav.find_job':        'البحث عن وظيفة',
    'nav.find_employers':  'البحث عن أصحاب العمل',
    'nav.candidates':      'المرشحون',
    'nav.blog':            'المدونة',
    'nav.about':           'من نحن',
    'nav.sign_in':         'تسجيل الدخول',
    'nav.post_job':        'نشر وظيفة',
    'nav.sign_out':        'تسجيل الخروج',
    'nav.my_profile':      'ملفي الشخصي',
    'nav.settings':        'الإعدادات',
    'nav.dashboard':       'لوحة التحكم',

    /* ── Navigation (dashboard) ──────────────────────────── */
    'dash.nav.overview':          'لوحة التحكم',
    'dash.nav.applied':           'الوظائف المتقدم إليها',
    'dash.nav.favorites':         'الوظائف المفضلة',
    'dash.nav.alerts':            'تنبيهات الوظائف',
    'dash.nav.settings':          'الإعدادات',
    'dash.nav.my_jobs':           'وظائفي',
    'dash.nav.applications':      'الطلبات',
    'dash.nav.saved_candidates':  'المرشحون المحفوظون',
    'dash.nav.post_job':          'نشر وظيفة',
    'dash.nav.plans':             'الخطط والفواتير',
    'dash.nav.company_profile':   'ملف الشركة',

    /* ── Hero section ────────────────────────────────────── */
    'hero.eyebrow':             'انطلق في مسيرتك المهنية القادمة',
    'hero.title_line1':         'ابحث عن وظيفة تناسب',
    'hero.title_line2':         '',
    'hero.title_highlight':     'اهتماماتك',
    'hero.title_line3':         'ومهاراتك.',
    'hero.subtitle':            'ابحث في آلاف الوظائف عبر مختلف القطاعات. تواصل مع أفضل أصحاب العمل وانطلق نحو مستقبلك المهني اليوم.',
    'hero.kw_placeholder':      'المسمى الوظيفي، كلمة مفتاحية...',
    'hero.loc_placeholder':     'موقعك الجغرافي',
    'hero.find_job_btn':        'ابحث عن وظيفة',
    'hero.popular_label':       'البحث الشائع:',
    'hero.badge_verified':      '✓ تم التحقق من الملف',
    'hero.badge_alert':         '+ تنبيه وظيفة جديدة',

    /* ── Stats bar ───────────────────────────────────────── */
    'stats.live_jobs':   'وظيفة نشطة',
    'stats.companies':   'شركة',
    'stats.candidates':  'مرشح',
    'stats.new_jobs':    'وظائف جديدة',

    /* ── Homepage sections ───────────────────────────────── */
    'home.vacancies_title':      'أكثر الوظائف طلبًا',
    'home.categories_title':     'الفئات الشائعة',
    'home.categories_sub':       'استكشف الوظائف في أكثر المجالات طلبًا',
    'home.featured_title':       'الوظائف المميزة',
    'home.featured_sub':         'فرص مختارة بعناية من أفضل أصحاب العمل',
    'home.companies_title':      'أبرز الشركات',
    'home.companies_sub':        'اكتشف أفضل أصحاب العمل الذين يوظفون الآن',
    'home.testimonials_title':   'ما يقوله مستخدمونا',
    'home.testimonials_sub':     'يثق الآلاف من المرشحين وأصحاب العمل بـ Shaghlni لبناء التواصل الصحيح.',
    'home.cta_title':            'هل أنت مستعد للعثور على فرصتك القادمة؟',
    'home.cta_sub':              'انضم إلى ملايين المحترفين الذين وجدوا وظيفة أحلامهم على Shaghlni.',
    'home.cta_create':           'إنشاء حساب',
    'home.cta_browse':           'تصفح الوظائف',
    'home.open_jobs':            'وظيفة شاغرة',
    'home.how_title':            'كيف يعمل Shaghlni',
    'home.how_sub':              'البحث عن وظيفة عملية سهلة مع منصتنا. اتبع الخطوات التالية للعثور على وظيفتك المثالية.',

    /* ── Page headers ────────────────────────────────────── */
    'page.find_job':         'البحث عن وظيفة',
    'page.find_employers':   'البحث عن أصحاب العمل',
    'page.candidates':       'المرشحون',
    'page.about':            'من نحن',
    'page.contact':          'تواصل معنا',
    'page.blog':             'المدونة والمقالات',
    'page.faqs':             'الأسئلة الشائعة',
    'page.terms':            'الشروط والأحكام',

    /* ── Breadcrumbs ─────────────────────────────────────── */
    'bc.home':        'الرئيسية',
    'bc.find_job':    'البحث عن وظيفة',
    'bc.employers':   'أصحاب العمل',
    'bc.candidates':  'المرشحون',
    'bc.details':     'التفاصيل',

    /* ── Browse / Filter ─────────────────────────────────── */
    'filter.title':         'التصفية',
    'filter.job_type':      'نوع الوظيفة',
    'filter.salary':        'نطاق الراتب',
    'filter.experience':    'مستوى الخبرة',
    'filter.category':      'تصنيف الوظيفة',
    'filter.apply':         'تطبيق الفلاتر',
    'filter.sort':          'ترتيب حسب',
    'filter.latest':        'الأحدث',
    'filter.oldest':        'الأقدم',
    'filter.salary_high':   'الراتب من الأعلى للأدنى',
    'filter.salary_low':    'الراتب من الأدنى للأعلى',
    'filter.full_time':     'دوام كامل',
    'filter.part_time':     'دوام جزئي',
    'filter.internship':    'تدريب',
    'filter.remote':        'عن بُعد',
    'filter.contract':      'عقد',
    'filter.entry':         'مبتدئ',
    'filter.mid':           'متوسط الخبرة',
    'filter.senior':        'خبرة عالية',
    'filter.director':      'مدير',
    'filter.search_job':    'ابحث: المسمى الوظيفي، المنصب، الكلمة المفتاحية...',
    'filter.search_loc':    'المدينة، الولاية أو الرمز البريدي',
    'filter.search_co':     'اسم الشركة أو كلمة مفتاحية',

    /* ── Job card / listing ──────────────────────────────── */
    'job.apply_now':      'تقدم الآن',
    'job.save':           'حفظ الوظيفة',
    'job.saved':          'محفوظة',
    'job.view':           'عرض التفاصيل',
    'job.jobs_found':     'وظيفة متاحة',
    'job.open_jobs':      'وظائف شاغرة',
    'job.no_jobs':        'لا توجد وظائف',
    'job.no_jobs_sub':    'حاول تعديل بحثك أو الفلاتر.',
    'job.loading':        'جارٍ تحميل الوظائف...',
    'job.salary':         'الراتب',
    'job.location':       'الموقع',
    'job.posted':         'نُشر',
    'job.deadline':       'آخر موعد',
    'job.job_type':       'نوع الوظيفة',
    'job.experience':     'الخبرة',
    'job.related':        'وظائف مشابهة',

    /* ── Employer / Company ──────────────────────────────── */
    'emp.open_jobs':      'وظائف شاغرة',
    'emp.follow':         'متابعة',
    'emp.website':        'الموقع الإلكتروني',
    'emp.industry':       'القطاع',
    'emp.company_size':   'حجم الشركة',
    'emp.founded':        'تأسست عام',
    'emp.employees':      'موظف',
    'emp.about':          'عن الشركة',
    'emp.no_employers':   'لا توجد شركات',
    'emp.companies':      'شركة',
    'emp.showing':        'عرض',

    /* ── Candidate ───────────────────────────────────────── */
    'cand.hire':              'توظيف',
    'cand.view_profile':      'عرض الملف',
    'cand.experience':        'الخبرة',
    'cand.education':         'التعليم',
    'cand.no_candidates':     'لا يوجد مرشحون',
    'cand.no_candidates_sub': 'حاول تعديل الفلاتر',
    'cand.failed':            'فشل تحميل المرشحين',
    'cand.search_ph':         'الاسم أو المسمى الوظيفي',
    'cand.location_ph':       'المدينة أو الدولة',
    'cand.showing':           'عرض {n} مرشح',
    'cand.sort_az':           'أ – ي',
    'cand.find_candidates':   'البحث عن مرشحين',
    'filter.clear':           'مسح الفلاتر',
    'filter.any_level':       'أي مستوى',
    'cat.design':             'تصميم',
    'cat.development':        'تطوير',
    'cat.marketing':          'تسويق',
    'cat.finance':            'مالية',
    'cat.healthcare':         'رعاية صحية',
    'cat.writing':            'كتابة',

    /* ── Auth pages ──────────────────────────────────────── */
    'auth.sign_in':           'تسجيل الدخول',
    'auth.create_account':    'إنشاء حساب جديد',
    'auth.email':             'البريد الإلكتروني',
    'auth.email_ph':          'أدخل بريدك الإلكتروني',
    'auth.password':          'كلمة المرور',
    'auth.password_ph':       'أدخل كلمة المرور',
    'auth.confirm_pw':        'تأكيد كلمة المرور',
    'auth.confirm_pw_ph':     'أعد إدخال كلمة المرور',
    'auth.remember_me':       'تذكرني',
    'auth.forgot_pw':         'نسيت كلمة المرور؟',
    'auth.no_account':        'ليس لديك حساب؟',
    'auth.have_account':      'لديك حساب بالفعل؟',
    'auth.register_link':     'إنشاء حساب',
    'auth.login_link':        'تسجيل الدخول',
    'auth.or':                'أو',
    'auth.full_name':         'الاسم الكامل',
    'auth.full_name_ph':      'أدخل اسمك الكامل',
    'auth.i_am':              'أنا...',
    'auth.candidate':         'باحث عن عمل',
    'auth.employer':          'صاحب عمل',
    'auth.agree_terms':       'أوافق على',
    'auth.terms':             'الشروط والأحكام',

    /* ── Dashboard common ────────────────────────────────── */
    'dash.welcome':           'مرحبًا بعودتك،',
    'dash.profile_complete':  'اكتمال الملف الشخصي',
    'dash.quick_actions':     'إجراءات سريعة',
    'dash.recent_activity':   'النشاط الأخير',
    'dash.notifications':     'الإشعارات',
    'dash.mark_all_read':     'تحديد الكل كمقروء',
    'dash.no_notifications':  'لا توجد إشعارات جديدة',
    'dash.view_all':          'عرض الكل',

    /* ── Candidate dashboard ─────────────────────────────── */
    'cd.stats.applied':       'وظائف تقدمت إليها',
    'cd.stats.favorites':     'وظائف مفضلة',
    'cd.stats.alerts':        'تنبيهات الوظائف',
    'cd.stats.profile_views': 'مشاهدات الملف',
    'cd.applied.title':       'الوظائف المتقدم إليها',
    'cd.applied.empty':       'لم تتقدم لأي وظيفة بعد.',
    'cd.applied.empty_sub':   'ابدأ التصفح والتقديم على الوظائف التي تناسب مهاراتك.',
    'cd.favorites.title':     'الوظائف المفضلة',
    'cd.favorites.empty':     'لم تحفظ أي وظيفة بعد.',
    'cd.favorites.empty_sub': 'تصفح الوظائف واضغط على أيقونة الإشارة لحفظها هنا.',
    'cd.alerts.title':        'تنبيهات الوظائف',
    'cd.alerts.empty':        'لم يتم إعداد تنبيهات بعد.',

    /* ── Employer dashboard ──────────────────────────────── */
    'ed.stats.posted':        'وظائف منشورة',
    'ed.stats.applications':  'طلبات التوظيف',
    'ed.stats.saved':         'مرشحون محفوظون',
    'ed.stats.views':         'مشاهدات الملف',
    'ed.jobs.title':          'وظائفي',
    'ed.jobs.post_btn':       'نشر وظيفة',
    'ed.jobs.empty':          'لم تنشر أي وظيفة بعد.',
    'ed.jobs.empty_sub':      'انشر أول وظيفة لك لتبدأ في استقبال الطلبات.',
    'ed.apps.title':          'طلبات التوظيف',
    'ed.apps.empty':          'لا توجد طلبات بعد.',
    'ed.saved.title':         'المرشحون المحفوظون',
    'ed.saved.empty':         'لم تحفظ أي مرشح بعد.',

    /* ── Settings ────────────────────────────────────────── */
    'settings.account':       'إعدادات الحساب',
    'settings.personal':      'المعلومات الشخصية',
    'settings.profile':       'الملف الشخصي',
    'settings.social':        'روابط التواصل الاجتماعي',
    'settings.save':          'حفظ التغييرات',
    'settings.cancel':        'إلغاء',

    /* ── Forms ───────────────────────────────────────────── */
    'form.submit':            'إرسال',
    'form.cancel':            'إلغاء',
    'form.save':              'حفظ التغييرات',
    'form.upload':            'رفع',
    'form.browse':            'استعراض',
    'form.required':          'هذا الحقل مطلوب.',
    'form.invalid_email':     'يرجى إدخال بريد إلكتروني صحيح.',
    'form.name':              'الاسم الكامل',
    'form.name_ph':           'اسمك الكامل',
    'form.email':             'البريد الإلكتروني',
    'form.email_ph':          'بريدك@الإلكتروني.com',
    'form.subject':           'الموضوع',
    'form.subject_ph':        'كيف يمكننا مساعدتك؟',
    'form.message':           'الرسالة',
    'form.message_ph':        'أخبرنا المزيد عن استفسارك...',
    'form.send':              'إرسال الرسالة ←',

    /* ── Contact page ────────────────────────────────────── */
    'contact.get_in_touch':   'تواصل معنا',
    'contact.subtitle':       'هل لديك سؤال أو تحتاج مساعدة؟ يسعدنا سماعك.',
    'contact.office':         'مكتبنا',
    'contact.office_val':     'متاح عن بُعد في جميع أنحاء العالم.',
    'contact.phone':          'الهاتف',
    'contact.phone_val':      'متاح عبر البريد الإلكتروني أو نموذج التواصل',
    'contact.email':          'البريد الإلكتروني',
    'contact.send_msg':       'أرسل رسالة',

    /* ── About page ──────────────────────────────────────── */
    'about.mission_label':    'مهمتنا',
    'about.mission_title':    'ربط المواهب بالفرص — على مستوى العالم.',
    'about.values_title':     'قيمنا الأساسية',
    'about.values_sub':       'المبادئ التي تحكم كل ما نقوم به',
    'about.team_title':       'تعرف على فريقنا',
    'about.team_sub':         'الأشخاص المتحمسون الذين يبنون Shaghlni',

    /* ── Footer ──────────────────────────────────────────── */
    'footer.tagline':         'ابحث عن وظيفة أحلامك أو اكتشف أفضل المرشحين مع Shaghlni.',
    'footer.quick_link':      'روابط سريعة',
    'footer.candidate':       'المرشح',
    'footer.employers':       'أصحاب العمل',
    'footer.support':         'الدعم',
    'footer.about':           'من نحن',
    'footer.contact':         'تواصل معنا',
    'footer.blog':            'المدونة',
    'footer.browse_jobs':     'تصفح الوظائف',
    'footer.browse_emp':      'تصفح أصحاب العمل',
    'footer.browse_cand':     'تصفح المرشحين',
    'footer.dash_cand':       'لوحة تحكم المرشح',
    'footer.saved_jobs':      'الوظائف المحفوظة',
    'footer.post_job':        'نشر وظيفة',
    'footer.dash_emp':        'لوحة تحكم صاحب العمل',
    'footer.faqs':            'الأسئلة الشائعة',
    'footer.terms':           'الشروط والأحكام',
    'footer.copy':            '© 2026 Shaghlni. جميع الحقوق محفوظة.',

    /* ── Common UI ───────────────────────────────────────── */
    'ui.loading':             'جارٍ التحميل...',
    'ui.load_more':           'تحميل المزيد',
    'ui.back':                'رجوع',
    'ui.close':               'إغلاق',
    'ui.search':              'بحث',
    'ui.apply':               'تطبيق',
    'ui.reset':               'إعادة ضبط',
    'ui.confirm':             'تأكيد',
    'ui.delete':              'حذف',
    'ui.edit':                'تعديل',
    'ui.view':                'عرض',
    'ui.all':                 'الكل',
    'ui.none':                'لا شيء',
    'ui.yes':                 'نعم',
    'ui.no':                  'لا',
    'ui.prev':                'السابق',
    'ui.next':                'التالي',
    'ui.page':                'صفحة',
    'ui.of':                  'من',

    /* ── Quick search modal ──────────────────────────────── */
    'qs.placeholder':         'ابحث عن صفحات، وظائف، شركات...',
    'qs.hint':                'اضغط Esc للإغلاق',
    'qs.no_results':          'لا توجد نتائج.',
    'qs.nav_label':           'التنقل',
    'qs.jobs_label':          'وظائف',

    /* ── Toasts / notifications ──────────────────────────── */
    'toast.saved':            'تم الحفظ بنجاح.',
    'toast.removed':          'تم الحذف.',
    'toast.error':            'حدث خطأ. يرجى المحاولة مرة أخرى.',
    'toast.login_required':   'يرجى تسجيل الدخول للمتابعة.',

    /* ── Theme / lang controls ───────────────────────────── */
    'theme.toggle_dark':      'التبديل إلى الوضع الداكن',
    'theme.toggle_light':     'التبديل إلى الوضع الفاتح',
    'lang.en':                'EN',
    'lang.ar':                'ع',
    'lang.switch_ar':         'العربية',
    'lang.switch_en':         'English',
  }
};

/* ============================================================
   I18n Engine
   ============================================================ */
const I18n = {
  lang: localStorage.getItem('jp_lang') || 'en',

  /** Initialise on page load */
  init() {
    this._applyDir();
    this._applyFont();
    this.apply();
    this._updateSwitcherUI();
    this._wireControls();
  },

  /** Translate a key, fall back to English, then the key itself */
  t(key) {
    return TRANSLATIONS[this.lang]?.[key]
        ?? TRANSLATIONS.en?.[key]
        ?? key;
  },

  /** Apply all data-i18n attributes on the current page */
  apply() {
    /* Text content */
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key  = el.getAttribute('data-i18n');
      const text = this.t(key);
      if (text !== key) el.textContent = text;
    });

    /* Placeholder attributes */
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key  = el.getAttribute('data-i18n-placeholder');
      const text = this.t(key);
      if (text !== key) el.placeholder = text;
    });

    /* aria-label attributes */
    document.querySelectorAll('[data-i18n-aria]').forEach(el => {
      const key  = el.getAttribute('data-i18n-aria');
      const text = this.t(key);
      if (text !== key) el.setAttribute('aria-label', text);
    });

    /* title attributes */
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
      const key  = el.getAttribute('data-i18n-title');
      const text = this.t(key);
      if (text !== key) el.setAttribute('title', text);
    });
  },

  /** Switch to a new language */
  setLang(lang) {
    if (!TRANSLATIONS[lang]) return;
    this.lang = lang;
    localStorage.setItem('jp_lang', lang);
    this._applyDir();
    this._applyFont();
    this.apply();
    this._updateSwitcherUI();
  },

  /** Toggle between en and ar */
  toggle() {
    this.setLang(this.lang === 'en' ? 'ar' : 'en');
  },

  /* ── Private helpers ───────────────────────────────────── */
  _applyDir() {
    const isAr = this.lang === 'ar';
    document.documentElement.lang = this.lang;
    document.documentElement.dir  = isAr ? 'rtl' : 'ltr';
    document.documentElement.setAttribute('data-lang', this.lang);
  },

  _applyFont() {
    document.body.classList.toggle('font-arabic', this.lang === 'ar');
  },

  _updateSwitcherUI() {
    document.querySelectorAll('[data-lang-btn]').forEach(btn => {
      const active = btn.getAttribute('data-lang-btn') === this.lang;
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-pressed', String(active));
    });

    /* Update switcher label if present */
    const label = document.querySelector('[data-lang-current]');
    if (label) label.textContent = this.lang === 'ar' ? 'ع' : 'EN';
  },

  /** Wire [data-lang-btn] click handlers.
   *  Uses event delegation on document so dynamically-injected buttons
   *  (e.g. from navbar-auth.js replacing the nav after init) always work.
   *  Called once on init — safe to call again after DOM mutations. */
  _wireControls() {
    /* Legacy per-element wiring — kept for any buttons already in DOM */
    document.querySelectorAll('[data-lang-btn]').forEach(btn => {
      if (btn._i18nWired) return;
      btn._i18nWired = true;
      btn.addEventListener('click', () => this.setLang(btn.getAttribute('data-lang-btn')));
    });

    /* Delegation — only registered once via flag on document */
    if (!document._i18nDelegated) {
      document._i18nDelegated = true;
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-lang-btn]');
        if (btn) this.setLang(btn.getAttribute('data-lang-btn'));
      });
    }
  }
};

/* Auto-init on DOM ready */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => I18n.init());
} else {
  I18n.init();
}
