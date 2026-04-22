<?php
/**
 *
 * Plugin:
 * [+] notocoloremoji, codemirror
 *
 * Button:
 * [+] notocoloremoji, codemirror
 *
 * Конфиг-параметры TinyMCE4 для сайта
 * https://www.tinymce.com/docs/configure/
 *
 * Приведенная ниже настройка конфигурации по умолчанию гарантирует, что все параметры редактора имеют резервное значение, а тип для каждого ключа известен.
 * $this->set($editorParam, $value, $type, $emptyAllowed=false)
 *
 * $editorParam = параметр для установки
 * $value = значение для установки
 * $type = строка, число, логическое значение, json (массив или строка)
 * $emptyAllowed = true, false (разрешает параметр: '' вместо возврата к значениям по умолчанию)
 * Если $editorParam пуст, а $emptyAllowed равен true, $defaultValue будет игнорироваться
 *
 * $this->modxParams содержит массив фактических настроек Modx/user-settings
 *
 */

$modx_evo = evo();

// Используемые шрифты. Шрифты указывать так, как они именуются в CSS
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
$this->set('entity_encoding', 'raw', 'string');
// Используемые плагины
$this->set('plugins', 'codemirror textcolor autolink lists layer table modxlink image media contextmenu paste visualchars nonbreaking visualblocks charmap wordcount code autoresize spellchecker notocoloremoji', 'string');
// Отключаем templates
$this->set('templates', false, 'bool');
// Первая строка тулбара
$this->set('toolbar1', 'formatselect | fontselect | forecolor | undo redo | cut copy paste pastetext | visualchars | visualblocks | code | codemirror', 'string');
// Вторая строка тулбара
$this->set('toolbar2', 'bold italic underline strikethrough subscript superscript removeformat | alignleft aligncenter alignright alignjustify | bullist numlist | blockquote', 'string');
// Третья строка тулбара
$this->set('toolbar3', 'image media | link unlink | table | charmap notocoloremoji | spellchecker', 'string');
// Четвёртая строка тулбара
$this->set('toolbar4', false, 'bool');
// Основное меню (включаем)
$this->set('menubar', 'edit insert view format tools table', 'string');
$this->set('table_header_type', 'thead', 'string');
// rel="noopener" disabled
$this->set('allow_unsafe_link_target', true, 'bool');

$this->set('image_dimensions', false, 'bool');
$this->set('image_description', false, 'bool');

// Исключить эмоджи. Это пример, если раскоментировать
$this->set('notocoloremoji_exclude', '[
		//"smiles",
		//"emotics",
		//"people",
		//"animals",
		//"places",
		//"events",
		//"objects",
		//"symbols"
]', 'json');
// Количество Emoji вряд. По умолчанию 30
$this->set('notocoloremoji_length', '29', 'string');



// Проверка орфографии
$this->set('spellchecker_languages', 'Russian=ru,English=en', 'string');
$this->set('spellchecker_language', 'ru', 'string');
$this->set('spellchecker_rpc_url', '//speller.yandex.net/services/tinyspell', 'string');

// Показать блоки и символы
$this->set('visualblocks_default_state', true, 'bool');
$this->set('visualchars_default_state', true, 'bool');

// Вставить как текст
$this->set('paste_as_text', true, 'bool');
$this->set('paste_remove_styles', true, 'bool');
$this->set('paste_remove_spans', true, 'bool');

// Конфигурируем контекстное меню
// По умолчанию, если включён плагин `contextmenu`, имеются значения - `llink openlink image inserttable | cell row column deletetable`
// Добавим `notocoloremoji`, проверку `spellchecker` и иструменты таблицы, но изменим порядок + добавим свойства таблицы
// Со значениями для таблицы `nserttable tableprops deletetable | cell row column` не шутить. Они нужны все и всегда )))
$this->set('contextmenu', 'link openlink image notocoloremoji | spellchecker | inserttable tableprops deletetable | cell row column', 'string');

// Старт и сохранение
$this->set('setup', 'function(ed) { ed.on("change", function(e) { documentDirty=true; }); }',  'object');
$this->set('save_onsavecallback', 'function () { documentDirty=false; document.getElementById("stay").value = 2; document.mutate.save.click(); }',  'object');

// Установить локаль по конфигурации локали EvolutionCMS
// Этого нет из коробки EvolutionCMS, а должно по сути.
$langCode = $modx_evo->config["locale"];
switch ($modx_evo->config["manager_language"]) {
	case 'bg':
		$langCode = 'bg_BG';
		break;
	case 'zh':
		$langCode = 'zh_CN';
		break;
	case 'he':
		$langCode = 'he_IL';
		break;
	case 'no':
		$langCode = 'nb_NO';
		break;
	case 'sv':
		$langCode = 'sv_SE';
		break;
	case 'portuguese':
		$langCode = 'pt_PT';
		break;
	case 'portuguese-br':
	case 'portuguese-br-utf8':
		$langCode = 'pt_BR';
		break;
	default:
		break;
}

$dirLang = str_replace('\\', '/', dirname(__DIR__)) . '/';

if(!is_file($dirLang . 'tinymce/langs/' . $langCode . '.js')):
	$langCode = 'en_GB';
endif;

$fileLang = $dirLang . 'tinymce/langs/' . $langCode . '.js';


$language_url = str_replace(MODX_BASE_PATH, "/", $fileLang);

$this->set('language', $langCode, 'string');

$this->set('language_load', true, 'bool');

// Забираем css файлы из настроек если они есть
// Добавляем хэшь для отключения кэша скриптов
try {
	$css_conf = trim($modx_evo->config["editor_css_path"]);
	$pattern = "/([|,;]+)/";
	$css = preg_split($pattern, $css_conf, -1, PREG_SPLIT_NO_EMPTY);
	$array_css = [];
	foreach ($css as $key => $value):
		$value = trim($value, "/");
		if(is_file(MODX_BASE_PATH . $value)):
			$hash = filemtime(MODX_BASE_PATH . $value);
			$value .= '?hash=hash' . $hash;
		endif;
		$array_css[] = "/" . $value;
	endforeach;
	if(count($array_css)):
		$files_css = json_encode($array_css, JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
		$this->set('content_css', $files_css, 'json');
	else:
		// Если нет файлов. Пытаемся подключить от плагина notocoloremoji
		$path = str_replace(MODX_BASE_PATH, "", str_replace('\\', '/', dirname(__DIR__, 1))) . '/tinymce/plugins/notocoloremoji/content.min.css';
		if(is_file(MODX_BASE_PATH . $path)):
			$hash = filemtime(MODX_BASE_PATH . $path);
			$path .= '?hash=hash' . $hash;
			$this->set('content_css', '/' . $path, 'string');
		endif;
	endif;
} catch (Exception $e) {}
