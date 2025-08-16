(function($){
	DataTable.Buttons.defaults.dom.button.liner.tag = '';
	DataTable.Buttons.defaults.dom.container.className = DataTable.Buttons.defaults.dom.container.className + ' btn-group';
	DataTable.ext.buttons.pdfHtml5.className = DataTable.ext.buttons.pdfHtml5.className + ' btn btn-secondary';
	DataTable.ext.buttons.pdfHtml5.attr = {
		title: "Экспорт в PDF"
	};
	DataTable.ext.buttons.excelHtml5.className = DataTable.ext.buttons.excelHtml5.className + ' btn btn-secondary';
	DataTable.ext.buttons.excelHtml5.attr = {
		title: "Экспорт в XLSX"
	};
	DataTable.ext.buttons.userAdd = {
		//<i class="far fa-address-card"></i>&nbsp;<span>[+text+]</span>
		className: 'btn btn-success',
		text: '<i class="far fa-address-card"></i><span>Добавить пользователя</span>',
		attr: {
			title: "Добавить пользователя"
		},
		action: function (e, dt, button, config, cb) {}
	};
	DataTable.ext.buttons.userImport = {
		className: 'btn btn-secondary',
		text: '<i class="far fa-file-excel"></i><span>Импорт из Excel (*.xlsx)</span>',
		attr: {
			title: "Импорт из Excel (*.xlsx)"
		},
		action: function (e, dt, button, config, cb) {}
	};
	DataTable.ext.buttons.groupAdd = {
		className: 'btn btn-success',
		text: '<i class="icon-layer-plus"></i><span>Добавить группу</span>',
		attr: {
			title: "Добавить группу"
		},
		action: function (e, dt, button, config, cb) {}
	};

	let users, groups, dialog;

	const getDateTime = function(timestamp = 0) {
		let time = new Date(timestamp),
			date = time.getDate(),
			month = time.getMonth() + 1,
			year = time.getFullYear(),
			hour = time.getHours(),
			minute = time.getMinutes(),
			second = time.getSeconds(),
			arrDate = [
				leftPad(date,  2, '0'),
				leftPad(month, 2, '0'),
				String(year)
			],
			arrTime = [
				leftPad(hour,   2, '0'),
				leftPad(minute, 2, '0'),
				leftPad(second, 2, '0')
			];
		return arrDate.join('-') + ' ' + arrTime.join(':');

	},
	leftPad = function (str, len, ch) {
		str = String(str);
		let i = -1;
		if (!ch && ch !== 0) ch = ' ';
		len = len - str.length;
		while (++i < len) {
			str = ch + str;
		}
		return str;
	},
	componentName = `Модуль рассылки`,
	userName = `ProjectSoft`,
	SEND_MAIL = `Рассылка`,
	url = `${window.location.origin}${window.location.pathname}`,
	searchParams = new URLSearchParams(window.location.search),
	aUrl = searchParams.get("a"),
	idUrl = searchParams.get("id"),
	work = () => {
		try {
			window.parent.modx.main.work();
		}catch(e){}
	}.
	stopWork = () => {
		try {
			window.parent.modx.main.stopWork();
		}catch(e){}
	},
	dialogClose = () => {
			if(dialog) {
				dialog.close();
				document.body.removeChild(dialog);
				document.body.classList.remove('scroll-lock');
				dialog = false;
			}
		},
		errorStatus = (xhr, exception) => {
			// Коды ошибок
			// Нужно будет всё заполнить. На будущее ))
			/**
			 * 300 Multiple Choice («Множественный выбор»).
			 * 301 Moved Permanently («Перемещено навсегда»).
			 * 302 Moved Temporarily («Временно перемещен»).
			 * 303 See Other («Смотреть другое»).
			 * 304 Not Modified («Не изменено»).
			 * 305 Use Proxy («Использовать прокси»).
			 * 306 Switch Proxy («Переключить прокси»).
			 * 308 Permanent Redirect («Постоянное перенаправление»).
			 */
			dialogClose();
			// console.log(`Uncaught Error.\n${xhr.responseText}\n${xhr.status}\n${exception}`);
			let text = "";
			switch(xhr.status) {
				case 0:
					text = '0. Проверьте соединение с Интернетом.';
					break;
				case 400:
					text = '400. Bad Request («Неверный запрос»).';
					break;
				case 401:
					text = '401. Unauthorized («Несанкционированный запрос»).';
					break;
				case 402:
					text = '402. Payment Required («Необходима оплата»).';
					break;
				case 403:
					text = '403. Forbidden («Доступ запрещён»).';
					break;
				case 404:
					text = '404. Not Found («Ничего не найдено»).';
					break;
				case 405:
					text = '405. Method Not Allowed («Метод не поддерживается»).';
					break;
				case 406:
					text = '406. Not Acceptable («Неприемлемо»).';
					break;
				case 407:
					text = '407. Proxy Authentication Required («Необходима аутентификация прокси»).';
					break;
				case 408:
					text = '408. Request Timeout («Истекло время ожидания»).';
					break;
				case 409:
					text = '409. Conflict («Конфликт»).';
					break;
				case 410:
					text = '410. Gone («Удалён»).';
					break;
				case 411:
					text = '411. Length Required («Необходима длина»).';
					break;
				case 412:
					text = '412. Precondition Failed («Предварительное условие не выполнено»).';
					break;
				case 413:
					text = '413. Payload Too Large («Полезная нагрузка слишком большая»).';
					break;
				case 414:
					text = '414. URI Too Long («URI слишком длинный»).';
					break;
				case 415:
					text = '415. Unsupported Media Type («Неподдерживаемый тип данных»).';
					break;
				case 500:
					text = '500. Internal Server Error («Внутренняя ошибка сервера»).';
					break;
				case 501:
					text = '501. Not Implemented («Не реализовано»).';
					break;
				case 502:
					text = '502. Bad Gateway («Плохой, ошибочный шлюз»).';
					break;
				case 503:
					text = '503. Service Unavailable («Сервис недоступен»).';
					break;
				case 504:
					text = '504. Gateway Timeout («Шлюз не отвечает»).';
					break;
				case 505:
					text = '505. HTTP Version Not Supported («Версия HTTP не поддерживается»).';
					break;
				case 506:
					text = '506. Variant Also Negotiates («Вариант тоже проводит согласование»).';
					break;
				case 507:
					text = '507. Insufficient Storage («Переполнение хранилища»).';
					break;
				case 508:
					text = '508. Loop Detected («Пбнаружено бесконечное перенаправление»).';
					break;
				case 509:
					text = '509. Bandwidth Limit Exceeded («Исчерпана пропускная ширина канала»).';
					break;
				case 510:
					text = '510. Not Extended («Не расширено»).';
					break;
				case 511:
					text = '511. Network Authentication Required («Требуется сетевая аутентификация»).';
					break;
				case 520:
					text = '520. Unknown Error («Неизвестная ошибка»).';
					break;
				case 521:
					text = '521. Web Server Is Down («Веб-сервер не работает»).';
					break;
				case 522:
					text = '522. Connection Timed Out («Соединение не отвечает»).';
					break;
				case 523:
					text = '523. Origin Is Unreachable («Источник недоступен»).';
					break;
				case 524:
					text = '524. A Timeout Occurred («Время ожидания истекло»).';
					break;
				case 525:
					text = '525. SSL Handshake Failed («Квитирование SSL не удалось»).';
					break;
				case 526:
					text = '526. Invalid SSL Certificate («Недействительный сертификат SSL»).';
					break;
				default:
					text = `${xhr.status}. Неизвестная ошибка`;
					break;
			}
			stopWork();
			setTimeout(alert, 100, text);
		};


	$(document).on('click', ".group_edit", (e) => {
		work();
		let group_id = $(e.target).data("group");
		$.ajax({
			url: `${url}?a=${aUrl}&id=${idUrl}`,
			method: 'post',
			dataType: 'html',
			data: {
				action: 'edit',
				type: 'group',
				group_id: group_id
			},
			success: function(data) {
				let $html = $(data);
				dialog = $html[0];
				document.body.append(dialog);
				document.body.classList.add('scroll-lock');
				dialog.showModal();
				stopWork();
			},
			error: errorStatus
		});
	}).on('click', ".group_delete", (e) => {
		let btn = $(e.target),
			id = btn.data('group'),
			tr = btn.closest('tr'),
			$tds = $('td', tr),
			name = $($tds[1]).text();
		if(confirm(`${LANG_SENDMAIL["mailsend.groups_table_delete_group"]}: ${name}?`)){
			work();
			$.ajax({
				url: `${url}?a=${aUrl}&id=${idUrl}`,
				method: 'post',
				dataType: 'json',
				data: {
					type: 'group',
					action: 'delete',
					groip_id: id
				},
				success: function(data) {
					if(data.request) {
						// Удачно
						if(typeof groups == 'object') {
							groups.destroy();
							groups = false;
						}
						tr.remove();
						renderTables();
					}else{
						// Неудачно
					}
					stopWork();
				},
				error: errorStatus
			});
		}
	}).on('click', ".user_edit", (e) => {
		console.log('Редактировать пользователя');
	}).on('click', ".user_delete", (e) => {
		console.log('Удалить пользователя');
	}).on('click', 'form [type=button], .close_dialog', (e) => {
		// Закрыть форму
		dialogClose();
	}).on('submit', 'form', (e) => {
		let form = e.target;
		switch(form.name){
			// Сначало всё с группами
			case 'edit_group':
				e.preventDefault();
				work();
				$.ajax({
					url: `${url}?a=${aUrl}&id=${idUrl}`,
					method: 'post',
					dataType: 'json',
					data: $(form).serialize(),
					success: function(data) {
						if(data.request){
							// Удачно
							if(typeof groups == 'object') {
								groups.destroy();
								groups = false;
							}
							let aBtn = $('td .group_edit[data-group=' + data.id + ']');
							let tds = $('td', aBtn.closest('tr'));
							if(tds[1]) {
								$(tds[1]).text(data.name);
								dialogClose();
							}
							renderTables();
							data.request && setTimeout(alert, 100, `${data.message}\n${data.id}: ${data.name}`);
						}else{
							dialogClose();
							setTimeout(alert, 100, `${data.message}`);
						}
						stopWork();
					},
					error: errorStatus
				});
				return !1;
				break;
			case 'insert_group':
				e.preventDefault();
				work();
				$.ajax({
					url: `${url}?a=${aUrl}&id=${idUrl}`,
					method: 'post',
					dataType: 'json',
					data: $(form).serialize(),
					success: function(data) {
						if(data.request){
							let group_id = data.id,
								group_name = data.name;
							// Добавить к таблице
							let tr = $(`<tr><td>${group_id}</td><td>${group_name}</td><td><div class="btn-group"><a class="btn btn-success group_edit" title="${LANG_SENDMAIL["mailsend.groups_table_edit_group"]}" data-group="${group_id}"><i class="fas fa-user-edit"></i></a>&nbsp;<a class="btn btn-danger group_delete" title="${LANG_SENDMAIL["mailsend.groups_table_delete_group"]}" data-group="${group_id}"><i class="fas fa-user-times"></i></a></div></td></tr>`);
							if(typeof groups == 'object') {
								groups.destroy();
								groups = false;
							}
							let groupTable = $("table.grid-groups tbody");
							groupTable.append(tr);
							dialogClose();
							renderTables();
							setTimeout(alert, 100, `${data.message}\n\n${data.id}. ${data.name}`);
						}else{
							dialogClose();
							setTimeout(alert, 100, `${data.message}`);
						}
						stopWork();
					},
					error: errorStatus
				});
				return !1;
				break;
			case 'delete_group':
				break;
			default:
				// Закрыть форму
				dialogClose();
				break;
		}
	});

	function renderTables() {
		if(typeof users == 'object') {
			users.destroy();
			users = false;
		}
		if(typeof groups == 'object') {
			groups.destroy();
			groups = false;
		}
		users = new DataTable(`table.grid-users`, {
			// Колонки
			columns: [
				{ name: 'id' },
				{ name: 'name' },
				{ name: 'email' },
				{ name: 'groups_id' },
				{ name: 'groups_name' },
				{ name: 'unsubscribe' },
				{ name: 'actions' }
			],
			// Настройки по колонкам
			columnDefs : [
				// Разрешено для первой колонки поиск, сортировка
				{
					'searchable'    : !0,
					'targets'       : [1,2,4],
					'orderable'     : !0
				},
				// Запрещено для последующих колонок поиск, сортировка
				{
					'searchable'    : !1,
					'targets'       : [0,3,5,6],
					'orderable'     : !1
				},
			],
			// Разрешена сортировка
			ordering: !0,
			// Разрешаем запоминание всех свойств
			stateSave: !0,
			stateSaveCallback: function (settings, data) {
				localStorage.setItem(
					'DataTables_' + settings.sInstance + '_users',
					JSON.stringify(data)
				);
			},
			stateLoadCallback: function (settings) {
				return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance + '_users'));
			},
			lengthMenu: [
				[25, 50, 100, 300, 500, 1000, -1],
				['по 25', 'по 50', 'по 100', 'по 300', 'по 500', 'по 1000', 'Все']
			],
			layout: {
				topStart: [
					'pageLength',
					'search'
				],
				topEnd: {
					buttons: [
						{
							extend: 'userAdd',
							attr: {
								title: `Добавить пользователя`
							},
							text: '<i class="far fa-address-card"></i><span>Добавить пользователя</span>',
							action: function ( e, dt, node, config ) {
								/**
								console.log( {
									'e=>': e,
									'dt=>': dt,
									'node=>': node,
									'config=>': config
								} );
								*/
							}
						},
						{
							extend: 'userImport',
							attr: {
								title: `Импорт пользователей рассылки из Excel файла (*.xlsx)`
							},
							text: '<i class="far fa-file-excel"></i><span>Импорт из Excel (*.xlsx)</span>',
							action: function ( e, dt, node, config ) {
								/**
								console.log( {
									'e=>': e,
									'dt=>': dt,
									'node=>': node,
									'config=>': config
								} );
								*/
							}
						},
						// Кнопка экспорта XLSX
						{
							extend: 'excel',
							text: '<i class="icon-file-excel"></i><span>Экспорт в XLSX</span>',
							download: 'Экспорт пользователей рассылки',
							filename: `Экспорт пользователей рассылки`,
							title: "Экспорт пользователей рассылки",
							attr: {
								title: `Экспорт пользователей рассылки в XLSX файл`
							},
							sheetName: `${SEND_MAIL}`,
							customize: function (xlsx) {
								let date = new Date();
								let dateISO = date.toISOString();
								// Создаём xml файлы для свойств документа (метатеги)
								xlsx["_rels"] = {};
								xlsx["_rels"][".rels"] = $.parseXML(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">` +
										`<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>` +
										`<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>` +
										`<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>` +
									`</Relationships>`);
								xlsx["docProps"] = {};
								xlsx["docProps"]["core.xml"] = $.parseXML(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">` +
										// Заголовок
										`<dc:title>Экспорт пользователей рассылки</dc:title>` +
										// Тема
										`<dc:subject>Экспорт пользователей рассылки</dc:subject>` +
										// Создатель
										`<dc:creator>ProjectSoft</dc:creator>` +
										// Теги
										`<cp:keywords />` +
										// Описание
										`<dc:description>Экспорт пользователей рассылки</dc:description>` +
										// Последнее изменение
										`<cp:lastModifiedBy>ProjectSoft</cp:lastModifiedBy>` +
										// Дата создания - время создания
										`<dcterms:created xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:created>` +
										// Дата изменеия - время создания
										`<dcterms:modified xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:modified>` +
										// Категория
										`<cp:category>Экспорт пользователей рассылки</cp:category>` +
									`</cp:coreProperties>`);
								xlsx["docProps"]["app.xml"] = $.parseXML(
									`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">` +
										`<Application>Microsoft Excel</Application>` +
										`<DocSecurity>0</DocSecurity>` +
										`<ScaleCrop>false</ScaleCrop>` +
										`<HeadingPairs>` +
											`<vt:vector size="2" baseType="variant">` +
												`<vt:variant>` +
													`<vt:lpstr>Листы</vt:lpstr>` +
												`</vt:variant>` +
												`<vt:variant>` +
													`<vt:i4>1</vt:i4>` +
												`</vt:variant>` +
											`</vt:vector>` +
										`</HeadingPairs>` +
										`<TitlesOfParts>` +
											`<vt:vector size="1" baseType="lpstr">` +
												`<vt:lpstr>Экспорт пользователей рассылки</vt:lpstr>` +
											`</vt:vector>` +
										`</TitlesOfParts>` +
										// Руководитель - автор компонента
										`<Manager>ProjectSoft</Manager>` +
										// Организация - автор компонента
										`<Company>STUDIONIONS</Company>` +
										`<LinksUpToDate>false</LinksUpToDate>` +
										`<SharedDoc>false</SharedDoc>` +
										`<HyperlinkBase>${url}</HyperlinkBase>` +
										`<HyperlinksChanged>false</HyperlinksChanged>` +
										`<AppVersion>16.0300</AppVersion>` +
									`</Properties>`
								);
								let contentType = xlsx["[Content_Types].xml"];
								let Types = contentType.querySelector('Types');
								let Core = contentType.createElement('Override');
								Core.setAttribute("PartName", "/docProps/core.xml");
								Core.setAttribute("ContentType", "application/vnd.openxmlformats-package.core-properties+xml");
								Types.append(Core);

								let App = contentType.createElement('Override');
								App.setAttribute("PartName", "/docProps/app.xml");
								App.setAttribute("ContentType", "application/vnd.openxmlformats-officedocument.extended-properties+xml");
								Types.append(App);
								xlsx["[Content_Types].xml"] = contentType;
							},
							action: function (e, dt, node, config, cb) {
								DataTable.ext.buttons.excelHtml5.action.call(
									tdThis,
									e,
									dt,
									node,
									config,
									cb
								);
							}
						},
						// Кнопка экспорта PDF
						{
							extend: 'pdf',
							text: '<i class="icon-file-pdf"></i><span>Экспорт в PDF</span>',
							title: "Экспорт пользователей рассылки",
							attr: {
								title: `Экспорт пользователей рассылки в PDF файл`
							},
							download: 'Экспорт пользователей рассылки в PDF файл',
							orientation: `landscape`,
							// Кастомизируем вывод
							customize: function (doc) {
								let date = new Date();
								let dateISO = date.toISOString();
								let title = [
									`Экспорт пользователей рассылки`
								];
								// Используемый язык экспорта
								doc.language = 'ru-RU';
								// Метатеги экспорта
								doc.info = {
									title: 'Экспорт пользователей рассылки',
									author: 'ProjectSoft',
									subject: 'Экспорт пользователей рассылки',
									keywords: 'Экспорт пользователей рассылки',
									creator: 'ProjectSoft',
									producer: 'ProjectSoft',
									modDate: `${dateISO}`
								};
								// Колонтитулы
								// Верхний
								doc.header = {
									columns: [
										{
											text: `${url}`,
											margin: [15, 15, 15, 15],
											alignment: 'left'
										},
										{
											text: getDateTime((new Date()).getTime()),
											margin: [15, 15, 15, 15],
											alignment: 'right'
										}
									]
								};
								// Нижний
								doc.footer = function(currentPage, pageCount) {
									return [
										{
											text: currentPage.toString() + ' из ' + pageCount,
											margin: [15, 15, 15, 15],
											alignment: 'center'
										}
									];
								};
								// Текст контента.
								doc.content[0].text = title.join('\r\n');
							},
							action: function (e, dt, node, config, cb) {
								DataTable.ext.buttons.pdfHtml5.action.call(
									this,
									e,
									dt,
									node,
									config,
									cb
								);
							}
						}
					]
				}
			},
			language: {
				url: `/assets/modules/MailSend/js/ru_RU.json`,
			}
		});
		groups = new DataTable(`table.grid-groups`, {
			// Колонки
			columns: [
				{ name: 'id' },
				{ name: 'name' },
				{ name: 'actions' }
			],
			// Настройки по колонкам
			columnDefs : [
				// Разрешено для первой колонки поиск, сортировка
				{
					'searchable'    : !0,
					'targets'       : [1],
					'orderable'     : !0
				},
				// Запрещено для последующих колонок поиск, сортировка
				{
					'searchable'    : !1,
					'targets'       : [0,2],
					'orderable'     : !1
				},
			],
			// Разрешена сортировка
			ordering: !0,
			// Разрешаем запоминание всех свойств
			stateSave: !0,
			stateSaveCallback: function (settings, data) {
				localStorage.setItem(
					'DataTables_' + settings.sInstance + '_groups',
					JSON.stringify(data)
				);
			},
			stateLoadCallback: function (settings) {
				return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance + '_groups'));
			},
			lengthMenu: [
				[25, 50, 100, -1],
				['по 25', 'по 50', 'по 100', 'Все']
			],
			layout: {
				topStart: [
					'pageLength',
					'search'
				],
				topEnd: {
					buttons: [
						{
							extend: 'groupAdd',
							className: 'btn btn-success insert_group',
							text: '<i class="icon-layer-plus"></i><span>Добавить группу</span>',
							attr: {
								title: `Добавить группу`
							},
							action: function ( e, dt, node, config ) {
								console.log('Добавить группу');
								work();
								$.ajax({
									url: `${url}?a=${aUrl}&id=${idUrl}`,
									method: 'post',
									dataType: 'html',
									data: {
										action: 'edit',
										type: 'group'
									},
									success: function(data) {
										let $html = $(data);
										dialog = $html[0];
										document.body.append(dialog);
										document.body.classList.add('scroll-lock');
										dialog.showModal();
										stopWork();
									},
									error: errorStatus
								});
							}
						},
						// Кнопка экспорта XLSX
						{
							extend: 'excel',
							text: '<i class="icon-file-excel"></i><span>Экспорт в XLSX</span>',
							title: "Экспорт Групп рассылки",
							attr: {
								title: `Экспорт Групп рассылки в XLSX файл`
							},
							download: 'Экспорт Групп рассылки',
							filename: `Экспорт Групп рассылки`,
							sheetName: `${SEND_MAIL}`,
							customize: function (xlsx) {
								let date = new Date();
								let dateISO = date.toISOString();
								// Создаём xml файлы для свойств документа (метатеги)
								xlsx["_rels"] = {};
								xlsx["_rels"][".rels"] = $.parseXML(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">` +
										`<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>` +
										`<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>` +
										`<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>` +
									`</Relationships>`);
								xlsx["docProps"] = {};
								xlsx["docProps"]["core.xml"] = $.parseXML(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">` +
										// Заголовок
										`<dc:title>Экспорт Групп рассылки</dc:title>` +
										// Тема
										`<dc:subject>Экспорт Групп рассылки</dc:subject>` +
										// Создатель
										`<dc:creator>ProjectSoft</dc:creator>` +
										// Теги
										`<cp:keywords />` +
										// Описание
										`<dc:description>Экспорт Групп рассылки</dc:description>` +
										// Последнее изменение
										`<cp:lastModifiedBy>${dateISO}</cp:lastModifiedBy>` +
										// Дата создания - время создания
										`<dcterms:created xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:created>` +
										// Дата изменеия - время создания
										`<dcterms:modified xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:modified>` +
										// Категория
										`<cp:category>Экспорт Групп рассылки</cp:category>` +
									`</cp:coreProperties>`);
								xlsx["docProps"]["app.xml"] = $.parseXML(
									`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">` +
										`<Application>Microsoft Excel</Application>` +
										`<DocSecurity>0</DocSecurity>` +
										`<ScaleCrop>false</ScaleCrop>` +
										`<HeadingPairs>` +
											`<vt:vector size="2" baseType="variant">` +
												`<vt:variant>` +
													`<vt:lpstr>Листы</vt:lpstr>` +
												`</vt:variant>` +
												`<vt:variant>` +
													`<vt:i4>1</vt:i4>` +
												`</vt:variant>` +
											`</vt:vector>` +
										`</HeadingPairs>` +
										`<TitlesOfParts>` +
											`<vt:vector size="1" baseType="lpstr">` +
												`<vt:lpstr>Экспорт Групп рассылки</vt:lpstr>` +
											`</vt:vector>` +
										`</TitlesOfParts>` +
										// Руководитель - автор компонента
										`<Manager>ProjectSoft</Manager>` +
										// Организация - автор компонента
										`<Company>STUDIONIONS</Company>` +
										`<LinksUpToDate>false</LinksUpToDate>` +
										`<SharedDoc>false</SharedDoc>` +
										`<HyperlinkBase>${url}</HyperlinkBase>` +
										`<HyperlinksChanged>false</HyperlinksChanged>` +
										`<AppVersion>16.0300</AppVersion>` +
									`</Properties>`
								);
								let contentType = xlsx["[Content_Types].xml"];
								let Types = contentType.querySelector('Types');
								let Core = contentType.createElement('Override');
								Core.setAttribute("PartName", "/docProps/core.xml");
								Core.setAttribute("ContentType", "application/vnd.openxmlformats-package.core-properties+xml");
								Types.append(Core);

								let App = contentType.createElement('Override');
								App.setAttribute("PartName", "/docProps/app.xml");
								App.setAttribute("ContentType", "application/vnd.openxmlformats-officedocument.extended-properties+xml");
								Types.append(App);
								xlsx["[Content_Types].xml"] = contentType;
							},
							action: function (e, dt, node, config, cb) {
								DataTable.ext.buttons.excelHtml5.action.call(
									this,
									e,
									dt,
									node,
									config,
									cb
								);
							}
						},
						// Кнопка экспорта PDF
						{
							extend: 'pdf',
							text: '<i class="icon-file-pdf"></i><span>Экспорт в PDF</span>',
							download: 'Экспорт Групп рассылки',
							filename: `Экспорт Групп рассылки`,
							title: "Экспорт Групп рассылки",
							attr: {
								title: `Экспорт Групп рассылки в PDF файл`
							},
							// Кастомизируем вывод
							customize: function (doc) {
								let date = new Date();
								let dateISO = date.toISOString();
								let title = [
									`Экспорт Групп рассылки`
								];
								// Используемый язык экспорта
								doc.language = 'ru-RU';
								// Метатеги экспорта
								doc.info = {
									title: 'Экспорт Групп рассылки',
									author: 'ProjectSoft',
									subject: 'Экспорт Групп рассылки',
									keywords: 'Экспорт Групп рассылки',
									creator: 'ProjectSoft',
									producer: `ProjectSoft`,
									modDate: `${dateISO}`
								};
								// Колонтитулы
								// Верхний
								doc.header = {
									columns: [
										{
											text: `${url}`,
											margin: [15, 15, 15, 15],
											alignment: 'left'
										},
										{
											text: getDateTime((new Date()).getTime()),
											margin: [15, 15, 15, 15],
											alignment: 'right'
										}
									]
								};
								// Нижний
								doc.footer = function(currentPage, pageCount) {
									return [
										{
											text: currentPage.toString() + ' из ' + pageCount,
											margin: [15, 15, 15, 15],
											alignment: 'center'
										}
									];
								};
								// Текст контента.
								doc.content[0].text = title.join('\r\n');
							},
							action: function (e, dt, node, config, cb) {
								DataTable.ext.buttons.pdfHtml5.action.call(
									this,
									e,
									dt,
									node,
									config,
									cb
								);
							}
						}
					]
				}
			},
			language: {
				url: `/assets/modules/MailSend/js/ru_RU.json`,
			}
		});
	}
	renderTables();
}(jQuery))
