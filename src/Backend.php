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

use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Plugin\errorlogger\MaintenanceTask\ErrorloggerCache;

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(App::backend()->menus()::MENU_SYSTEM);

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => static function (Favorites $favs): string {
                $favs->register('errorlogger', [
                    'title'       => __('Error Logger'),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => My::checkContext(My::MENU),
                ]);

                return '';
            },
            'dcMaintenanceInit' => static function ($maintenance): string {
                $maintenance->addTask(ErrorloggerCache::class);

                return '';
            },
        ]);

        return true;
    }
}
