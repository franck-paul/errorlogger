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
    /** @var dcCore dcCore instance */
    protected $core;
    protected $old_error_handler;
    public $errnos;
    protected $default_settings;
    protected $settings;
    protected $already_annoyed;
    protected $bin_file;
    protected $txt_file;
    protected $ts_format;

    /**
    Inits ErrorLogger object

    @param	core		<b>dcCore</b>		Dotclear core reference
     */
    public function __construct($core)
    {
        $this->core   = $core;
        $this->errnos = [
            E_ERROR   => 'ERROR',
            E_WARNING => 'WARNING',
            E_NOTICE  => 'NOTICE'];
        $this->already_annoyed   = false;
        $this->old_error_handler = set_error_handler([$this,'errorHandler']);
        $this->ts_format         = $core->blog->settings->system->date_formats[0] . ' %H:%M:%S';
    }

    public function initSettings()
    {
        $ws                     = $this->core->blog->settings->addNamespace('errorlogger');
        $this->default_settings = [
            'backtrace'   => ['boolean',false, 'Enable backtrace in logs'],
            'silent_mode' => ['boolean',false, 'Silent native errors, only show logs'],
            'enabled'     => ['boolean',false,'Enable error logger'],
            'annoy_user'  => ['boolean',true,''],
            'bin_file'    => ['string','errors.bin','Binary log file name'],
            'txt_file'    => ['string','errors.txt','Text log file name'],
            'dir'         => ['string','errorlogger','directory used for logs (under cache dir)'],
            'annoy_flag'  => ['boolean',false,'annoy flag']
        ];
        $settings = [];
        foreach ($this->default_settings as $k => $v) {
            $value = $ws->$k;
            if ($value === null) {
                $settings[$k] = $v[1];
                $ws->put($k, $v[1], $v[0], $v[2]);
            } else {
                $settings[$k] = $value;
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
        $this->core->blog->settings->errorlogger->put('annoy_flag', false);
    }

    public function setup()
    {
        global $_lang;
        $this->settings = $this->initSettings();
        if (isset($_GET['ack_errorlogger'])) {
            $lfile = dirname(__FILE__) . '/locales/%s/main';
            if (l10n::set(sprintf($lfile, $_lang)) === false && $_lang != 'en') {
                l10n::set(sprintf($lfile, 'en'));
            }

            $this->acknowledge();
            dcPage::addSuccessNotice(__('Error Logs acknowledged.'));
        } elseif (
            $this->settings['annoy_user'] && $this->core->blog->settings->errorlogger->annoy_flag == true && !$this->already_annoyed) {
            if (isset($_SESSION['notifications'])) {
                $notifications = $_SESSION['notifications'];
                foreach ($notifications as $n) {
                    if (isset($n['errorlogger'])) {
                        return;
                    }
                }
            }

            $lfile = dirname(__FILE__) . '/locales/%s/main';
            if (l10n::set(sprintf($lfile, $_lang)) === false && $_lang != 'en') {
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
                ['divtag' => true, 'with_ts' => false, 'errorlogger' => true]);
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
            $value = isset($settings[$k]) ? $settings[$k] : $v[1];
            if ($v[0] == 'string' && $value == '') {
                $value = $v[1];
            }
            $this->core->blog->settings->errorlogger->put($k, $value);
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
            $this->core->blog->settings->errorlogger->put('annoy_flag', true, 'boolean');
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
        $errno = isset($this->errnos[$errno]) ? $this->errnos[$errno] : $errno;
        $lents = count($msg['ts']);
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
        global $core;
        if (!$this->settings['enabled'] || (0 === error_reporting())) {
            return false;
        }

        $msg = [
            'no'   => $errno,
            'ts'   => dt::str(__($this->ts_format), time(), $core->auth->getInfo('user_tz')),
            'str'  => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'url'  => $_SERVER['REQUEST_URI']
        ];

        if ($this->settings['backtrace']) {
            $debug = debug_backtrace();
            $dbg   = [];
            unset($debug[0]);
            foreach ($debug as $d) {
                $dbg[] = sprintf('[%s:%s] : %s::%s',
                    isset($d['file']) ? $d['file'] : 'N/A',
                    isset($d['line']) ? $d['line'] : 'N/A',
                    isset($d['class']) ? $d['class'] : '',
                    isset($d['function']) ? $d['function'] : 'N/A'
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
