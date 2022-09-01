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
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'ErrorLogger',                  // Name
    'Error logger for Dotclear2',   // Description
    'Bruno Hondelatte',             // Author
    '0.7.1',
    [
        'requires'    => [['core', '2.23']],
        'permissions' => 'admin',                                      // Permissions
        'priority'    => 1,                                            // Priority
        'type'        => 'plugin',                                     // Type

        'details'    => 'https://open-time.net/?q=errorlogger',       // Details URL
        'support'    => 'https://github.com/franck-paul/errorlogger', // Support URL
        'repository' => 'https://raw.githubusercontent.com/franck-paul/errorlogger/master/dcstore.xml',
    ]
);
