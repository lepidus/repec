<?php

/**
 * @file plugins/generic/repec/index.php
 *
 * @ingroup plugins_generic_repec
 * @brief Wrapper for loading the RePEc/ReDIF plugin.
 */

require_once('RepecPlugin.php');

return new \APP\plugins\generic\repec\RepecPlugin();
