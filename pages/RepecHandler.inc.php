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

		$settings = $this->getSettings($plugin, $context->getId());
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

		if (count($path) === 2 && $path[1] === $settings['archiveCode'] . 'arch.rdf') {
			$this->sendRedif($this->getFormatter()->formatArchive($this->getArchiveData($request, $context, $settings)));
		}

		if (count($path) === 2 && $path[1] === $settings['archiveCode'] . 'seri.rdf') {
			$this->sendRedif($this->getFormatter()->formatSeries($this->getSeriesData($request, $context, $settings)));
		}

		if (count($path) === 2 && $path[1] === $settings['seriesCode']) {
			$this->sendDirectoryIndex($request, $settings, 'series');
		}

		if (count($path) === 3 && $path[1] === $settings['seriesCode'] && $path[2] === 'articles.rdf') {
			$this->sendRedif($this->getFormatter()->formatArticles($this->getArticlesData($request, $context, $settings)));
		}

		$request->getDispatcher()->handle404();
	}

	private function getSettings($plugin, $contextId)
	{
		$settings = array();
		foreach (RepecSettingsForm::$settings as $settingName => $settingType) {
			$settings[$settingName] = trim((string) $plugin->getSetting($contextId, $settingName));
		}
		$settings['archiveCode'] = strtolower($settings['archiveCode']);
		$settings['seriesCode'] = strtolower($settings['seriesCode']);
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
		$settings['archiveUrl'] = $request->url($context->getPath(), 'repec', $settings['archiveCode'], null, null, null, true) . '/';
		return $settings;
	}

	private function getSeriesData($request, $context, $settings)
	{
		if (empty($settings['providerHomepage'])) {
			$settings['providerHomepage'] = $request->url($context->getPath(), 'index', null, null, null, null, true);
		}
		return $settings;
	}

	private function getArticlesData($request, $context, $settings)
	{
		import('classes.submission.SubmissionDAO');

		$articles = array();
		$submissions = Services::get('submission')->getMany(array(
			'contextId' => $context->getId(),
			'status' => STATUS_PUBLISHED,
			'orderBy' => ORDERBY_DATE_PUBLISHED,
			'orderDirection' => 'ASC',
		));

		foreach ($submissions as $submission) {
			$publication = $submission->getCurrentPublication();
			if (!$publication) {
				continue;
			}
			$article = $this->getArticleData($request, $context, $settings, $submission, $publication);
			if (!empty($article['authors']) && !empty($article['title'])) {
				$articles[] = $article;
			}
		}

		return $articles;
	}

	private function getArticleData($request, $context, $settings, $submission, $publication)
	{
		$issue = $this->getIssue($context, $publication, $submission);
		$galley = $this->getPreferredGalley((array) $publication->getData('galleys'));
		$fileUrl = '';
		$fileFormat = '';
		if ($galley) {
			$fileUrl = $galley->getRemoteURL();
			if (!$fileUrl) {
				$fileUrl = $request->url($context->getPath(), 'article', 'download', array($submission->getBestId(), $galley->getBestGalleyId()), null, null, true);
			}
			$fileFormat = $galley->getFileType() ?: 'application/octet-stream';
		}

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
			'fileUrl' => $fileUrl,
			'fileFormat' => $fileFormat,
			'handle' => 'RePEc:' . $settings['archiveCode'] . ':' . $settings['seriesCode'] . ':a:' . $submission->getId(),
		);
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

	private function getPreferredGalley($galleys)
	{
		$firstGalley = null;
		foreach ($galleys as $galley) {
			if (!$firstGalley) {
				$firstGalley = $galley;
			}
			if ($galley->getFileType() === 'application/pdf') {
				return $galley;
			}
		}
		return $firstGalley;
	}

	private function getFormatter()
	{
		return new RepecFormatter();
	}

	private function sendRedif($contents)
	{
		header('Content-Type: text/plain; charset=UTF-8');
		header('Cache-Control: public, max-age=3600');
		echo "\xEF\xBB\xBF" . $contents;
		exit();
	}

	private function sendDirectoryIndex($request, $settings, $level)
	{
		header('Content-Type: text/html; charset=UTF-8');
		$archiveCode = htmlspecialchars($settings['archiveCode'], ENT_QUOTES, 'UTF-8');
		$seriesCode = htmlspecialchars($settings['seriesCode'], ENT_QUOTES, 'UTF-8');
		$archiveTemplateUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['archiveCode'] . 'arch.rdf'), null, null, true);
		$seriesTemplateUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['archiveCode'] . 'seri.rdf'), null, null, true);
		$seriesUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['seriesCode']), null, null, true);
		$articlesUrl = $request->url(null, 'repec', $settings['archiveCode'], array($settings['seriesCode'], 'articles.rdf'), null, null, true);

		echo '<!doctype html><html><head><meta charset="utf-8"><title>RePEc ' . $archiveCode . '</title></head><body>';
		echo '<h1>RePEc ' . $archiveCode . '</h1><ul>';
		if ($level === 'archive') {
			echo '<li><a href="' . htmlspecialchars($archiveTemplateUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . 'arch.rdf</a></li>';
			echo '<li><a href="' . htmlspecialchars($seriesTemplateUrl, ENT_QUOTES, 'UTF-8') . '">' . $archiveCode . 'seri.rdf</a></li>';
			echo '<li><a href="' . htmlspecialchars($seriesUrl, ENT_QUOTES, 'UTF-8') . '">' . $seriesCode . '/</a></li>';
		} else {
			echo '<li><a href="' . htmlspecialchars($articlesUrl, ENT_QUOTES, 'UTF-8') . '">articles.rdf</a></li>';
		}
		echo '</ul></body></html>';
		exit();
	}
}
