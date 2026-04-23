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
		'archiveName' => 'string',
		'archiveDescription' => 'string',
		'seriesName' => 'string',
		'providerName' => 'string',
		'providerHomepage' => 'string',
		'providerInstitution' => 'string',
		'maintainerName' => 'string',
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
		$this->addCheck(new FormValidator($this, 'archiveName', 'required', 'plugins.generic.repec.settings.archiveNameRequired'));
		$this->addCheck(new FormValidator($this, 'seriesName', 'required', 'plugins.generic.repec.settings.seriesNameRequired'));
		$this->addCheck(new FormValidator($this, 'providerName', 'required', 'plugins.generic.repec.settings.providerNameRequired'));
		$this->addCheck(new FormValidator($this, 'providerHomepage', 'required', 'plugins.generic.repec.settings.providerHomepageRequired'));
		$this->addCheck(new FormValidator($this, 'maintainerName', 'required', 'plugins.generic.repec.settings.maintainerNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'maintainerEmail', 'required', 'plugins.generic.repec.settings.maintainerEmailRequired'));
	}

	public function initData()
	{
		$this->_data = array();
		foreach (self::$settings as $settingName => $settingType) {
			$this->_data[$settingName] = $this->plugin->getSetting($this->contextId, $settingName);
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

	private function getRepecBaseUrl($request)
	{
		$archiveCode = strtolower(trim((string) $this->plugin->getSetting($this->contextId, 'archiveCode')));
		if (!$archiveCode || !$this->validateArchiveCode($archiveCode)) {
			return '';
		}
		return $request->url(null, 'repec', $archiveCode);
	}
}
