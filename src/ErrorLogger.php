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
use dcNamespace;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Date;
use Dotclear\Helper\L10n;
use Exception;

class ErrorLogger
{
    public $errnos;

    /**
     * @var array
     */
    protected $default_settings;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $already_annoyed;

    /**
     * @var string|null
     */
    protected $bin_file;

    /**
     * @var string|null
     */
    protected $txt_file;

    /**
     * @var string|null
     */
    protected $ts_format;

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

        $this->already_annoyed = false;

        if (dcCore::app()->blog) {
            $this->ts_format = dcCore::app()->blog->settings->system->date_formats[0] . ' %H:%M:%S';
        } else {
            $this->ts_format = '%Y-%m-%d %H:%M:%S';
        }

        set_error_handler($this->errorHandler(...));
    }

    /**
     * Initializes the settings.
     *
     * @return     array
     */
    public function initSettings(): array
    {
        $this->default_settings = [
            'backtrace'   => [dcNamespace::NS_BOOL, false, 'Enable backtrace in logs'],
            'silent_mode' => [dcNamespace::NS_BOOL, false, 'Silent native errors, only show logs'],
            'enabled'     => [dcNamespace::NS_BOOL, false, 'Enable error logger'],
            'annoy_user'  => [dcNamespace::NS_BOOL, true, ''],
            'bin_file'    => [dcNamespace::NS_STRING, 'errors.bin', 'Binary log file name'],
            'txt_file'    => [dcNamespace::NS_STRING, 'errors.txt', 'Text log file name'],
            'dir'         => [dcNamespace::NS_STRING, 'errorlogger', 'directory used for logs (under cache dir)'],
            'annoy_flag'  => [dcNamespace::NS_BOOL, false, 'annoy flag'],
        ];

        $settings = [];
        if (dcCore::app()->blog) {
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
        if (!is_dir(DC_TPL_CACHE . '/' . $this->settings['dir'])) {
            mkdir(DC_TPL_CACHE . '/' . $this->settings['dir']);
        }

        return DC_TPL_CACHE . '/' . $this->settings['dir'] . '/' . $this->settings[$setting];
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
        My::settings()->put('annoy_flag', false, dcNamespace::NS_BOOL);
    }

    /**
     * Setup
     */
    public function setup(): void
    {
        $this->settings = $this->initSettings();
        if (isset($_GET['ack_errorlogger'])) {
            $lfile = __DIR__ . '/locales/%s/main';
            if (L10n::set(sprintf($lfile, dcCore::app()->lang)) === false && dcCore::app()->lang != 'en') {
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
            if (L10n::set(sprintf($lfile, dcCore::app()->lang)) === false && dcCore::app()->lang != 'en') {
                L10n::set(sprintf($lfile, 'en'));
            }
            $uri    = explode('?', $_SERVER['REQUEST_URI']);
            $params = $_GET;
            if (!isset($params['p']) && isset($_POST['p'])) {
                $params['p'] = $_POST['p'];
            }
            $params['ack_errorlogger'] = 1;
            $ack_uri                   = $uri[0] . '?' . http_build_query($params, '', '&amp;');

            $my_uri       = '';
            $my_uri_annoy = '';

            if (isset(dcCore::app()->admin->url)) {
                try {
                    $my_uri       = '<a class="button" href="' . dcCore::app()->admin->url->get('admin.plugin.' . My::id()) . '">' . __('View error logs') . '</a> ';
                    $my_uri_annoy = '<a class="button" href="' . dcCore::app()->admin->url->get('admin.plugin.' . My::id(), ['annoy' => 1]) . '#error-settings">' . __("Don't bother me again") . '</a>';
                } catch (Exception $e) {
                    // Ignore exception here
                }
            }

            Notices::addWarningNotice(
                '<p>' . __('Some new error messages have been detected') . '</p>' .
                '<p>' . $my_uri . '<a class="button" href="' . $ack_uri . '">' . __('Acknowledge') . '</a> ' . $my_uri_annoy . '</p>',
                ['divtag' => true, 'with_ts' => false, 'errorlogger' => true]
            );
            $this->already_annoyed = true;
        }
    }

    /**
     * Gets the settings.
     *
     * @return     array  The settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Sets the settings.
     *
     * @param      array  $settings  The settings
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
     * @return     array  The errors.
     */
    public function getErrors(): array
    {
        $binfile = $this->getFileName('bin_file');
        if (file_exists($binfile)) {
            $contents = file_get_contents($binfile);
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
     * @param      array  $msg    The message
     */
    public function addBinaryMessage(array $msg): void
    {
        $binfile = $this->getFileName('bin_file');
        $binmsg  = $this->getErrors();

        $msg['hash']  = hash('md5', $msg['no'] . $msg['file'] . $msg['line'] . $msg['str']);
        $msg['count'] = 1;
        $done         = false;
        foreach ($binmsg as $k => $b) {
            if (isset($b['hash']) && $b['hash'] == $msg['hash']) {
                $binmsg[$k]['ts'] = $msg['ts'];
                $binmsg[$k]['count']++;
                $done = true;

                break;
            }
        }
        if (!$done) {
            $binmsg[] = $msg;
            $ns       = My::settings();
            $ns->put('annoy_flag', true, dcNamespace::NS_BOOL);
        }
        file_put_contents($binfile, serialize($binmsg));
    }

    /**
     * Adds a message in the text file.
     *
     * @param      array  $msg    The message
     */
    public function addErrorMessage(array $msg): void
    {
        $out = $this->getFileName('txt_file');
        if (!($fp = fopen($out, 'a'))) {
            return;
        }
        $errno = $msg['no'];
        $errno = $this->errnos[$errno] ?? $errno;
        $lents = strlen($msg['ts']);
        fprintf($fp, "%s [%7s] URL: %s\n", $msg['ts'], $errno, $msg['url']);
        fprintf($fp, "%s %s (file : %s, %s)\n", str_repeat(' ', $lents + 7 + 1), $msg['str'], $msg['file'], $msg['line']);
    }

    /**
     * Add a message
     *
     * @param      array  $msg    The message
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
     *
     * @return     bool
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
            'ts'   => Date::str(__($this->ts_format), time(), dcCore::app()->auth->getInfo('user_tz')),
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

        return ($this->settings['silent_mode']);
    }

    /**
     * Clear the files (binary and text)
     */
    public function clearLogs(): void
    {
        if (file_exists($this->getFileName('bin_file'))) {
            @unlink($this->getFileName('bin_file'));
        }
        if (file_exists($this->getFileName('txt_file'))) {
            @unlink($this->getFileName('txt_file'));
        }
    }
}
