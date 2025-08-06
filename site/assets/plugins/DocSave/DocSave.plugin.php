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

// Устанавливаем часовой пояс
define('TIME_ZONE', 'Asia/Yekaterinburg');
date_default_timezone_set(TIME_ZONE);

if(!function_exists('join_paths')):
	function join_paths() {
		$parts = func_get_args();
		if (sizeof($parts) === 0) return '';
		$prefix = ($parts[0] === "/") ? "/" : '';
		$processed = array_filter(array_map(function ($part) {
			return rtrim($part, "/");
		}, $parts), function ($part) {
			return !empty($part);
		});
		return $prefix . implode("/", $processed);
	}
endif;

$e = &$modx->event;
$params = $e->params;

$cron_sendmail = join_paths(MODX_BASE_PATH, "assets", "modules", "MailSend", "cron.json");
$log_sendmail  = join_paths(MODX_BASE_PATH, "assets", "plugins", "DocSave", "cron.log.txt");

switch ($e->name) {
	case 'OnDocFormSave':
		$table = $modx->getFullTableName( 'mailsend_users' );
		$table_members = $modx->getFullTableName( 'mailsend_group_member' );
		$tableVar = $modx->getFullTableName('site_tmplvars');
		$tableVal = $modx->getFullTableName('site_tmplvar_contentvalues');
		$tableResource = $modx->getFullTableName('mailsend_resources');
		/**
		 * Выбор осуществляется строго по порядку
		 * date_send - преобразовать дату
		 * news_date - преобразовать дату
		 * groups_send - получить группы
		 * reinit_send - перезапустить отправку
		 */
		$rs = $modx->db->select('id,name', $tableVar, "name IN ('date_send','news_date','groups_send','reinit_send')");
		$current = strtotime(date("d-m-Y 00:00:00", time()));
		$next = $current + 86400 - 1;
		// Вторая часть
		$resource = $params["id"];
		$groups = "0";
		$count = 0;
		$length = 0;
		$time = 946670400;
		// Перезапуск
		$reinit = 0;
		// Преобразуем даты
		while( $row = $modx->db->getRow( $rs ) ):
			$rws = $modx->db->select('*', $tableVal, "contentid='" . $params['id'] . "' and tmplvarid='" . $row['id'] . "'");
			$rows = $modx->db->getRow($rws);
			switch ($row['name']) {
				case 'date_send':
					// Преобразуем дату к формату d-m-Y 00:00:00 и преобразуем её в int
					$time = strtotime(date("d-m-Y 00:00:00", strtotime($rows["value"])));
					$data = array(
						"value" => $time,
						"tmplvarid" => $row['id']
					);
					$modx->db->update($data, $tableVal, "id='" . $rows["id"] . "'");
					break;
				case 'news_date':
					// Просто преобразуем запись в int
					$val = strtotime($rows["value"]);
					$data = array(
						"value" => $val,
						"tmplvarid" => $row['id']
					);
					$modx->db->update($data, $tableVal, "id='" . $rows["id"] . "'");
					break;
				case 'groups_send':
					$groups = isset($rows["value"]) ? $rows["value"] : "0";
					break;
				case 'reinit_send':
					if(isset($rows["value"])):
						// Удаляем текущее значение. Т.е. устанавливаем 0
						$reinit = $rows["value"];
						$modx->db->delete($tableVal, "id='" . $rows["id"] . "'");
					endif;
					break;
				default:
					// code...
					break;
			}
		endwhile;
		$groups = explode("||", $groups);
		$rs = $modx->db->select("*", $tableResource, "resource=" . $modx->db->escape($resource));
		$rows = $modx->db->getRow($rs);
		// Выбор групп
		$slt = "SELECT users.*, COUNT(users_groups.id_group) as group_count FROM " . $table . " AS users JOIN " . $table_members . " AS users_groups ON users.id = users_groups.id_user WHERE users_groups.id_group IN ('" . implode("','", $groups) . "') AND users.unsubscribe = 0 GROUP BY users.id ORDER BY `users`.`id` ASC;";
		$result = $modx->db->query($slt);
		$length = $modx->db->getRecordCount( $result );
		// Если изменили дату на большее значение
		$reinit = $time > $current ? 1 : $reinit;
		$count = $reinit ? 0 : ($rows ? ($rows["count"] >= $length ? $length : $rows["count"]) : 0);
		$length = $length != ($rows ? $rows["length"] : 0) ? $length : ($rows ? $rows["length"] : $length);
		$fields = array(
			'resource' => $params['id'],
			'groups' => (string) implode(',', $groups),
			'status' => $reinit ? 0 : ($rows ? $rows["status"] : 0),
			'count' => $reinit ? 0 : $count,
			'length' => $length,
			'time' => $time
		);
		//file_put_contents($log_sendmail, print_r($fields, true) . PHP_EOL, FILE_APPEND);
		
		if(!$rows):
			$fields['id'] = null;
			// Новая запись
			$modx->db->insert( $fields, $tableResource);  
		else:
			$id = $rows["id"];
			// Обновление
			$modx->db->update( $fields, $tableResource, 'id = "' . $id . '"' );  
		endif;
		
}