<?php
/**
 * DocSave
 *
 * Пересохранить даты в метку системного времени Unix
 *
 * @category     plugin
 * @version      1.0.0
 * @package      evo
 * @internal     @events OnDocFormSave
 * @internal     @modx_category НАСТРОЙКИ ОТПРАВКИ
 * @internal     @installset base
 * @internal     @disabled 0
 * @license      https://github.com/skat59/mailsend/LICENSE MIT License (MIT)
 * @reportissues https://github.com/skat59/mailsend/issues
 * @author       Чернышёв Андрей aka ProjectSoft
 * @lastupdate   28-01-2024
 */
if (!defined('MODX_BASE_PATH')) {
	http_response_code(403);
	die('For'); 
}

$e = &$modx->event;
$params = $e->params;

switch ($e->name) {
	case 'OnDocFormSave':
		$tableVar = $modx->getFullTableName('site_tmplvars');
		$tableVal = $modx->getFullTableName('site_tmplvar_contentvalues');
		$rs = $modx->db->select('id,name', $tableVar, "name IN ('date_send','news_date')");
		while( $row = $modx->db->getRow( $rs ) ):
			$rws = $modx->db->select('*', $tableVal, "contentid='" . $params['id'] . "' and tmplvarid='" . $row['id'] . "'");
			$rows = $modx->db->getRow($rws);
			$val = strtotime($rows["value"]);
			$data = array(
				"value" => $val,
				"tmplvarid" => $row['id']
			);
			$modx->db->update($data, $tableVal, "id='" . $rows["id"] . "'");
		endwhile;
		break;
}