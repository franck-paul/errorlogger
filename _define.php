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
    'ErrorLogger',                  // Name
    'Error logger for Dotclear2',   // Description
    'Bruno Hondelatte',             // Author
    '0.6',                          // Version
    [
        'requires'    => [['core', '2.19']],
        'permissions' => 'admin',                                      // Permissions
        'priority'    => 1,                                            // Priority
        'type'        => 'plugin',                                     // Type
        'support'     => 'https://github.com/franck-paul/errorlogger', // Support URL
    ]
);
