<?php
if (IN_MANAGER_MODE != 'true') {
    die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}

global $_style;

$id = $row["id"];
if(!$row["value"]){
	$row["value"] = time();
}
$val = (int)$row["value"];
//26-01-2024 0:10:00
$value = $val ? date("d-m-Y G:i:00", $val) : $row["value"];

echo '<input id="tv' . $id . '" name="tv' . $id . '" class="DatePicker" type="text" value="' . $value . '" onblur="documentDirty=true;" autocomplete="off"> <a onclick="document.forms[\'mutate\'].elements[\'tv' . $id . '\'].value=\'\';document.forms[\'mutate\'].elements[\'tv' . $id . '\'].onblur(); return true;" onmouseover="window.status=\'clear the date\'; return true;" onmouseout="window.status=\'\'; return true;" style="cursor:pointer; cursor:hand"><i class="' . $_style["actions_calendar_delete"] . '"></i></a>';
