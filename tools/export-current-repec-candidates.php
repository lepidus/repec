<?php

/**
 * Export current OJS article metadata for legacy RePEc handle matching.
 *
 * Run from the OJS root or from any path:
 * php plugins/generic/repec/tools/export-current-repec-candidates.php --context-path=bjpe > current-articles.tsv
 * php plugins/generic/repec/tools/export-current-repec-candidates.php --context-id=1 > current-articles.tsv
 */

use APP\facades\Repo;
use APP\submission\Submission;
use PKP\config\Config;
use PKP\db\DAORegistry;

$ojsRoot = realpath(__DIR__ . '/../../../..');
if (!$ojsRoot || !file_exists($ojsRoot . '/tools/bootstrap.inc.php')) {
    fwrite(STDERR, "Could not locate OJS root from this plugin path.\n");
    exit(1);
}

require_once $ojsRoot . '/tools/bootstrap.inc.php';

$options = getopt('', array('context-path:', 'context-id:', 'help'));
if (isset($options['help']) || (!isset($options['context-path']) && !isset($options['context-id']))) {
    fwrite(STDERR, "Usage: php plugins/generic/repec/tools/export-current-repec-candidates.php --context-path=PATH\n");
    fwrite(STDERR, "   or: php plugins/generic/repec/tools/export-current-repec-candidates.php --context-id=ID\n");
    exit(isset($options['help']) ? 0 : 1);
}

$journalDao = DAORegistry::getDAO('JournalDAO');
$context = null;
if (isset($options['context-id'])) {
    $context = $journalDao->getById((int) $options['context-id']);
} else {
    $context = $journalDao->getByPath((string) $options['context-path']);
}

if (!$context) {
    fwrite(STDERR, "Journal context not found.\n");
    exit(1);
}

$columns = array(
    'submission_id',
    'best_id',
    'title',
    'first_author',
    'authors',
    'year',
    'month',
    'volume',
    'issue',
    'pages',
    'doi',
    'date_published',
    'issue_id',
    'issue_identification',
    'article_url',
);

echo implode("\t", $columns) . "\n";

$preferredLocale = getPreferredLocale($context);
$submissions = Repo::submission()->getCollector()
    ->filterByContextIds([$context->getId()])
    ->filterByStatus([Submission::STATUS_PUBLISHED])
    ->getMany();

foreach ($submissions as $submission) {
    $publication = $submission->getCurrentPublication();
    if (!$publication) {
        continue;
    }

    $issue = getIssue($context, $publication, $submission);
    $datePublished = (string) $publication->getData('datePublished');
    $year = $datePublished ? date('Y', strtotime($datePublished)) : '';
    $month = $datePublished ? date('F', strtotime($datePublished)) : '';
    if ($issue) {
        $year = $issue->getYear() ?: $year;
        if ($issue->getDatePublished()) {
            $month = date('F', strtotime($issue->getDatePublished()));
        }
    }

    $authors = getAuthors((array) $publication->getData('authors'), $preferredLocale);
    $bestId = $submission->getBestId();
    $row = array(
        $submission->getId(),
        $bestId,
        localizedValue($publication->getData('title'), $preferredLocale),
        isset($authors[0]) ? $authors[0] : '',
        implode('; ', $authors),
        $year,
        $month,
        $issue ? $issue->getVolume() : '',
        $issue ? $issue->getNumber() : '',
        $publication->getData('pages'),
        $publication->getStoredPubId('doi') ?: $submission->getStoredPubId('doi'),
        $datePublished,
        $issue ? $issue->getId() : '',
        $issue ? getIssueIdentification($issue) : '',
        buildArticleUrl($context, $bestId),
    );

    echo implode("\t", array_map('tsvValue', $row)) . "\n";
}

function getIssue($context, $publication, $submission)
{
    $issueId = $publication->getData('issueId');
    if ($issueId) {
        return Repo::issue()->get((int) $issueId, $context->getId());
    }
    return Repo::issue()->getCollector()
        ->filterByContextIds([$context->getId()])
        ->filterByPublished(true)
        ->getMany()
        ->first(function ($issue) use ($submission) {
            return Repo::submission()->getCollector()
                ->filterByIssueIds([$issue->getId()])
                ->filterByStatus([Submission::STATUS_PUBLISHED])
                ->getIds()
                ->contains($submission->getId());
        });
}

function getAuthors($authors, $preferredLocale)
{
    $names = array();
    foreach ($authors as $author) {
        $name = localizedValue($author->getData('preferredPublicName'), $preferredLocale);
        if ($name === '') {
            $name = trim(localizedValue($author->getData('givenName'), $preferredLocale) . ' ' . localizedValue($author->getData('familyName'), $preferredLocale));
        }
        if ($name !== '') {
            $names[] = $name;
        }
    }
    return $names;
}

function getPreferredLocale($context)
{
    $locale = (string) $context->getPrimaryLocale();
    if ($locale !== '') {
        return $locale;
    }

    $supportedLocales = (array) $context->getSupportedLocales();
    return reset($supportedLocales) ?: '';
}

function localizedValue($value, $preferredLocale)
{
    if (!is_array($value)) {
        return trim((string) $value);
    }

    if ($preferredLocale !== '' && !empty($value[$preferredLocale])) {
        return trim((string) $value[$preferredLocale]);
    }

    foreach ($value as $localized) {
        if (trim((string) $localized) !== '') {
            return trim((string) $localized);
        }
    }
    return '';
}

function getIssueIdentification($issue)
{
    $parts = array();
    if ($issue->getVolume()) {
        $parts[] = 'Vol. ' . $issue->getVolume();
    }
    if ($issue->getNumber()) {
        $parts[] = 'No. ' . $issue->getNumber();
    }
    if ($issue->getYear()) {
        $parts[] = $issue->getYear();
    }
    return implode(', ', $parts);
}

function buildArticleUrl($context, $bestId)
{
    $baseUrl = rtrim((string) Config::getVar('general', 'base_url'), '/');
    return $baseUrl . '/index.php/' . rawurlencode($context->getPath()) . '/article/view/' . rawurlencode($bestId);
}

function tsvValue($value)
{
    $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value);
    return str_replace(array("\t", "\r", "\n"), ' ', trim($value));
}
