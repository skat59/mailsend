<?php
/*
 * All available config-params of TinyMCE4
 * https://www.tinymce.com/docs/configure/
 *
 * Belows default configuration setup assures all editor-params have a fallback-value, and type per key is known
 * $this->set( $editorParam, $value, $type, $emptyAllowed=false )
 *
 * $editorParam     = param to set
 * $value           = value to set
 * $type            = string, number, bool, json (array or string)
 * $emptyAllowed    = true, false (allows param:'' instead of falling back to default)
 * If $editorParam is empty and $emptyAllowed is true, $defaultValue will be ignored
 *
 * $this->modxParams holds an array of actual Modx- / user-settings
 *
 * */

if( !empty( $this->modxParams['custom_plugins'])) {
	$this->set('plugins', $this->modxParams['custom_plugins'], 'string' );
};
// Используемые шрифты
$this->set('font_formats', 'Arial=Arial;Helvetica=Helvetica;Tahoma=Tahoma;Times New Roman=Times New Roman', 'string');
$this->set('formats', '{
	alignleft: {
		selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img,audio,video",
		styles: {
			textAlign: "left"
		}
	},
	aligncenter: {
		selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img,audio,video",
		styles: {
			textAlign: "center"
		}
	},
	alignright: {
		selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img,audio,video",
		styles: {
			textAlign: "right"
		}
	},
	alignfull: {
		selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img,audio,video",
		styles: {
			textAlign: "alignjustify"
		}
	}
}', 'object');
// Используемые плагины
$this->set('plugins', 'textcolor autolink lists layer table modxlink image emoticons media contextmenu paste visualchars nonbreaking visualblocks charmap wordcount code autoresize', 'string');

// Первая строка тулбара
$this->set('toolbar1', 'formatselect | fontselect | forecolor | undo redo | cut copy paste pastetext | visualchars | visualblocks | code', 'string');

// Вторая строка тулбара
$this->set('toolbar2', 'bold italic underline strikethrough subscript superscript removeformat | alignleft aligncenter alignright alignjustify | bullist numlist | blockquote', 'string');

// Третья строка тулбара
$this->set('toolbar3', 'image media | link unlink | table | charmap emoticons', 'string');

// Четвёртая строка тулбара (отключаем)
$this->set('toolbar4', false, 'bool');


$this->set('object_resizing', false, 'bool');
$this->set('table_resize_bars', false, 'bool');

// Основное меню (отключаем)
$this->set('menubar', false, 'bool');
// ???
$this->set('table_header_type', 'thead', 'string');
$this->set('visualblocks_default_state', true, 'bool');
// rel="noopener" disabled
$this->set('allow_unsafe_link_target', true, 'bool');

$this->set('image_dimensions', false, 'bool');
$this->set('image_description', false, 'bool');

// Старт и сохранение
$this->set('setup', 'function(ed) { ed.on("change", function(e) { documentDirty=true; }); }',  'object');
$this->set('save_onsavecallback', 'function () { documentDirty=false; document.getElementById("stay").value = 2; document.mutate.save.click(); }',  'object');

try {
	$hash = "1.0.0";
	$css = $this->themeConfig["content_css"]["value"][0];
	if(is_file(MODX_BASE_PATH . $css)){
		$hash = filemtime(MODX_BASE_PATH . $css);
		$css .= '?hash=hash' . $hash;
		$this->themeConfig["content_css"]["value"][0] = $css;
	}
} catch (Exception $e) {}

