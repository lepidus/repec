<?php

/**
 * @file plugins/generic/repec/RepecPlugin.php
 *
 * @class RepecPlugin
 * @ingroup plugins_generic_repec
 *
 * @brief Publishes OJS journal metadata as RePEc/ReDIF templates.
 */

namespace APP\plugins\generic\repec;

use APP\core\Application;
use APP\plugins\generic\repec\classes\RepecLegacyHandleMap;
use APP\plugins\generic\repec\classes\RepecSettingsForm;
use APP\plugins\generic\repec\pages\RepecHandler;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class RepecPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!$success) {
            return false;
        }

        $this->addLocaleData();

        if (!Application::isInstalled() || Application::isUnderMaintenance() || defined('RUNNING_UPGRADE')) {
            return true;
        }

        Hook::add('LoadHandler', [$this, 'setupRepecHandler']);

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

    public function isSitePlugin()
    {
        return !Application::get()->getRequest()->getContext();
    }

    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;
        return array_merge(
            $this->getEnabled($contextId) ? array(
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

                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

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

            case 'downloadLegacyHandles':
                $context = $request->getContext();
                if (!$context) {
                    return parent::manage($args, $request);
                }

                $parser = new RepecLegacyHandleMap();
                $contents = $parser->encode($parser->decode($this->getSetting($context->getId(), 'legacyHandles')));

                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="repec-legacy-handles-' . $context->getPath() . '.json"');
                echo $contents;
                exit();
        }

        return parent::manage($args, $request);
    }

    public function setupRepecHandler($hookName, $params)
    {
        $page = &$params[0];
        $handler = &$params[3];

        if ($page !== 'repec') {
            return false;
        }

        $handler = new RepecHandler();
        return true;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\repec\RepecPlugin', '\RepecPlugin');
}
