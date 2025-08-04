<?php
if (IN_MANAGER_MODE != 'true') {
	die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}
$value = empty($row['value']) ? $row['default_text'] : $row['value'];
$id = $row['id'];
$out = '<select id="tv' . $id . '" name="tv' . $id . '" onchange="documentDirty=true;" size="20">';
$value_arr = explode('||', $value);
echo $value;
if(in_array(false, $value_arr)) {
	$row['value'] = "0";
	$out .= '<option value="0" selected="selected">Нет группы (Отправлено не будет)</option>';
}else{
	$out .= '<option value="0">Нет группы (Отправлено не будет)</option>';
}

// Получаем группы
$table = $modx->getFullTableName('mailsend_groups');
$select = 'SELECT * FROM ' . $table . ' ORDER BY `id` ASC';
$result = $modx->db->query($select);

while($row_groups = $modx->db->getRow($result)){
	$group_id   = $row_groups['id'];
	$group_name = $row_groups['name'];
	if (in_array($group_id, $value_arr) && !in_array(false, $value_arr)) {
		$out .= '<option value="' . $group_id . '" selected="selected">' . $group_id . ". " . $group_name . '</option>';
	}else{
		$out .= '<option value="' . $group_id . '">' . $group_id . '. ' . $group_name . '</option>';
	}
}
$out .= '</select>';
echo $out;
