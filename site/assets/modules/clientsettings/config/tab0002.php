<?php

return [
	'caption' => 'SMTP ОТПРАВИТЕЛь',
	'introtext' => '<style>#tab_tab0002 .multitv .list li.element input[type="email"]:not(.inline),#tab_tab0001 .multitv .list li.element input[type="text"]:not(.inline),#tab_tab0001 .multitv .list li.element input[type="password"]:not(.inline),#tab_tab0001 .multitv .list li.element input[type="number"]:not(.inline){width: calc(100% - 160px) !important;}</style>',
	'settings' => [
		'perent_site_url' => [
			'caption' => 'Сайт для которого идёт рассылка',
			'type' => 'text'
		],
		'title_parent' => [
			'caption' => 'Заголовок в блоке оформления',
			'type' => 'text'
		],
		'title_logotip' => [
			'caption' => 'Логотип в блоке оформления',
			'type' => 'image'
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
