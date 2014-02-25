jQuery(document).ready(function () {
    'use strict';
    CodeMirror.fromTextArea(document.getElementById('wm_less_compiler'), {
        viewportMargin: Infinity,
        indentWithTabs: true
    });
});