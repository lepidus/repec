<?php

/**
 * @file plugins/generic/repec/RepecPlugin.inc.php
 *
 * @class RepecPlugin
 * @ingroup plugins_generic_repec
 *
 * @brief Publishes OJS journal metadata as RePEc/ReDIF templates.
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.core.JSONMessage');
import('lib.pkp.classes.linkAction.LinkAction');

class RepecPlugin extends GenericPlugin
{
	public function register($category, $path, $mainContextId = null)
	{
		$success = parent::register($category, $path, $mainContextId);
		if (!$success) {
			return false;
		}

		$this->addLocaleData();

		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
			return true;
		}

		if ($this->getEnabled($mainContextId)) {
			HookRegistry::register('LoadHandler', array($this, 'setupRepecHandler'));
		}

		return true;
	}

	public function getDisplayName()
	{
		return __('plugins.generic.repec.displayName');
	}

	public function getName()
	{
		return 'repecplugin';
	}

	public function getDescription()
	{
		return __('plugins.generic.repec.description');
	}

	public function getActions($request, $verb)
	{
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled() ? array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array(
							'verb' => 'settings',
							'plugin' => $this->getName(),
							'category' => 'generic',
						)),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			) : array(),
			parent::getActions($request, $verb)
		);
	}

	public function manage($args, $request)
	{
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				$contextId = $context ? $context->getId() : 0;

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('classes.RepecSettingsForm');
				$form = new RepecSettingsForm($this, $contextId);

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}

		return parent::manage($args, $request);
	}

	public function setupRepecHandler($hookName, $params)
	{
		$page = &$params[0];
		$op = &$params[1];

		if ($page !== 'repec') {
			return false;
		}

		$op = 'index';
		if (!defined('HANDLER_CLASS')) {
			define('HANDLER_CLASS', 'RepecHandler');
		}
		$this->import('pages.RepecHandler');
		return true;
	}
}
