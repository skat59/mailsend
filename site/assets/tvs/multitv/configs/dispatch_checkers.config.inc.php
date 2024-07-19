<?php
$settings['display'] = 'vertical';
$settings['fields'] = array(
	'user' => array(
		'caption' => 'Фамилия Имя <br><span style="color: red;">(ОБЯЗАТЕЛЬНО)</span>',
		'type' => 'text'
	),
	'email' => array(
		'caption' => 'Email <br><span style="color: red;">(ОБЯЗАТЕЛЬНО)</span>',
		'type' => 'email'
	),
    'option' => array(
        'caption' => 'Отправлять письмо рассылки?',
        'type' => 'dropdown',
        'elements' => 'Не отправлять==0||Отправлять==1',
        'default' => '0'
    )
);
$settings['templates'] = array(
	'outerTpl' => '[+wrapper+]',
	'rowTpl' => '[+user+]<[+email+]>
'
);