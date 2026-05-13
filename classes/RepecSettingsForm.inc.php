<?php

/**
 * @file plugins/generic/repec/classes/RepecSettingsForm.inc.php
 *
 * @class RepecSettingsForm
 * @ingroup plugins_generic_repec
 *
 * @brief Settings form for the RePEc/ReDIF plugin.
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.form.validation.FormValidator');
import('lib.pkp.classes.form.validation.FormValidatorCSRF');
import('lib.pkp.classes.form.validation.FormValidatorCustom');
import('lib.pkp.classes.form.validation.FormValidatorEmail');
import('lib.pkp.classes.form.validation.FormValidatorPost');
import('plugins.generic.repec.classes.RepecLegacyHandleMap');

class RepecSettingsForm extends Form
{
    public $plugin;
    public $contextId;

    public static $settings = array(
        'archiveCode' => 'string',
        'seriesCode' => 'string',
        'maintainerEmail' => 'string',
        'articleHandlePattern' => 'string',
        'legacyHandles' => 'string',
    );

    public static $globalSettings = array(
        'archiveCode' => 'string',
        'maintainerEmail' => 'string',
        'globalJournals' => 'string',
    );

    private $legacyHandlesUploadError = null;

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        if ($this->isGlobalContext() || !$this->isManagedByGlobalArchive()) {
            $this->addCheck(new FormValidator($this, 'archiveCode', 'required', 'plugins.generic.repec.settings.archiveCodeRequired'));
            $this->addCheck(new FormValidatorCustom($this, 'archiveCode', 'required', 'plugins.generic.repec.settings.archiveCodeInvalid', array($this, 'validateArchiveCode')));
        }
        if (!$this->isGlobalContext() && !$this->isManagedByGlobalArchive()) {
            $this->addCheck(new FormValidator($this, 'seriesCode', 'required', 'plugins.generic.repec.settings.seriesCodeRequired'));
            $this->addCheck(new FormValidatorCustom($this, 'seriesCode', 'required', 'plugins.generic.repec.settings.seriesCodeInvalid', array($this, 'validateSeriesCode')));
        } elseif ($this->isGlobalContext()) {
            $this->addCheck(new FormValidatorCustom($this, 'globalJournals', 'required', 'plugins.generic.repec.settings.globalJournalsInvalid', array($this, 'validateGlobalJournals')));
            $this->addCheck(new FormValidator($this, 'maintainerEmail', 'required', 'plugins.generic.repec.settings.maintainerEmailRequired'));
        }
        if (!$this->isManagedByGlobalArchive()) {
            $this->addCheck(new FormValidatorEmail($this, 'maintainerEmail', $this->isGlobalContext() ? 'required' : 'optional', 'plugins.generic.repec.settings.maintainerEmailInvalid'));
            $this->addCheck(new FormValidatorCustom($this, 'maintainerEmail', $this->isGlobalContext() ? 'required' : 'optional', 'plugins.generic.repec.settings.maintainerEmailInvalid', array($this, 'validateMaintainerEmail')));
        }
        if (!$this->isGlobalContext()) {
            $this->addCheck(new FormValidatorCustom($this, 'legacyHandles', 'optional', 'plugins.generic.repec.settings.legacyHandlesInvalid', array($this, 'validateLegacyHandlesUpload')));
            $this->addCheck(new FormValidatorCustom($this, 'articleHandlePattern', 'optional', 'plugins.generic.repec.settings.articleHandlePatternInvalid', array($this, 'validateArticleHandlePattern')));
        }
    }

    public function initData()
    {
        $this->_data = array();
        $settings = $this->isGlobalContext() ? self::$globalSettings : self::$settings;
        foreach ($settings as $settingName => $settingType) {
            $this->_data[$settingName] = $this->plugin->getSetting($this->contextId, $settingName);
        }
        $this->_data['suggestedSeriesCode'] = $this->isGlobalContext() ? '' : $this->generateSeriesCode();
        if ($this->isGlobalContext() && empty($this->_data['globalJournals'])) {
            $this->_data['globalJournals'] = '{}';
        }
    }

    public function readInputData()
    {
        if ($this->isGlobalContext()) {
            $this->readUserVars(array('archiveCode', 'maintainerEmail'));
            $this->setData('globalJournals', $this->buildGlobalJournalsSetting());
            return;
        }

        $this->readUserVars(array_keys(self::$settings));
        $this->readLegacyHandlesInput();
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('repecBaseUrl', $this->getRepecBaseUrl($request));
        $templateMgr->assign('supportEmailInUse', $this->getSupportEmailInUse($request));
        $templateMgr->assign('isGlobalContext', $this->isGlobalContext());
        $templateMgr->assign('globalJournalOptions', $this->getGlobalJournalOptions());
        $templateMgr->assign('isManagedByGlobalArchive', $this->isManagedByGlobalArchive());
        $templateMgr->assign('globalArchiveCode', $this->getGlobalArchiveCodeForContext());
        $templateMgr->assign('legacyHandlesCount', count($this->getLegacyHandles()));
        $templateMgr->assign('legacyHandlesDownloadUrl', $this->getLegacyHandlesDownloadUrl($request));
        $templateMgr->assign('suggestedSeriesCode', $this->getData('suggestedSeriesCode'));
        $templateMgr->assign('defaultArticleHandlePattern', $this->getDefaultArticleHandlePattern());
        $templateMgr->assign('articleHandlePatternLocked', !$this->isGlobalContext() && trim((string) $this->plugin->getSetting($this->contextId, 'articleHandlePattern')) !== '');
        $templateMgr->assign('articleHandlePatternPreview', $this->buildArticleHandlePatternPreview(
            $this->getData('archiveCode'),
            $this->getData('seriesCode'),
            $this->getData('articleHandlePattern')
        ));
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $settings = $this->isGlobalContext() ? self::$globalSettings : self::$settings;
        if (!$this->isGlobalContext() && $this->isManagedByGlobalArchive()) {
            $settings = array(
                'articleHandlePattern' => 'string',
                'legacyHandles' => 'string',
            );
        }
        foreach ($settings as $settingName => $settingType) {
            $value = trim((string) $this->getData($settingName));
            if (in_array($settingName, array('archiveCode', 'seriesCode'))) {
                $value = strtolower($value);
            }
            if ($settingName === 'legacyHandles') {
                $value = $this->normalizeLegacyHandlesSetting($value);
            } elseif ($settingName === 'articleHandlePattern') {
                $value = $this->getArticleHandlePatternValueForStorage($value);
            }
            $this->plugin->updateSetting($this->contextId, $settingName, $value, $settingType);
        }

        parent::execute(...$functionArgs);
    }

    public function validateArchiveCode($archiveCode)
    {
        return (bool) preg_match('/^[a-z]{3}$/', strtolower(trim((string) $archiveCode)));
    }

    public function validateSeriesCode($seriesCode)
    {
        return (bool) preg_match('/^[a-z0-9]{6}$/', strtolower(trim((string) $seriesCode)));
    }

    public function validateGlobalJournals($globalJournals)
    {
        $journals = $this->decodeGlobalJournals($globalJournals);
        if (empty($journals)) {
            return false;
        }

        $seriesCodes = array();
        foreach ($journals as $journalId => $seriesCode) {
            if (!$this->validateSeriesCode($seriesCode)) {
                return false;
            }
            if (isset($seriesCodes[$seriesCode])) {
                return false;
            }
            $seriesCodes[$seriesCode] = true;
        }
        return true;
    }

    public function validateMaintainerEmail($email)
    {
        return filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validateLegacyHandlesUpload()
    {
        return $this->legacyHandlesUploadError === null;
    }

    public function validateArticleHandlePattern($pattern)
    {
        if (!$this->isGlobalContext() && trim((string) $this->plugin->getSetting($this->contextId, 'articleHandlePattern')) !== '') {
            return true;
        }

        $pattern = trim((string) $pattern);
        if ($pattern === '') {
            return true;
        }
        if (stripos($pattern, 'RePEc:') === 0) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9:%._;(),?\/-]+$/', $pattern)) {
            return false;
        }

        $knownTokensPattern = '/%(v|Y|i|a)/';
        $withoutKnownTokens = preg_replace($knownTokensPattern, '', $pattern);
        return strpos($withoutKnownTokens, '%') === false;
    }

    private function isGlobalContext()
    {
        return (int) $this->contextId === 0;
    }

    private function buildGlobalJournalsSetting()
    {
        $selected = (array) Application::get()->getRequest()->getUserVar('globalJournalIds');
        $seriesCodes = (array) Application::get()->getRequest()->getUserVar('globalSeriesCodes');
        $journals = array();

        foreach ($selected as $journalId) {
            $journalId = (int) $journalId;
            $seriesCode = isset($seriesCodes[$journalId]) ? strtolower(trim((string) $seriesCodes[$journalId])) : '';
            if ($journalId) {
                $journals[$journalId] = $seriesCode;
            }
        }

        return json_encode($journals);
    }

    private function readLegacyHandlesInput()
    {
        $this->setData('legacyHandles', $this->plugin->getSetting($this->contextId, 'legacyHandles'));
        $legacyHandlesJson = trim((string) Application::get()->getRequest()->getUserVar('legacyHandlesJson'));
        if ($legacyHandlesJson === '') {
            return;
        }

        list($handles, $error) = $this->getLegacyHandleMapParser()->parseJson($legacyHandlesJson);
        if ($error) {
            $this->legacyHandlesUploadError = $error;
            return;
        }

        $this->setData('legacyHandles', $this->getLegacyHandleMapParser()->encodeForStorage($handles));
    }

    private function normalizeLegacyHandlesSetting($value)
    {
        return $this->getLegacyHandleMapParser()->encodeForStorage($this->getLegacyHandleMapParser()->decode($value));
    }

    private function decodeGlobalJournals($value)
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function getGlobalJournalOptions()
    {
        if (!$this->isGlobalContext()) {
            return array();
        }

        $selectedJournals = $this->decodeGlobalJournals($this->getData('globalJournals'));
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journals = $journalDao->getAll(true);
        $options = array();

        while ($journal = $journals->next()) {
            $journalId = $journal->getId();
            $individualSettings = array();
            foreach (self::$settings as $settingName => $settingType) {
                $individualSettings[$settingName] = trim((string) $this->plugin->getSetting($journalId, $settingName));
            }
            $hasIndividualConfiguration = $this->hasRequiredIndividualSettings($individualSettings);
            $isSelected = isset($selectedJournals[$journalId]);

            $options[] = array(
                'id' => $journalId,
                'name' => $journal->getLocalizedName(),
                'path' => $journal->getPath(),
                'selected' => $isSelected,
                'seriesCode' => $isSelected ? $selectedJournals[$journalId] : '',
                'suggestedSeriesCode' => $this->generateSeriesCodeForContext($journal),
                'disabled' => $hasIndividualConfiguration && !$isSelected,
            );
        }

        return $options;
    }

    private function hasRequiredIndividualSettings($settings)
    {
        return !empty($settings['archiveCode'])
            && !empty($settings['seriesCode'])
            && $this->validateArchiveCode($settings['archiveCode'])
            && $this->validateSeriesCode($settings['seriesCode']);
    }

    private function isManagedByGlobalArchive()
    {
        if ($this->isGlobalContext()) {
            return false;
        }

        return $this->getGlobalArchiveCodeForContext() !== '';
    }

    private function getGlobalArchiveCodeForContext()
    {
        if ($this->isGlobalContext()) {
            return '';
        }

        $globalJournals = $this->decodeGlobalJournals($this->plugin->getSetting(0, 'globalJournals'));
        if (!isset($globalJournals[$this->contextId])) {
            return '';
        }

        return trim((string) $this->plugin->getSetting(0, 'archiveCode'));
    }

    private function generateSeriesCode()
    {
        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return '';
        }

        return $this->generateSeriesCodeForContext($context);
    }

    private function generateSeriesCodeForContext($context)
    {
        $candidates = array(
            $context->getPath(),
            $context->getData('onlineIssn'),
            $context->getData('printIssn'),
            $context->getLocalizedName(),
        );

        foreach ($candidates as $candidate) {
            $code = $this->normalizeSeriesCodeCandidate($candidate);
            if ($code !== '') {
                return $this->fitSeriesCodeLength($code);
            }
        }

        return '';
    }

    private function normalizeSeriesCodeCandidate($candidate)
    {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $candidate);
            if ($converted !== false) {
                $candidate = $converted;
            }
        }
        $candidate = strtolower($candidate);
        return preg_replace('/[^a-z0-9]/', '', $candidate);
    }

    private function fitSeriesCodeLength($code)
    {
        if (strlen($code) >= 6) {
            return substr($code, 0, 6);
        }
        return str_pad($code, 6, '0');
    }

    private function getRepecBaseUrl($request)
    {
        $archiveCode = strtolower(trim((string) $this->plugin->getSetting($this->contextId, 'archiveCode')));
        if (!$archiveCode || !$this->validateArchiveCode($archiveCode)) {
            return '';
        }
        $context = $request->getContext();
        if ($this->isGlobalContext()) {
            return $request->getDispatcher()->url($request, ROUTE_PAGE, 'index', 'repec', $archiveCode);
        }
        $contextPath = $context ? $context->getPath() : null;
        return $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'repec', $archiveCode);
    }

    private function getLegacyHandles()
    {
        if ($this->isGlobalContext()) {
            return array();
        }

        return $this->getLegacyHandleMapParser()->decode($this->getData('legacyHandles'));
    }

    private function getLegacyHandlesDownloadUrl($request)
    {
        if ($this->isGlobalContext() || empty($this->getLegacyHandles())) {
            return '';
        }

        return $request->getRouter()->url($request, null, null, 'manage', null, array(
            'verb' => 'downloadLegacyHandles',
            'plugin' => $this->plugin->getName(),
            'category' => 'generic',
        ));
    }

    private function getLegacyHandleMapParser()
    {
        return new RepecLegacyHandleMap();
    }

    private function getDefaultArticleHandlePattern()
    {
        return 'v:%v:y:%Y:i:%i:id:%a';
    }

    private function getArticleHandlePatternValueForStorage($value)
    {
        $existing = trim((string) $this->plugin->getSetting($this->contextId, 'articleHandlePattern'));
        if ($existing !== '') {
            return $existing;
        }

        return trim((string) $value);
    }

    private function buildArticleHandlePatternPreview($archiveCode, $seriesCode, $pattern)
    {
        $archiveCode = strtolower(trim((string) $archiveCode));
        $seriesCode = strtolower(trim((string) $seriesCode));
        $pattern = trim((string) $pattern);
        if ($pattern === '') {
            $pattern = $this->getDefaultArticleHandlePattern();
        }

        $suffix = strtr($pattern, array(
            '%v' => '35',
            '%Y' => '1995',
            '%i' => '3',
            '%a' => '59960',
        ));

        return 'RePEc:' . ($archiveCode ?: 'aaa') . ':' . ($seriesCode ?: 'series') . ':' . $suffix;
    }

    private function getSupportEmailInUse($request)
    {
        if (trim((string) $this->getData('maintainerEmail')) !== '') {
            return '';
        }

        $context = $request->getContext();
        if (!$context) {
            return '';
        }

        return trim((string) $context->getData('supportEmail'));
    }
}
