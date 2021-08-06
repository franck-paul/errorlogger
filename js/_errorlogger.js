/*global $, dotclear */
'use strict';

dotclear.viewLogContent = function (img, line) {
  const logId = line.id.substr(1);
  const tr = document.getElementById('pe' + logId);
  if (tr.style.display == 'none') {
    $(tr).toggle();
    $(line).toggleClass('expand');
    img.src = dotclear.img_minus_src;
    img.alt = dotclear.img_minus_alt;
  } else {
    $(tr).toggle();
    $(line).toggleClass('expand');
    img.src = dotclear.img_plus_src;
    img.alt = dotclear.img_plus_alt;
  }
};

dotclear.logExpander = function (line) {
  const td = line.firstChild;

  const img = document.createElement('img');
  img.src = dotclear.img_plus_src;
  img.alt = dotclear.img_plus_alt;
  img.className = 'expand';
  $(img).css('cursor', 'pointer');
  img.line = line;
  img.onclick = function () {
    dotclear.viewLogContent(this, this.line);
  };

  td.insertBefore(img, td.firstChild);
};

$(function () {
  $('#logs-list tr.line').each(function () {
    let sib = $(this).next('tr:not(.line)');
    if (sib.length != 0) {
      sib.toggle();
      dotclear.logExpander(this);
    }
  });
  // Confirm post deletion
  $('input[name="clearfiles"]').click(function () {
    return window.confirm(dotclear.msg.confirm_delete_logs);
  });
});
