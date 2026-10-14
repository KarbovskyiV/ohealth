<?php

declare(strict_types=1);

require __DIR__.'/../scripts/azure-requirements-review.php';

function requirementsCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

requirementsCheck(requirementIds('PHP 8.4, release 3.5.1.3') === [], 'Ordinary versions parsed as requirements');
requirementsCheck(requirementIds('ТВ: 3.5.1.3, 3.7.1.3') === ['3.5.1.3', '3.7.1.3'], 'Ukrainian list not parsed');
requirementsCheck(requirementIds('Requirements: 3.5.1.3') === ['3.5.1.3'], 'English declaration not parsed');
requirementsCheck(requirementPrIds(['title' => 'Feature', 'body' => 'Closes #7'], static fn (): array =>
    ['title' => '3.7.1.3 Вимоги', 'body' => 'ТВ: 3.5.1.3']) === ['3.5.1.3', '3.7.1.3'], 'Linked issue not inspected');
$corpus = requirementCorpus();
$clause = requirementClause($corpus, '3.5.1.3');
requirementsCheck($corpus['edition'] === '39' && $corpus['pdfPages'] === 218, 'Unexpected edition/pages');
requirementsCheck(str_contains($clause['text'], '3.5.1.3.1.1.5') && str_contains($clause['text'], 'skip_medication_dispense_sign'), 'Child requirements omitted');
requirementsCheck(!str_contains($clause['text'], '3.5.1.4 Вимоги'), 'Sibling requirements leaked');
try {
    requirementClause($corpus, '3.999.1');
    throw new LogicException('Invented requirement accepted');
} catch (RuntimeException $error) {
    requirementsCheck(str_contains($error->getMessage(), 'not found'), 'Wrong missing-clause failure');
}
foreach (['.env', 'app/../.env', 'vendor/Example.php', 'app/private.pem', 'docs/technical-requirements/edition-39.json'] as $path) {
    requirementsCheck(!requirementCodePath($path), 'Secret/non-source path accepted');
}
requirementsCheck(requirementCodePath('app/Livewire/Example.php'), 'Source path rejected');
$context = ['head' => ['app/Example.php' => ['lines' => [4 => 'return false;']]], 'base' => []];
$result = ['status' => 'gap', 'explanation' => 'Конкретна невідповідність', 'evidence' => [['side' => 'head', 'path' => 'app/Example.php', 'line' => 4]]];
requirementsCheck(requirementResult($result, $context) === $result, 'Valid citation rejected');
foreach ([['status' => 'safe'], ['evidence' => []], ['evidence' => [['side' => 'head', 'path' => 'app/Example.php', 'line' => 5]]]] as $change) {
    try {
        requirementResult(array_replace($result, $change), $context);
        throw new LogicException('Invalid assessment accepted');
    } catch (RuntimeException) {
    }
}
function simulateRequirements(array $options = []): array
{
    putenv('GITHUB_REPOSITORY='.REVIEW_REPOSITORY);
    putenv('GITHUB_REF=refs/heads/main');
    putenv('PR_NUMBER=123');
    putenv('GITHUB_RUN_ID=456');
    putenv('AZURE_OPENAI_ENDPOINT=https://test-resource.cognitiveservices.azure.com');
    putenv('AZURE_OPENAI_DEPLOYMENT=gpt-6.1-sol');
    putenv('EXPECTED_HEAD_SHA');
    putenv('AZURE_REVIEW_HEAD_SHA');
    putenv('AZURE_REVIEW_CHECK_ID=42');
    $pr = ['state' => 'open', 'draft' => false, 'title' => 'Feature', 'body' => $options['body'] ?? 'ТВ: 3.5.1.3',
        'head' => ['sha' => str_repeat('a', 40)], 'base' => ['sha' => str_repeat('b', 40), 'ref' => 'main']];
    $models = 0;
    $reads = 0;
    $comments = [];
    $updates = [];
    $request = static function ($method, $url, $payload) use ($pr, $options, &$models, &$reads, &$comments, &$updates): array {
        if (str_contains($url, '/check-runs/')) {
            $updates[] = $payload;
            return [];
        }
        if (str_ends_with($url, '/pulls/123')) {
            $reads++;
            return !empty($options['stale']) && $models > 1 ? array_replace_recursive($pr, ['head' => ['sha' => str_repeat('c', 40)]]) : $pr;
        }
        if (str_contains($url, '/comments?')) {
            return $options['comments'] ?? [];
        }
        if (str_contains($url, '/files?')) {
            return [['filename' => 'app/Example.php', 'status' => 'modified', 'patch' => "@@ -1 +1 @@\n-old\n+new"]];
        }
        if (str_contains($url, '/git/trees/')) {
            return ['truncated' => false, 'tree' => [['type' => 'blob', 'path' => 'app/Example.php']]];
        }
        if (str_contains($url, '/contents/')) {
            return ['type' => 'file', 'encoding' => 'base64', 'content' => base64_encode("<?php\nreturn true;\n")];
        }
        if (str_contains($url, '/chat/completions')) {
            $models++;
            requirementsCheck($payload['store'] === false && $payload['max_completion_tokens'] === 6000, 'Missing bounded request');
            $result = $models === 1 ? ['paths' => $options['paths'] ?? ['app/Example.php']] :
                ['status' => 'covered', 'explanation' => 'Поведінка підтверджена кодом',
                    'evidence' => [['side' => 'head', 'path' => 'app/Example.php', 'line' => $options['line'] ?? 2]]];
            return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($result)]]]];
        }
        if (in_array($method, ['POST', 'PATCH'], true) && str_contains($url, '/comments')) {
            $comments[] = $payload['body'];
            return ['id' => 1];
        }
        throw new RuntimeException('Unexpected request in requirements fixture');
    };
    $error = null;
    try {
        runRequirementsReview($request);
    } catch (Throwable $failure) {
        $error = $failure->getMessage();
    }

    return compact('models', 'comments', 'updates', 'error');
}
$simulation = simulateRequirements();
requirementsCheck($simulation['error'] === null && $simulation['models'] === 2 && count($simulation['comments']) === 1, 'Full requirements flow failed');
requirementsCheck(str_contains($simulation['comments'][0], 'PDF стор. 48–50') && str_contains($simulation['comments'][0], '/blob/'.str_repeat('a', 40)), 'Missing verified source pages/code links');
$repeat = simulateRequirements(['comments' => [['id' => 1, 'user' => ['login' => 'github-actions[bot]'], 'body' => $simulation['comments'][0]]]]);
requirementsCheck($repeat['models'] === 0 && $repeat['comments'] === [], 'Repeated review spent tokens');
$none = simulateRequirements(['body' => 'No requirements declared']);
requirementsCheck($none['models'] === 0 && $none['comments'] === [] && $none['updates'][0]['conclusion'] === 'neutral', 'No-requirements flow was not neutral');
foreach ([['paths' => ['.env']], ['line' => 900], ['body' => 'ТВ: 3.999.1']] as $option) {
    $bad = simulateRequirements($option);
    requirementsCheck($bad['error'] !== null && $bad['comments'] === [], 'Unsafe or invalid result published');
}
$stale = simulateRequirements(['stale' => true]);
requirementsCheck($stale['comments'] === [] && $stale['updates'][count($stale['updates']) - 1]['conclusion'] === 'neutral', 'Stale result published');
echo 'Requirements parsing, corpus, code citations, full flow, deduplication and stale-result checks passed ('.count($corpus['sections'])." clauses).\n";
