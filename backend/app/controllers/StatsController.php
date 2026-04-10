<?php
/**
 * StatsController
 *
 * GET /api/stats  → index()
 * Returns platform-wide counts for the homepage.
 */

class StatsController
{
    public function index(array $params = []): void
    {
        $db = \DB::get();

        $jobs       = (int) $db->query('SELECT COUNT(*) FROM jobs WHERE status = "active"')->fetchColumn();
        $companies  = (int) $db->query('SELECT COUNT(*) FROM employer_profiles')->fetchColumn();
        $candidates = (int) $db->query('SELECT COUNT(*) FROM candidate_profiles')->fetchColumn();
        $categories = (int) $db->query('SELECT COUNT(DISTINCT industry) FROM jobs WHERE industry IS NOT NULL AND industry != ""')->fetchColumn();
        $newJobs    = (int) $db->query('SELECT COUNT(*) FROM jobs WHERE status = "active" AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();

        Response::success([
            'live_jobs'       => $jobs,
            'companies'       => $companies,
            'candidates'      => $candidates,
            'job_categories'  => $categories,
            'new_jobs'        => $newJobs,
        ]);
    }
}
