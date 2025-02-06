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
    '5.2',
    [
        'date'        => '2025-02-06T14:53:48+0100',
        'type'        => 'plugin',
        'requires'    => [['core', '2.33']],
        'permissions' => 'My',

        'priority' => 1,

        'details'    => 'https://open-time.net/?q=errorlogger',
        'support'    => 'https://github.com/franck-paul/errorlogger',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/errorlogger/main/dcstore.xml',
    ]
);
