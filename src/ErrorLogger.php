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
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\L10n;
use Exception;

class ErrorLogger
{
    /**
     * @var array<string>
     */
    public array $errnos;

    /**
     * @var array<string, mixed>
     */
    protected array $default_settings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $settings = [];

    protected bool $already_annoyed = false;

    protected ?string $bin_file = null;

    protected ?string $txt_file = null;

    protected ?string $ts_format;

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
            $this->ts_format = App::blog()->settings()->system->date_formats[0] . ' %H:%M:%S';
        } else {
            $this->ts_format = '%Y-%m-%d %H:%M:%S';
        }

        set_error_handler($this->errorHandler(...));
    }

    /**
     * Initializes the settings.
     *
     * @return     array<string, mixed>
     */
    public function initSettings(): array
    {
        $this->default_settings = [
            'backtrace'   => [App::blogWorkspace()::NS_BOOL, false, 'Enable backtrace in logs'],
            'silent_mode' => [App::blogWorkspace()::NS_BOOL, false, 'Silent native errors, only show logs'],
            'enabled'     => [App::blogWorkspace()::NS_BOOL, false, 'Enable error logger'],
            'annoy_user'  => [App::blogWorkspace()::NS_BOOL, true, ''],
            'bin_file'    => [App::blogWorkspace()::NS_STRING, 'errors.bin', 'Binary log file name'],
            'txt_file'    => [App::blogWorkspace()::NS_STRING, 'errors.txt', 'Text log file name'],
            'dir'         => [App::blogWorkspace()::NS_STRING, 'errorlogger', 'directory used for logs (under cache dir)'],
            'annoy_flag'  => [App::blogWorkspace()::NS_BOOL, false, 'annoy flag'],
        ];

        $settings = [];
        if (App::blog()->isDefined()) {
            $ns = My::settings();
            foreach ($this->default_settings as $k => $v) {
                $value = $ns->$k;
                if ($value === null) {
                    $settings[$k] = $v[1];
                    $ns->put($k, $v[1], $v[0], $v[2]);
                } else {
                    $settings[$k] = $value;
                }
            }
        } else {
            foreach ($this->default_settings as $k => $v) {
                $settings[$k] = $v[1];
            }
        }

        return $settings;
    }

    /**
     * Gets the filename.
     *
     * @param      string   $setting  The setting (should be bin_file or txt_file)
     *
     * @return     string  The filename.
     */
    protected function getFilename(string $setting): string
    {
        if (!is_dir(App::config()->cacheRoot() . '/' . $this->settings['dir'])) {
            mkdir(App::config()->cacheRoot() . '/' . $this->settings['dir']);
        }

        return App::config()->cacheRoot() . '/' . $this->settings['dir'] . '/' . $this->settings[$setting];
    }

    /**
     * Cope with acknowledge
     */
    public function acknowledge(): void
    {
        if (isset($_SESSION['notifications'])) {
            $notifications = $_SESSION['notifications'];
            foreach ($notifications as $k => $n) {
                if (isset($n['errorlogger'])) {
                    unset($notifications[$k]);
                }
            }

            $_SESSION['notifications'] = $notifications;
        }

        My::settings()->put('annoy_flag', false, App::blogWorkspace()::NS_BOOL);
    }

    /**
     * Setup
     */
    public function setup(): void
    {
        $this->settings = $this->initSettings();
        if (isset($_GET['ack_errorlogger'])) {
            $lfile = __DIR__ . '/locales/%s/main';
            if (L10n::set(sprintf($lfile, App::lang()->getLang())) === false && App::lang()->getLang() != 'en') {
                L10n::set(sprintf($lfile, 'en'));
            }

            $this->acknowledge();
            Notices::addSuccessNotice(__('Error Logs acknowledged.'));
        } elseif ($this->settings['annoy_user'] && My::settings()->annoy_flag && !$this->already_annoyed) {
            if (isset($_SESSION['notifications'])) {
                $notifications = $_SESSION['notifications'];
                foreach ($notifications as $n) {
                    if (isset($n['errorlogger'])) {
                        return;
                    }
                }
            }

            $lfile = __DIR__ . '/locales/%s/main';
            if (L10n::set(sprintf($lfile, App::lang()->getLang())) === false && App::lang()->getLang() != 'en') {
                L10n::set(sprintf($lfile, 'en'));
            }

            $uri    = explode('?', (string) $_SERVER['REQUEST_URI']);
            $params = $_GET;
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

            Notices::addWarningNotice($msg, ['divtag' => true, 'with_ts' => false, 'errorlogger' => true]);
            $this->already_annoyed = true;
        }
    }

    /**
     * Gets the settings.
     *
     * @return     array<string, mixed>  The settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Sets the settings.
     *
     * @param      array<string, mixed>  $settings  The settings
     */
    public function setSettings(array $settings): void
    {
        $ns = My::settings();
        foreach ($this->default_settings as $k => $v) {
            $value = $settings[$k] ?? $v[1];
            if ($v[0] == 'string' && $value == '') {
                $value = $v[1];
            }

            $ns->put($k, $value);
        }

        $this->settings = $settings;
    }

    /**
     * Gets the errors from binary file.
     *
     * @return     array<string|int, array<string, mixed>>  The errors.
     */
    public function getErrors(): array
    {
        $binfile = $this->getFilename('bin_file');
        if (file_exists($binfile)) {
            $contents = (string) file_get_contents($binfile);
            $binmsg   = @unserialize($contents);
            if (!is_array($binmsg)) {
                $binmsg = [];
            }
        } else {
            $binmsg = [];
        }

        return $binmsg;
    }

    /**
     * Adds a message in the binary file.
     *
     * @param      array<string, mixed>  $msg    The message
     */
    public function addBinaryMessage(array $msg): void
    {
        $binfile = $this->getFilename('bin_file');
        $binmsg  = $this->getErrors();

        $msg['hash']  = hash('md5', $msg['no'] . $msg['file'] . $msg['line'] . $msg['str']);
        $msg['count'] = 1;
        $done         = false;
        foreach ($binmsg as $k => $b) {
            if (isset($b['hash']) && $b['hash'] == $msg['hash']) {
                $binmsg[$k]['ts'] = $msg['ts'];
                ++$binmsg[$k]['count'];
                $done = true;

                break;
            }
        }

        if (!$done) {
            $binmsg[] = $msg;
            $ns       = My::settings();
            $ns->put('annoy_flag', true, App::blogWorkspace()::NS_BOOL);
        }

        file_put_contents($binfile, serialize($binmsg));
    }

    /**
     * Adds a message in the text file.
     *
     * @param      array<string, mixed>  $msg    The message
     */
    public function addErrorMessage(array $msg): void
    {
        $out = $this->getFilename('txt_file');
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
     * @param      array<string, mixed>  $msg    The message
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
        if (!$this->settings['enabled'] || (0 === error_reporting())) {
            return false;
        }

        if (in_array($errstr, $this->ignored_str)) {
            return false;
        }

        $msg = [
            'no'   => $errno,
            'ts'   => Date::str(__((string) $this->ts_format), time(), App::auth()->getInfo('user_tz')),
            'str'  => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'url'  => $_SERVER['REQUEST_URI'],
        ];

        if ($this->settings['backtrace']) {
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

        return (bool) $this->settings['silent_mode'];
    }

    /**
     * Clear the files (binary and text)
     */
    public function clearLogs(): void
    {
        if (file_exists($this->getFilename('bin_file'))) {
            @unlink($this->getFilename('bin_file'));
        }

        if (file_exists($this->getFilename('txt_file'))) {
            @unlink($this->getFilename('txt_file'));
        }
    }
}
