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

class RepecSettingsForm extends Form
{
    public $plugin;
    public $contextId;

    public static $settings = array(
        'archiveCode' => 'string',
        'seriesCode' => 'string',
        'maintainerEmail' => 'string',
    );

    public static $globalSettings = array(
        'archiveCode' => 'string',
        'maintainerEmail' => 'string',
        'globalJournals' => 'string',
    );

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        if (!$this->isGlobalContext() && $this->isManagedByGlobalArchive()) {
            return;
        }

        $this->addCheck(new FormValidator($this, 'archiveCode', 'required', 'plugins.generic.repec.settings.archiveCodeRequired'));
        $this->addCheck(new FormValidatorCustom($this, 'archiveCode', 'required', 'plugins.generic.repec.settings.archiveCodeInvalid', array($this, 'validateArchiveCode')));
        if (!$this->isGlobalContext()) {
            $this->addCheck(new FormValidator($this, 'seriesCode', 'required', 'plugins.generic.repec.settings.seriesCodeRequired'));
            $this->addCheck(new FormValidatorCustom($this, 'seriesCode', 'required', 'plugins.generic.repec.settings.seriesCodeInvalid', array($this, 'validateSeriesCode')));
        } else {
            $this->addCheck(new FormValidatorCustom($this, 'globalJournals', 'required', 'plugins.generic.repec.settings.globalJournalsInvalid', array($this, 'validateGlobalJournals')));
            $this->addCheck(new FormValidator($this, 'maintainerEmail', 'required', 'plugins.generic.repec.settings.maintainerEmailRequired'));
        }
        $this->addCheck(new FormValidatorEmail($this, 'maintainerEmail', $this->isGlobalContext() ? 'required' : 'optional', 'plugins.generic.repec.settings.maintainerEmailInvalid'));
    }

    public function initData()
    {
        $this->_data = array();
        $settings = $this->isGlobalContext() ? self::$globalSettings : self::$settings;
        foreach ($settings as $settingName => $settingType) {
            $this->_data[$settingName] = $this->plugin->getSetting($this->contextId, $settingName);
        }
        if (!$this->isGlobalContext() && empty($this->_data['seriesCode'])) {
            $this->_data['seriesCode'] = $this->generateSeriesCode();
        }
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
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        if (!$this->isGlobalContext() && $this->isManagedByGlobalArchive()) {
            return parent::execute(...$functionArgs);
        }

        $settings = $this->isGlobalContext() ? self::$globalSettings : self::$settings;
        foreach ($settings as $settingName => $settingType) {
            $value = trim((string) $this->getData($settingName));
            if (in_array($settingName, array('archiveCode', 'seriesCode'))) {
                $value = strtolower($value);
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
                'seriesCode' => $isSelected ? $selectedJournals[$journalId] : $this->generateSeriesCodeForContext($journal),
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
