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

	/**
	 * Slick
	 */
	$('#slick').slick({
		infinite: true,
		slidesToShow: 1,
		slidesToScroll: 1,
		autoplay: true,
		autoplaySpeed: 5000,
		dots: false,
		arrows: false
	});
	$(document)
		.on('click', function(e){
			$('[role="navigation"]').removeClass('open-menu');
			$('button i').removeClass('icon-menu-close').addClass('icon-menu-open')
		})
		.on('click', '[data-menu-href]', function(e){
			e.preventDefault();
			let _this = $(e.target),
				url = _this.data('menu-href');
			window.location.href = url;
			return !1;
		})
		.on('click', '[role="navigation"] button', function(e){
			e.preventDefault();
			let _this = $(e.target),
				_i = $('i', _this),
				parent = _this.parent('[role="navigation"]');
			parent.hasClass('open-menu') ? (
				parent.removeClass('open-menu'),
				_i.removeClass('icon-menu-close').addClass('icon-menu-open')
			) : (
				parent.addClass('open-menu'),
				_i.addClass('icon-menu-close').removeClass('icon-menu-open')
			);
			return !1;
		})
		.on("click", "a[href$='.pdf'], a[href$='.docx'], a[href$='.xlsx']", function(e){
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
	new isvek.Bvi({
		target: '.eya-panel',
		builtElements: true,
		images: true,
		lang: 'ru-RU',
		panelFixed: true,
		speech: false,
		fontSize: 14
	});
}(jQuery));