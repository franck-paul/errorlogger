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
$this->registerModule(
    'ErrorLogger',
    'Error logger for Dotclear2',
    'Bruno Hondelatte',
    '2.6',
    [
        'type'        => 'plugin',
        'requires'    => [['core', '2.26']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]),

        'priority' => 1,

        'details'    => 'https://open-time.net/?q=errorlogger',
        'support'    => 'https://github.com/franck-paul/errorlogger',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/errorlogger/master/dcstore.xml',
    ]
);
