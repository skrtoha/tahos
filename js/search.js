$(function(){
	$("#in_stock_only").styler();
	$('#offers-filter-form button').on('click', function(e){
		e.preventDefault();
		var price_from = $('#price-from').val();
		var price_to = $('#price-to').val();
		var time_from = $('#time-from').val();
		var time_to = $('#time-to').val();
		var in_stock_only = $('#in_stock_only').is(':checked') ? 1 : 0;
		var offers_filter = {"offers_filter": {
			"price_from": price_from,
			"price_to": price_to,
			"time_from": time_from,
			"time_to": time_to,
			"in_stock_only": in_stock_only
		}};
		$.cookie('offers_filter', JSON.stringify(offers_filter), cookieOptions);
		console.log('offers_filter: ' + $.cookie('offers_filter'));
		window.location.reload();
	});
	$(".button-row button").click(function(event) {
		var elem = $(this);
		var query = elem.attr('query');
		var type = elem.attr('type');
		// $('#popup').css('display', 'flex');
		$.ajax({
			type: "POST",
			url: "/ajax/all_offers.php",
			data: "query=" + query + "&type=" + type,
			success: function(msg){
				elem.closest(".button-row").prev().toggle().html(msg);
				elem.text(function(i, text){
					return text === "Остальные предложения" ? "Свернуть" : "Остальные предложения";
				});
				// alert(msg);
				// $('#popup').css('display', 'none');
			} 
		});
	});
	$(document).on('click', '.product-popup-link', function(e){
		e.preventDefault();
		var item_id = $(this).attr('item_id');
		$.ajax({
			type: "POST",
			url: "/ajax/item_full.php",
			data: 'id=' + item_id,
			success: function(msg){
				$('#mgn_popup').html(msg);
				$.magnificPopup.open({
					type: 'inline',
					preloader: false,
					mainClass: 'product-popup-wrap',
					items: {
						src: '#mgn_popup'
					},
					callbacks: {
						beforeOpen: function() {
							if($(window).width() < 700) {
								this.st.focus = false;
							} else {
								this.st.focus = '#name';
							}
						},
						open: function() {
							//change main pic
							$("#gallery img").on("click", function(event) {
								var imgsrc = $(this).attr("data-big-img");
								$("#main-pic img").attr("src", imgsrc);
								$("#main-pic img").attr("data-zoom-image", imgsrc);
							});
							$("#main-pic img").elevateZoom({
								zoomType: "inner",
								cursor: "crosshair"
							});
							$.ionTabs(".product-popup-tabs",{
								type: "storage"
							});
							$('#gallery').owlCarousel({
								loop: true,
								margin: 5,
								nav: true,
								dots: false,
								items: 3
							});
							$("#gallery img").on("click", function(event) {
								var imgsrc = $(this).attr("data-big-img");
								$("#main-pic img").attr("src", imgsrc);
								$("#main-pic img").attr("data-zoom-image", imgsrc);
							});
							$("#main-pic img").elevateZoom({
								zoomType: "inner",
								cursor: "crosshair"
							});
							// hide status form by clicking outside
						}
					}
				});
			} 
		})
	});
	$(document).on('click', '.hit-list-table tr:not(.notFound):not(.searchFromOtherProviders):nth-child(n + 2)', function(){
		var item_id = $(this).attr('item_id');
		var article = $(this).attr('article');
		var href = "/article/" + item_id + "-" + article;
		if ($(this).attr('is_armtek')) document.location.href = '/search/armtek/' + item_id;
		else document.location.href = href;
	})
	if ($('tr.notFound.removable').size()){
		$.ajax({
			type: 'get',
			url: document.location.href,
			beforeSend: function(){
				show_message('Начат поиск по другим складам');
			},
			success: function(response){
				show_message('Проверка других складов выполена!');
				// console.log(response);
				if (!response){
					$('tr.notFound.removable td').html('Поиск по поставщикам не дал результатов.');
					return false;
				} 
				var results = JSON.parse(response)
				// console.log(results);
				var str = '';
				for(var brend in results){
					str += 
						'<tr class="searchFromOtherProviders">' +
							'<td>' + brend + '</td>' +
							'<td><a class="articul" href="/search/article/' + $('input[name=search]').val() + '">' + $('input[name=search]').val() + '</a></td>' +
							'<td style="text-align: left">' + results[brend] + '</td>' +
							'<td colspan="2">' +
								'<a href="#">Проверить наличие</a>' +
							'</td>' +
						'</tr>';
				}
				$('table.hit-list-table .notFound').remove();
				$('table.hit-list-table').append(str);
				if ($('tr.searchFromOtherProviders').size() == 1) $('tr.searchFromOtherProviders').click();
			}
		})
	}
	$(document).on('click', '.searchFromOtherProviders', function(e){
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/ajax/common.php',
			data: {
				act: 'addItemFromSearch',
				brend: th.find('td:nth-child(1)').html(),
				article: th.find('a.articul').html(),
				title: th.find('td:nth-child(3)').html()
			},
			success: function(response){
				document.location.href = '/article/' + response + '-' + $('input[name=search]').val();
			}
		})
	})
});