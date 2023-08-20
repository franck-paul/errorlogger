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

use dcCore;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;
use Dotclear\Plugin\errorlogger\MaintenanceTask\ErrorloggerCache;

class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->admin->menus[Menus::MENU_SYSTEM]->addItem(
            __('Error Logger'),
            My::manageUrl(),
            My::icons(),
            preg_match(My::urlScheme(), $_SERVER['REQUEST_URI']),
            My::checkContext(My::MENU)
        );

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register('errorlogger', [
                    'title'      => __('Error Logger'),
                    'url'        => My::manageUrl(),
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
