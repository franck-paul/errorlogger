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
use dcNsProcess;
use dcPage;
use dcPager;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class Manage extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE);

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            if (isset($_POST['save'])) {
                $settings = [
                    'enabled'     => isset($_POST['enabled'])     && $_POST['enabled']     == 1,
                    'backtrace'   => isset($_POST['backtrace'])   && $_POST['backtrace']   == 1,
                    'silent_mode' => isset($_POST['silent_mode']) && $_POST['silent_mode'] == 1,
                    'annoy_user'  => isset($_POST['annoy_user'])  && $_POST['annoy_user']  == 1,
                    'bin_file'    => $_POST['bin_file'] ?? '',
                    'txt_file'    => isset($_POST['bin_file']) ? $_POST['txt_file'] : '',
                    'dir'         => isset($_POST['bin_file']) ? $_POST['dir'] : '',
                ];
                dcCore::app()->errorlogger->setSettings($settings);
                dcPage::addSuccessNotice(__('Settings have been successfully updated'));
                Http::redirect(dcCore::app()->admin->getPageURL() . '#error-settings');
            } elseif (isset($_POST['clearfiles'])) {
                dcCore::app()->errorlogger->clearLogs();
                dcCore::app()->errorlogger->acknowledge();
                dcPage::addSuccessNotice(__('Log files have been successfully cleared'));
                Http::redirect(dcCore::app()->admin->getPageURL() . '#error-logs');
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $head = dcPage::jsPageTabs('error-logs') .
            dcPage::jsJson('errorlogger', ['confirm_delete_logs' => __('Are you sure you want to delete log files ?')]) .
            dcPage::jsModuleLoad(My::id() . '/js/admin.js', dcCore::app()->getVersion(My::id()));

        dcPage::openModule(__('ErrorLogger'), $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Error Logger')                          => '',
            ]
        );
        echo dcPage::notices();

        // Form
        $settings    = dcCore::app()->errorlogger->getSettings();
        $page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $nb_per_page = 30;
        $offset      = ($page - 1) * $nb_per_page;

        $bases = array_map(fn ($path) => Path::real($path), [
            DC_ROOT,                                        // Core
            dcCore::app()->blog->themes_path,               // Theme
            ...explode(PATH_SEPARATOR, DC_PLUGINS_ROOT),    // Plugins
        ]);
        $prefixes = ['(core)', '(theme)', '(plugin)'];

        echo
        '<div class="multi-part" title="' . __('Errors log') . '" id="error-logs">' .
        '<h3>' . __('Errors log') . '</h3>';

        $logs = array_reverse(dcCore::app()->errorlogger->getErrors());
        if (!count($logs)) {
            echo '<p>' . __('No logs') . '</p>';
        } else {
            $pager = new dcPager($page, count($logs), $nb_per_page, 10);

            echo $pager->getLinks();

            echo
            '<table id="logs-list"><tr>' .
            '<th class="first nowrap">' . __('Date') . '</th>' .
            '<th>' . __('Type') . '</th>' .
            '<th>' . __('File') . '</th>' .
            '<th>' . __('Description') . '</th>' .
            '<th>' . __('Count') . '</th>' .
            '<th>' . __('URL') . '</th>' .
            '</tr>';

            for ($k = $offset; ($k < count($logs)) && ($k < $offset + $nb_per_page); $k++) {
                $l    = $logs[$k];
                $file = $l['file'];
                foreach ($bases as $index => $base) {
                    if (strstr($file, $base)) {
                        $file = $prefixes[min($index, 2)] . substr($file, strlen($base));
                    }
                }
                echo
                '<tr class="line" id="p' . $k . '">' .
                '<td class="nowrap">' . Html::escapeHTML($l['ts']) . '</td>' .
                '<td>' . Html::escapeHTML(dcCore::app()->errorlogger->errnos[$l['no']] ?? $l['no']) . '</td>' .
                '<td>' . Html::escapeHTML($file . ':' . $l['line']) . '</td>' .
                '<td>' . Html::escapeHTML($l['str']) . '</td>' .
                '<td>' . Html::escapeHTML((string) $l['count']) . '</td>' .
                '<td>' . Html::escapeHTML($l['url']) . '</td>' .
                '</tr>' ;
                if (isset($l['backtrace'])) {
                    echo
                    '<tr id="pe' . $k . '"><td colspan="6"><strong>' . __('Backtrace') . '</strong><ul>';
                    foreach ($l['backtrace'] as $b) {
                        echo
                        '<li>' . $b . '</li>';
                    }
                    echo
                    '</ul></td></tr>';
                }
            }
            echo
            '</table>' .
            $pager->getLinks() .
            '<form action="plugin.php" id="form-logs" method="post">' .
            '<p><input type="submit" class="delete" name="clearfiles" value="' . __('Clear log files') . '"/>' .
            form::hidden(['p'], 'errorlogger') . dcCore::app()->formNonce() . '</p></form>';
        }
        echo
        '</div>';

        echo
        '<div class="multi-part" title="' . __('Settings') . '" id="error-settings">' .
        '<h3>' . __('Settings') . '</h3>' .
        '<form action="plugin.php" method="post">' .
        '<p><label for="enabled">' . form::checkbox('enabled', 1, $settings['enabled']) .
        __('Enable error logging') . '</label></p>' .
        '<p><label for="backtrace">' . form::checkbox('backtrace', 1, $settings['backtrace']) .
        __('Enable backtrace logging') . '</label></p>' .
        '<p><label for="silent_mode">' . form::checkbox('silent_mode', 1, $settings['silent_mode']) .
        __('Enable silent mode : standard errors will only be logged, no output') . '</label></p>' .
        (
            isset($_GET['annoy']) ?
            ('<p class="info">' . __('If you do not want to be annoyed with warning messages, unselect the checkbox below') . '</p>') :
            ''
        ) .
        '<p><label for="annoy_user">' . form::checkbox('annoy_user', 1, $settings['annoy_user']) .
        __('Enable Annoying mode : warn user every time a new error has been detected') . '</label></p>' .
        '<p><label for="dir">' . __('Directory for logs (will be created in dotclear cache dir)') . ' : </label>' .
        form::field('dir', 20, 255, Html::escapeHTML($settings['dir'])) . '</p>' .
        '<p><label for="bin_file">' . __('Binary log file name') . ' : </label>' .
        form::field('bin_file', 20, 255, Html::escapeHTML($settings['bin_file'])) . '</p>' .
        '<p><label for="txt_file">' . __('Text log file name') . ' : </label>' .
        form::field('txt_file', 20, 255, Html::escapeHTML($settings['txt_file'])) . '</p>' .
        '<p><input type="submit" value="' . __('Save') . ' (s)" ' . 'accesskey="s" name="save" /> ' .
        form::hidden('p', 'errorlogger') . dcCore::app()->formNonce() .
        '</p></form>' .
        '</div>';

        dcPage::closeModule();
    }
}
