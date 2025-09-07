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
use Dotclear\Helper\Process\TraitProcess;

class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (App::task()->checkContext('FRONTEND')) {
            if (!isset(App::frontend()->errorlogger)) {
                App::frontend()->errorlogger = new ErrorLogger();
                App::frontend()->errorlogger->setup();
            }
        } elseif (App::task()->checkContext('BACKEND')) {
            if (!isset(App::backend()->errorlogger)) {
                App::backend()->errorlogger = new ErrorLogger();
                App::backend()->errorlogger->setup();
            }
        }

        return true;
    }
}
