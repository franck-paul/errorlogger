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
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Exception;

class ErrorLogger
{
    /**
     * @var array<string>
     */
    public array $errnos;

    protected bool $already_annoyed = false;

    protected string $ts_format = '%Y-%m-%d %H:%M:%S';

    /**
     * List of ignored errors
     *
     * @var array<string>
     */
    private array $ignored_str = [
        // Ignored until PHP 9 full support
        'Function strftime() is deprecated',
    ];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->errnos = [
            E_ERROR   => 'ERROR',
            E_WARNING => 'WARNING',
            E_NOTICE  => 'NOTICE', ];

        if (App::blog()->isDefined()) {
            $ts_format       = is_array(App::blog()->settings()->system->date_formats) && is_string($ts_format = App::blog()->settings()->system->date_formats[0]) ? $ts_format : '%Y-%m-%d';
            $this->ts_format = $ts_format . ' %H:%M:%S';
        }

        set_error_handler($this->errorHandler(...));
    }

    /**
     * Gets the filename.
     *
     * @param      bool    $binary  True if its the binary one, false if text one
     *
     * @return     string  The filename.
     */
    protected function getFilename(bool $binary): string
    {
        $settings = My::settings();

        if ($binary) {
            $filename = is_string($filename = $settings->bin_file) ? $filename : '';
        } else {
            $filename = is_string($filename = $settings->txt_file) ? $filename : '';
        }

        $dir = is_string($dir = $settings->dir) ? $dir : '';

        if ($dir !== '' && !is_dir(App::config()->cacheRoot() . '/' . $dir)) {
            mkdir(App::config()->cacheRoot() . '/' . $dir);
        }

        return App::config()->cacheRoot() . '/' . ($dir !== '' ? $dir . '/' : '') . $filename;
    }

    /**
     * Cope with acknowledge
     */
    public function acknowledge(): void
    {
        if (App::session()->get('notifications')) {
            $notifications = App::session()->get('notifications');
            if (is_array($notifications)) {
                foreach ($notifications as $k => $n) {
                    if (is_array($n) && isset($n['errorlogger'])) {
                        unset($notifications[$k]);
                    }
                }
            }

            App::session()->set('notifications', $notifications);
        }

        My::settings()->put('annoy_flag', false, App::blogWorkspace()::NS_BOOL);
    }

    /**
     * Setup
     */
    public function setup(): void
    {
        $settings = My::settings();

        if (isset($_GET['ack_errorlogger'])) {
            $lfile = __DIR__ . '/locales/%s/main';
            if (App::lang()->set(sprintf($lfile, App::lang()->getLang())) === false && App::lang()->getLang() !== 'en') {
                App::lang()->set(sprintf($lfile, 'en'));
            }

            $this->acknowledge();
            App::backend()->notices()->addSuccessNotice(__('Error Logs acknowledged.'));
        } elseif ((bool) $settings->annoy_user && My::settings()->annoy_flag && !$this->already_annoyed) {
            if (App::session()->get('notifications')) {
                $notifications = App::session()->get('notifications');
                if (is_array($notifications)) {
                    foreach ($notifications as $n) {
                        if (is_array($n) && isset($n['errorlogger'])) {
                            return;
                        }
                    }
                }
            }

            $lfile = __DIR__ . '/locales/%s/main';
            if (App::lang()->set(sprintf($lfile, App::lang()->getLang())) === false && App::lang()->getLang() !== 'en') {
                App::lang()->set(sprintf($lfile, 'en'));
            }

            $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($request_uri = $_SERVER['REQUEST_URI']) ? $request_uri : '';
            $uri         = explode('?', $request_uri);
            $params      = $_GET;
            if (!isset($params['p']) && isset($_POST['p'])) {
                $params['p'] = $_POST['p'];
            }

            $params['ack_errorlogger'] = 1;
            $ack_uri                   = $uri[0] . '?' . http_build_query($params, '', '&amp;');

            try {
                $myurl       = My::manageUrl();
                $myurl_annoy = My::manageUrl(['annoy' => 1]);
            } catch (Exception) {
                // In some rare cases, the admin plugin URL is not already ready, so use a static one
                $myurl       = 'index.php?process=Plugin&p=errorlogger';
                $myurl_annoy = $myurl . '&annoy=1';
            }

            $msg = (new Set())
                ->items([
                    (new Note())
                        ->text(__('Some new error messages have been detected')),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Link())
                                ->class('button')
                                ->href($myurl)
                                ->text(__('View error logs')),
                            (new Link())
                                ->class('button')
                                ->href($ack_uri)
                                ->text(__('Acknowledge')),
                            (new Link())
                                ->class('button')
                                ->href($myurl_annoy)
                                ->text(__("Don't bother me again")),
                        ]),
                ])
            ->render();

            App::backend()->notices()->addWarningNotice($msg, ['divtag' => true, 'with_ts' => false, 'errorlogger' => true]);
            $this->already_annoyed = true;
        }
    }

    /**
     * Gets the errors from binary file.
     *
     * @return     array<array-key, array{
     *                 no: int,
     *                 ts: string,
     *                 str: string,
     *                 file: string,
     *                 line: int,
     *                 url: string,
     *                 backtrace?: string[],
     *                 hash: string,
     *                 count: int
     *             }>  The errors.
     */
    public function getErrors(): array
    {
        $binmsg  = [];
        $binfile = $this->getFilename(true);
        if (file_exists($binfile)) {
            $contents = (string) file_get_contents($binfile);

            try {
                /**
                 * @var array<array-key, array{
                 *                 no: int,
                 *                 ts: string,
                 *                 str: string,
                 *                 file: string,
                 *                 line: int,
                 *                 url: string,
                 *                 backtrace?: string[],
                 *                 hash: string,
                 *                 count: int
                 *             }>
                 */
                $binmsg = unserialize($contents);
            } catch (Exception) {
                $binmsg = [];
            }
        }

        return $binmsg;
    }

    /**
     * Adds a message in the binary file.
     *
     * @param      array{
     *                 no: int,
     *                 ts: string,
     *                 str: string,
     *                 file: string,
     *                 line: int,
     *                 url: string,
     *                 backtrace?: string[],
     *             }  $msg    The message
     */
    public function addBinaryMessage(array $msg): void
    {
        $binfile = $this->getFilename(true);
        $binmsg  = $this->getErrors();

        $hash  = hash('md5', $msg['no'] . $msg['file'] . $msg['line'] . $msg['str']);
        $count = 1;

        $done = false;
        foreach ($binmsg as $k => $b) {
            if ($b['hash'] === $hash) {
                $binmsg[$k]['ts'] = $msg['ts'];
                ++$binmsg[$k]['count'];
                $done = true;

                break;
            }
        }

        if (!$done) {
            $binmsg[] = [
                ... $msg,
                $hash,
                $count,
            ];
            My::settings()->put('annoy_flag', true, App::blogWorkspace()::NS_BOOL);
        }

        file_put_contents($binfile, serialize($binmsg));
    }

    /**
     * Adds a message in the text file.
     *
     * @param      array{
     *                 no: int,
     *                 ts: string,
     *                 str: string,
     *                 file: string,
     *                 line: int,
     *                 url: string,
     *                 backtrace?: string[],
     *             }  $msg    The message
     */
    public function addErrorMessage(array $msg): void
    {
        $out = $this->getFilename(false);
        if (!($fp = fopen($out, 'a'))) {
            return;
        }

        $errno = $msg['no'];
        $errno = $this->errnos[$errno] ?? $errno;

        $lents = strlen((string) $msg['ts']);
        fprintf($fp, "%s [%7s] URL: %s\n", $msg['ts'], $errno, $msg['url']);
        fprintf($fp, "%s %s (file : %s, %s)\n", str_repeat(' ', $lents + 7 + 1), $msg['str'], $msg['file'], $msg['line']);
    }

    /**
     * Add a message
     *
     * @param      array{
     *                 no: int,
     *                 ts: string,
     *                 str: string,
     *                 file: string,
     *                 line: int,
     *                 url: string,
     *                 backtrace?: string[],
     *             }  $msg    The message
     */
    protected function log(array $msg): void
    {
        $this->addErrorMessage($msg);
        $this->addBinaryMessage($msg);
    }

    /**
     * Error handler
     *
     * @param      int     $errno    The error number
     * @param      string  $errstr   The error message
     * @param      string  $errfile  The file where the error occured
     * @param      int     $errline  The line where the error occured in file
     */
    public function errorHandler(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
    {
        $settings = My::settings();

        if ((bool) $settings->enabled === false || error_reporting() === 0) {
            return false;
        }

        if (in_array($errstr, $this->ignored_str)) {
            return false;
        }

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : null;

        /**
         * @var array{
         *          no: int,
         *          ts: string,
         *          str: string,
         *          file: string,
         *          line: int,
         *          url: string,
         *          backtrace?: string[],
         *      }
         */
        $msg = [
            'no'   => $errno,
            'ts'   => Date::str(__($this->ts_format), time(), $user_tz),
            'str'  => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'url'  => $_SERVER['REQUEST_URI'],
        ];

        if ((bool) $settings->backtrace) {
            $debug = debug_backtrace();
            $dbg   = [];
            unset($debug[0]);
            foreach ($debug as $d) {
                $dbg[] = sprintf(
                    '[%s:%s] : %s::%s',
                    $d['file']  ?? 'N/A',
                    $d['line']  ?? 'N/A',
                    $d['class'] ?? '',
                    $d['function'] ?: 'N/A'
                );
            }

            $msg['backtrace'] = $dbg;
        }

        $this->log($msg);

        return (bool) $settings->silent_mode;
    }

    /**
     * Clear the files (binary and text)
     */
    public function clearLogs(): void
    {
        if (file_exists($this->getFilename(true))) {
            @unlink($this->getFilename(true));
        }

        if (file_exists($this->getFilename(false))) {
            @unlink($this->getFilename(false));
        }
    }
}
