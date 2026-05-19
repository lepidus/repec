<?php

/**
 * @file plugins/generic/repec/pages/RepecHandler.php
 *
 * @class RepecHandler
 * @ingroup plugins_generic_repec
 *
 * @brief Public handler for RePEc/ReDIF archive URLs.
 */

namespace APP\plugins\generic\repec\pages;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\issue\Collector as IssueCollector;
use APP\plugins\generic\repec\classes\RepecFormatter;
use APP\plugins\generic\repec\classes\RepecLegacyHandleMap;
use APP\plugins\generic\repec\classes\RepecSettingsForm;
use APP\submission\Collector as SubmissionCollector;
use APP\submission\Submission;
use PKP\config\Config;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RepecHandler extends Handler
{
    private const DEFAULT_ARTICLE_HANDLE_PATTERN = 'v:%v:y:%Y:i:%i:id:%a';

    public function index($args, $request)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'repecplugin');
        $context = $request->getContext();
        if (!$plugin) {
            $this->notFound();
        }

        if (!$context) {
            $this->handleGlobalArchive($plugin, $request, $args);
        }

        if (!$plugin->getEnabled($context->getId())) {
            $this->notFound();
        }

        if ($this->getGlobalSeriesCodeForContext($plugin, $context)) {
            $this->notFound();
        }

        $settings = $this->getSettings($plugin, $context, $request);
        if (!$this->hasRequiredSettings($settings)) {
            $this->notFound();
        }

        $path = $this->getRepecPath($request, $args);
        if (empty($path)) {
            $request->redirect($context->getPath(), 'repec', $settings['archiveCode']);
        }

        if ($path[0] !== $settings['archiveCode']) {
            $this->notFound();
        }

        if (count($path) === 1) {
            $this->sendDirectoryIndex($request, $settings, 'archive');
        }

        if (count($path) === 2 && $path[1] === $settings['archiveCode'] . 'arch.redif') {
            $this->sendRedif($this->getFormatter()->formatArchive($this->getArchiveData($request, $context, $settings)));
        }

        if (count($path) === 2 && $path[1] === $settings['archiveCode'] . 'seri.redif') {
            $this->sendRedif($this->getFormatter()->formatSeries($this->getSeriesData($request, $context, $settings)));
        }

        if (count($path) === 2 && $path[1] === $settings['seriesCode']) {
            $this->sendDirectoryIndex($request, $settings, 'series', $this->getIssueFileNames($context));
        }

        if (count($path) === 3 && $path[1] === $settings['seriesCode']) {
            $issueFileNames = $this->getIssueFileNames($context);
            if (isset($issueFileNames[$path[2]])) {
                $this->sendRedif($this->getFormatter()->formatArticles($this->getArticlesData($request, $context, $settings, $issueFileNames[$path[2]])));
            }
        }

        $this->notFound();
    }

    private function handleGlobalArchive($plugin, $request, $args)
    {
        $path = $this->getRepecPath($request, $args);

        $settings = $this->getGlobalSettings($plugin, $request);
        if (empty($path)) {
            $this->sendSiteDirectoryIndex($request, $plugin, $settings);
        }

        if (!$plugin->getEnabled(0)) {
            $this->notFound();
        }

        if (!$this->hasRequiredGlobalSettings($settings)) {
            $this->notFound();
        }

        if ($path[0] !== $settings['archiveCode']) {
            $this->notFound();
        }

        if (count($path) === 1) {
            $this->sendGlobalDirectoryIndex($request, $settings, 'archive');
        }

        if (count($path) === 2 && $path[1] === $settings['archiveCode'] . 'arch.redif') {
            $this->sendRedif($this->getFormatter()->formatArchive($this->getGlobalArchiveData($request, $settings)));
        }

        if (count($path) === 2 && $path[1] === $settings['archiveCode'] . 'seri.redif') {
            $this->sendRedif($this->getFormatter()->formatSeriesList($this->getGlobalSeriesListData($request, $plugin, $settings)));
        }

        if (count($path) >= 2) {
            $series = $this->getGlobalSeriesByCode($settings, $path[1]);
            if ($series) {
                if (count($path) === 2) {
                    $this->sendGlobalDirectoryIndex($request, $settings, 'series', $this->getIssueFileNames($series['context']), $series['seriesCode']);
                }
                if (count($path) === 3) {
                    $issueFileNames = $this->getIssueFileNames($series['context']);
                    if (isset($issueFileNames[$path[2]])) {
                        $articleSettings = $this->getSettings($plugin, $series['context'], $request);
                        $articleSettings['archiveCode'] = $settings['archiveCode'];
                        $articleSettings['seriesCode'] = $series['seriesCode'];
                        $this->sendRedif($this->getFormatter()->formatArticles($this->getArticlesData($request, $series['context'], $articleSettings, $issueFileNames[$path[2]])));
                    }
                }
            }
        }

        $this->notFound();
    }

    private function notFound()
    {
        throw new NotFoundHttpException();
    }

    private function getSettings($plugin, $context, $request)
    {
        $settings = array();
        foreach (RepecSettingsForm::$settings as $settingName => $settingType) {
            $settings[$settingName] = trim((string) $plugin->getSetting($context->getId(), $settingName));
        }
        $settings['providerInstitution'] = '';
        $settings['legacyHandles'] = $this->getLegacyHandleMapParser()->decode($settings['legacyHandles']);
        $settings['archiveCode'] = strtolower($settings['archiveCode']);
        $settings['seriesCode'] = strtolower($settings['seriesCode']);
        if (empty($settings['archiveName'])) {
            $settings['archiveName'] = 'RePEc archive for ' . $context->getLocalizedName();
        }
        if (empty($settings['archiveDescription'])) {
            $settings['archiveDescription'] = 'Articles published by ' . $context->getLocalizedName();
        }
        if (empty($settings['seriesName'])) {
            $settings['seriesName'] = $context->getLocalizedName();
        }
        if (empty($settings['providerName'])) {
            $settings['providerName'] = trim((string) $context->getData('publisherInstitution'));
        }
        if (empty($settings['providerName'])) {
            $settings['providerName'] = $context->getLocalizedName();
        }
        if (empty($settings['providerHomepage'])) {
            $settings['providerHomepage'] = $request->url($context->getPath());
        }
        $settings['issn'] = trim((string) $context->getData('onlineIssn'));
        if (empty($settings['issn'])) {
            $settings['issn'] = trim((string) $context->getData('printIssn'));
        }
        if (empty($settings['maintainerName'])) {
            $settings['maintainerName'] = trim((string) $context->getData('supportName'));
        }
        if (empty($settings['maintainerName'])) {
            $settings['maintainerName'] = trim((string) $context->getData('contactName'));
        }
        if (empty($settings['maintainerName'])) {
            $settings['maintainerName'] = $context->getLocalizedName();
        }
        if (empty($settings['maintainerEmail'])) {
            $settings['maintainerEmail'] = trim((string) $context->getData('supportEmail'));
        }
        if (empty($settings['maintainerEmail'])) {
            $settings['maintainerEmail'] = trim((string) $context->getData('contactEmail'));
        }
        return $settings;
    }

    private function getGlobalSettings($plugin, $request)
    {
        $settings = array(
            'archiveCode' => strtolower(trim((string) $plugin->getSetting(0, 'archiveCode'))),
            'seriesCode' => '',
            'maintainerEmail' => trim((string) $plugin->getSetting(0, 'maintainerEmail')),
            'globalJournals' => $this->decodeGlobalJournals($plugin->getSetting(0, 'globalJournals')),
        );

        $site = $request->getSite();
        $siteName = $site ? $site->getLocalizedTitle() : '';
        if (empty($siteName)) {
            $siteName = Config::getVar('general', 'base_url');
        }

        $settings['archiveName'] = 'RePEc archive for ' . $siteName;
        $settings['archiveDescription'] = 'Articles published by journals in ' . $siteName;
        $settings['seriesName'] = '';
        $settings['providerName'] = $siteName;
        $settings['providerHomepage'] = $request->url('index');
        $settings['providerInstitution'] = '';
        $settings['issn'] = '';
        $settings['maintainerName'] = $siteName;
        if (empty($settings['maintainerEmail']) && $site) {
            $settings['maintainerEmail'] = trim((string) $site->getLocalizedContactEmail());
        }
        return $settings;
    }

    private function hasRequiredSettings($settings)
    {
        $required = array('archiveCode', 'seriesCode', 'archiveName', 'seriesName', 'providerName', 'providerHomepage', 'maintainerName', 'maintainerEmail');
        foreach ($required as $settingName) {
            if (empty($settings[$settingName])) {
                return false;
            }
        }
        return preg_match('/^[a-z]{3}$/', $settings['archiveCode'])
            && preg_match('/^[a-z0-9]{6}$/', $settings['seriesCode'])
            && $this->isValidEmail($settings['maintainerEmail']);
    }

    private function hasRequiredGlobalSettings($settings)
    {
        if (empty($settings['archiveCode']) || empty($settings['maintainerEmail']) || empty($settings['globalJournals'])) {
            return false;
        }
        if (!preg_match('/^[a-z]{3}$/', $settings['archiveCode'])) {
            return false;
        }
        if (!$this->isValidEmail($settings['maintainerEmail'])) {
            return false;
        }

        $seriesCodes = array();
        foreach ($settings['globalJournals'] as $journalId => $seriesCode) {
            if (!preg_match('/^[a-z0-9]{6}$/', $seriesCode) || isset($seriesCodes[$seriesCode])) {
                return false;
            }
            $seriesCodes[$seriesCode] = true;
        }
        return true;
    }

    private function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function getRepecPath($request, $args)
    {
        $path = array();
        $op = $request->getRequestedOp();
        if ($op && !in_array($op, array('index', 'repec'))) {
            $path[] = $op;
        }
        foreach ($args as $arg) {
            $path[] = $arg;
        }
        return array_values(array_filter($path, 'strlen'));
    }

    private function getArchiveData($request, $context, $settings)
    {
        $settings['archiveUrl'] = $this->ensureDirectoryUrl($request->url($context->getPath(), 'repec', $settings['archiveCode']));
        return $settings;
    }

    private function getGlobalArchiveData($request, $settings)
    {
        $settings['archiveUrl'] = $this->ensureDirectoryUrl($request->url('index', 'repec', $settings['archiveCode']));
        return $settings;
    }

    private function ensureDirectoryUrl($url)
    {
        if (strpos($url, '?') !== false || substr($url, -1) === '/') {
            return $url;
        }
        return $url . '/';
    }

    private function getSeriesData($request, $context, $settings)
    {
        if (empty($settings['providerHomepage'])) {
            $settings['providerHomepage'] = $request->url($context->getPath());
        }
        return $settings;
    }

    private function getGlobalSeriesListData($request, $plugin, $settings)
    {
        $seriesList = array();
        foreach ($this->getGlobalSeries($settings) as $series) {
            $seriesSettings = $this->getSettings($plugin, $series['context'], $request);
            $seriesSettings['archiveCode'] = $settings['archiveCode'];
            $seriesSettings['seriesCode'] = $series['seriesCode'];
            if (empty($seriesSettings['maintainerEmail'])) {
                $seriesSettings['maintainerEmail'] = $settings['maintainerEmail'];
            }
            if (empty($seriesSettings['maintainerName'])) {
                $seriesSettings['maintainerName'] = $settings['maintainerName'];
            }
            $seriesList[] = $this->getSeriesData($request, $series['context'], $seriesSettings);
        }
        return $seriesList;
    }

    private function getArticlesData($request, $context, $settings, $issue)
    {
        $articles = array();
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByIssueIds([$issue->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(SubmissionCollector::ORDERBY_SEQUENCE, SubmissionCollector::ORDER_DIR_ASC)
            ->getMany();

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if (!$publication) {
                continue;
            }
            $article = $this->getArticleData($request, $context, $settings, $submission, $publication, $issue);
            if (!empty($article['authors']) && !empty($article['title'])) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    private function getArticleData($request, $context, $settings, $submission, $publication, $issue = null)
    {
        if (!$issue) {
            $issue = $this->getIssue($context, $publication, $submission);
        }
        $files = $this->getArticleFiles($request, $context, $submission, $publication);

        $datePublished = $publication->getData('datePublished');
        $year = $datePublished ? date('Y', strtotime($datePublished)) : '';
        $month = $datePublished ? date('F', strtotime($datePublished)) : '';
        if ($issue) {
            $year = $issue->getYear() ?: $year;
            $issueDate = $issue->getDatePublished();
            if ($issueDate) {
                $month = date('F', strtotime($issueDate));
            }
        }

        return array(
            'authors' => $this->getAuthorsData($publication->getData('authors')),
            'title' => $publication->getLocalizedTitle(),
            'abstract' => $publication->getLocalizedData('abstract'),
            'keywords' => $publication->getLocalizedData('keywords'),
            'journal' => $context->getLocalizedName(),
            'pages' => $publication->getData('pages'),
            'volume' => $issue ? $issue->getVolume() : '',
            'issue' => $issue ? $issue->getNumber() : '',
            'year' => $year,
            'month' => $month,
            'doi' => $publication->getStoredPubId('doi') ?: $submission->getStoredPubId('doi'),
            'files' => $files,
            'handle' => $this->getArticleHandle($settings, $submission, $issue, $year),
        );
    }

    private function getArticleHandle($settings, $submission, $issue, $year)
    {
        if (isset($settings['legacyHandles'][$submission->getId()])) {
            return $settings['legacyHandles'][$submission->getId()];
        }

        $pattern = trim((string) (isset($settings['articleHandlePattern']) ? $settings['articleHandlePattern'] : ''));
        if ($pattern === '') {
            $pattern = self::DEFAULT_ARTICLE_HANDLE_PATTERN;
        }

        $tokens = array(
            '%v' => $issue ? $this->cleanHandlePart($issue->getVolume()) : '',
            '%Y' => $this->cleanHandlePart($year),
            '%i' => $issue ? $this->cleanHandlePart($issue->getNumber()) : '',
            '%a' => $submission->getId(),
        );
        $suffix = $this->resolveArticleHandlePattern($pattern, $tokens);

        return implode(':', array_filter(array(
            'RePEc',
            $settings['archiveCode'],
            $settings['seriesCode'],
            $suffix,
        ), 'strlen'));
    }

    private function resolveArticleHandlePattern($pattern, $tokens)
    {
        $parts = array();
        foreach (explode(':', $pattern) as $part) {
            if (isset($tokens[$part]) && $tokens[$part] === '') {
                array_pop($parts);
                continue;
            }
            $resolvedPart = strtr($part, $tokens);
            if ($resolvedPart !== '') {
                $parts[] = $resolvedPart;
            }
        }

        return implode(':', $parts);
    }

    private function decodeGlobalJournals($value)
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function getGlobalSeriesCodeForContext($plugin, $context)
    {
        $globalJournals = $this->decodeGlobalJournals($plugin->getSetting(0, 'globalJournals'));
        return isset($globalJournals[$context->getId()]) ? $globalJournals[$context->getId()] : '';
    }

    private function getGlobalSeries($settings)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $series = array();
        foreach ($settings['globalJournals'] as $journalId => $seriesCode) {
            $context = $journalDao->getById((int) $journalId);
            if ($context && $context->getData('enabled')) {
                $series[] = array(
                    'context' => $context,
                    'seriesCode' => $seriesCode,
                );
            }
        }
        return $series;
    }

    private function getGlobalSeriesByCode($settings, $seriesCode)
    {
        foreach ($this->getGlobalSeries($settings) as $series) {
            if ($series['seriesCode'] === $seriesCode) {
                return $series;
            }
        }
        return null;
    }

    private function cleanHandlePart($value)
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    private function getLegacyHandleMapParser()
    {
        return new RepecLegacyHandleMap();
    }

    private function getArticleFiles($request, $context, $submission, $publication)
    {
        $files = array(array(
            'function' => 'Abstract page',
            'url' => $request->url($context->getPath(), 'article', 'view', array($submission->getBestId())),
            'format' => 'text/html',
        ));

        $pdfGalley = $this->getPdfGalley($publication->getData('galleys'));
        if ($pdfGalley) {
            $pdfUrl = method_exists($pdfGalley, 'getRemoteURL') ? $pdfGalley->getRemoteURL() : $pdfGalley->getData('urlRemote');
            if (!$pdfUrl) {
                $pdfUrl = $request->url($context->getPath(), 'article', 'download', array($submission->getBestId(), $pdfGalley->getBestGalleyId()));
            }
            if ($pdfUrl && $pdfUrl !== $files[0]['url']) {
                $files[] = array(
                    'function' => 'Full text',
                    'url' => $pdfUrl,
                    'format' => 'application/pdf',
                );
            }
        }

        return $files;
    }

    private function getPdfGalley($galleys)
    {
        if (empty($galleys) || !is_iterable($galleys)) {
            return null;
        }

        foreach ($galleys as $galley) {
            if (is_object($galley) && method_exists($galley, 'getFileType') && $galley->getFileType() === 'application/pdf') {
                return $galley;
            }
        }
        return null;
    }

    private function getAuthorsData($authors)
    {
        $authorsData = array();
        if (empty($authors) || !is_iterable($authors)) {
            return $authorsData;
        }

        foreach ($authors as $author) {
            if (!is_object($author) || !method_exists($author, 'getFullName')) {
                continue;
            }
            $name = trim($author->getFullName());
            if ($name === '') {
                $name = trim($author->getLocalizedGivenName() . ' ' . $author->getLocalizedFamilyName());
            }
            if ($name === '') {
                continue;
            }
            $authorsData[] = array(
                'name' => $name,
                'affiliation' => $author->getLocalizedData('affiliation'),
            );
        }
        return $authorsData;
    }

    private function getIssue($context, $publication, $submission)
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

    private function getIssueFileNames($context)
    {
        $publishedIssues = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->orderBy(IssueCollector::ORDERBY_PUBLISHED_ISSUES)
            ->getMany();
        $issues = array();
        $counts = array();
        foreach ($publishedIssues as $issue) {
            $baseName = $this->getIssueFileBaseName($issue);
            $issues[] = array($baseName, $issue);
            if (!isset($counts[$baseName])) {
                $counts[$baseName] = 0;
            }
            $counts[$baseName]++;
        }

        $issueFileNames = array();
        foreach ($issues as $issueData) {
            list($baseName, $issue) = $issueData;
            if ($counts[$baseName] > 1) {
                $baseName .= 'id' . $issue->getId();
            }
            $issueFileNames[$baseName . '.redif'] = $issue;
        }
        return $issueFileNames;
    }

    private function getIssueFileBaseName($issue)
    {
        $parts = array();
        $volume = $this->cleanFileNamePart($issue->getVolume());
        $number = $this->cleanFileNamePart($issue->getNumber());
        $year = $this->cleanFileNamePart($issue->getYear());

        if ($volume !== '') {
            $parts[] = 'v' . $volume;
        }
        if ($number !== '') {
            $parts[] = 'i' . $number;
        }
        if ($year !== '') {
            $parts[] = 'y' . $year;
        }
        if (empty($parts)) {
            $parts[] = 'issue' . $issue->getId();
        }

        return implode('', $parts);
    }

    private function cleanFileNamePart($value)
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = trim($value);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = preg_replace('/[^a-z0-9]+/', '', strtolower($value));
        return trim($value);
    }

    private function getFormatter()
    {
        return new RepecFormatter();
    }

    private function sendRedif($contents)
    {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo $contents;
        exit();
    }

    private function sendDirectoryIndex($request, $settings, $level, $issueFileNames = array())
    {
        header('Content-Type: text/html; charset=UTF-8');
        $archiveCode = htmlspecialchars($settings['archiveCode'], ENT_QUOTES, 'UTF-8');
        $seriesCode = htmlspecialchars($settings['seriesCode'], ENT_QUOTES, 'UTF-8');
        $archiveTemplateUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['archiveCode'] . 'arch.redif'), null, null, true);
        $seriesTemplateUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['archiveCode'] . 'seri.redif'), null, null, true);
        $seriesUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['seriesCode']), null, null, true);

        echo '<!doctype html><html><head><meta charset="utf-8"><title>RePEc ' . $archiveCode . '</title></head><body>';
        echo '<h1>RePEc ' . $archiveCode . '</h1><ul>';
        if ($level === 'archive') {
            echo '<li><a href="' . htmlspecialchars($archiveTemplateUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . 'arch.redif</a></li>';
            echo '<li><a href="' . htmlspecialchars($seriesTemplateUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . 'seri.redif</a></li>';
            echo '<li><a href="' . htmlspecialchars($seriesUrl, ENT_QUOTES, 'UTF-8') . '">' . $seriesCode . '/</a></li>';
        } else {
            foreach ($issueFileNames as $fileName => $issue) {
                $issueUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['seriesCode'], $fileName), null, null, true);
                $issueLabel = $issue->getIssueIdentification();
                if (empty($issueLabel)) {
                    $issueLabel = $fileName;
                }
                echo '<li><a href="' . htmlspecialchars($issueUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . '</a> - ' . htmlspecialchars($issueLabel, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }
        echo '</ul></body></html>';
        exit();
    }

    private function sendSiteDirectoryIndex($request, $plugin, $globalSettings)
    {
        header('Content-Type: text/html; charset=UTF-8');

        echo '<!doctype html><html><head><meta charset="utf-8"><title>RePEc archives</title></head><body>';
        echo '<h1>RePEc archives</h1>';
        echo '<ul>';

        if ($plugin->getEnabled(0) && $this->hasRequiredGlobalSettings($globalSettings)) {
            $this->printArchiveLink($request, 'index', $globalSettings);
        }

        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journals = $journalDao->getAll(true);
        while ($context = $journals->next()) {
            if (!$plugin->getEnabled($context->getId()) || $this->getGlobalSeriesCodeForContext($plugin, $context)) {
                continue;
            }

            $settings = $this->getSettings($plugin, $context, $request);
            if (!$this->hasRequiredSettings($settings)) {
                continue;
            }

            $this->printArchiveLink($request, $context->getPath(), $settings);
        }

        echo '</ul></body></html>';
        exit();
    }

    private function printArchiveLink($request, $contextPath, $settings)
    {
        $archiveCode = htmlspecialchars($settings['archiveCode'], ENT_QUOTES, 'UTF-8');
        $archiveUrl = $request->url($contextPath, 'repec', $settings['archiveCode'], null, null, null, true);
        echo '<li><a href="' . htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . '</a></li>';
    }

    private function sendGlobalDirectoryIndex($request, $settings, $level, $issueFileNames = array(), $currentSeriesCode = '')
    {
        header('Content-Type: text/html; charset=UTF-8');
        $archiveCode = htmlspecialchars($settings['archiveCode'], ENT_QUOTES, 'UTF-8');
        $archiveTemplateUrl = $request->url('index', 'repec', $settings['archiveCode'], array($settings['archiveCode'] . 'arch.redif'), null, null, true);
        $seriesTemplateUrl = $request->url('index', 'repec', $settings['archiveCode'], array($settings['archiveCode'] . 'seri.redif'), null, null, true);

        echo '<!doctype html><html><head><meta charset="utf-8"><title>RePEc ' . $archiveCode . '</title></head><body>';
        echo '<h1>RePEc ' . $archiveCode . '</h1><ul>';
        if ($level === 'archive') {
            echo '<li><a href="' . htmlspecialchars($archiveTemplateUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . 'arch.redif</a></li>';
            echo '<li><a href="' . htmlspecialchars($seriesTemplateUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . 'seri.redif</a></li>';
            foreach ($this->getGlobalSeries($settings) as $series) {
                $seriesUrl = $request->url('index', 'repec', $settings['archiveCode'], array($series['seriesCode']), null, null, true);
                echo '<li><a href="' . htmlspecialchars($seriesUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($series['seriesCode'], ENT_QUOTES, 'UTF-8') . '/</a> - ' . htmlspecialchars($series['context']->getLocalizedName(), ENT_QUOTES, 'UTF-8') . '</li>';
            }
        } else {
            foreach ($issueFileNames as $fileName => $issue) {
                $issueUrl = $request->url('index', 'repec', $settings['archiveCode'], array($currentSeriesCode, $fileName), null, null, true);
                $issueLabel = $issue->getIssueIdentification();
                if (empty($issueLabel)) {
                    $issueLabel = $fileName;
                }
                echo '<li><a href="' . htmlspecialchars($issueUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . '</a> - ' . htmlspecialchars($issueLabel, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }
        echo '</ul></body></html>';
        exit();
    }
}
