/*global dotclear */
'use strict';

dotclear.ready(() => {
  Object.assign(dotclear.msg, dotclear.getData('errorlogger'));

  // Hide line with backtrace
  for (const trace of document.querySelectorAll('#logs-list tbody tr:not(.line)')) {
    trace.style.display = 'none';
  }

  const viewLogContent = (line) => {
    const logId = line.id.substring(1);
    const target = document.getElementById(`pe${logId}`);
    if (target.style.display === 'none') {
      target.style.display = '';
      line.classList.add('expand');
      return;
    }
    target.style.display = 'none';
    line.classList.remove('expand');
  };

  dotclear.expandContent({
    line: document.querySelector('#logs-list thead tr:not(.line)'),
    lines: document.querySelectorAll('#logs-list tr.line'),
    callback: viewLogContent,
  });

  // Confirm logs deletion
  document.querySelector('input[name="clearfiles"]')?.addEventListener('click', (event) => {
    if (window.confirm(dotclear.msg.confirm_delete_logs)) return true;
    event.preventDefault();
    return false;
  });
});
