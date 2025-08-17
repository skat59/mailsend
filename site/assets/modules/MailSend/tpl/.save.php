<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
header("Content-type: application/json; charset=utf8");
// header("Content-type: text/plain; charset=utf8");

function trimToLower($n = "") {
	return trim( mb_strtolower($n) );
}

function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
}

function gen_token(string $eml = "") {
	$secret_phrase = $modx->config["secret_phrase"] || "ProjectSoft";
	$token = hash_hmac('sha256', $eml, $secret_phrase);
	return $token;
}

// Нужно помнить, что email не изменяется. Он один и уникальный.
$return = array(
	"request" => false,
	"message" => "Ничего не сделано"
);

$url_a = (int) $_POST['a'];
$url_id = (int) $_POST['id'];
$start_link = $modx->config["site_manager_url"] . 'index.php?a=' . $url_a . '&id=' . $url_id;

// Пока ставим в GET. Потом заменить на POST
$postType = isset($_POST['type']) ? filter_input(INPUT_POST, 'type', FILTER_SANITIZE_ENCODED) : "";

switch ($postType) {
	case 'user':
		// Сохранение Пользователя
		$user_id = isset($_POST['user_id']) ? intval($modx->db->escape($_POST['user_id'])) : 0;
		$user_name = isset($_POST['user_name']) ? trim($modx->db->escape($_POST['user_name'])) : "";
		$user_email = isset($_POST['user_email']) ? trimToLower($modx->db->escape($_POST['user_email'])) : "";
		$user_groups = isset($_POST['user_groups']) ? $modx->db->escape($_POST['user_groups']) : "";
		// Запомнить, что данное значение задано как негативное
		$user_unsubscribe = isset($_POST['user_unsubscribe']) ? intval($modx->db->escape($_POST['user_unsubscribe'])) : 1;
		$user_unsubscribe = $user_unsubscribe ? 1 : 0;
		//
		$user_email = mb_convert_case(trim($user_email, "\r\n\t ;,.'\"-_/|!"), MB_CASE_LOWER, "UTF-8");
		if(!isValidEmail($user_email) || mb_strlen($user_name) < 3):
			// Email невалидный или короткое имя
			// Выходим
			$return = array(
				"request" => false,
				"message" => "Проверьте введённые данные"
			);
			break;
		endif;
		if($user_id):
			// Достаём всех по $user_email кроме самого пользователя $user_id
			// Если $user_email существует -> выходим с сообщением, что данный $user_email уже используется
			$result_check = $modx->db->select("*", $table_users, "id!='" . $user_id . "' AND email='" . $user_email . "'");
			if($modx->db->getRecordCount( $result_check)):
				// Email уже существует. Выходим
				$row_check = $modx->db->getRow( $result_check );
				$return = array(
					"request" => false,
					"message" => "Данный Email уже используется.\r\nID:        " . $row_check["id"] . "\r\nNAME: " . $row_check["name"]
				);
				break;
			endif;
			// Достаём пользователя по ID
			$result = $modx->db->select("*", $table_users, "id='" . $user_id . "'");
			if($modx->db->getRecordCount( $result )):
				// Получили пользователя
				$row = $modx->db->getRow( $result );
				// Выбрать группы и проверить соответствие $user_groups
				$groups = [];
				$result_groups = $modx->db->select("id", $table_groups);
				if($modx->db->getRecordCount( $result_groups )):
					while ($row_group = $modx->db->getRow( $result_groups )):
						$groups[] = $row_group["id"];
					endwhile;
				endif;
				// Достать все рассыки пользователя
				if($user_groups):
					if($groups):
						// Оставляем только существующие
						$array = array_intersect($user_groups, $groups);
						if(count($array)):
							// Выбрать существующие подписки пользователя
							$result_members = $modx->db->select("id, id_group", $table_members, "id_user='" . $user_id . "'");
							// Существующие
							$members = array();
							if($modx->db->getRecordCount($result_members)):
								while ($row_members = $modx->db->getRow( $result_members )):
									$members[] = $row_members["id_group"];
								endwhile;
							endif;
							// Удалить
							$remove_array = array_diff($members, $array);
							foreach ($remove_array as $key => $value):
								$modx->db->delete($table_members, 'id_user="' . $user_id . '" AND id_group="' . $value . '"');
							endforeach;
							// Добавить
							$add_array = array_diff($array, $members);
							foreach($add_array as $key => $value):
								$modx->db->insert(array(
									"id_user" => $user_id,
									"id_group" => $value
								), $table_members);
							endforeach;
							// Обновить данные пользователя
							$fields = array(
								"name" => $user_name,
								"email" => $user_email,
								"unsubscribe" => $user_unsubscribe
							);
							$modx->db->update( $fields, $table_users, 'id = "' . $user_id . '"' );
							$return = array(
								"request" => true,
								"message" => "Данные пользователя обновлены"
							);
						else:
							// У пользователя нет групп
							$return = array(
								"request" => false,
								"message" => "У пользователя нет групп"
							);
						endif;
					else:
						// Нет групп для обновления или внесения
						$return = array(
							"request" => false,
							"message" => "Нет групп"
						);
					endif;
				else:
					// У пользователя нет групп
					$return = array(
						"request" => false,
						"message" => "У пользователя нет групп"
					);
				endif;
			else:
				// Пользователя с данным id нет
				$return = array(
					"request" => false,
					"message" => "Пользователя с данным ID нет"
				);
			endif;
		else:
			// Добавить пользователя
			// Достаём всех по $user_email
			// Если $user_email существует -> выходим с сообщением, что данный $user_email уже используется
			$result_check = $modx->db->select("*", $table_users, "email='" . $user_email . "'");
			if($modx->db->getRecordCount( $result_check)):
				// Email уже существует. Выходим
				$row_check = $modx->db->getRow( $result_check );
				$return = array(
					"request" => false,
					"message" => "Данный Email уже используется.\r\nID:        " . $row_check["id"] . "\r\nNAME: " . $row_check["name"]
				);
				break;
			endif;
			// Получить все группы рассылок
			$groups = [];
			$result_groups = $modx->db->select("id", $table_groups);
			if($modx->db->getRecordCount( $result_groups )):
				while ($row_group = $modx->db->getRow( $result_groups )):
					$groups[] = $row_group["id"];
				endwhile;
			endif;
			if($user_groups):
				if($groups):
					// Оставляем только существующие
					$array = array_intersect($user_groups, $groups);
					if(count($array)):
						// Вставить данные пользователя
						$fields = array(
							"name" => $user_name,
							"email" => $user_email,
							"unsubscribe" => $user_unsubscribe,
							"token" => gen_token($user_email)
						);
						$id = $modx->db->insert( $fields, $table_users );
						if($id):
							// Добавить в members
							foreach($array as $key => $value):
								$modx->db->insert(array(
									"id_user" => $id,
									"id_group" => $value
								), $table_members);
							endforeach;
							// Данные вставлены
							$return = array(
								"request" => true,
								"message" => "Организация добавлена.\n\nNAME:  " . $user_name . "\nEMAIL: " . $user_email
							);
						endif;
					else:
						// Нет групп для обновления или внесения
						$return = array(
							"request" => false,
							"message" => "Нет групп для обновления или внесения"
						);
					endif;
				else:
					// Нет групп для обновления или внесения
					$return = array(
						"request" => false,
						"message" => "Нет групп для обновления или внесения"
					);
				endif;
			else:
				// У пользователя нет групп
				$return = array(
					"request" => false,
					"message" => "Нет групп рассылок"
				);
			endif;
		endif;
		break;
	case 'group':
		// Сохранение Группы
		$group_id = isset($_POST["group_id"]) ? $modx->db->escape((int)$_POST['group_id']) : 0;
		$group_name = isset($_POST["group_name"]) ? $_POST['group_name'] : "";
		$group_name = $modx->db->escape(trim(preg_replace('/\s+/', " ", $group_name)));
		if($group_name):
			// Имя группы есть
			if($group_id):
				// ID группы есть
				$result = $modx->db->select("*", $table_groups, "id='" . $group_id . "'");
				if($modx->db->getRecordCount( $result )):
					// Запись есть
					$fields = array(
						"name" => $group_name
					);
					$update = $modx->db->update( $fields, $table_groups, 'id="' . $group_id . '"' );
					if($update):
						// Запись обновлена
						$return = array(
							"request"   => true,
							"message"   => "Запись обновлена"
						);
					else:
						// Запись не обновлена
						$return = array(
							"request"   => false,
							"message"   => "Запись не обновлена"
						);
					endif;
				else:
					// Записи нет
					$return = array(
						"request"   => false,
						"message"   => "Нет записи"
					);
				endif;
			else:
				// ID нет. Новая запись
				$fields = array(
					"id" => NULL,
					"name" => $group_name
				);
				$autoincrement = $modx->db->insert( $fields, $table_groups);
				$return = array(
					"request"   => true,
					"message"   => "Запись добавлена"
				);
			endif;
		else:
			// Не задано имя группы
			$return = array(
				"request"   => false,
				"message"   => "Не задано имя группы"
			);
		endif;
		break;
	default:
		//
		break;
}
echo json_encode($return);
