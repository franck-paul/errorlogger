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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$_menu['System']->addItem(__('Error Logger'),'plugin.php?p=errorlogger','index.php?pf=errorlogger/icon.png',
        preg_match('/plugin.php(.*)$/', $_SERVER['REQUEST_URI']) && !empty($_REQUEST['p']) && $_REQUEST['p'] == 'errorlogger',
        $core->auth->isSuperAdmin());

$core->addBehavior('adminDashboardFavorites', ['errorloggerDashboard','dashboardFavs']);

class errorloggerDashboard
{
    public static function dashboardFavs($core, $favs)
    {
        $favs->register('errorlogger', [
            'title'       => __('Error Logger'),
            'url'         => 'plugin.php?p=errorlogger',
            'small-icon'  => 'index.php?pf=errorlogger/icon.png',
            'large-icon'  => 'index.php?pf=errorlogger/icon-big.png',
            'permissions' => 'admin'
        ]);
    }
}
