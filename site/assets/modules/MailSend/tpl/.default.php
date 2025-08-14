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

$start_link = $modx->config["site_manager_url"] . 'index.php?a=112&id=' . $content['id'];
$css = filemtime(MODX_BASE_PATH . "assets/modules/MailSend/css/main.min.css");
$js = filemtime(MODX_BASE_PATH . "assets/modules/MailSend/js/main.js");
?>
<link rel="stylesheet" href="/assets/modules/MailSend/css/main.min.css?<?= $css; ?>" />
<h1 class="d-none"><i class="<?= $content["icon"];?>"></i><?= $content["name"]; ?></h1>
<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="d-none" href="javascript:;" onclick="return false;"></a>
    </div>
</div>
<div class="container-fluid">
	<h1><i class="<?= $content["icon"];?>"></i>&nbsp;&nbsp;<?= $_lang["mailsend.title"]; ?></h1>
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
				<div class="tab-body-mailsend">
					<div>
						<table class="grid grid-users">
							<thead>
								<tr>
									<th><?= $_lang["mailsend.users_table_col1"]; ?></th>
									<th><?= $_lang["mailsend.users_table_col2"]; ?></th>
									<th><?= $_lang["mailsend.users_table_col3"]; ?></th>
									<th><?= $_lang["mailsend.users_table_col4"]; ?></th>
									<th><?= $_lang["mailsend.users_table_col5"]; ?></th>
									<th><?= $_lang["mailsend.users_table_col6"]; ?></th>
									<th><?= $_lang["mailsend.users_table_col7"]; ?></th>
								</tr>
							</thead>
							<tbody>
<?php
	$sql = "SELECT users_table.id, users_table.name, users_table.email, GROUP_CONCAT(groups_table.id ORDER BY groups_table.id SEPARATOR \", \r\n\") AS groups_id, GROUP_CONCAT(groups_table.name ORDER BY groups_table.id SEPARATOR \", \r\n\") AS groups_name, users_table.unsubscribe FROM " . $table_users . " users_table inner JOIN " . $table_members . " group_memmer_table on group_memmer_table.id_user = users_table.id inner JOIN " . $table_groups . " groups_table on groups_table.id = group_memmer_table.id_group group by users_table.id";
	$result = $modx->db->query($sql);
	while( $row = $modx->db->getRow( $result ) ):
?>
								<tr>
									<td><?= $row["id"]; ?></td>
									<td><?= $row["name"]; ?></td>
									<td><?= $row["email"]; ?></td>
									<td><?= $row["groups_id"]; ?></td>
									<td><?= $row["groups_name"]; ?></td>
									<td><?= $row["unsubscribe"]; ?></td>
									<td>
										<div class="btn-group">
											<a class="btn btn-success user_edit" title="<?= $_lang["mailsend.groups_table_edit_user"]; ?>" data-group="<?= $row["id"]; ?>"><i class="fas fa-user-edit"></i></a>&nbsp;<a class="btn btn-danger user_delete" title="<?= $_lang["mailsend.groups_table_delete_user"]; ?>" data-group="<?= $row["id"]; ?>"><i class="fas fa-user-times"></i></a>
										</div>
									</td>
								</tr>
<?php
	endwhile;
?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-page" id="MailSendManager_groups">
			<h2 class="tab"><i class="fa fa-list-alt"></i> <?= $_lang["mailsend.groups_tab_title"]; ?></h2>
			<script type="text/javascript">
				tpResources.addTabPage(document.getElementById('MailSendManager_groups'));
			</script>
			<div class="container-fluid clearfix">
				<div class="tab-body-mailsend">
					<div>
						<table class="grid grid-groups">
							<thead>
								<tr>
									<th><?= $_lang["mailsend.groups_table_col1"]; ?></th>
									<th><?= $_lang["mailsend.groups_table_col2"]; ?></th>
									<th><?= $_lang["mailsend.groups_table_col3"]; ?></th>
								</tr>
							</thead>
							<tbody>
						<?php
						$result = $modx->db->select("*", $table_groups);
						if( $modx->db->getRecordCount( $result ) >= 1 ):
							while ($row = $modx->db->getRow($result)):?>
								<tr>
									<td><?= $row["id"]; ?></td>
									<td><?= $row["name"]; ?></td>
									<td>
										<div class="btn-group">
											<a class="btn btn-success group_edit" title="<?= $_lang["mailsend.groups_table_edit_group"]; ?>" data-group="<?= $row["id"]; ?>"><i class="fas fa-user-edit"></i></a>&nbsp;<a class="btn btn-danger group_delete" title="<?= $_lang["mailsend.groups_table_delete_group"]; ?>" data-group="<?= $row["id"]; ?>"><i class="fas fa-user-times"></i></a>
										</div>
									</td>
								</tr>
							<?php endwhile;
						endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="/assets/modules/MailSend/js/datatables.min.js?<?= $js;?>"></script>
<script src="/assets/modules/MailSend/js/main.js?<?= $js;?>"></script>
