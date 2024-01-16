<?php
define('MODX_API_MODE', true);
define('MODX_BASE_PATH', dirname(__FILE__) . "/");
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');
include_once(dirname(__FILE__) . "/index.php");
$modx->db->connect();
if (empty($modx->config)) {
    $modx->getSettings();
}
//дальше можно делать, что угодно, например сниппет
$out = $modx->runSnippet('SendMail', array());
echo $out;