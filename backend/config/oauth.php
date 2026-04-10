<?php
/**
 * OAuth 2.0 Credentials
 *
 * 1. Go to https://console.cloud.google.com/ → APIs & Services → Credentials
 *    Create an OAuth 2.0 Client ID (Web application).
 *    Add http://localhost/jobpilot/api/auth/google/callback to Authorised redirect URIs.
 *
 * 2. Go to https://www.linkedin.com/developers/ → My Apps → Auth tab.
 *    Add http://localhost/jobpilot/api/auth/linkedin/callback to Authorised Redirect URLs.
 *
 * Replace the placeholder strings below with your real credentials.
 */

return [

    'google' => [
        'client_id'     => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri'  => 'http://localhost/jobpilot/api/auth/google/callback',
        'scopes'        => 'openid email profile',
    ],

    'linkedin' => [
        'client_id'     => 'YOUR_LINKEDIN_CLIENT_ID',
        'client_secret' => 'YOUR_LINKEDIN_CLIENT_SECRET',
        'redirect_uri'  => 'http://localhost/jobpilot/api/auth/linkedin/callback',
        'scopes'        => 'openid profile email',
    ],

];
