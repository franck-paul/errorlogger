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
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Exception;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            $settings = My::settings();

            $settings->put('enabled', false, BlogWorkspaceInterface::NS_BOOL, 'Enable error logger', false, true);
            $settings->put('backtrace', false, BlogWorkspaceInterface::NS_BOOL, 'Enable backtrace in logs', false, true);
            $settings->put('silent_mode', false, BlogWorkspaceInterface::NS_BOOL, 'Silent native errors, only show logs', false, true);
            $settings->put('annoy_user', false, BlogWorkspaceInterface::NS_BOOL, 'Annoy flag', false, true);
            $settings->put('bin_file', 'errors.bin', BlogWorkspaceInterface::NS_STRING, 'Binary log file name', false, true);
            $settings->put('txt_file', 'errors.txt', BlogWorkspaceInterface::NS_STRING, 'Text log file name', false, true);
            $settings->put('dir', 'errorlogger', BlogWorkspaceInterface::NS_STRING, 'directory used for logs (under cache dir)', false, true);
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }
}
