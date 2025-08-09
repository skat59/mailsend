<?php

return [
	'caption' => 'НАСТРОЙКА ОТПРАВКИ',
	'introtext' => '<style>
	#tab_tab0001 .multitv .list li.element input[type="email"]:not(.inline),
	#tab_tab0001 .multitv .list li.element input[type="text"]:not(.inline),
	#tab_tab0001 .multitv .list li.element input[type="number"]:not(.inline),
	#tab_tab0001 .multitv .list li.element select:not(.inline) {
		width: calc(100% - 160px) !important;
	}
</style>',
	'settings' => [
		'dispatch_checkers' => [
			'caption' => 'Проверяющие отправку',
			'type' => 'custom_tv:multitv',
		],
		'secret_phrase' => [
			'caption' => 'Секретная фраза',
			'type' => 'text',
		],
		'send_count_messages' => [
			'caption' => 'Количество сообщений за одну отправку',
			'type' => 'number',
			'default_text' => '10',
		],
		'send_sleep_messages' => [
			'caption' => 'Количество секунд сна между сообщениями',
			'type' => 'number',
			'default_text' => '10',
		],
		'send_debug_message' => [
			'caption' => 'Режим разработки<br><strong style="color: red;">(только для разработчика)</strong>',
			'type' => 'dropdown',
			'elements' => 'Нет==0||Да==1',
		]
	],
];
