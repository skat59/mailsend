<?php
header("Content-type: text/javascript; charset=utf8");

$dir = str_replace('\\','/', dirname((dirname(dirname(dirname(__FILE__)))))) . '/';

$module_path = str_replace('\\','/', dirname(__FILE__)) . '/';

$lang_path = $module_path . 'lang/';
// Переменные для работы API Modx EVO
define('MODX_API_MODE',      true);
define('MODX_BASE_PATH',     $dir);
define('MODX_SITE_URL',      'https://mailsend.skat59.ru/');
define('MODX_BASE_URL',      'https://mailsend.skat59.ru/');

include_once($dir . "index.php");

// Получаем все настройки сайта
$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

$js_path = str_replace(MODX_BASE_PATH, "/", $module_path)  . 'js/';

$path = $js_path . 'lang/';

$_MailSendLang = [];
$_lang = [];

$manager_language = $modx->config['manager_language'];

include $lang_path . 'english.php';
if (is_file($lang_path . $manager_language . '.php')) {
	include $lang_path. $manager_language . '.php';
}
$_lang = array_merge($_lang, $_MailSendLang);

$LANG = preg_replace('/(^[A-z0-9_]+).*$/', '$1', $manager_language);

$LANG_FILE = is_file($module_path . 'js/lang/' . $LANG . ".json") ? $path . $LANG . ".json" : $path . "english.json";
echo 'const MOD_JS_PATH = "' . $js_path . '";' . PHP_EOL;
echo 'const LANG = "' . $LANG . '";' . PHP_EOL;
echo 'const LANG_FILE = "' . $LANG_FILE . '";' . PHP_EOL;
echo "const LANG_SENDMAIL = " . json_encode( $_MailSendLang, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_FORCE_OBJECT ) . ";";
