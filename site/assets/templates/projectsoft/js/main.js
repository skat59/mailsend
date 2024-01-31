!(function(){
	if(navigator.clipboard){
		!document.body.classList.contains("clipboard") && document.body.classList.add('clipboard');
	}

	/**
	 * Изменяем location.hash при клике по табу
	 */
	function showEmoji(input) {
		if(Boolean(input)){
			if(input.tagName == 'INPUT'){
				let id = input.id;
				if(id) {
					window.location.hash = id;
				}
			}
		}
	}

	/**
	 * Очистка после копирования
	 * если нет поддержки navigator.clipboard
	 */
	function clearSelection(span){
		if (window.getSelection) {
			window.getSelection().removeAllRanges();
		} else if (document.selection) {
			document.selection.empty();
		}
		span.setAttribute("data-copy", "Copied");
		setTimeout(function(){
			span.classList.remove('copy');
			span.setAttribute("data-copy", "");
		}, 500);
	}
	
	/**
	 * Клик по иконке
	 * Копирование
	 */
	document.addEventListener('click', async function(e){
		if(e.target.classList.contains('emoji--icon')){
			let span = e.target,
				html = "", rng;
			if(navigator.clipboard){
				span.classList.remove('copy');
				span.classList.remove('error');
				span.classList.add('copy');
				navigator.clipboard.writeText(span.firstChild.nodeValue).then(function(){
					span.setAttribute("data-copy", "Copied");
					setTimeout(function(){
						span.classList.remove('copy');
						span.setAttribute("data-copy", "");
					}, 500);
				}).catch(function(){
					span.setAttribute("data-copy", "ERROR");
					span.classList.remove('copy');
					span.classList.add('error');
					setTimeout(function(){
						span.classList.remove('error');
						span.setAttribute("data-copy", "");
					}, 1000);
				});
			}else{
				span.classList.remove('copy');
				span.classList.remove('error');
				span.classList.add('copy');
				let rnd, sel
				if (document.createRange) {
					rng = document.createRange();
					rng.selectNode(span)
					sel = window.getSelection();
					sel.removeAllRanges();
					sel.addRange(rng);
					document.execCommand("copy");
					clearSelection(span);
				} else {
					rng = document.body.createTextRange();
					rng.moveToElementText(target);
					rng.select();
					document.execCommand("copy");
					clearSelection(span);
				}
			}
		}
	});

	/**
	 * Клик по табу
	 */
	document.addEventListener("input", function(e){
		if(e.target.name && e.target.name == "emoji"){
			e.preventDefault();
			let input = e.target;
			let value = e.target.value;
			let wrap = input.closest('.emoji-wrapp');
			let emoji_tabs = input.closest('.emoji-tabs');
			let tab = input.closest('.tabs-item');
			let tabs_item = [...emoji_tabs.querySelectorAll('.tabs-item')];
			let tabs_content = [...wrap.querySelectorAll('.tabs-content')]
			tabs_item.forEach(function(a, b, c){
				a.classList.remove('active');
			});
			tabs_content.forEach(function(a, b, c){
				a.classList.remove('active');
			});
			wrap.querySelector("#emoji-" + value).classList.add('active');
			tab.classList.add('active');
			showEmoji(input);
			return !1;
		}
	});

	/**
	 * location.hash при загрузке
	 * Функция не идеальна. Нужна дороботка
	 */
	let idHash = window.location.hash;
	let inp = document.querySelector(idHash);
	if(Boolean(inp)){
		if(inp.tagName == 'INPUT'){
			inp.checked = true;
			let event = new Event('input', {
				bubbles: true,
				cancelable: true,
				target: inp
			});
			inp.dispatchEvent(event);
			setTimeout(function(){inp.closest('.tabs-item').scrollIntoView({behavior: "smooth"});}, 200);
		}
	}
})();

