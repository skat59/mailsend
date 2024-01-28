<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
global $content;
if(is_string($content['icon'])){
	if(trim($content["icon"]) == ""){
		$content["icon"] = "fa fa-cube";
	}
}else{
	$content["icon"] = "fa fa-cube";
}
$page = (int)$page > 0 ? "&page=" . $page : "";
$start_link = $modx->config["site_manager_url"] . 'index.php?a=112&id=' . $content['id'] . $page;
?>
<h1 class="d-none"><i class="fa fa-file-text"></i><?= $content["name"]; ?></h1>
<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="b-none" href="javascript:;" onclick="return false;"></a>
    </div>
</div>
<div class="container-fluid">
	<h3 style="font-size: 1.5em; line-height: 1.5rem; padding: 0.8rem 0; margin-bottom: 1.6rem; margin-left: 10px;"><i class="fa fa-file-text"></i>&nbsp;&nbsp;<?= $_lang["mailsend.title"]; ?></h3>
	<hr>
	<?= $content["icon"] ?>
</div>
