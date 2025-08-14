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
		$user_id = isset($_POST['user_id']) ? $modx->db->escape($_POST['user_id']) : "";
		break;
	case 'group':
		// Редактирование Группы
		$group_id = isset($_POST['group_id']) ? $modx->db->escape((int)$_POST['group_id']) : 0;
		$result = $modx->db->select("*", $table_groups, "id='" . $group_id . "'");
		if($modx->db->getRecordCount( $result )):
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
				<div class="form-group">
					<label for="group_name"><strong><?= $_lang['mailsend.groups_edit_group']; ?>:</strong></label>
					<div class="input-group">
						<span class="input-group-addon"><i class="icon-layer"></i></span>
						<input type="text" class="form-control" name="group_name" id="group_name" placeholder="<?= $_lang['mailsend.groups_edit_group_placeholder']; ?>" required="required" value="<?= $modx->htmlspecialchars($row["name"]);?>">
					</div>
				</div>
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
			// Вывести сообщение об отмене
?>
<form class="form-horizontal" name="edit_group" action="<?= $start_link; ?>" method="GET">
	<div class="form-group">
		<p class="text-center"><strong>Нет группы для редактирования</strong></p>
	</div>
	<div class="form-group text-right">
		<button class="btn btn-default" type="button">Отмена</button>
	</div>
</form>
<?php
		endif;
		break;
	default:
		// Обдумать
		break;
}
