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

dcCore::app()->menu[dcAdmin::MENU_SYSTEM]->addItem(
    __('Error Logger'),
    dcCore::app()->adminurl->get('admin.plugin.errorlogger'),
    dcPage::getPF('errorlogger/icon.svg'),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.errorlogger')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->isSuperAdmin()
);

dcCore::app()->addBehavior('adminDashboardFavoritesV2', ['errorloggerDashboard','dashboardFavs']);

class errorloggerDashboard
{
    public static function dashboardFavs($favs)
    {
        $favs->register('errorlogger', [
            'title'       => __('Error Logger'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.errorlogger'),
            'small-icon'  => dcPage::getPF('errorlogger/icon.svg'),
            'large-icon'  => dcPage::getPF('errorlogger/icon.svg'),
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
            ]),
        ]);
    }
}
