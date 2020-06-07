(function($){
	window['category'] = {
		sliders: new Array(),
		totalNumber: null,
		pageNumber: $('input[name=pageNumber]').val(),
		comparingMode: $('input[name=comparing]').is(':checked') ? true : false,
		filterListTitles: new Array(),
		init: function(){
			$(document).on('click', ".option-panel a[sort]", function(event) {
				event.preventDefault();
				elem = $(this);
				$(".option-panel a").removeClass("active");
				elem.addClass('active');
				if (elem.hasClass('active')) elem.toggleClass('desc');
				// if(!$('.mosaic-view .item_1').length) return false;
				/*if (!sub_id){
					if (!elem.hasClass('desc')) category.setMosaicList(asc);
					else{
						if (desc.mosaic) category.setMosaicList(desc);
						else{
							$.ajax({
								type: "POST",
								url: "/ajax/category_items.php",
								data: 'type=subcategories&category_id=' + $('#category_id').val(),
								success: function(msg){
									return;
									// console.log(msg);
									var res = JSON.parse(msg);
									desc = category.getHtmlMosaicList(res);
									category.setMosaicList(desc);
								}
							})
						}
					}
				}
				else{*/
					setTimeout(category.setItems(), 1);
				// }
			});
			$(document).on('click', '#search-same', function(){
				document.location.href = $(this).attr('href');
			})
			$(".view-switch").click(function() {
				if ($(this).hasClass('active')) return false;
				$(".view-switch").removeClass("active");
				$(this).addClass("active");

				$(".mosaic-view").toggleClass('hidden');
				$(".list-view").toggleClass('hidden');

				category.setHistoryFilter('viewTab=' + $(this).attr("id"));
				category.setPagination();
			});
			$("#sort-change-mobile").click(function(event) {
				event.preventDefault();
				$(".sort-block").toggle();
				var t = ($(this).position().top) + 20;
				var l = ($(this).position().left) - 50;
				$(".sort-block").offset({top: t, left: l});
			});
			$(document).mouseup(function (e)	{
				var container = $(".sort-block");
				if (!container.is(e.target) // if the target of the click isn't the container...
						&& container.has(e.target).length === 0) // ... nor a descendant of the container
				{
					container.hide();
				}
			});
			$(".sort-block a").click(function(event) {
				event.preventDefault();
				$("#sort-change-mobile").text($(this).text()).attr('type', $(this).attr('type'));
				$(".sort-block").hide();
				setTimeout(category.setItems(), 1);
			});
			$("#sort-direction-mobile").click(function(event) {
				$(this).toggleClass('up down');
				setTimeout(category.setItems(), 1);
			});
			$(document).on('click', 'label.filter_value', function(){
				let th = $(this);
				let value = th.find('input').val();
				th
					.closest('.input_box')
					.find('select')
					.removeClass('hidden')
					.find('option[value=' + value + ']')
					.removeClass('added')
					.prop('disabled', false);
				th.remove();
				category.setItems();
				$('select.filter').trigger('refresh');
			})
			$(document).on('click', '#add-to-favorits', function(){
				var e = $(this);
				var data = 'item_id=' + e.attr('item_id');
				if (!e.hasClass('added')) data += '&act=add';
				else data += '&act=delete';
				// console.log(data);
				$.ajax({
					type: "POST",
					url: "/ajax/favorite.php",
					data: data,
					success: function(msg){
						e.toggleClass('added');
						if (e.hasClass('added')) show_message('Успешно добавлено в избранное!');
						else show_message('Успешно удалено из избранного!');
					}
				})
			})
			$(document).on('click', '.to-stock-btn', function(){
				// $('.mfp-wrap').click();
				var e = $(this);
				var store_id = +e.attr('store_id');
				var price = +e.attr('price');
				var item_id = e.attr('item_id');
				var quan = +e.attr('packaging');
				if ($('.login_btn span').html() == 'Войти'){
					$('.login_btn').click();
					show_message('Для добавления товара в корзину необходимо авторизоваться!', 'error');
					return false;
				}
				$.ajax({
					type: "POST",
					url: "/ajax/to_basket.php",
					data: "store_id=" + store_id + '&price=' + price + '&quan=' + quan + '&item_id=' + item_id,
					success: function(msg){
						// console.log(msg);
						// return;
						// console.log(JSON.parse(msg));
						get_basket(JSON.parse(msg));
						if (!$('.cart span').text()) $('.cart').html('<div class="arrow_up"></div><span>' + quan + '</span>');
						else $('.cart span').html(parseInt($('.cart span').text()) + parseInt(quan));
						show_popup_basket();
						setTimeout(function(){
							 if (!$('.cart-popup').is(':hover')) $('.overlay').click();
						}, 2500);
						var curr = +e.find('i').html();
						if (curr) curr += quan;
						else curr = +quan;
						e.html('<i class="goods-counter">' + curr + '</i>');
					} 
				});
			});
			$(document).on('mouseover', '.product-popup .rating i', function(){
				e = $(this);
				if (e.parent().hasClass('no_selectable')) return false;
				e.removeClass('fa-star-o');
				e.addClass('fa-star');
				e.prevAll().removeClass('fa-star-o');
				e.prevAll().addClass('fa-star');
			});
			$(document).on('mouseout', '.product-popup .rating i', function(){
				e = $(this);
				if (e.parent().hasClass('no_selectable')) return false;
				e.removeClass('fa-star');
				e.addClass('fa-star-o');
				e.prevAll().removeClass('fa-star');
				e.prevAll().addClass('fa-star-o');
			})
			$(document).on('click', '.product-popup-link', function(e){
				e.preventDefault();
				var t = e.target;
				if (t.className == 'brend_info') return false;
				var item_id = $(this).attr('item_id');
				$.ajax({
					type: "POST",
					url: "/ajax/item_full.php",
					data: 'id=' + item_id + '&category=1',
					success: function(msg){
						// console.log(msg);
						// return;
						// console.log(JSON.parse(msg));
						$('#mgn_popup').html(category.getFullItem(JSON.parse(msg)));
						$.magnificPopup.open({
							type: 'inline',
							preloader: false,
							mainClass: 'product-popup-wrap',
							items: {
								src: '#mgn_popup'
							},
							callbacks: {
								beforeOpen: function() {
									if($(window).width() < 700) this.st.focus = false;
									else this.st.focus = '#name';
								},
								open: function() {
									$("#gallery img").on("click", function(event) {
										$("#main-pic img").attr("src", $(this).attr("src"));
										$("#main-pic img").attr("data-zoom-image", $(this).attr('data-big-img'));
										// console.log($("#main-pic").html());
									});
									$.ionTabs(".product-popup-tabs",{
										type: "none"
									});
									$(".ionTabs__tab:first-child").click();
									$('#gallery').owlCarousel({
										loop: true,
										margin: 5,
										nav: true,
										dots: false,
										items: 3
									});
								}
							}
						});
					} 
				})
			})
			$(document).on('click', '.product-popup .rating i', function(){
				if ($('.login_btn span').html() == 'Войти') return false;
				var e = $(this);
				if (e.parent().hasClass('no_selectable')) return false;
				var rate = $(this).prevAll().length + 1;
				$.ajax({
					type: "POST",
					url: "/ajax/category.php",
					data: 'table=rating&item_id=' + e.parent().attr('item_id') + '&user_id=' + $('#user_id').val() + '&rate=' + rate,
					success: function(msg){
						// console.log(msg);
						e.parent().addClass('no_selectable');
						e.removeClass('fa-star-o').addClass('fa-star');
						e.prevAll().removeClass('fa-star-o').addClass('fa-star');
					} 
				})
			})
			$('input[name=comparing]').on('change', function(){
				category.filterReset();
				if ($(this).is(':checked')){
					category.comparingMode = true;
					history.pushState(false, false, document.location.href + '&comparing=on');
				} 
				else category.comparingMode = false;
			})
			$('input.slider').each(function(){
				var e = $(this);
				let instance = e.ionRangeSlider({
					type: "double",
					min: e.attr('min'),
					max: e.attr('max'),
					from: e.attr('from'),
					to: e.attr('to'),
					onFinish: function(data){
						e.attr('name', e.attr('freak'));
						setTimeout(category.setItems(), 1);
					}
				})
				category.sliders[e.attr('freak')] = instance.data('ionRangeSlider');
			})
			$('.subcategory').on('change', function(){
				window.location.href = '/category/' + $(this).val();
				return false;
			})
			$('input[type=reset]').on('click', function(){
				category.filterReset();
			})
			$('a.perPage').on('click', function(e){
				e.preventDefault();
				category.setPerPage($(this));
				category.setItems();
			})
			$('select.filter').on('change', function(){
				let select = $(this);
				let value = select.val();
				let title = select.find('option[value=' + value + ']').text();
				select.find('option[value=' + value + ']').prop('disabled', true).addClass('added');
				select.find('option:first-child').prop('selected', true);
				title = title.trim(title);
				select.closest('div.input').next().append(
					'<label class="filter_value">' +
						'<input type="hidden" name="fv[' + select.attr('filter_id') + '][]" value="' + value + '">' +
						title +
						' <span class="icon-cross1"></span>' +
					'</label>'
				)
				select.trigger('refresh');
				category.setItems();
			});
			$('input[name=search]').on('keyup', function(e){
				if (e.keyCode != 13) return false;
				category.setItems();
			})
			category.totalNumber = category.getTotalNumber();
			category.setPagination();
		},
		filterReset: function(){
			$('select.filter option').prop('disabled', false);
			$('select.filter').removeClass('hidden');
			$('input[name=search]').val('');
			$('div.selected').empty();
			$('select.filter option').removeClass('added');
			
			for(let key in category.sliders){
				let slider = category.sliders[key];
				slider.update({
					from: slider.options.min,
					to: slider.options.max
				});
			}

			$('a[sort]').removeClass('active').removeClass('desc');
			$('a[sort]:first-child').addClass('active');

			$('input.slider').removeAttr('name');
			$('select.filter').trigger('refresh');
			category.setPerPage();
			category.pageNumber = 1;
			category.totalNumber = category.getTotalNumber();
			category.comparingMode = false;
			category.setPagination();
			category.setHistoryFilter();
		},
		setPageNumber: function(View){
			let tab = category.getTab();
			if (Object.keys(category.paginationContainer).length == 0) return $('div.' + tab + ' input[name=pageNumber]').val();
			else return category.paginationContainer.pagination('getSelectedPageNum');
		},
		setPagination: function(){
			$container = $('div.' + category.getViewTab());
			let object = {
				pageNumber: category.pageNumber,
				dataSource: '/ajax/get_items.php?acts[]=items&' + category.getFormData(),
				className: 'paginationjs-big',
				locator: 'items',
				totalNumber: category.totalNumber,
				pageSize: $('a.perPage.checked').text(),
				ajax: {
					beforeSend: function(){
						$container.find('.goods').empty();
					},
				},
				callback: function(data, pagination){
					let filterTitles = new Array();
					if (category.getViewTab() == 'mosaic-view'){
						for(var key in data){
							$container.find('.goods').append(category.getHtmlMosaicView(data[key]));
						}
					}
					else $container.find('.goods').append(category.getHtmlListView(data));
					category.setHistoryPagination(pagination.pageNumber);
					if ($(document).width() <= 700){
						window.scrollTo(0, $('.catalogue.catalogue-filter .filter-form').height() + 120);
					}
					else window.scrollTo(0, 0);
				}
			};
			$container.find('.pagination-container').pagination(object);
		},
		getTotalNumber: function(){
			$.ajax({
				type: 'get',
				url: '/ajax/get_items.php?acts[]=totalNumber',
				data: category.getFormData(),
				async: false,
				success: function(response){
					let result = JSON.parse(response);
					category.totalNumber = result.totalNumber;
				}
			})
			return category.totalNumber;
		},
		setPerPage: function(object = null){
			$('a.perPage').removeClass('checked');
			if (object) object.addClass('checked');
			else{
				$('a.perPage:nth-child(2)').addClass('checked');
			} 
		},
		getFormData: function (){
			let data = $('#filter').serialize();
			data += '&perPage=' + $('a.perPage.checked').text();
			data += category.getSort();
			data += '&viewTab=' + category.getViewTab();
			return data;
		},
		getSort: function(){
			let output = '&sort=';
			if ($(document).width() > 700){
				let $elem = $('a[sort].active');
				output += $elem.attr('sort');
				if ($elem.hasClass('desc')) output += '&direction=desc';
				else output += '&direction=asc';
			}
			else{
				output += $('#sort-change-mobile').attr('type');
				if ($('#sort-direction-mobile').hasClass('up')) output += '&direction=asc';
				else output += '&direction=desc';
			}
			return output;
		},
		getFullItem: function (i){
			var s = '';
			var item = i.item;
			var b = new Object();
			var del = new Object();
			if (typeof i.min != 'undefined'){
				b = i.min.price;
				del = i.min.delivery;
			} 
			var d = item.full_desc || item.characteristics || item.applicability;
			if (!d) s = 'float: none; display: block; margin: 0 auto;';
			var str = '' +
			'<div id="div_10">' +
				'<h2 class="title"><b>' + item.brend + '</b> ' + item.article + '</h2>' + 
				'<p>' + item.title + '</p>' +
			'</div>';
			if (Object.keys(b).length){
				str +=
				'<div id="div_table">' +
					'<table id="item_into">' +
						'<tr>' +
							'<td>' + b.delivery + ' дн.</td>' +
							'<td>' + b.user_price + i.designation + '</td>' +
							'<td>' + 
								'<i price="' + b.price + '" store_id="' + b.store_id + 
									'" item_id="' + item.id + '" packaging="' + 
									b.packaging + 
									'" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
				if (+b.in_basket) str += '' + 
									'<i class="goods-counter">' + b.in_basket + '</i>';
						str +=	'</i>' +
							'</td>' +
						'</tr>';
						if (del.price) str += '' +
						'<tr>' +
							'<td>' + del.delivery + ' дн.</td>' +
							'<td>' + del.user_price + i.designation + '</td>' +
							'<td>' + 
								'<i price="' + del.price + '" store_id="' + del.store_id + 
									'" item_id="' + item.id + '" packaging="' + 
									del.packaging + 
									'" class="fa fa-cart-arrow-down to-stock-btn" aria-hidden="true">';
				if (del.in_basket) str += '' + 
									'<i class="goods-counter">' + del.in_basket + '</i>';
						str +=	'</i>' +
							'</td>' +
						'</tr>';
						str += '' +
					'</table>';
			}
			str +=
			'</div>' +
			'<div class="clearfix"></div>' + 
		 	'<div class="gallery-block" style="' + s + '">';
			var is_foto = item.foto ? true : false;
			var c_fotos = Object.keys(i.fotos).length;
			// console.log(c_fotos);
			if (is_foto){
				var src_small = getImgUrl() + '/items/small/' + item.id + '/' + item.foto;
				var src_big = getImgUrl() + '/items/big/' + item.id + '/' + item.foto;
				str += '' +
						'<div id="main-pic">' + 
							'<img src="'+ src_small + '" data-zoom-image="' + src_big + '">' + 
						'</div>';
				if (c_fotos){
					str += '<div id="gallery">';
					for (var k in i.fotos){
						var src_small = getImgUrl() + '/items/small/' + item.id + '/' + i.fotos[k];
						var src_big = getImgUrl() + '/items/big/' + item.id + '/' + i.fotos[k];
						str += '<img src="' + src_small + '" data-big-img="' + src_big + '">';
					}
					str += '</div>';
				}
			} 
			else str += '' +
						'<div id="pic">' + 
							'<img src="/images/no_foto.png">' + 
						'</div>';
			var rating = item.rating ? item.rating - 1 : -1;
			var no_selectable = item.rating ? 'no_selectable' : '';
			s = is_foto ? '' : 'margin-top: 0px';
			str += '<div style="' + s + '" item_id="' + item.id + '" class="rating ' + no_selectable + '">';
			for (var k = 0; k < 5; k++){
				var kkk = k <= rating ? 'fa-star' : 'fa-star-o';
				str += '<i class="fa ' + kkk + '" aria-hidden="true"></i>';
			}
			str += '</div>' +
			'</div>';
			if (d){
				str += '<div class="description-block">';
				str += '<div class="ionTabs product-popup-tabs" data-name="product-popup-tabs">';
				str += '<ul class="ionTabs__head">';
				if (item.full_desc) str += '<li class="ionTabs__tab" data-target="Tab_1_name"><i class="fa fa-question-circle-o" aria-hidden="true"></i></li>';
				if (item.characteristics) str += '<li class="ionTabs__tab" data-target="Tab_2_name"><i class="fa fa-cog" aria-hidden="true"></i></li>';
				if (item.applicability) str += '<li class="ionTabs__tab" data-target="Tab_3_name"><i class="fa fa-wrench" aria-hidden="true"></i></li>';
				str += '</ul>';
				if ($(document).height() > 700){
					if (c_fotos) s = 'height: 304px';
					else s = 'height: 215px';
				}
				str += '<div style="' + s + '" class="ionTabs__body">';
				if (item.full_desc) str += '<div class="ionTabs__item" data-name="Tab_1_name">' + item.full_desc + '</div>';
				if (item.characteristics) str += '<div class="ionTabs__item" data-name="Tab_2_name">' + item.characteristics + '</div>';
				if (item.applicability) str += '<div class="ionTabs__item" data-name="Tab_3_name">' + item.applicability + '</div>';
				str += '<div class="ionTabs__preloader"></div>';
				str += '</div>';
			}
			str +=	'</div>' +
			'</div>' +
			'<div class="clearfix"></div>' +
			'<div class="buttons">' +
				'<button class="brend_info" brend_id="' + item.brend_id + '"><span class="icon_put-in-basket"></span>Информация о бренде</button>';
			if (i.user_id){
				var added = +item.in_favorite ? 'added' : '';
				str += '<button class="' + added + '" item_id="' + item.id + '" user_id="' + i.user_id + '" id="add-to-favorits">' + 
									'<span class="icon_heart"></span>' +
								'</button>';
			}
			str +=	'' +
				'<button href="/article/' + item.id + '-' + item.article + '"  id="search-same"><span class="icon_search"></span>Другие предложения</button>' +
				'<div class="clearfix"></div>' +
			'</div>';
			return str;
		},
		getViewTab: function(){
			let tab;
			$('div.view-switch').each(function(){
				if ($(this).hasClass('active')) tab = $(this).attr('id');
			})
			return tab;
		},
		getDataItems: function(){
			let data = category.getFormData();
			let selectedPageNum;
			let tab = category.getViewTab();

			// if (Object.keys(category.paginationContainer).length == 0) category.pageNumber = $('div.' + tab + ' input[name=pageNumber]').val();
			// else category.pageNumber = category.paginationContainer.pagination('getSelectedPageNum');
			// data += '&pageNumber=' + category.pageNumber;

			// category.setHistory(data);
			let output = '';
			$.ajax({
				type: 'get',
				url: '/ajax/get_items.php',
				processData: false,
				async: false,
				data: data,
				success: function(response){
					output = JSON.parse(response);
				}
			})
			return output;
		},
		setItems: function(){
			$.ajax({
				type: 'get',
				url: '/ajax/get_items.php?acts[]=filters&acts[]=totalNumber&' + category.getFormData(),
				success: function(response){
					let itemsData = JSON.parse(response);
					if (!category.comparingMode) category.setFilters(itemsData.filters);
					else category.hideUterlyEmptySelects();
					category.totalNumber = itemsData.totalNumber;
					category.pageNumber = 1;
					category.setHistoryFilter(category.getFormData());
					category.setPagination();
				}
			})
		},
		hideUterlyEmptySelects: function(){
			$('select.filter').each(function(){
				let select = $(this);
				let isEmpty = true;
				select.find('option').each(function(){
					let option = $(this);
					if (!option.attr('value')) return 1;
					if (typeof option.attr('disabled') == 'undefined') isEmpty = false;
				})
				if (isEmpty) select.addClass('hidden');
				$('select').trigger('refresh');
			})
		},
		setFilters: function(filters){
			$('select.filter option:not(.added)').prop('disabled', true);
			$('select.filter').each(function(){
				let th = $(this);
				let title = th.attr('data-placeholder');
				let filter = filters[title];
				if (typeof filter == 'undefined') return 1;
				$.each(filter.filter_values, function(i, fv){
					th.find('option[value=' + fv.id + ']:not(.added)').attr('disabled', false);
				})
			})
			category.hideEmptyFilters();
			$('select.filter').trigger('refresh');
			$.each(filters, function(i, filter){
				if (!+filter.slider) return 1;
				category.sliders['sliders[' + filter.id + ']'].update({
					from: filter.min,
					to: filter.max
				})
			})
		},
		hideEmptyFilters: function(){
			$('select.filter').each(function(){
			let select = $(this);
			let isEmpty = true;
			$(select).find('option').each(function(){
				let th = $(this);
				if (typeof th.attr('disabled') == 'undefined') isEmpty = false;
			})
			if (isEmpty) select.addClass('hidden');
			})
		},
		setHistoryPagination: function(pageNumber){
			let href = document.location.href;
			href = href.replace(/&pageNumber=\d/, '');
			history.pushState(false, false, href + '&pageNumber=' + pageNumber);
		},
		setHistoryFilter: function(data = null){
			let href = window.location.href;
			let parts = href.split('&');
			let loc = parts[0];
			if (data) loc += '&' + data;
			history.pushState(false, false, loc);
		},
		getHtmlMosaicView: function(item){
			str = 
				'<div class="item_1 product-popup-link" item_id="' + item.item_id + '">' +
				'<div class="product">' +
					'<p>' + 
						'<b class="brend_info" brend_id="' + item.brend_id + '">' + item.brend + '</b> ' +
						'<a href="/article/' + item.item_id + '-' + item.article +  '" class="articul">' + item.article + '</a>' +
					'</p>' +
					'<p><strong>' + item.title_full + '</strong></p>' + 
					'<div class="pic-and-description">' +
						'<div class="img-wrap">' +
							'<img src="' + item.src + '" alt="' + item.alt + '">' +
						'</div>' +
						item.description +
					'</div>' +
					'<div class="clearfix"></div>' +
					'<div class="rating no_selectable">' +
						item.rating +
					'</div>' +
				'</div>' +
				item.priceDelivery +
			'</div>';
			return str;
		},
		getListTableHead: function(data){
			category.filterListTitles = new Array();
			$.each(data, function(key, title){
				$.each(title.filter_values, function(k, fv){
					if (!category.filterListTitles.includes(fv.filter)) {
				      category.filterListTitles.push(fv.filter);
				    }
				})
			})

			let tableHead = '<tr><th>Название</th>';
			$.each(category.filterListTitles, function(i, ft){
				tableHead += '<th>' + ft + '</th>';
			})
			tableHead += '<th>Рейтинг</th><th>Доставка</th><th>Цена</th><tr>';
			
			return tableHead;
		},
		getListTableBody: function(data){
			let output = '';
			$.each(data, function(i, item){
				output +=
					'<tr class="product-popup-link" item_id="' + item.item_id + '">' +
						'<td label="Название" class="name-col">' +
							'<b class="brend_info" brend_id="' + item.brend_id + '">' + item.brend + '</b> ' +
							'<a href="/article/' + item.item_id + '-' + item.article +  '" class="articul">' + item.article + '</a>' +
								item.title_full +
						'</td>';
				output += category.getListFilterValues(item.filter_values);
				output +=
						'<td>' + item.rating + '</td>' +
						'<td>' + item.delivery + '</td>' +
						'<td>' + item.price + '</td>' +
					'</tr>';
			})
			return output;
		},
		getListFilterValues(filter_values){
			let groupedFilterValues = new Array();
			$.each(filter_values, function(i, fv){
				if (typeof groupedFilterValues[fv.filter] == 'undefined') groupedFilterValues[fv.filter] = new Array();
				groupedFilterValues[fv.filter].push(fv.filter_value);
			})

			let output = '';
			$.each(category.filterListTitles, function(i, filterTitle){
				output += '<td label="' + filterTitle + '">';
				if (typeof groupedFilterValues[filterTitle] == 'undefined') return 1;
				for(let j in groupedFilterValues[filterTitle]){
					output += '<span class="filter_value">' + groupedFilterValues[filterTitle][j] + '</span>';
				} 
				output += '</td>';
			})
			return output;
		},
		getHtmlListView: function(data){
			let tableHead = category.getListTableHead(data);
			let tableBody = category.getListTableBody(data);
			return tableHead + tableBody;
		}
	}
})(jQuery)
$(function(){
	category.init();
})
