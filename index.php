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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$settings = $core->errorlogger->getSettings();

if (isset($_POST['save'])) {
    $settings = [
        'enabled'     => isset($_POST['enabled']) && $_POST['enabled'] == 1,
        'backtrace'   => isset($_POST['backtrace']) && $_POST['backtrace'] == 1,
        'silent_mode' => isset($_POST['silent_mode']) && $_POST['silent_mode'] == 1,
        'annoy_user'  => isset($_POST['annoy_user']) && $_POST['annoy_user'] == 1,
        'bin_file'    => $_POST['bin_file'] ?? '',
        'txt_file'    => isset($_POST['bin_file']) ? $_POST['txt_file'] : '',
        'dir'         => isset($_POST['bin_file']) ? $_POST['dir'] : '',
    ];
    $core->errorlogger->setSettings($settings);
    dcPage::addSuccessNotice(__('Settings have been successfully updated'));
    http::redirect($p_url . '#error-settings');
    exit;
} elseif (isset($_POST['clearfiles'])) {
    $core->errorlogger->clearLogs();
    $core->errorlogger->acknowledge();
    dcPage::addSuccessNotice(__('Log files have been successfully cleared'));
    http::redirect($p_url . '#error-logs');
    exit;
}

$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 30;
$offset      = ($page - 1) * $nb_per_page;
?>
<html>
<head>
	<?php
        echo
            dcPage::jsPageTabs('error-logs') .
            dcPage::jsLoad('index.php?pf=errorlogger/js/_errorlogger.js') .
            '<script type="text/javascript">' . "\n" .
            '//<![CDATA[' . "\n" .
            dcPage::jsVar('dotclear.msg.confirm_delete_logs', __('Are you sure you want to delete log files ?')) . "\n" .
            '//]]>' .
            '</script>';
    ?>
	<title><?php echo __('ErrorLogger'); ?></title>
</head>
<body>
<?php

echo dcPage::breadcrumb([
    html::escapeHTML($core->blog->name) => '',
    __('Error Logger')                  => ''
]) . dcPage::notices();

echo
    '<div class="multi-part" title="' . __('Errors log') . '" id="error-logs">' .
    '<h3>' . __('Errors log') . '</h3>';
$logs = array_reverse($core->errorlogger->getErrors());
if (!count($logs)) {
    echo '<p>' . __('No logs') . '</p>';
} else {
    $pager = new dcPager($page, count($logs), $nb_per_page, 10);

    echo $pager->getLinks() .
        '<table id="logs-list"><tr>' .
        '<th class="first nowrap">' . __('Date') . '</th>' .
        '<th>' . __('Type') . '</th>' .
        '<th>' . __('File') . '</th>' .
        '<th>' . __('Description') . '</th>' .
        '<th>' . __('Count') . '</th>' .
        '<th>' . __('URL') . '</th>' .
        '</tr>';

    for ($k = $offset; ($k < count($logs)) && ($k < $offset + $nb_per_page); $k++) {
        $l = $logs[$k];
        echo '<tr class="line" id="p' . $k . '">' .
            '<td class="nowrap">' . html::escapeHTML($l['ts']) . '</td>' .
            '<td>' . html::escapeHTML($core->errorlogger->errnos[$l['no']]) . '</td>' .
            '<td>' . html::escapeHTML($l['file'] . ':' . $l['line']) . '</td>' .
            '<td>' . html::escapeHTML($l['str']) . '</td>' .
            '<td>' . html::escapeHTML($l['count']) . '</td>' .
            '<td>' . html::escapeHTML($l['url']) . '</td>' .
            '</tr>'	;
        if (isset($l['backtrace'])) {
            echo '<tr id="pe' . $k . '"><td colspan="6"><strong>' . __('Backtrace') . '</strong><ul>';
            foreach ($l['backtrace'] as $b) {
                echo '<li>' . $b . '</li>';
            }
            echo '</ul></td></tr>';
        }
    }
    echo '</table>' .
        $pager->getLinks() .
        '<form action="plugin.php" id="form-logs" method="post">' .
        '<p><input type="submit" class="delete" name="clearfiles" value="' . __('Clear log files') . '"/>' .
        form::hidden(['p'], 'errorlogger') . $core->formNonce() . '</p></form>';
}
echo
    '</div>';

echo '<div class="multi-part" title="' . __('Settings') . '" id="error-settings">' .
    '<h3>' . __('Settings') . '</h3>' .
    '<form action="plugin.php" method="post">' .
    '<p><label for="enabled">' . form::checkbox('enabled', 1, $settings['enabled']) .
    __('Enable error logging') . '</label></p>' .
    '<p><label for="backtrace">' . form::checkbox('backtrace', 1, $settings['backtrace']) .
    __('Enable backtrace logging') . '</label></p>' .
    '<p><label for="silent_mode">' . form::checkbox('silent_mode', 1, $settings['silent_mode']) .
    __('Enable silent mode : standard errors will only be logged, no output') . '</label></p>' .
    (isset($_GET['annoy'])?
        ('<p class="info">' . __('If you do not want to be annoyed with warning messages, unselect the checkbox below') . '</p>'):
        ''
    ) .
    '<p><label for="annoy_user">' . form::checkbox('annoy_user', 1, $settings['annoy_user']) .
    __('Enable Annoying mode : warn user every time a new error has been detected') . '</label></p>' .
    '<p><label for="dir">' . __('Directory for logs (will be created in dotclear cache dir)') . ' : </label>' .
    form::field('dir', 20, 255, html::escapeHTML($settings['dir'])) . '</p>' .
    '<p><label for="bin_file">' . __('Binary log file name') . ' : </label>' .
    form::field('bin_file', 20, 255, html::escapeHTML($settings['bin_file'])) . '</p>' .
    '<p><label for="txt_file">' . __('Text log file name') . ' : </label>' .
    form::field('txt_file', 20, 255, html::escapeHTML($settings['txt_file'])) . '</p>' .
    '<p><input type="submit" value="' . __('Save') . ' (s)" ' . 'accesskey="s" name="save" /> ' .
    form::hidden('p', 'errorlogger') . $core->formNonce() .
    '</p></form>' .
    '</div>';

?>

</body>
</html>
