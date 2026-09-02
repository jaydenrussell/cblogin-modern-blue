/**
 * CB Login — Modern Soft Blue password toggle
 * Scoped to .scc-modern-blue container via event delegation.
 * @version 1.3.5
 */
(function () {
  var toggles = document.querySelectorAll('.scc-modern-blue .scc-password-toggle');
  for (var i = 0; i < toggles.length; i++) {
    toggles[i].addEventListener('click', function () {
      var container = this.closest('.scc-modern-blue');
      if (!container) return;
      var pw = container.querySelector('input[type="password"], input.scc-pw-field');
      if (!pw) return;

      var svgShow = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
        + '<path d="M2 12s3-6 10-6 10 6 10 6-3 6-10 6-10-6-10-6z" stroke="currentColor" stroke-width="1.5" fill="none"/>'
        + '<circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>';
      var svgHide = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
        + '<path d="M1 1l22 22M12 7.5V10.5M12 13.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
        + '<circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>';

      if (pw.type === 'password') {
        pw.type = 'text';
        this.innerHTML = svgShow;
      } else {
        pw.type = 'password';
        this.innerHTML = svgHide;
      }
    });
  }
})();
