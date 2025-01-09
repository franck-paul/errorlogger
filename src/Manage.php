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
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @todo switch Helper/Html/Form/...
 */
class Manage extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            /**
             * @var        ErrorLogger
             */
            $errorlogger = App::task()->checkContext('FRONTEND') ? App::frontend()->errorlogger : App::backend()->errorlogger;

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
                $errorlogger->setSettings($settings);
                Notices::addSuccessNotice(__('Settings have been successfully updated'));
                My::redirect([], '#error-settings');
            } elseif (isset($_POST['clearfiles'])) {
                $errorlogger->clearLogs();
                $errorlogger->acknowledge();
                Notices::addSuccessNotice(__('Log files have been successfully cleared'));
                My::redirect([], '#error-logs');
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $head = Page::jsPageTabs('error-logs') .
            Page::jsJson('errorlogger', ['confirm_delete_logs' => __('Are you sure you want to delete log files ?')]) .
            My::jsLoad('admin.js') .
            My::cssLoad('admin.css');

        Page::openModule(My::name(), $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Error Logger')                    => '',
            ]
        );
        echo Notices::getNotices();

        // Form

        /**
         * @var        ErrorLogger
         */
        $errorlogger = App::task()->checkContext('FRONTEND') ? App::frontend()->errorlogger : App::backend()->errorlogger;

        $settings    = $errorlogger->getSettings();
        $page        = empty($_GET['page']) ? 1 : max(1, (int) $_GET['page']);
        $nb_per_page = 30;
        $offset      = ($page - 1) * $nb_per_page;

        $bases = array_map(static fn ($path) => Path::real($path), [
            App::config()->dotclearRoot(),                                      // Core
            App::blog()->themesPath(),                                          // Theme
            ...explode(PATH_SEPARATOR, (string) App::config()->pluginsRoot()),  // Plugins
        ]);
        $prefixes = ['[core]', '[theme]', '[plugin]'];

        echo
        '<div class="multi-part" title="' . __('Errors log') . '" id="error-logs">' .
        '<h3>' . __('Errors log') . '</h3>';

        /**
         * @var array<string|int, array<string, mixed>>
         */
        $logs = array_reverse($errorlogger->getErrors());
        if ($logs === []) {
            echo '<p>' . __('No logs') . '</p>';
        } else {
            $pager = new Pager($page, count($logs), $nb_per_page, 10);

            echo $pager->getLinks();

            echo
            '<table id="logs-list"><tr><th class="first nowrap">' . __('Date') . '</th>' .
            '<th>' . __('Type') . '</th>' .
            '<th>' . __('File') . '</th>' .
            '<th>' . __('Description') . '</th>' .
            '<th>' . __('Count') . '</th>' .
            '<th>' . __('URL') . '</th>' .
            '</tr>';

            for ($k = $offset; ($k < count($logs)) && ($k < $offset + $nb_per_page); ++$k) {
                $l           = $logs[$k];
                $file        = $l['file'];
                $description = $l['str'];
                $backtrace   = $l['backtrace'] ?? [];
                foreach ($bases as $index => $base) {
                    // Filter bases (beginning of path) of file
                    if (strstr((string) $file, (string) $base)) {
                        $file = $prefixes[min($index, 2)] . substr((string) $file, strlen((string) $base));
                    }

                    // Filter bases in description
                    $description = str_replace((string) $base, $prefixes[min($index, 2)], $description);
                    if (is_array($description)) {
                        $description = $description[0];
                    }
                    // Filter backtrace
                    foreach ($backtrace as $key => $trace) {
                        $backtrace[$key] = str_replace((string) $base, $prefixes[min($index, 2)], $trace);
                    }
                }

                echo
                '<tr class="line" id="p' . $k . '">' .
                '<td class="nowrap">' . Html::escapeHTML($l['ts']) . '</td>' .
                '<td class="nowrap">' . Html::escapeHTML((string) ($errorlogger->errnos[$l['no']] ?? $l['no'])) . '</td>' .
                '<td>' . Html::escapeHTML($file . ':' . $l['line']) . '</td>' .
                '<td>' . Html::escapeHTML($description) . '</td>' .
                '<td class="nowrap count">' . Html::escapeHTML((string) $l['count']) . '</td>' .
                '<td>' . Html::escapeHTML($l['url']) . '</td>' .
                '</tr>' ;
                if (count($backtrace) > 0) {
                    echo
                    '<tr id="pe' . $k . '"><td colspan="6"><strong>' . __('Backtrace') . '</strong><ol>';
                    foreach ($backtrace as $trace) {
                        echo
                        '<li>' . $trace . '</li>';
                    }

                    echo
                    '</ol></td></tr>';
                }
            }

            echo
            '</table>';

            echo $pager->getLinks();

            echo
            (new Form('form-logs'))
                ->action(App::backend()->getPageURL())
                ->method('post')
                ->fields([
                    // Submit
                    (new Para())->items([
                        (new Submit(['clearfiles']))
                            ->value(__('Clear log files')),
                        ... My::hiddenFields(),
                    ]),
                ])
            ->render();
        }

        echo
        '</div>';

        echo
        '<div class="multi-part" title="' . __('Settings') . '" id="error-settings">' .
        '<h3>' . __('Settings') . '</h3>';

        echo
        (new Form('form-options'))
            ->action(App::backend()->getPageURL())
            ->method('post')
            ->fields([
                (new Para())->items([
                    (new Checkbox('enabled', $settings['enabled']))
                        ->value(1)
                        ->label((new Label(__('Enable error logging'), Label::INSIDE_TEXT_AFTER))),
                ]),
                (new Para())->items([
                    (new Checkbox('backtrace', $settings['backtrace']))
                        ->value(1)
                        ->label((new Label(__('Enable backtrace logging'), Label::INSIDE_TEXT_AFTER))),
                ]),
                (new Para())->items([
                    (new Checkbox('silent_mode', $settings['silent_mode']))
                        ->value(1)
                        ->label((new Label(__('Enable silent mode : standard errors will only be logged, no output'), Label::INSIDE_TEXT_AFTER))),
                ]),
                (new Para())->class('info')->items([
                    (new Text(null, __('If you do not want to be annoyed with warning messages, unselect the checkbox below'))),
                ]),
                (new Para())->items([
                    (new Checkbox('annoy_user', $settings['annoy_user']))
                        ->value(1)
                        ->label((new Label(__('Enable Annoying mode : warn user every time a new error has been detected'), Label::INSIDE_TEXT_AFTER))),
                ]),
                (new Para())->items([
                    (new Input('dir'))
                        ->size(20)
                        ->maxlength(256)
                        ->value(Html::escapeHTML($settings['dir']))
                        ->required(true)
                        ->placeholder('errorlogger')
                        ->label((new Label(__('Directory for logs (will be created in dotclear cache dir)'), Label::OUTSIDE_TEXT_BEFORE))),
                ]),
                (new Para())->items([
                    (new Input('bin_file'))
                        ->size(20)
                        ->maxlength(256)
                        ->value(Html::escapeHTML($settings['bin_file']))
                        ->required(true)
                        ->placeholder('errorlogger')
                        ->label((new Label(__('Binary log file name'), Label::OUTSIDE_TEXT_BEFORE))),
                ]),
                (new Para())->items([
                    (new Input('txt_file'))
                        ->size(20)
                        ->maxlength(256)
                        ->value(Html::escapeHTML($settings['txt_file']))
                        ->required(true)
                        ->placeholder('errorlogger')
                        ->label((new Label(__('Text log file name'), Label::OUTSIDE_TEXT_BEFORE))),
                ]),
                // Submit
                (new Para())->items([
                    (new Submit(['save']))
                        ->value(__('Save')),
                    ... My::hiddenFields(),
                ]),
            ])
        ->render();

        echo '</div>';

        Page::closeModule();
    }
}