(function($){
	// Default fancybox options
	var $style = $("<style></style>")[0];
	$("head").append($style);
	$.fancybox.defaults.parentEl = ".fancybox__wrapper";
	$.fancybox.defaults.transitionEffect = "circular";
	$.fancybox.defaults.transitionDuration = 500;
	$.fancybox.defaults.lang = "ru";
	$.fancybox.defaults.i18n.ru = {
		CLOSE: "Закрыть",
		NEXT: "Следующий",
		PREV: "Предыдущий",
		ERROR: "Запрошенный контент не может быть загружен.<br/>Повторите попытку позже.",
		PLAY_START: "Начать слайдшоу",
		PLAY_STOP: "Остановить слайдшоу",
		FULL_SCREEN: "Полный экран",
		THUMBS: "Миниатюры",
		DOWNLOAD: "Скачать",
		SHARE: "Поделиться",
		ZOOM: "Увеличить"
	};
	$.fancybox.defaults.onInit = function(instance, slide) {
		if(!$.fancybox.isMobile && document.body.scrollHeight > window.innerHeight) {
			let wr = window.innerWidth - (document.body.clientWidth - 2);
			$style.innerText = `body.fancybox-active.compensate-for-scrollbar .bodywrapp::after {background-position: calc(100% - ${wr}px) 0;}`;
		}
	};
	$.fancybox.defaults.afterClose = function(instance, slide) {
		$style.innerText = ``;
	};

	$(document)
		/**
		 * Просмотр PDF, DOCX, XLSX
		 */
		.on("click", "a[href$='.pdf'], a[href$='.docx'], a[href$='.xlsx']", function(e){
			// Файлы  на сервере
			var base = window.location.origin + '/',
				reg = new RegExp("^" + base),
				href = this.href,
				test = this.href,
				go = false,
				arr = href.split('.'),
				ext = arr.at(-1).toLowerCase(),
				options = {};
			if(reg.test(href)){
				$(this).data('google', go);
				$(this).data('options', options);
				switch (ext){
					case "pdf":
						href = href.replace(base, '');
						go = window.location.origin + '/viewer/pdf_viewer/?file=' + href;
						options = {
							src: go,
							opts : {
								afterShow : function( instance, current ) {
									$(".fancybox-content").css({
										height: '100% !important',
										overflow: 'hidden'
									}).addClass('pdf_viewer');
								},
								afterLoad : function( instance, current ) {
									$(".fancybox-content").css({
										height: '100% !important',
										overflow: 'hidden'
									}).addClass('pdf_viewer');
								},
							}
						};
						e.preventDefault();
						$.fancybox.open(options);
						return !1;
						break;
					case "xlsx":
						go = window.location.origin + '/viewer/xlsx_viewer/?file=' + test;
						options = {
							src: go,
							type: 'iframe',
							opts : {
								afterShow : function( instance, current ) {
									$(".fancybox-content").css({
										height: '100% !important',
										overflow: 'hidden'
									}).addClass('xlsx_viewer');
								},
								afterLoad : function( instance, current ) {
									$(".fancybox-content").css({
										height: '100% !important',
										overflow: 'hidden'
									}).addClass('xlsx_viewer');
								},
							}
						};
						e.preventDefault();
						$.fancybox.open(options);
						return !1;
						break;
					case "docx":
						go = window.location.origin + '/viewer/docx_viewer/?file=' + test;
						options = {
							src: go,
							type: 'iframe',
							opts : {
								afterShow : function( instance, current ) {
									$(".fancybox-content").css({
										height: '100% !important',
										overflow: 'hidden'
									}).addClass('docx_viewer');
								},
								afterLoad : function( instance, current ) {
									$(".fancybox-content").css({
										height: '100% !important',
										overflow: 'hidden'
									}).addClass('docx_viewer');
								},
							}
						};
						e.preventDefault();
						$.fancybox.open(options);
						return !1;
						break;
				}
			}else {
				e.preventDefault();
				window.open(href);
				return !1;
			}
	})
	/**
	 * Просмотр изображений
	 */
	.on("click", "a[href$='.jpg'], a[href$='.jpeg'], a[href$='.png'], a[href$='.gif']", function(e){
		// Изображения  на сервере
		var base = window.location.origin,
			reg = new RegExp("^" + base),
			href = this.href,
			$this = $(this);
		if(reg.test(href)){
			if(!$this.hasClass("fancybox")){
				if(typeof $this.data("fancybox") !== "string") {
					e.preventDefault();
					$.fancybox.open({
						src: href
					});
					return !1;
				}
			}
		}
	});
}(jQuery));
