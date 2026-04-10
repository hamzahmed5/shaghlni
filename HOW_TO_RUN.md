# Shaghlni — How to Run Locally

## Step 1 — Install Required Software

Download and install both:
- **Laragon** → https://laragon.org/download (Full version)
- **XAMPP** → https://www.apachefriends.org/download.html

---

## Step 2 — Put the Project in the Right Place

Copy this entire folder (`jobpilot`) into:
```
C:\laragon\www\jobpilot\
```

So the structure looks like:
```
C:\laragon\www\jobpilot\frontend\
C:\laragon\www\jobpilot\backend\
C:\laragon\www\jobpilot\database\
...
```

---

## Step 3 — Start the Servers

1. Open **Laragon** → click **Start All** (starts Apache)
2. Open **XAMPP Control Panel** → click **Start** next to **MySQL**

---

## Step 4 — Set Up the Database

Open **Command Prompt** and run:
```
C:\xampp\mysql\bin\mysql.exe -u root < "C:\laragon\www\jobpilot\database\schema.sql"
```

---

## Step 5 — Open the Website

Open your browser and go to:
```
http://localhost/jobpilot/frontend/index.html
```

That's it! 🎉

---

## Login Credentials (for testing)

You can register a new account from the website.

- Go to: http://localhost/jobpilot/frontend/pages/auth/register.html
- Choose **Candidate** or **Employer**
- Fill in your details and register

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Page not loading | Make sure Laragon is running (Apache started) |
| Database error | Make sure XAMPP MySQL is started |
| Wrong website opens | Make sure the folder is named exactly `jobpilot` inside `C:\laragon\www\` |
| API not working | Check that the folder path is `C:\laragon\www\jobpilot\` not anywhere else |
