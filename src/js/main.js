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
	function inpScroltoView(inp) {
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

	let idHash = window.location.hash || '#nohash';
	let inp;
	if(Boolean(inp = document.querySelector(idHash))){
		inpScroltoView(inp);
	}
})();
