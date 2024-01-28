<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module'))
	die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.');
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
//$css_path = MODX_FORM_BASE_PATH . "css/style.css";
//$vars_path = MODX_FORM_BASE_PATH . "css/variables.css";
//$main_path = MODX_FORM_BASE_PATH . "css/main.css";
//if(is_file($css_path)):
//	$css = file_get_contents($vars_path) . file_get_contents($main_path) . file_get_contents($css_path);
//	$css = $modform->minimize_css($css);
?>
<?= $start_link; ?>
<?php
endif;