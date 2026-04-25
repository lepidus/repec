<?php

/**
 * @file plugins/generic/repec/pages/RepecHandler.inc.php
 *
 * @class RepecHandler
 * @ingroup plugins_generic_repec
 *
 * @brief Public handler for RePEc/ReDIF archive URLs.
 */

import('classes.handler.Handler');
import('plugins.generic.repec.classes.RepecFormatter');
import('plugins.generic.repec.classes.RepecSettingsForm');

class RepecHandler extends Handler
{
    public function index($args, $request)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'repecplugin');
        $context = $request->getContext();
        if (!$plugin || !$context) {
            $request->getDispatcher()->handle404();
        }

        $settings = $this->getSettings($plugin, $context, $request);
        if (!$this->hasRequiredSettings($settings)) {
            $request->getDispatcher()->handle404();
        }

        $path = $this->getRepecPath($request, $args);
        if (empty($path) || $path[0] !== $settings['archiveCode']) {
            $request->getDispatcher()->handle404();
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

        $request->getDispatcher()->handle404();
    }

    private function getSettings($plugin, $context, $request)
    {
        $settings = array();
        foreach (RepecSettingsForm::$settings as $settingName => $settingType) {
            $settings[$settingName] = trim((string) $plugin->getSetting($context->getId(), $settingName));
        }
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

    private function hasRequiredSettings($settings)
    {
        $required = array('archiveCode', 'seriesCode', 'archiveName', 'seriesName', 'providerName', 'providerHomepage', 'maintainerName', 'maintainerEmail');
        foreach ($required as $settingName) {
            if (empty($settings[$settingName])) {
                return false;
            }
        }
        return preg_match('/^[a-z]{3}$/', $settings['archiveCode'])
            && preg_match('/^[a-z0-9]{6}$/', $settings['seriesCode']);
    }

    private function getRepecPath($request, $args)
    {
        $path = array();
        $op = $request->getRequestedOp();
        if ($op && $op !== ROUTER_DEFAULT_OP) {
            $path[] = $op;
        }
        foreach ($args as $arg) {
            $path[] = $arg;
        }
        return array_values(array_filter($path, 'strlen'));
    }

    private function getArchiveData($request, $context, $settings)
    {
        $settings['archiveUrl'] = $request->url($context->getPath(), 'repec', $settings['archiveCode']);
        return $settings;
    }

    private function getSeriesData($request, $context, $settings)
    {
        if (empty($settings['providerHomepage'])) {
            $settings['providerHomepage'] = $request->url($context->getPath());
        }
        return $settings;
    }

    private function getArticlesData($request, $context, $settings, $issue)
    {
        import('classes.submission.SubmissionDAO');

        $articles = array();
        $submissions = Services::get('submission')->getMany(array(
            'contextId' => $context->getId(),
            'issueIds' => $issue->getId(),
            'status' => STATUS_PUBLISHED,
            'orderBy' => 'seq',
            'orderDirection' => 'ASC',
        ));

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
            'authors' => $this->getAuthorsData((array) $publication->getData('authors')),
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
            'handle' => 'RePEc:' . $settings['archiveCode'] . ':' . $settings['seriesCode'] . ':a:' . $submission->getId(),
        );
    }

    private function getArticleFiles($request, $context, $submission, $publication)
    {
        $files = array(array(
            'url' => $request->url($context->getPath(), 'article', 'view', array($submission->getBestId())),
            'format' => 'text/html',
        ));

        $pdfGalley = $this->getPdfGalley((array) $publication->getData('galleys'));
        if ($pdfGalley) {
            $pdfUrl = $pdfGalley->getRemoteURL();
            if (!$pdfUrl) {
                $pdfUrl = $request->url($context->getPath(), 'article', 'download', array($submission->getBestId(), $pdfGalley->getBestGalleyId()));
            }
            if ($pdfUrl && $pdfUrl !== $files[0]['url']) {
                $files[] = array(
                    'url' => $pdfUrl,
                    'format' => 'application/pdf',
                );
            }
        }

        return $files;
    }

    private function getPdfGalley($galleys)
    {
        foreach ($galleys as $galley) {
            if ($galley->getFileType() === 'application/pdf') {
                return $galley;
            }
        }
        return null;
    }

    private function getAuthorsData($authors)
    {
        $authorsData = array();
        foreach ($authors as $author) {
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
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issueId = $publication->getData('issueId');
        if ($issueId) {
            return $issueDao->getById($issueId, $context->getId(), true);
        }
        return $issueDao->getBySubmissionId($submission->getId(), $context->getId());
    }

    private function getIssueFileNames($context)
    {
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $publishedIssues = $issueDao->getPublishedIssues($context->getId());
        $issues = array();
        $counts = array();
        while ($issue = $publishedIssues->next()) {
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
}
