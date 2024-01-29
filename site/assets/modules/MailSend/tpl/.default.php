<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
global $content;
if(is_string($content['icon'])){
	if(trim($content["icon"]) == ""){
		$content["icon"] = "fa fa-users";
	}
}else{
	$content["icon"] = "fa fa-users";
}
$page = (int)$page > 0 ? "&page=" . $page : "";
$start_link = $modx->config["site_manager_url"] . 'index.php?a=112&id=' . $content['id'] . $page;
?>
<style>
	.clearfix::before {
		display: table;
		clear: both;
		content: "";
	}
	.btn {
		font-size: .6772rem;
		height: 2.4em;
		line-height: 1.4;
	}
	a.btn-success,
	a.btn-danger,
	a.btn-success:not([href]),
	a.btn-danger:not([href]),
	a.btn-success:not([tabindex]),
	a.btn-danger:not([tabindex]),
	a.btn-success:not([href]):not([tabindex]),
	a.btn-danger:not([href]):not([tabindex]) {
		color: white;
	}
	a.btn:not([href]),
	a.btn:not([tabindex]),
	a.btn:not([href]):not([tabindex]) {
		cursor: pointer;
	}
	.tab-row .tab .fa,
	.tab-row .tab .far {
		margin-right: 0.5em;
		font-size: 0.875rem;
	}
	.tab-row .tab,
	.tab-pane > .tab-page > .tab {
		border: 1px solid rgba(0, 0, 0, 0.05);
		border-bottom: none;
	}
	.tab-header-mailsend {
		padding: 1rem 0 0.5rem 0;
		letter-spacing: 0;
	}
	.tab-body-mailsend {
		padding-bottom: 1rem;
	}
</style>
<h1 class="d-none"><i class="<?= $content["icon"];?>"></i><?= $content["name"]; ?></h1>
<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="d-none" href="javascript:;" onclick="return false;"></a>
    </div>
</div>
<div class="container-fluid">
	<h3 style="font-size: 1.5em; line-height: 1.5rem; padding: 0.8rem 0; margin-bottom: 1.6rem; margin-left: 10px;"><i class="fa fa-users"></i>&nbsp;&nbsp;<?= $_lang["mailsend.title"]; ?></h3>
	<div class="tab-pane" id="MailSendManager_pane">
		<script type="text/javascript">
			tpResources = new WebFXTabPane(document.getElementById('MailSendManager_pane'));
		</script>
		<div class="tab-page" id="MailSendManager_users">
			<h2 class="tab"><i class="far fa-address-card"></i> <?= $_lang["mailsend.users_tab_title"]; ?></h2>
			<script type="text/javascript">
				tpResources.addTabPage(document.getElementById('MailSendManager_users'));
			</script>
			<div class="container-fluid clearfix">
				<div class="tab-header-mailsend clearfix text-right">
					<div class="btn-group">
						<a class="btn btn-success" title="Добавить пользователя"><i class="far fa-address-card"></i>&nbsp;<span>Добавить пользователя</span></a>&nbsp;<a class="btn btn-secondary" title="Импорт из Excel (*.xlsx)"><i class="far fa-file-excel"></i>&nbsp;<span>Импорт из Excel (*.xlsx)</span></a>
					</div>
				</div>
				<div class="tab-body-mailsend">
					<form name="mail-users" action>
						<table class="grid">
							<thead>
								<tr>
									<th width="55%"><?= $_lang["mailsend.users_table_col1"]; ?></th>
									<th width="20%"><?= $_lang["mailsend.users_table_col2"]; ?></th>
									<th width="20%"><?= $_lang["mailsend.users_table_col3"]; ?></th>
									<th width="5%"><?= $_lang["mailsend.users_table_col4"]; ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Название организации</td>
									<td>Адрес Email</td>
									<td>Группы рассылки</td>
									<td>
										<div class="btn-group">
											<a class="btn btn-success" title="Редактировать адресат"><i class="fas fa-user-edit"></i></a>&nbsp;<a class="btn btn-danger" title="Удалить адресат"><i class="fas fa-user-times"></i></a>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
		</div>
		<div class="tab-page" id="MailSendManager_groups">
			<h2 class="tab"><i class="fa fa-list-alt"></i> <?= $_lang["mailsend.groups_tab_title"]; ?></h2>
			<script type="text/javascript">
				tpResources.addTabPage(document.getElementById('MailSendManager_groups'));
			</script>
			<div class="container-fluid clearfix">
				<div class="tab-header-mailsend clearfix text-right">
					<div class="btn-group">
						<a class="btn btn-success" title="Добавить Группу"><span>Добавить Группу</span></a>
					</div>
				</div>
				<div class="tab-body-mailsend">
					<form name="mail-users" action>
						<table class="grid">
							<thead>
								<tr>
									<th width="5%"><?= $_lang["mailsend.groups_table_col1"]; ?></th>
									<th width="90%"><?= $_lang["mailsend.groups_table_col2"]; ?></th>
									<th width="5%"><?= $_lang["mailsend.groups_table_col3"]; ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?= $_lang["mailsend.groups_table_col1"]; ?></td>
									<td><?= $_lang["mailsend.groups_table_col2"]; ?></td>
									<td>
										<div class="btn-group">
											<a class="btn btn-success" title="Редактировать группу"><i class="fas fa-user-edit"></i></a>&nbsp;<a class="btn btn-danger" title="Удалить группу"><i class="fas fa-user-times"></i></a>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
