<?php

declare(strict_types=1);

require __DIR__.'/../scripts/azure-ci-gate.php';

function gateCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

gateCheck(str_contains(reviewApiFailure(false, 404, '{"error":{"code":"DeploymentNotFound","message":"sensitive prompt"}}'), 'Azure OpenAI API request failed (HTTP 404, code=DeploymentNotFound)'), 'Azure service/error code is missing');
foreach (['{"error":{"code":"secret with spaces","message":"sensitive prompt"}}', '{"error":"sensitive prompt"}', 'sensitive prompt'] as $body) {
    gateCheck(!str_contains(reviewApiFailure(false, 404, $body), 'sensitive prompt')
        && !str_contains(reviewApiFailure(false, 404, $body), 'secret'), 'API diagnostics exposed the error body');
}
gateCheck(str_starts_with(reviewApiFailure(true, 404, '{}'), 'GitHub API request failed'), 'GitHub and Azure failures are ambiguous');

$sha = str_repeat('a', 40);
$run = ['id' => 123, 'event' => 'pull_request', 'status' => 'completed', 'conclusion' => 'success',
    'head_sha' => $sha, 'repository' => ['full_name' => REVIEW_REPOSITORY],
    'head_repository' => ['full_name' => 'Mefizz/ohealth'], 'run_number' => 10, 'run_attempt' => 1,
    'head_branch' => 'feature/fork-review', 'pull_requests' => [],
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
    'head' => ['sha' => $sha, 'ref' => 'feature/fork-review', 'repo' => ['full_name' => 'Mefizz/ohealth']], 'base' => ['ref' => 'main']];
$dispatches = 0;
$handler = static function (string $method, string $url, ?array $payload) use ($jobs, $pr, $run, &$dispatches): array {
    if (str_contains($url, '/jobs?')) {
        return $jobs;
    }
    if (str_contains($url, '/pulls?state=open&base=main&')) {
        return [$pr];
    }
    if (str_contains($url, '/commits/')) {
        throw new RuntimeException('Fork PR discovery must not depend on the commit-to-PR index');
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
$logs = [];
$forkRun = array_replace($run, ['pull_requests' => []]);
gateCheck(queueAzureReview($forkRun, $handler, static function (string $message) use (&$logs): void {
    $logs[] = $message;
}) === [7] && str_contains(implode(' ', $logs), 'PR #7 queued'), 'Fork run with empty associations was not dispatched or logged');
$pages = [];
gateCheck(queueAzureReview($run, static function ($method, $url, $payload) use ($handler, $pr, &$pages): array {
    if (str_contains($url, '/pulls?state=open')) {
        $pages[] = $url;
        return str_ends_with($url, 'page=1') ? array_fill(0, 100, array_replace_recursive($pr, ['head' => ['sha' => str_repeat('b', 40)]])) : [$pr];
    }
    return $handler($method, $url, $payload);
}) === [7] && count($pages) === 2, 'PR discovery did not paginate beyond the first 100 PRs');
foreach (['sha' => str_repeat('b', 40), 'ref' => 'another-branch', 'repo' => ['full_name' => 'another/fork']] as $field => $value) {
    gateCheck(queueAzureReview($run, static function ($method, $url, $payload) use ($handler, $pr, $field, $value): array {
        return str_contains($url, '/pulls?state=open') ? [array_replace_recursive($pr, ['head' => [$field => $value]])] : $handler($method, $url, $payload);
    }) === [], 'Mismatched discovery candidate accepted: '.$field);
}
foreach (['draft' => true, 'state' => 'closed', 'author_association' => 'CONTRIBUTOR', 'head' => ['sha' => str_repeat('c', 40)]] as $field => $value) {
    gateCheck(queueAzureReview($run, static function ($method, $url, $payload) use ($handler, $pr, $field, $value): array {
        return str_contains($url, '/pulls/7') ? array_replace_recursive($pr, [$field => $value]) : $handler($method, $url, $payload);
    }) === [], 'PR eligibility was not revalidated before dispatch: '.$field);
}
try {
    queueAzureReview($run, static function ($method, $url, $payload) use ($handler, $pr): array {
        return str_contains($url, '/pulls?state=open') ? array_fill(0, 100, $pr) : $handler($method, $url, $payload);
    });
    throw new LogicException('Incomplete discovery silently dispatched reviews');
} catch (RuntimeException $error) {
    gateCheck(str_contains($error->getMessage(), 'pagination budget'), 'Wrong discovery overflow failure');
}
$latestFailure = array_replace($run, ['id' => 124, 'run_number' => 11, 'conclusion' => 'failure']);
gateCheck(queueAzureReview($run, static function ($method, $url, $payload) use ($handler, $run, $latestFailure): array {
    return str_contains($url, '/domain-tests.yml/runs?') ? ['workflow_runs' => [$run, $latestFailure]] : $handler($method, $url, $payload);
}) === [], 'Superseded automatic run must skip instead of dispatching or failing');
try {
    azureCiLatest($pr, static function ($method, $url) use ($run, $latestFailure, $jobs): array {
        return str_contains($url, '/runs?') ? ['workflow_runs' => [$run, $latestFailure]] : $jobs;
    });
    throw new LogicException('Older green CI incorrectly accepted after newer failure');
} catch (RuntimeException $error) {
    gateCheck(str_contains($error->getMessage(), 'needs successful'), 'Wrong failure');
}
echo "CI provenance, required jobs, sequencing and latest-attempt checks passed.\n";
$issueHandler = static function ($method, $url, $payload) use ($handler): array {
    if ($url === 'https://api.github.com/graphql') {
        return ['data' => ['repository' => ['issue' => ['closedByPullRequestsReferences' => [
            'nodes' => [['number' => 7, 'repository' => ['nameWithOwner' => REVIEW_REPOSITORY]]],
            'pageInfo' => ['hasNextPage' => false],
        ]]]]];
    }
    return $handler($method, $url, $payload);
};
gateCheck(queueIssueReview(963, $issueHandler) === [7], 'Issue change did not queue linked green PR');
gateCheck(queueIssueReview(963, static function ($method, $url, $payload) use ($issueHandler, $latestFailure): array {
    return str_contains($url, '/domain-tests.yml/runs?') ? ['workflow_runs' => [$latestFailure]] : $issueHandler($method, $url, $payload);
}) === [], 'Issue edit bypassed failed CI');
foreach (['draft' => true, 'state' => 'closed', 'author_association' => 'CONTRIBUTOR', 'base' => ['ref' => 'develop']] as $field => $value) {
    gateCheck(queueIssueReview(963, static function ($method, $url, $payload) use ($issueHandler, $pr, $field, $value): array {
        return str_contains($url, '/pulls/7') ? array_replace($pr, [$field => $value]) : $issueHandler($method, $url, $payload);
    }) === [], 'Issue edit queued an ineligible PR: '.$field);
}
echo "Issue edits preserve CI and trusted PR gates.\n";
