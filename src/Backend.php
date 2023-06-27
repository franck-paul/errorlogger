<?php
/**
 * @brief errorlogger, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\errorlogger;

use dcAdmin;
use dcCore;
use dcFavorites;
use dcNsProcess;
use Dotclear\Plugin\errorlogger\MaintenanceTask\ErrorloggerCache;

class Backend extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::BACKEND);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_SYSTEM]->addItem(
            __('Error Logger'),
            My::makeUrl(),
            My::icons(),
            preg_match(My::urlScheme(), $_SERVER['REQUEST_URI']),
            My::checkContext(My::MENU)
        );

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register('errorlogger', [
                    'title'      => __('Error Logger'),
                    'url'        => My::makeUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                    My::checkContext(My::MENU),
                ]);
            },
            'dcMaintenanceInit' => function ($maintenance) {
                $maintenance->addTask(ErrorloggerCache::class);
            },
        ]);

        return true;
    }
}
