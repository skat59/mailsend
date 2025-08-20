<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}

use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

$url_a = $_GET['a'];
$url_id = $_GET['id'];
$start_link = $modx->config["site_manager_url"] . 'index.php?a=' . $url_a . '&id=' . $url_id;

$postType = isset($_POST['type']) ? filter_input(INPUT_POST, 'type', FILTER_SANITIZE_ENCODED) : "";

$upload_dir = MODX_MAILSEND_BASE_PATH . 'upload/';
$dirPerms  = intval($modx->config['new_folder_permissions'], 8);
$filePerms = intval($modx->config['new_file_permissions'], 8);

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

switch ($postType) {
	case 'user':
		header("Content-type: application/json; charset=utf8");
		if(!is_dir(MODX_MAILSEND_BASE_PATH . 'upload/')):
			@mkdir(MODX_MAILSEND_BASE_PATH . 'upload/', $dirPerms, TRUE);
		endif;
		$input_name = 'file_import';
		$allow = array('xlsx');
		$string = array();
		// Группа рассылки
		$user_group = intval(isset($_POST['user_groups']) ? filter_input(INPUT_POST, 'user_groups', FILTER_SANITIZE_ENCODED) : "0");
		// Проверяем группу рассылки
		$groups = array();
		$result_groups = $modx->db->select("*", $table_groups);
		if($modx->db->getRecordCount( $result_groups )):
			while ($row_groups = $modx->db->getRow( $result_groups )):
				$groups[] = intval($row_groups['id']);
			endwhile;
			if(!in_array($user_group, $groups)):
				// Выходим и не даём выполнение скрипта далее
				$return = array(
					"request"        => false,
					"message"         => $_lang['mailsend.user_save_not_groups_all']
				);
				echo json_encode($return);
				break;
			endif;
		else:
			// Выходим и не даём выполнение скрипта далее
			$return = array(
				"request"        => false,
				"message"         => $_lang['mailsend.user_save_not_groups_all']
			);
			echo json_encode($return);
			break;
		endif;
		// Генерируем имя файла
		// Точка входа время и группа
		// Чисто для предотвращения совпадения.
		// Решение не является максимально хорошим.
		$file_name = gen_token(time() . rand() . $user_group) . '.xlsx';
		// Далее выход происходит по завершени блока case
		// Есть ли файл
		if (!isset($_FILES[$input_name])):
			$return = array(
				"request"        => false,
				"message"         => $_lang['mailsend.import_error_file'],
			);
		else:
			// Файл есть
			$file = $_FILES[$input_name];
			// Проверим на ошибки загрузки.
			if(is_uploaded_file($file['tmp_name'])):
				// Файл загружен
				// Начинаем проверки
				$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
				if(in_array($extension, $allow)):
					// Разрешённый формат файла
					// Перемещаем файл в директорию.
					$inputFileName = $upload_dir . $file_name;
					if (move_uploaded_file($file['tmp_name'], $inputFileName)):
						// Удачное перемещение
						// Читаем файл
						try {
							/**
							 * Определяем тип файла
							*/
							$inputFileType = IOFactory::identify($inputFileName);
							/**
							 * Создаём новый объект Reader определенного типа.
							*/
							$reader = IOFactory::createReader($inputFileType);
							/**
							 * Загружаем файл в объект и получаем SpreadSheet
							*/
							$spreadsheet = $reader->load($inputFileName);
							/**
							 * Только чтение данных
							 */
							$reader->setReadDataOnly(true);
							/**
							 * Данные в виде массива
							 */
							$data = $spreadsheet->getActiveSheet()->toArray();
							/**
							 * Цикл по данным
							 */
							foreach ($data as $item):
								/**
								 * Email's
								 */
								$mails = explode("\n", $item[1]);
								// Удаляем пробелы сначала и конца, переводим в нижний регистр
								$mails = array_map('trimToLower', $mails);
								// Удаляем пробелы сначала и конца строки. А вдруг )))
								$item[0] = trim($item[0]);
								// Сортируем
								sort($mails);
								$mail_array = array();
								/**
								 * Пробежим по массиву адресов
								 */
								foreach($mails as $mail):
									$email = mb_convert_case(trim($mail, "\r\n\t ;,.'\"-_/|!"), MB_CASE_LOWER, "UTF-8");
									/**
									 * Если адрес валидный
									 * И имя больше или равен трём символам
									 */
									if(isValidEmail($email) && mb_strlen($item[0]) >= 3):
										$std = new stdClass;
										$std->name = $item[0];
										$std->email = $email;
										$std->token = gen_token($std->email);
										$std->unsubscribe = "0";
										$result = $modx->db->select("*", $table_users,  "email='" . $std->email ."'");
										$total_rows = $modx->db->getRecordCount( $result );

										/**
										 * Если в базе нет адресов. Вносим
										 */
										if($total_rows < 1):
											$fields = array(
												'name'        => $modx->db->escape($std->name),
												'email'       => $modx->db->escape($std->email),
												'unsubscribe' => $modx->db->escape($std->unsubscribe),
												'token'       => $modx->db->escape($std->token)
											);
											/**
											 * Если удалось внести адрес
											 */
											$modx->db->insert( $fields, $table_users);
											$id = $modx->db->getInsertId();
											if(!is_null($id)):
												/**
												 * Пытаемся вставить принадлежность к группе
												 * Но сначало проверим, есть ли записи в базе
												 */
												$result = $modx->db->select("*", $table_members,  "`id_user`='$id' AND `id_group`='$user_group'");
												/**
												 * Если результата нет
												 */
												if( !$modx->db->getRecordCount( $result ) ):
													$fields = array(
														'id_user' => $modx->db->escape($id),
														'id_group'=> $modx->db->escape($user_group)
													);
													/**
													 * Вставляем сточку
													 */
													$modx->db->insert( $fields, $table_members);
												endif;
												$string[] = str_pad((string)$id, 10, " ", STR_PAD_RIGHT) . "->" . $std->email;
											endif;
										else:
											/**
											 * Если в базе есть запись
											 * Достаём её.
											 */
											while($row = $modx->db->getRow( $result )):
												$id = $modx->db->escape($row['id']);
												/**
												 * Пытаемся вставить принадлежность к группе
												 * Но сначало проверим, есть ли записи в базе
												 */
												$result_members = $modx->db->select("*", $table_members,  "`id_user`='$id' AND `id_group`='$user_group'");
												/**
												 * Если результата нет
												 */
												if( !$modx->db->getRecordCount( $result_members ) ):
													$fields = array(
														'id_user' => $id,
														'id_group'=> $user_group
													);
													/**
													 * Вставляем сточку
													 */
													$modx->db->insert( $fields, $table_members);
												endif;
												$string[] = str_pad((string)$id, 10, " ", STR_PAD_RIGHT) . "->" . $std->email;
											endwhile;
										endif;
									endif;
								endforeach;
							endforeach;
							$return = array(
								"request"        => true,
								"message"        => implode("\n", $string)
							);
						} catch(ReaderException $e) {
							$return = array(
								"request"        => false,
								"message"         => "Ошибка чтения файла",
							);
						}
					else:
						// Неудачное перемещение
						$return = array(
							"request"        => false,
							"message"        => $_lang['mailsend.import_error_file']
						);
					endif;
				else:
					// Запрещённый формат файла
					$return = array(
						"request"        => false,
						"message"         => $_lang['mailsend.import_error_file_type'],
					);
				endif;
			else:
				// Файл не загружен
				$return = array(
					"request"        => false,
					"message"         => $_lang['mailsend.import_error_file'],
				);
			endif;
		endif;
		// Удаляем файл
		@unlink($upload_dir . $file_name);
		echo json_encode($return);
		break;
	case 'default':
		header("Content-type: text/html; charset=utf8");
?>
<dialog class="child">
	<div class="row clearfix">
		<div class="container-fluid">
			<header class="clearfix row">
				<h2><i class="fas fa-laptop-code"></i></i>&nbsp;<?= $_lang['mailsend.file_import_excel']; ?></h2>
				<a href="javascript:;" class="close_dialog btn" tabindex="-1"><i class="icon-menu-close"></i></a>
			</header>
			<form class="form-horizontal" name="import_user" action="<?= $start_link; ?>">
				<input type="hidden" name="action" value="import">
				<input type="hidden" name="type" value="user">
				<!-- Выбор файла -->
				<div class="form-group">
					<label for="file_import"><strong><?= $_lang['mailsend.file_import']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fas fa-file-import"></i></span>
						<input type="file" class="form-control btn" name="file_import" id="file_import" required="required" tabindex="1" accept=".xlsx">
					</div>
				</div>
				<!--   -->
				<div class="form-group">
					<label for="user_groups"><strong><?= $_lang['mailsend.file_import_groups']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="icon-layer"></i></span>
						<select class="form-control" name="user_groups" id="user_groups" tabindex="3">
<?php
						$result_groups = $modx->db->select("*", $table_groups);
						if($modx->db->getRecordCount( $result_groups )):
							while ($row_groups = $modx->db->getRow( $result_groups )):
?>
							<option value="<?= $row_groups['id']; ?>"><?= $row_groups['name']; ?></option>

<?php
							endwhile;
						endif;
?>
						</select>
					</div>
				</div>
				<!-- Кнопки -->
				<div class="form-group text-right">
					<button class="btn btn-success" type="submit" title="<?= $_lang['mailsend.form_controll_import']; ?>" tabindex="5"><i class="fa fa-floppy-o"></i><strong>&nbsp;</strong><i class="fa fa-pencil"></i><span><?= $_lang['mailsend.form_controll_import']; ?></span></button>
					<button class="btn btn-secondary" type="button" title="<?= $_lang['mailsend.form_controll_close']; ?>" tabindex="6"><i class="fa fa-times-circle"></i><span><?= $_lang['mailsend.form_controll_close']; ?></span></button>
				</div>
			</form>
		</div>
	</div>
</dialog>
<?php
		break;
	default:
		break;
}
