// Self-hosted TinyMCE (GPL) — no account/API-key, everything bundled by Vite from node_modules.
import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.min.css';

// Plugins (full set)
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/media';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/emoticons/js/emojis';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/help';
import 'tinymce/plugins/help/js/i18n/keynav/en';
import 'tinymce/plugins/code';

// In-editor content CSS — imported as raw text via Vite and passed to content_style.
import contentUiCss from 'tinymce/skins/ui/oxide/content.min.css?raw';
import contentDefaultCss from 'tinymce/skins/content/default/content.min.css?raw';

const CONTENT_STYLE = `${contentUiCss}\n${contentDefaultCss}\n`
    + 'body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:14px;line-height:1.6;color:#1f2937}'
    + 'img{max-width:100%;height:auto}table{border-collapse:collapse}table td,table th{border:1px solid #d1d5db;padding:6px 10px}';

/**
 * Binds TinyMCE to a single textarea (or DOM element).
 * Content is written back to the textarea on every change (for form submit).
 */
export function initTinyEditor(el) {
    if (! el || el.dataset.tinyInited) {
        return;
    }
    el.dataset.tinyInited = '1';

    tinymce.init({
        target: el,
        license_key: 'gpl',
        skin: false,          // skin CSS imported above
        content_css: false,   // content CSS provided via content_style
        content_style: CONTENT_STYLE,
        plugins: 'advlist autolink lists link image table media charmap anchor searchreplace visualblocks insertdatetime wordcount emoticons fullscreen preview nonbreaking help code',
        toolbar: 'undo redo | blocks fontsizeinput | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | blockquote hr charmap emoticons | removeformat | searchreplace visualblocks | code preview fullscreen | help',
        toolbar_mode: 'wrap',
        menubar: 'edit insert view format table tools',
        branding: false,
        promotion: false,
        statusbar: true,
        height: 520,          // fixed height (no autoresize) — fullscreen works correctly
        language: 'en',
        paste_data_images: true,       // converts pasted images to base64
        image_caption: true,
        image_advtab: true,
        table_default_attributes: { border: '1' },
        setup(editor) {
            // Sync to textarea on every change (so HTML is sent on form submit)
            editor.on('change input undo redo keyup SetContent', () => editor.save());
        },
    });
}

window.initTinyEditor = initTinyEditor;
