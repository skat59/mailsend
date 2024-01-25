<?php
$settings['display'] = 'vertical';
$settings['fields'] = array(
    'file' => array(
        'caption' => 'Файл',
        'type' => 'file'
    ),
    'title' => array(
        'caption' => 'Название файла (ОБЯЗАТЕЛЬНО)',
        'type' => 'text'
    ),
);
$settings['templates'] = array(
    'outerTpl' => '[+wrapper+]',
    'rowTpl' => '[+title+]
[+file+]
'
);
