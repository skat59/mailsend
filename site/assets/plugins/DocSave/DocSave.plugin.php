<?php
/**
 * DocSave
 *
 * Пересохранить даты в метку системного времени Unix
 *
 * @category     plugin
 * @version      1.0.0
 * @package      evo
 * @internal     @events OnDocFormSave,OnDocPublished,OnDocUnPublished,OnDocFormDelete,OnDocFormUnDelete,OnEmptyTrash
 * @internal     @modx_category НАСТРОЙКИ ОТПРАВКИ
 * @internal     @installset base
 * @internal     @disabled 0
 * @license      https://github.com/skat59/mailsend/LICENSE MIT License (MIT)
 * @reportissues https://github.com/skat59/mailsend/issues
 * @author       Чернышёв Андрей aka ProjectSoft
 * @lastupdate   12-08-2025
 */

/**
 * event OnDocFormSave
 * Сохранение ресурса
 * Изменение записи о ресурсе отправки рассылки после сохранения ресурса отправки
 *
 * event OnDocPublished
 * event OnDocFormUnDelete
 * Публикация и отмена удаления
 * Добавляется запись о ресурсе отправки рассылки
 *
 * event OnDocUnPublished
 * event OnDocFormDelete
 * Отмена публикации, пометка на удаление
 * Удаляется запись о ресурсе отправки рассылки
 *
 * event OnEmptyTrash
 * Очистка корзины от удалённых ресурсов
 * Удаляются записи о ресурсах отправки рассылки
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
		/**
		 * Выбор осуществляется строго по порядку
		 * date_send - преобразовать дату
		 * news_date - преобразовать дату
		 * groups_send - получить группы
		 * reinit_send - перезапустить отправку
		 */
		$current = strtotime(date("d-m-Y 00:00:00", time()));
		$next = $current + 86400 - 1;
		$rowDoc = $modx->db->getRow($modx->db->select('id,published,template', $modx->getFullTableName( 'site_content' ), "id=" . $params["id"]));
		// Вторая часть
		$resource = $params["id"];
		$groups = "0";
		$count = 0;
		$length = 0;
		$time = 946670400;
		// Перезапуск
		$reinit = 0;
		if($rowDoc["template"] == 6):
			$rs = $modx->db->select('id,name', $modx->getFullTableName('site_tmplvars'), "name IN ('date_send','news_date','groups_send','reinit_send')");
			// Преобразуем даты
			while( $row = $modx->db->getRow( $rs ) ):
				$rws = $modx->db->select('*', $modx->getFullTableName('site_tmplvar_contentvalues'), "contentid='" . $resource . "' and tmplvarid='" . $row['id'] . "'");
				$rows = $modx->db->getRow($rws);
				switch ($row['name']) {
					case 'date_send':
						// Преобразуем дату к формату d-m-Y 00:00:00 и преобразуем её в int
						$time = strtotime(date("d-m-Y 00:00:00", strtotime($rows["value"])));
						$data = array(
							"value" => $time,
							"tmplvarid" => $row['id']
						);
						$modx->db->update($data, $modx->getFullTableName('site_tmplvar_contentvalues'), "id='" . $rows["id"] . "'");
						break;
					case 'news_date':
						// Просто преобразуем запись в int
						$val = strtotime($rows["value"]);
						$data = array(
							"value" => $val,
							"tmplvarid" => $row['id']
						);
						$modx->db->update($data, $modx->getFullTableName('site_tmplvar_contentvalues'), "id='" . $rows["id"] . "'");
						break;
					case 'groups_send':
						$groups = isset($rows["value"]) ? $rows["value"] : "0";
						$groups = explode("||", $groups);
						sort($groups);
						$groups = implode("||", $groups);
						$data = array(
							"value" => $groups,
							"tmplvarid" => $row['id']
						);
						$modx->db->update($data, $modx->getFullTableName('site_tmplvar_contentvalues'), "id='" . $rows["id"] . "'");
						break;
					case 'reinit_send':
						if(isset($rows["value"])):
							// Удаляем текущее значение. Т.е. устанавливаем 0
							$reinit = $rows["value"];
							$modx->db->delete($modx->getFullTableName('site_tmplvar_contentvalues'), "id='" . $rows["id"] . "'");
						endif;
						if($rowDoc["published"] == 0):
							// Удаляем и выходим
							$modx->db->delete($modx->getFullTableName('mailsend_resources'), "resource = $resource");
							break;
						endif;
						// Если не вышли
						// Далее обновляем
						$groups = explode("||", $groups);
						$rs = $modx->db->select("*", $modx->getFullTableName('mailsend_resources'), "resource=" . $modx->db->escape($resource));
						$rows = $modx->db->getRow($rs);
						// Выбор групп
						$slt = "SELECT users.*, COUNT(users_groups.id_group) as group_count FROM " . $modx->getFullTableName( 'mailsend_users' ) . " AS users JOIN " . $modx->getFullTableName( 'mailsend_group_member' ) . " AS users_groups ON users.id = users_groups.id_user WHERE users_groups.id_group IN ('" . implode("','", $groups) . "') AND users.unsubscribe = 0 GROUP BY users.id ORDER BY `users`.`id` ASC;";
						$result = $modx->db->query($slt);
						$length = $modx->db->getRecordCount( $result );
						// Если изменили дату на большее значение
						$reinit = $time > $current ? 1 : $reinit;
						$count = $reinit ? 0 : ($rows ? ($rows["count"] >= $length ? $length : $rows["count"]) : 0);
						$length = $length != ($rows ? $rows["length"] : 0) ? $length : ($rows ? $rows["length"] : $length);
						$fields = array(
							'resource' => $resource,
							'groups' => (string) implode(',', $groups),
							'status' => $reinit ? 0 : ($rows ? $rows["status"] : 0),
							'count' => $reinit ? 0 : $count,
							'length' => $length,
							'time' => $time
						);
						if(!$rows):
							$fields['id'] = null;
							// Новая запись
							$modx->db->insert( $fields, $modx->getFullTableName('mailsend_resources'));
						else:
							$id = $rows["id"];
							// Обновление
							$modx->db->update( $fields, $modx->getFullTableName('mailsend_resources'), 'id = "' . $id . '"' );
						endif;
						break;
					default:
						// code...
						break;
				}
			endwhile;
		endif;
		break;
	case 'OnDocPublished':
	case 'OnDocFormUnDelete':
		// Добавляем или обновляем
		$current = strtotime(date("d-m-Y 00:00:00", time()));
		$resource = isset($params['docid']) ? $params['docid'] : $params['id'];
		// $rowDoc = Ресурс
		$rowDoc = $modx->db->getRow($modx->db->select('id,published,template', $modx->getFullTableName( 'site_content' ), "id=" . $resource));
		if($rowDoc["template"] == 6):
			$rs = $modx->db->select('id,name', $modx->getFullTableName('site_tmplvars'), "name IN ('date_send','groups_send')");
			while( $row = $modx->db->getRow( $rs ) ):
				// Время, Группы
				$rws = $modx->db->select('*', $modx->getFullTableName('site_tmplvar_contentvalues'), "contentid='" . $resource . "' and tmplvarid='" . $row['id'] . "'");
				$rows = $modx->db->getRow($rws);
				switch ($row['name']) {
					case 'date_send':
						$current = $rows["value"];
						break;
					case 'groups_send':
						$groups = isset($rows["value"]) ? $rows["value"] : "0";
						break;
				}
			endwhile;
			// Добавляем
			// Если было снято с публикации, то статус меняется на 0
			// Выбор групп
			$groups = explode("||", $groups);
			$sql = "SELECT users.*, COUNT(users_groups.id_group) as group_count FROM " . $modx->getFullTableName( 'mailsend_users' ) . " AS users JOIN " . $modx->getFullTableName( 'mailsend_group_member' ) . " AS users_groups ON users.id = users_groups.id_user WHERE users_groups.id_group IN ('" . implode("','", $groups) . "') AND users.unsubscribe = 0 GROUP BY users.id ORDER BY `users`.`id` ASC;";
			$result = $modx->db->query($sql);
			$length = $modx->db->getRecordCount( $result );
			$fields = array(
				'resource' => $resource,
				'groups' => (string) implode(',', $groups),
				'status' => '0',
				'count' => '0',
				'length' => $length,
				'time' => $current
			);
			$rs = $modx->db->select("*", $modx->getFullTableName('mailsend_resources'), "resource=" . $resource);
			$rows = $modx->db->getRow($rs);
			if(!$rows):
				$fields['id'] = null;
				// Новая запись
				$modx->db->insert( $fields, $modx->getFullTableName('mailsend_resources'));
			else:
				$id = $rows["id"];
				// Обновление
				$modx->db->update( $fields, $modx->getFullTableName('mailsend_resources'), 'id = "' . $id . '"' );
			endif;
		endif;
		break;
	case 'OnDocUnPublished':
	case 'OnDocFormDelete':
		$resource = isset($params['docid']) ? $params['docid'] : $params['id'];
		// Тут ничего не важно. Если ресурс существует - просто удаляем.
		$modx->db->delete($modx->getFullTableName('mailsend_resources'), "resource = $resource");
		break;
	case 'OnEmptyTrash':
		// Удаляем
		$arr = array();
		$ids = $params["ids"];
		foreach ($ids as $key => $value) {
			$arr[] = $value;
		}
		if(count($arr)):
			$modx->db->delete($modx->getFullTableName('mailsend_resources'), "resource IN ('" . implode("','", $arr) . "')");
		endif;
		break;
}
