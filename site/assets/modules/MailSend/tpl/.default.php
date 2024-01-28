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
        <a id="Button1" class="btn btn-success" href="javascript:;" onclick="window.location.href='index.php?a=106';">
            <i class="fa fa-times-circle"></i><span><?= $_lang["mailsend.close"]; ?></span>
        </a>
    </div>
</div>
<div class="container-fluid">
	<h3><i class="fa fa-file-text"></i>&nbsp;&nbsp;<?= $_lang["mailsend.title"]; ?></h3>
	<hr>
	<?= $content["icon"] ?>
</div>
