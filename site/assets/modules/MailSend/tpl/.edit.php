<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
// Редактирование Пользователя или Группы
// Отдача контента на редактирование
$url_a = $_GET['a'];
$url_id = $_GET['id'];
$start_link = $modx->config["site_manager_url"] . 'index.php?a=' . $url_a . '&id=' . $url_id;

$postType = isset($_POST['type']) ? filter_input(INPUT_POST, 'type', FILTER_SANITIZE_ENCODED) : "";

switch ($postType) {
	case 'user':
		// Редактирование Пользователя
		$user_id = isset($_POST['user_id']) ? $modx->db->escape((int)$_POST['user_id']) : 0;
		// В вывод должно попасть все данные пользователя и данные о подписках на группы
		$sql = "SELECT users_table.id, users_table.name, users_table.email, GROUP_CONCAT(groups_table.id ORDER BY groups_table.id SEPARATOR \",\") AS groups_id, users_table.unsubscribe FROM " . $table_users . " users_table inner JOIN " . $table_members . " group_memmer_table on group_memmer_table.id_user = users_table.id inner JOIN " . $table_groups . " groups_table on groups_table.id = group_memmer_table.id_group WHERE users_table.id=" . $user_id;
		$result = $modx->db->query($sql);
		// Редактировать пользователя
		// Отдать форму на редактирование/Добавление
		$row = $modx->db->getRow( $result );

		if($row['id']):
			// Форма редактирования
?>
<dialog class="child">
	<div class="row clearfix">
		<div class="container-fluid">
			<header class="clearfix row">
				<h2><i class="fa fa-address-card-o"></i>&nbsp;<?= $_lang['mailsend.user_table_edit_user']; ?></h2>
				<a href="javascript:;" class="close_dialog btn" tabindex="-1"><i class="icon-menu-close"></i></a>
			</header>
			<form class="form-horizontal" name="edit_user" action="<?= $start_link; ?>">
				<input type="hidden" name="action" value="save">
				<input type="hidden" name="type" value="user">
				<input type="hidden" name="user_id" value="<?= $row['id'];?>">
				<!-- Название организации -->
				<div class="form-group">
					<label for="user_name"><strong><?= $_lang['mailsend.user_edit_name']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-user"></i></span>
						<input type="text" class="form-control" name="user_name" id="user_name" placeholder="<?= $_lang['mailsend.user_edit_name_placeholder']; ?>" required="required" value="<?= $modx->htmlspecialchars($row["name"]);?>" tabindex="1">
					</div>
				</div>
				<!-- Email организации -->
				<div class="form-group">
					<label for="user_email"><strong><?= $_lang['mailsend.user_edit_email']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-envelope"></i></span>
						<input type="text" class="form-control" name="user_email" id="user_email" placeholder="<?= $_lang['mailsend.user_edit_email_placeholder']; ?>" required="required" value="<?= $modx->htmlspecialchars($row["email"]);?>" tabindex="2">
					</div>
				</div>
				<!-- Группы подписки -->
				<div class="form-group">
					<label for="user_groups"><strong><?= $_lang['mailsend.user_edit_groups']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="icon-layer"></i></span>
						<select class="form-control" name="user_groups[]" id="user_groups" size="8" multiple="multiple" tabindex="3">
<?php
						$groups = explode(",", $row['groups_id']);
						// Запрос к базе и формирование multiselect
						$result_groups = $modx->db->select("*", $table_groups);
						if($modx->db->getRecordCount( $result_groups )):
							while ($row_groups = $modx->db->getRow( $result_groups )):
								print_r($row['groups_id']);
								if(in_array($row_groups["id"], $groups)):
?>
							<option value="<?= $row_groups['id']; ?>" selected="selected"><?= $row_groups['name']; ?></option>
<?php
								else:
?>
							<option value="<?= $row_groups['id']; ?>"><?= $row_groups['name']; ?></option>
<?php
								endif;
							endwhile;
						endif;
?>
						</select>
					</div>
				</div>
				<!-- Состояние подписки -->
<?php
						$unsubscribe = (int)$row['unsubscribe'];
						$checked = !$unsubscribe ? ' checked="checked"' : '';
?>
				<div class="form-group">
					<label for="user_unsubscribe"><strong><?= $_lang['mailsend.user_edit_unsubscribe']; ?>:</strong></label>
					<div class="input-group">
						<input type="checkbox" name="user_unsubscribe" id="user_unsubscribe" tabindex="4" value="0"<?= $checked; ?>>
					</div>
				</div>
				<!-- Кнопки -->
				<div class="form-group text-right">
					<button class="btn btn-success" type="submit" title="<?= $_lang['mailsend.form_controll_save']; ?>" tabindex="5"><i class="fa fa-floppy-o"></i><strong>&nbsp;</strong><i class="fa fa-pencil"></i><span><?= $_lang['mailsend.form_controll_save']; ?></span></button>
					<button class="btn btn-secondary" type="button" title="<?= $_lang['mailsend.form_controll_close']; ?>" tabindex="6"><i class="fa fa-times-circle"></i><span><?= $_lang['mailsend.form_controll_close']; ?></span></button>
				</div>
			</form>
		</div>
	</div>
</dialog>
<?php
		else:
			// Форма Добавления
?>
<dialog class="child">
	<div class="row clearfix">
		<div class="container-fluid">
			<header class="clearfix row">
				<h2><i class="fa fa-address-card-o"></i>&nbsp;<?= $_lang['mailsend.user_edit_add']; ?></h2>
				<a href="javascript:;" class="close_dialog btn" tabindex="-1"><i class="icon-menu-close"></i></a>
			</header>
			<form class="form-horizontal" name="insert_user" action="<?= $start_link; ?>">
				<input type="hidden" name="action" value="save">
				<input type="hidden" name="type" value="user">
				<!-- Название организации -->
				<div class="form-group">
					<label for="user_name"><strong><?= $_lang['mailsend.user_edit_name']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-user"></i></span>
						<input type="text" class="form-control" name="user_name" id="user_name" placeholder="<?= $_lang['mailsend.user_edit_name_placeholder']; ?>" required="required" value="" tabindex="1">
					</div>
				</div>
				<!-- Email организации -->
				<div class="form-group">
					<label for="user_email"><strong><?= $_lang['mailsend.user_edit_email']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-envelope"></i></span>
						<input type="text" class="form-control" name="user_email" id="user_email" placeholder="<?= $_lang['mailsend.user_edit_email_placeholder']; ?>" required="required" value="" tabindex="2">
					</div>
				</div>
				<!-- Группы подписки -->
				<div class="form-group">
					<label for="user_groups"><strong><?= $_lang['mailsend.user_edit_groups']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="icon-layer"></i></span>
						<select class="form-control" name="user_groups[]" id="user_groups" size="8" multiple="multiple" tabindex="3">
<?php
						// Запрос к базе и формирование multiselect
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
				<!-- Состояние подписки -->
				<div class="form-group">
					<label for="user_unsubscribe"><strong><?= $_lang['mailsend.user_edit_unsubscribe']; ?>:</strong></label>
					<div class="input-group">
						<input type="checkbox" name="user_unsubscribe" id="user_unsubscribe" value="0" tabindex="4">
					</div>
				</div>
				<!-- Кнопки -->
				<div class="form-group text-right">
					<button class="btn btn-success" type="submit" title="<?= $_lang['mailsend.form_controll_save']; ?>" tabindex="5"><i class="fa fa-floppy-o"></i><strong>&nbsp;</strong><i class="fa fa-pencil"></i><span><?= $_lang['mailsend.form_controll_save']; ?></span></button>
					<button class="btn btn-secondary" type="button" title="<?= $_lang['mailsend.form_controll_close']; ?>" tabindex="6"><i class="fa fa-times-circle"></i><span><?= $_lang['mailsend.form_controll_close']; ?></span></button>
				</div>
			</form>
		</div>
	</div>
</dialog>
<?php
		endif;
		break;
	case 'group':
		$group_id = isset($_POST['group_id']) ? $modx->db->escape((int)$_POST['group_id']) : 0;
		$result = $modx->db->select("*", $table_groups, "id='" . $group_id . "'");
		if($modx->db->getRecordCount( $result )):
			// Редактирование Группы
			// Отдать форму на редактирование
			$row = $modx->db->getRow( $result );
			// Форма редактирования Группы
?>
<dialog class="child">
	<div class="row clearfix">
		<div class="container-fluid">
			<header class="clearfix row">
				<h2><i class="icon-layer"></i>&nbsp;<?= $_lang['mailsend.groups_table_edit_group']; ?></h2>
				<a href="javascript:;" class="close_dialog btn" tabindex="-1"><i class="icon-menu-close"></i></a>
			</header>
			<form class="form-horizontal" name="edit_group" action="<?= $start_link; ?>">
				<input type="hidden" name="action" value="save">
				<input type="hidden" name="type" value="group">
				<input type="hidden" name="group_id" value="<?= $row["id"]; ?>">
				<!-- Название Группы -->
				<div class="form-group">
					<label for="group_name"><strong><?= $_lang['mailsend.groups_edit_group']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="icon-layer"></i></span>
						<input type="text" class="form-control" name="group_name" id="group_name" placeholder="<?= $_lang['mailsend.groups_edit_group_placeholder']; ?>" required="required" value="<?= $modx->htmlspecialchars($row["name"]);?>">
					</div>
				</div>
				<!-- Кнопки -->
				<div class="form-group text-right">
					<button class="btn btn-success" type="submit" title="<?= $_lang['mailsend.form_controll_save']; ?>"><i class="fa fa-floppy-o"></i><strong>&nbsp;</strong><i class="fa fa-pencil"></i><span><?= $_lang['mailsend.form_controll_save']; ?></span></button>
					<button class="btn btn-secondary" type="button" title="<?= $_lang['mailsend.form_controll_close']; ?>"><i class="fa fa-times-circle"></i><span><?= $_lang['mailsend.form_controll_close']; ?></span></button>
				</div>
			</form>
		</div>
	</div>
</dialog>
<?php
		else:
			// Добавить группу
?>
<dialog class="child">
	<div class="row clearfix">
		<div class="container-fluid">
			<header class="clearfix row">
				<h2><i class="icon-layer"></i>&nbsp;<?= $_lang['mailsend.groups_table_edit_group_insert']; ?></h2>
				<a href="javascript:;" class="close_dialog btn" tabindex="-1"><i class="icon-menu-close"></i></a>
			</header>
			<form class="form-horizontal" name="insert_group" action="<?= $start_link; ?>">
				<input type="hidden" name="action" value="save">
				<input type="hidden" name="type" value="group">
				<!-- Название Группы -->
				<div class="form-group">
					<label for="group_name"><strong><?= $_lang['mailsend.groups_edit_group']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="icon-layer"></i></span>
						<input type="text" class="form-control" name="group_name" id="group_name" placeholder="<?= $_lang['mailsend.groups_edit_group_placeholder']; ?>" required="required" value="<?= $modx->htmlspecialchars($row["name"]);?>">
					</div>
				</div>
				<!-- Кнопки -->
				<div class="form-group text-right">
					<button class="btn btn-success" type="submit" title="<?= $_lang['mailsend.form_controll_save']; ?>"><i class="fa fa-floppy-o"></i><strong>&nbsp;</strong><i class="fa fa-pencil"></i><span><?= $_lang['mailsend.form_controll_save']; ?></span></button>
					<button class="btn btn-secondary" type="button" title="<?= $_lang['mailsend.form_controll_close']; ?>"><i class="fa fa-times-circle"></i><span><?= $_lang['mailsend.form_controll_close']; ?></span></button>
				</div>
			</form>
		</div>
	</div>
</dialog>
<?php
		endif;
		break;
	default:
		// Обдумать
		// Пока отдаём недоступность
		http_response_code(403);
		break;
}
