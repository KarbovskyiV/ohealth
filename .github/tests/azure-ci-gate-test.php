<?php

declare(strict_types=1);

require __DIR__.'/../scripts/azure-ci-gate.php';

function gateCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$sha = str_repeat('a', 40);
$run = ['id' => 123, 'event' => 'pull_request', 'status' => 'completed', 'conclusion' => 'success',
    'head_sha' => $sha, 'repository' => ['full_name' => REVIEW_REPOSITORY],
    'head_repository' => ['full_name' => 'Mefizz/ohealth'], 'run_number' => 10, 'run_attempt' => 1,
    'path' => '.github/workflows/domain-tests.yml'];
$jobs = ['jobs' => [['name' => 'Owned domain suites', 'status' => 'completed', 'conclusion' => 'success'],
    ['name' => 'Frontend build', 'status' => 'completed', 'conclusion' => 'success']]];
$request = static fn (): array => $jobs;
gateCheck(azureCiJobs($run, $sha, $request), 'Successful matching CI must be accepted');
foreach (['event' => 'push', 'conclusion' => 'failure', 'status' => 'in_progress', 'head_sha' => str_repeat('b', 40),
    'path' => '.github/workflows/untrusted.yml', 'repository' => ['full_name' => 'Mefizz/ohealth']] as $key => $value) {
    gateCheck(!azureCiJobs(array_replace($run, [$key => $value]), $sha, $request), "Untrusted CI $key accepted");
}
gateCheck(!azureCiJobs($run, $sha, static fn (): array => ['jobs' => [$jobs['jobs'][0]]]), 'Missing frontend build accepted');
$failedJobs = $jobs;
$failedJobs['jobs'][1]['conclusion'] = 'skipped';
gateCheck(!azureCiJobs($run, $sha, static fn (): array => $failedJobs), 'Skipped frontend build accepted');
$pr = ['number' => 7, 'state' => 'open', 'draft' => false, 'author_association' => 'COLLABORATOR',
    'head' => ['sha' => $sha, 'repo' => ['full_name' => 'Mefizz/ohealth']], 'base' => ['ref' => 'main']];
$dispatches = 0;
$handler = static function (string $method, string $url, ?array $payload) use ($jobs, $pr, $run, &$dispatches): array {
    if (str_contains($url, '/jobs?')) {
        return $jobs;
    }
    if (str_contains($url, '/commits/')) {
        return [$pr];
    }
    if (str_contains($url, '/pulls/7')) {
        return $pr;
    }
    if (str_contains($url, '/domain-tests.yml/runs?')) {
        return ['workflow_runs' => [$run]];
    }
    if ($method === 'POST' && str_ends_with($url, '/dispatches')) {
        gateCheck($payload['ref'] === 'main' && $payload['inputs']['expected_head_sha'] === $pr['head']['sha'], 'Wrong dispatch commit');
        $dispatches++;
        return [];
    }
    throw new RuntimeException('Unexpected fixture request');
};
gateCheck(queueAzureReview($run, $handler) === [7] && $dispatches === 1, 'Valid PR was not dispatched');
$latestFailure = array_replace($run, ['id' => 124, 'run_number' => 11, 'conclusion' => 'failure']);
try {
    azureCiLatest($pr, static function ($method, $url) use ($run, $latestFailure, $jobs): array {
        return str_contains($url, '/runs?') ? ['workflow_runs' => [$run, $latestFailure]] : $jobs;
    });
    throw new LogicException('Older green CI incorrectly accepted after newer failure');
} catch (RuntimeException $error) {
    gateCheck(str_contains($error->getMessage(), 'needs successful'), 'Wrong failure');
}
echo "CI provenance, required jobs, sequencing and latest-attempt checks passed.\n";
