<?php
define('MODX_API_MODE', true);
include_once(dirname(__FILE__) . "/index.php");
$modx->db->connect();
if (empty($modx->config)) {
    $modx->getSettings();
}
//дальше можно делать, что угодно, например сниппет
$out = $modx->runSnippet('SendMail', array());
echo $out;