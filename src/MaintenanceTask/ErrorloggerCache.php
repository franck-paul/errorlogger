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

namespace Dotclear\Plugin\errorlogger\MaintenanceTask;

use Dotclear\App;
use Dotclear\Plugin\errorlogger\ErrorLogger;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class ErrorloggerCache extends MaintenanceTask
{
    protected string $group = 'purge';

    protected function init(): void
    {
        $this->task    = __('Empty errorlogger cache directory');
        $this->success = __('Errorlogger cache directory emptied.');
        $this->error   = __('Failed to empty errorlogger cache directory.');

        $this->description = __('Errorlogger keep every PHP errors during execution.');
    }

    public function execute(): bool|int
    {
        $errorlogger = App::task()->checkContext('FRONTEND') ? App::frontend()->errorlogger : App::backend()->errorlogger;

        if ($errorlogger instanceof ErrorLogger) {
            $errorlogger->clearLogs();
            $errorlogger->acknowledge();
        }

        return true;
    }
}
