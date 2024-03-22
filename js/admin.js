/*global $, dotclear */
'use strict';

dotclear.viewLogContent = (img, line) => {
  const logId = line.id.substring(1);
  const tr = document.getElementById(`pe${logId}`);
  if (tr.style.display == 'none') {
    $(tr).toggle();
    $(line).toggleClass('expand');
    img.src = dotclear.img_minus_src;
    img.alt = dotclear.img_minus_alt;
    return;
  }
  $(tr).toggle();
  $(line).toggleClass('expand');
  img.src = dotclear.img_plus_src;
  img.alt = dotclear.img_plus_alt;
};

dotclear.logExpander = (line) => {
  const td = line.firstChild;

  const img = document.createElement('img');
  img.src = dotclear.img_plus_src;
  img.alt = dotclear.img_plus_alt;
  img.className = 'expand';
  $(img).css('cursor', 'pointer');
  $(img).css('width', '2em');
  img.line = line;
  img.onclick = function () {
    dotclear.viewLogContent(this, this.line);
  };

  td.insertBefore(img, td.firstChild);
};

$(() => {
  Object.assign(dotclear.msg, dotclear.getData('errorlogger'));

  $('#logs-list tr.line').each(function () {
    const sib = $(this).next('tr:not(.line)');
    if (sib.length != 0) {
      sib.toggle();
      dotclear.logExpander(this);
    }
  });

  // Confirm post deletion
  $('input[name="clearfiles"]').on('click', () => window.confirm(dotclear.msg.confirm_delete_logs));
});
