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

	public function __construct($plugin, $contextId)
	{
		$this->plugin = $plugin;
		$this->contextId = $contextId;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidator($this, 'archiveCode', 'required', 'plugins.generic.repec.settings.archiveCodeRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'archiveCode', 'required', 'plugins.generic.repec.settings.archiveCodeInvalid', array($this, 'validateArchiveCode')));
		$this->addCheck(new FormValidator($this, 'seriesCode', 'required', 'plugins.generic.repec.settings.seriesCodeRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'seriesCode', 'required', 'plugins.generic.repec.settings.seriesCodeInvalid', array($this, 'validateSeriesCode')));
		$this->addCheck(new FormValidatorEmail($this, 'maintainerEmail', 'optional', 'plugins.generic.repec.settings.maintainerEmailInvalid'));
	}

	public function initData()
	{
		$this->_data = array();
		foreach (self::$settings as $settingName => $settingType) {
			$this->_data[$settingName] = $this->plugin->getSetting($this->contextId, $settingName);
		}
		if (empty($this->_data['seriesCode'])) {
			$this->_data['seriesCode'] = $this->generateSeriesCode();
		}
	}

	public function readInputData()
	{
		$this->readUserVars(array_keys(self::$settings));
	}

	public function fetch($request, $template = null, $display = false)
	{
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		$templateMgr->assign('repecBaseUrl', $this->getRepecBaseUrl($request));
		$templateMgr->assign('supportEmailInUse', $this->getSupportEmailInUse($request));
		return parent::fetch($request, $template, $display);
	}

	public function execute(...$functionArgs)
	{
		foreach (self::$settings as $settingName => $settingType) {
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

	private function generateSeriesCode()
	{
		$context = Application::get()->getRequest()->getContext();
		if (!$context) {
			return '';
		}

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
