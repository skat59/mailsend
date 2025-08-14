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
		$user_groups = isset($_POST['user_groups']) ? $modx->db->escape($_POST['user_groups']) : "";
		$user_groups = explode(",", $user_groups);
		print_r($user_groups);
		if($user_id):
			// Достаём пользователя по ID
			$result = $modx->db->select("name,email,unsubscribe", $table_users, "id='" . $user_id . "'");
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
				if($user_groups):
					if($groups):
						// Проверить равенство массивов
						// echo print_r($groups, true);
					else:
						// Нет групп для обновления или внесения
						// echo "Нет групп для обновления или внесения";
					endif;
				else:
					// У пользователя нет групп
					// echo "У пользователя нет групп";
				endif;
			else:
				// Пользователя с данным id нет
				// Проверить Автоинкркмент.
				// Если
			endif;
		else:
			// Добавить пользователя
			// echo "Добавить пользователя";
		endif;
		break;
	case 'group':
		// Сохранение Группы
		$group_id = isset($_POST["group_id"]) ? $modx->db->escape((int)$_POST['group_id']) : 0;
		$group_name = isset($_POST["group_name"]) ? $modx->db->escape(filter_input(INPUT_POST, 'group_name', FILTER_SANITIZE_ENCODED)) : "";
		$group_name = trim(preg_replace('/\s+/', " ", $group_name));
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
				$modx->db->insert( $fields, $table_groups);
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
		echo json_encode($return);
		break;
	default:
		echo json_encode($return);
		break;
}
