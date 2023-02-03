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
class ErrorLogger
{
    public $errnos;
    protected $default_settings;
    protected $settings;
    protected $already_annoyed;
    protected $bin_file;
    protected $txt_file;
    protected $ts_format;

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

        set_error_handler([$this,'errorHandler']);
    }

    public function initSettings()
    {
        $this->default_settings = [
            'backtrace'   => ['boolean',false, 'Enable backtrace in logs'],
            'silent_mode' => ['boolean',false, 'Silent native errors, only show logs'],
            'enabled'     => ['boolean',false,'Enable error logger'],
            'annoy_user'  => ['boolean',true,''],
            'bin_file'    => ['string','errors.bin','Binary log file name'],
            'txt_file'    => ['string','errors.txt','Text log file name'],
            'dir'         => ['string','errorlogger','directory used for logs (under cache dir)'],
            'annoy_flag'  => ['boolean',false,'annoy flag'],
        ];
        $settings = [];
        if (dcCore::app()->blog) {
            $ws = dcCore::app()->blog->settings->errorlogger;
            foreach ($this->default_settings as $k => $v) {
                $value = $ws->$k;
                if ($value === null) {
                    $settings[$k] = $v[1];
                    $ws->put($k, $v[1], $v[0], $v[2]);
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

    protected function getFilename($setting)
    {
        if (!is_dir(DC_TPL_CACHE . '/' . $this->settings['dir'])) {
            mkdir(DC_TPL_CACHE . '/' . $this->settings['dir']);
        }

        return DC_TPL_CACHE . '/' . $this->settings['dir'] . '/' . $this->settings[$setting];
    }

    public function acknowledge()
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
        dcCore::app()->blog->settings->errorlogger->put('annoy_flag', false);
    }

    public function setup()
    {
        $this->settings = $this->initSettings();
        if (isset($_GET['ack_errorlogger'])) {
            $lfile = __DIR__ . '/locales/%s/main';
            if (l10n::set(sprintf($lfile, dcCore::app()->lang)) === false && dcCore::app()->lang != 'en') {
                l10n::set(sprintf($lfile, 'en'));
            }

            $this->acknowledge();
            dcPage::addSuccessNotice(__('Error Logs acknowledged.'));
        } elseif (
            $this->settings['annoy_user'] && dcCore::app()->blog->settings->errorlogger->annoy_flag && !$this->already_annoyed) {
            if (isset($_SESSION['notifications'])) {
                $notifications = $_SESSION['notifications'];
                foreach ($notifications as $n) {
                    if (isset($n['errorlogger'])) {
                        return;
                    }
                }
            }

            $lfile = __DIR__ . '/locales/%s/main';
            if (l10n::set(sprintf($lfile, dcCore::app()->lang)) === false && dcCore::app()->lang != 'en') {
                l10n::set(sprintf($lfile, 'en'));
            }
            $uri    = explode('?', $_SERVER['REQUEST_URI']);
            $params = $_GET;
            if (!isset($params['p']) && isset($_POST['p'])) {
                $params['p'] = $_POST['p'];
            }
            $params['ack_errorlogger'] = 1;
            $ack_uri                   = $uri[0] . '?' . http_build_query($params, '', '&amp;');
            dcPage::addWarningNotice(
                '<p>' . __('Some new error messages have been detected') . '</p>' .
                '<p><a class="button" href="plugin.php?p=errorlogger">' . __('View error logs') . '</a> ' .
                '<a class="button" href="' . $ack_uri . '">' . __('Acknowledge') . '</a> ' .
                '<a class="button" href="plugin.php?p=errorlogger&amp;annoy=1#error-settings">' . __("Don't bother me again") . '</a></p>',
                ['divtag' => true, 'with_ts' => false, 'errorlogger' => true]
            );
            $this->already_annoyed = true;
        }
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings($settings)
    {
        foreach ($this->default_settings as $k => $v) {
            $value = $settings[$k] ?? $v[1];
            if ($v[0] == 'string' && $value == '') {
                $value = $v[1];
            }
            dcCore::app()->blog->settings->errorlogger->put($k, $value);
        }
        $this->settings = $settings;
    }

    public function getErrors()
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

    public function addBinaryMessage($msg)
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
            dcCore::app()->blog->settings->errorlogger->put('annoy_flag', true, 'boolean');
        }
        file_put_contents($binfile, serialize($binmsg));
    }

    public function addErrorMessage($msg)
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

    protected function log($msg)
    {
        $this->addErrorMessage($msg);
        $this->addBinaryMessage($msg);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!$this->settings['enabled'] || (0 === error_reporting())) {
            return false;
        }

        if (in_array($errstr, $this->ignored_str)) {
            return false;
        }

        $msg = [
            'no'   => $errno,
            'ts'   => dt::str(__($this->ts_format), time(), dcCore::app()->auth->getInfo('user_tz')),
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

    public function clearLogs()
    {
        @unlink($this->getFileName('bin_file'));
        @unlink($this->getFileName('txt_file'));
    }
}
