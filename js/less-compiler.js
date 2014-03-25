jQuery(document).ready(function () {
    'use strict';
    CodeMirror.fromTextArea(document.getElementById('less_compiler'), {
        viewportMargin: Infinity,
        tabSize: 2
    });
});
