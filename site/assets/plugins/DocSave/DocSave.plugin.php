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
		$tableVar = $modx->getFullTableName('site_tmplvars');
		$tableVal = $modx->getFullTableName('site_tmplvar_contentvalues');
		$rs = $modx->db->select('id,name', $tableVar, "name IN ('date_send','news_date','reinit_send')");
		$current = strtotime(date("d-m-Y 00:00:00", time()));
		$next = $current + 86400 - 1;
		while( $row = $modx->db->getRow( $rs ) ):
			$rws = $modx->db->select('*', $tableVal, "contentid='" . $params['id'] . "' and tmplvarid='" . $row['id'] . "'");
			$rows = $modx->db->getRow($rws);
			switch ($row['name']) {
				case 'date_send':
					$data = array(
						"value" => $current,
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
					break;
					$modx->db->update($data, $tableVal, "id='" . $rows["id"] . "'");
				case 'reinit_send':
					file_put_contents($log_sendmail, print_r($row,  true), FILE_APPEND);
					//sleep(2);
					file_put_contents($log_sendmail, print_r($rows, true), FILE_APPEND);
					if(isset($rows["value"])):
						// Получаем дату
						$DS = $modx->db->select('id,name', $tableVar, "name IN ('date_send')");
						$rowDS = $modx->db->getRow( $DS );
						$DS = $modx->db->select('*', $tableVal, "contentid='" . $params['id'] . "' and tmplvarid='" . $rowDS['id'] . "'");
						$rowDS = $modx->db->getRow( $DS );
						file_put_contents($log_sendmail, print_r($rowDS,  true), FILE_APPEND);
						if($rowDS['value'] >= $current && $rowDS['value'] <= $next):
							// Задание на сегодня
							// Проверим, есть ли задание на этот день для данного ресурса
							if(is_file($cron_sendmail)):
								$txt = file_get_contents($cron_sendmail);
								$txt = (array) json_decode($txt);
								if(!json_last_error()):
									$id = isset($txt[$current]->id) ? $txt[$current]->id : 0;
									if($id == $params['id']):
										// Задание есть
										// Удаляем файл
										@unlink($cron_sendmail);
									endif;
								endif;
							endif;
						endif;
						// Удаляем текущее значение. Т.е. устанавливаем 0
						$modx->db->delete($tableVal, "id='" . $rows["id"] . "'");
					endif;
					break;
				default:
					// code...
					break;
			}
		endwhile;
		break;
}