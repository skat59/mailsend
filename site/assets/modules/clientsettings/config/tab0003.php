<?php

return [
	'caption' => 'SMTP ОТПРАВИТЕЛь',
	'introtext' => '<style>#tab_tab0003 select {width: 100%;}</style>',
	'settings' => [
		'perent_site_url' => [
			'caption' => 'Сайт для которого идёт рассылка',
			'type' => 'text'
		],
		'send_user_evo' => [
			'caption' => 'Имя отправителя',
			'type' => 'text'
		],
		'send_email_evo' => [
			'caption' => 'Почтовый адрес',
			'type' => 'email'
		],
		'send_password_evo' => [
			'caption' => 'Пароль',
			'type' => 'text'
		],
		'smtp_host_evo' => [
			'caption' => 'SMTP HOST',
			'type' => 'text'
		],
		'smtp_port_evo' => [
			'caption' => 'SMTP PORT',
			'type' => 'number'
		],
		'smtp_auth_evo' => [
			'caption' => 'SMTP Auth',
			'type' => 'dropdown',
			'elements' => 'Да==1||Нет==0'
		],
	],
];
