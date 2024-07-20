<?php
/**
 * PageAuth
 *
 * При определённом шаблоне отдать 403-ю ошибку
 *
 * @category     plugin
 * @version      1.0.0
 * @package      evo
 * @internal     @events OnWebPageInit
 * @internal     @modx_category Content
 * @internal     @installset base
 * @internal     @disabled 0
 * @license      https://github.com/skat59/mailsend/LICENSE MIT License (MIT)
 * @reportissues https://github.com/skat59/mailsend/issues
 * @author       Чернышёв Андрей aka ProjectSoft
 * @lastupdate   28-01-2024
 */
if (!defined('MODX_BASE_PATH')) {
	http_response_code(403);
	die('For'); 
}

$e = &$modx->event;
$params = $e->params;

switch ($e->name) {
	case 'OnLoadDocumentObject':
		// Получаем шаблоны для которых нужно установить 403 заголовок
		$ids = explode(',', $params['templates']);
		// Получаем шаблон страницы
		$tmpl = $params['documentObject']['template'];
		// Присутствует ли шаблон в массиве шаблонов
		if(in_array($tmpl, $ids)):
			// Да, устанавливаем заголовок.
			header('HTTP/1.0 403 Forbidden');
		endif;
		break;
}