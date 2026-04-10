<?php
/**
 * RecommendationService
 *
 * Handles two modes controlled by config/app.php → ml_mode:
 *
 *   'cached'  → read pre-computed rows from `recommendations` table (shared hosting safe)
 *   'local'   → call Python script live, parse JSON, fetch job details from DB
 *
 * Falls back to 'cached' mode if the Python call fails.
 */

class RecommendationService
{
    private RecommendationModel $recoModel;
    private JobModel            $jobModel;
    private array               $config;

    public function __construct()
    {
        $this->recoModel = new RecommendationModel();
        $this->jobModel  = new JobModel();
        $this->config    = require BASE_PATH . '/config/app.php';
    }

    /**
     * Main entry point: return recommended jobs for a candidate profile.
     *
     * @param int $candidateProfileId  candidate_profiles.id
     * @param int $limit               max results to return
     * @return array                   array of job rows (with score + reason)
     */
    public function getRecommendations(int $candidateProfileId, int $limit = 10): array
    {
        if ($this->config['ml_mode'] === 'local') {
            return $this->liveMode($candidateProfileId, $limit);
        }

        return $this->cachedMode($candidateProfileId, $limit);
    }

    // ── Cached mode ───────────────────────────────────────────────────────────

    private function cachedMode(int $candidateProfileId, int $limit): array
    {
        $rows = $this->recoModel->findByCandidate($candidateProfileId, $limit);

        // Fallback: most-recent active jobs for brand-new users
        if (empty($rows)) {
            return (new \JobModel())->getAll(['status' => 'active'], 1, $limit);
        }

        return $rows;
    }

    // ── Live mode (Python) ────────────────────────────────────────────────────

    private function liveMode(int $candidateProfileId, int $limit): array
    {
        $pythonBin = escapeshellcmd($this->config['python_bin']);
        $script    = escapeshellarg($this->config['recommend_script']);
        $cId       = (int) $candidateProfileId;
        $lim       = (int) $limit;

        $command = "{$pythonBin} {$script} --mode local --candidate_id {$cId} --limit {$lim} 2>/dev/null";

        $output = shell_exec($command);

        if (!$output) {
            $this->log("Python returned no output for candidate_id={$cId}. Falling back to cached.");
            return $this->cachedMode($candidateProfileId, $limit);
        }

        $results = json_decode($output, true);

        if (!is_array($results)) {
            $this->log("Python output is not valid JSON for candidate_id={$cId}. Falling back.");
            return $this->cachedMode($candidateProfileId, $limit);
        }

        if (empty($results)) {
            return [];
        }

        // $results expected: [ { job_id, score, reason }, ... ]
        $jobIds = array_column($results, 'job_id');
        $jobs   = $this->jobModel->findByIds($jobIds);

        // Attach score and reason to each job row
        $scoreMap = [];
        foreach ($results as $r) {
            $scoreMap[(int) $r['job_id']] = [
                'score'  => $r['score']  ?? 0,
                'reason' => $r['reason'] ?? null,
            ];
        }

        foreach ($jobs as &$job) {
            $meta          = $scoreMap[(int) $job['id']] ?? [];
            $job['score']  = $meta['score']  ?? 0;
            $job['reason_text'] = $meta['reason'] ?? null;
        }
        unset($job);

        return $jobs;
    }

    // ── Logging ───────────────────────────────────────────────────────────────

    private function log(string $message): void
    {
        $logPath = $this->config['log_path'] ?? BASE_PATH . '/logs/app.log';
        $line    = '[' . date('Y-m-d H:i:s') . '] RecommendationService: ' . $message . PHP_EOL;
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
