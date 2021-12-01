<?php
/**
 * @brief errorLogger, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Bruno Hondelatte and contributors
 *
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_RC_PATH')) {
    return;
}

if (!property_exists($GLOBALS['core'], 'errorlogger')) {
    // Let's define autoloader here, since we want to catch as many errors as possible
    $GLOBALS['__autoload']['ErrorLogger'] = dirname(__FILE__) . '/class.errorlogger.php';
    $GLOBALS['core']->errorlogger         = new ErrorLogger($GLOBALS['core']);
    $GLOBALS['core']->errorlogger->setup();
}
