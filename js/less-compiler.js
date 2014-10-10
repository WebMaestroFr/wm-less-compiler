jQuery(document).ready(function ($) {
  'use strict';
  CodeMirror.fromTextArea(document.getElementById('less_compiler_stylesheet'), {
    viewportMargin: Infinity,
    tabSize: 2,
    indentWithTabs: true,
    mode: 'text/x-less'
  });
  var searchInput = $('#variable-search'),
    searchVariable = function (e) {
      var filter = searchInput.val().replace(/\s+/g, '-'),
        filterRow = function () {
          var varName = $('label', this).text();
          $(this).toggle(varName.search(filter) >= 0);
        };
      e.preventDefault();
      $('tr', '#less_compiler_less_variables').each(filterRow);
    };
  searchInput.keyup(searchVariable).on('search', searchVariable);
});
