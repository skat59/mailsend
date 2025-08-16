<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
header("Content-type: application/json; charset=utf8");
// header("Content-type: text/plain; charset=utf8");
// Сохранение Пользователя или Группы

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
		$user_name = isset($_POST['user_name']) ? $modx->db->escape($_POST['user_name']) : "";
		$user_email = isset($_POST['user_email']) ? $modx->db->escape($_POST['user_email']) : "";
		$user_groups = isset($_POST['user_groups']) ? $modx->db->escape($_POST['user_groups']) : "";
		// Запомнить, что данное значение задано как негативное
		$user_unsubscribe = isset($_POST['user_unsubscribe']) ? intval($modx->db->escape($_POST['user_unsubscribe'])) : 1;
		$user_unsubscribe = $user_unsubscribe ? 1 : 0;
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
				// $sql = "SELECT * "
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
							// Вытащить данные для обновления результата
							$sql = "SELECT users_table.id, users_table.name, users_table.email, GROUP_CONCAT(groups_table.id ORDER BY groups_table.id SEPARATOR \", \r\n\") AS groups_id, GROUP_CONCAT(groups_table.name ORDER BY groups_table.id SEPARATOR \", \r\n\") AS groups_name, users_table.unsubscribe FROM " . $table_users . " users_table INNER JOIN " . $table_members . " group_memmer_table ON group_memmer_table.id_user = users_table.id INNER JOIN " . $table_groups . " groups_table ON groups_table.id = group_memmer_table.id_group AND users_table.id='" . $user_id . "' GROUP BY users_table.id";
							$result_user = $modx->db->query($sql);
							if($modx->db->getRecordCount($result_user)):
								$row_user = $modx->db->getRow($result_user);
								$return = array(
									"request" => true,
									"message" => "Данные пользователя обновлены",
									"id" => $row_user['id'],
									"name" => $row_user['name'],
									"email" => $row_user['email'],
									"groups_id" => $row_user['groups_id'],
									"groups_name" => $row_user['groups_name'],
									"unsubscribe" => $row_user['unsubscribe']
								);
							else:
								$return = array(
									"request" => false,
									"message" => "Что за хрень?"
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
			$groups = [];
			$result_groups = $modx->db->select("id", $table_groups);
			if($modx->db->getRecordCount( $result_groups )):
				while ($row_group = $modx->db->getRow( $result_groups )):
					$groups[] = $row_group["id"];
				endwhile;
			endif;
			if($user_groups):
				if($groups):
					// Проверить равенство массивов
					// echo print_r($groups, true);
				else:
					// Нет групп для обновления или внесения
					// echo "Нет групп для обновления или внесения";
				endif;
			else:
				//
			endif;
			// У пользователя нет групп
			$return = array(
				"request" => false,
				"message" => "Добавление не реализовано"
			);
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
							"message"   => "Запись обновлена",
							"name"      => $group_name,
							"id"      	=> $group_id
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
					"message"   => "Запись добавлена",
					"name"      => $group_name,
					"id"		=> $autoincrement
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
