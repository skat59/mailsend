(function($){
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
	url = window.location.origin + "/";

	$(document).on('click', ".group_edit", (e) => {
		console.log('Редактировать группу');
	}).on('click', ".group_delete", (e) => {
		console.log('Удалить группу');
	}).on('click', ".user_edit", (e) => {
		console.log('Редактировать пользователя');
	}).on('click', ".user_delete", (e) => {
		console.log('Удалить пользователя');
	});
	new DataTable(`table.grid-users`, {
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
					// Кнопка экспорта XLSX
					{
						extend: 'excel',
						text: 'Экспорт в XLSX',
						download: '',
						filename: `Экспорт ${componentName} в XLSX`,
						title: `Экспорт пользователей рассылки`,
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
									`<cp:lastModifiedBy>${dateISO}</cp:lastModifiedBy>` +
									// Дата создания - время создания
									`<dcterms:created xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:created>` +
									// Дата изменеия - время создания
									`<dcterms:modified xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:modified>` +
									// Категория
									`<cp:category>${componentName}</cp:category>` +
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
											`<vt:lpstr>$${componentName}</vt:lpstr>` +
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
						text: 'Экспорт в PDF',
						download: '',
						filename: `Экспорт ${componentName} в PDF`,
						title: `${componentName}`,
						orientation: `landscape`,
						// Кастомизируем вывод
						customize: function (doc) {
							let date = new Date();
							let dateISO = date.toISOString();
							let title = [
								`${componentName}.`
							];
							// Используемый язык экспорта
							doc.language = 'ru-RU';
							// Метатеги экспорта
							doc.info = {
								title: title.join(' '),
								author: componentName,
								subject: title.join(' '),
								keywords: title.join(' '),
								creator: `${componentName}`,
								producer: `${userName}`,
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
	new DataTable(`table.grid-groups`, {
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
					// Кнопка экспорта XLSX
					{
						extend: 'excel',
						text: 'Экспорт в XLSX',
						download: '',
						filename: `Экспорт Групп рассылки в XLSX`,
						title: `${url}`,
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
									`<cp:category>${componentName}</cp:category>` +
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
											`<vt:lpstr>$${componentName}</vt:lpstr>` +
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
						text: 'Экспорт в PDF',
						download: '',
						filename: `Экспорт ${componentName} в PDF`,
						title: `${componentName}`,
						// Кастомизируем вывод
						customize: function (doc) {
							let date = new Date();
							let dateISO = date.toISOString();
							let title = [
								`${componentName}.`
							];
							// Используемый язык экспорта
							doc.language = 'ru-RU';
							// Метатеги экспорта
							doc.info = {
								title: title.join(' '),
								author: componentName,
								subject: title.join(' '),
								keywords: title.join(' '),
								creator: `${componentName}`,
								producer: `${userName}`,
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
}(jQuery))
