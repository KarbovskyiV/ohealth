<?php

declare(strict_types=1);

require_once __DIR__.'/azure-openai-review.php';

/** Verify the actual run and all required jobs; PR text and artifacts are never executed. */
function azureCiJobs(array $run, string $headSha, callable $request): bool
{
    if (($run['event'] ?? '') !== 'pull_request' || ($run['status'] ?? '') !== 'completed'
        || ($run['conclusion'] ?? '') !== 'success' || ($run['head_sha'] ?? '') !== $headSha
        || ($run['repository']['full_name'] ?? '') !== REVIEW_REPOSITORY
        || ($run['path'] ?? '') !== '.github/workflows/domain-tests.yml'
        || !is_int($run['id'] ?? null)) {
        return false;
    }
    $required = ['Owned domain suites' => false, 'Frontend build' => false];
    for ($page = 1; $page <= 5; $page++) {
        $result = $request('GET', 'https://api.github.com/repos/'.REVIEW_REPOSITORY.'/actions/runs/'.$run['id']."/jobs?per_page=100&page=$page", null);
        foreach ($result['jobs'] ?? [] as $job) {
            if (array_key_exists($job['name'], $required)) {
                $required[$job['name']] = ($job['status'] ?? '') === 'completed' && ($job['conclusion'] ?? '') === 'success';
            }
        }
        if (count($result['jobs'] ?? []) < 100) {
            break;
        }
    }

    return !in_array(false, $required, true);
}

function azureCiLatest(array $pr, callable $request, bool $requireSuccess = true): array
{
    $api = 'https://api.github.com/repos/'.REVIEW_REPOSITORY;
    $sha = $pr['head']['sha'];
    $result = $request('GET', "$api/actions/workflows/domain-tests.yml/runs?event=pull_request&head_sha=$sha&per_page=20", null);
    // Never fall back to an older successful attempt after a newer failure or pending run.
    $runs = $result['workflow_runs'] ?? [];
    usort($runs, static fn (array $a, array $b): int => [(int) $b['run_number'], (int) ($b['run_attempt'] ?? 1)] <=> [(int) $a['run_number'], (int) ($a['run_attempt'] ?? 1)]);
    $run = $runs[0] ?? [];
    if ($requireSuccess && !azureCiJobs($run, $sha, $request)) {
        throw new RuntimeException('Current PR commit needs successful Owned domain suites and Frontend build before Azure review.');
    }

    return $run;
}

function queueAzureReview(array $run, callable $request): array
{
    $sha = $run['head_sha'] ?? '';
    if (!preg_match('/^[a-f0-9]{40}$/', $sha) || !azureCiJobs($run, $sha, $request)) {
        return [];
    }
    $api = 'https://api.github.com/repos/'.REVIEW_REPOSITORY;
    $queued = [];
    foreach (reviewPages("$api/commits/$sha/pulls", $request) as $candidate) {
        $number = $candidate['number'];
        $pr = $request('GET', "$api/pulls/$number", null);
        if ($pr['state'] !== 'open' || $pr['draft'] || $pr['base']['ref'] !== 'main'
            || $pr['head']['sha'] !== $sha
            || ($pr['head']['repo']['full_name'] ?? '') !== ($run['head_repository']['full_name'] ?? '')
            || !in_array($pr['author_association'] ?? '', ['OWNER', 'MEMBER', 'COLLABORATOR'], true)) {
            continue;
        }
        $latest = azureCiLatest($pr, $request, false);
        if (($latest['id'] ?? null) !== $run['id'] || ($latest['run_attempt'] ?? 1) !== ($run['run_attempt'] ?? 1)
            || !azureCiJobs($latest, $sha, $request)) {
            continue;
        }
        $request('POST', "$api/actions/workflows/azure-openai-review.yml/dispatches", [
            'ref' => 'main', 'inputs' => ['pull_request_number' => (string) $number, 'expected_head_sha' => $sha],
        ]);
        $queued[] = $number;
    }

    return $queued;
}

function queueIssueReview(int $number, callable $request): array
{
    if ($number < 1) {
        throw new RuntimeException('Invalid issue number.');
    }
    $result = $request('POST', 'https://api.github.com/graphql', [
        'query' => 'query($number:Int!){repository(owner:"openhealths",name:"nationHealth"){issue(number:$number){closedByPullRequestsReferences(first:20){nodes{number repository{nameWithOwner}} pageInfo{hasNextPage}}}}}',
        'variables' => ['number' => $number],
    ]);
    $connection = $result['data']['repository']['issue']['closedByPullRequestsReferences'] ?? null;
    if (!empty($result['errors']) || !is_array($connection) || ($connection['pageInfo']['hasNextPage'] ?? true)) {
        throw new RuntimeException('Issue-linked PRs could not be read completely.');
    }
    $api = 'https://api.github.com/repos/'.REVIEW_REPOSITORY;
    $queued = [];
    foreach ($connection['nodes'] as $node) {
        if (($node['repository']['nameWithOwner'] ?? '') !== REVIEW_REPOSITORY) {
            continue;
        }
        $pr = $request('GET', "$api/pulls/".$node['number'], null);
        if ($pr['state'] !== 'open' || $pr['draft'] || $pr['base']['ref'] !== 'main'
            || !in_array($pr['author_association'] ?? '', ['OWNER', 'MEMBER', 'COLLABORATOR'], true)) {
            continue;
        }
        $run = azureCiLatest($pr, $request, false);
        if (!azureCiJobs($run, $pr['head']['sha'], $request)
            || ($pr['head']['repo']['full_name'] ?? '') !== ($run['head_repository']['full_name'] ?? '')) {
            continue;
        }
        $request('POST', "$api/actions/workflows/azure-openai-review.yml/dispatches", [
            'ref' => 'main', 'inputs' => ['pull_request_number' => (string) $pr['number'], 'expected_head_sha' => $pr['head']['sha']],
        ]);
        $queued[] = $pr['number'];
    }

    return $queued;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        if (reviewEnv('GITHUB_REPOSITORY') !== REVIEW_REPOSITORY || reviewEnv('GITHUB_REF') !== 'refs/heads/main') {
            throw new RuntimeException('CI gate must run on trusted company main.');
        }
        if (($_SERVER['argv'][1] ?? '') === '--queue') {
            $id = reviewEnv('CI_RUN_ID');
            if (!ctype_digit($id)) {
                throw new RuntimeException('Invalid CI run ID.');
            }
            $run = reviewRequest('GET', 'https://api.github.com/repos/'.REVIEW_REPOSITORY.'/actions/runs/'.$id);
            echo 'Queued reviews: '.count(queueAzureReview($run, 'reviewRequest')).".\n";
        } elseif (($_SERVER['argv'][1] ?? '') === '--issue') {
            $id = reviewEnv('ISSUE_NUMBER');
            if (!preg_match('/^[1-9]\d{0,8}$/', $id)) {
                throw new RuntimeException('Invalid issue number.');
            }
            echo 'Queued issue reviews: '.count(queueIssueReview((int) $id, 'reviewRequest')).".\n";
        } else {
            [$number, $api] = reviewContext();
            $pr = reviewRequest('GET', "$api/pulls/$number");
            if (($expected = getenv('EXPECTED_HEAD_SHA')) && $expected !== $pr['head']['sha']) {
                throw new RuntimeException('PR commit changed since CI completion.');
            }
            azureCiLatest($pr, 'reviewRequest');
            echo "Current CI verified.\n";
        }
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage()."\n");
        exit(1);
    }
}
