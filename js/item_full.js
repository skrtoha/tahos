(function($){
	window['item_full'] = {
		init: function(){
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
                const t = e.target;
                if (t.className == 'brend_info') return false;
                const item_id = $(this).attr('item_id');
                popup.style.display = 'flex';
				$.ajax({
					type: "POST",
					url: "/ajax/item_full.php",
					data: 'id=' + item_id + '&category=1',
					success: function(msg){
						$('#mgn_popup').html(item_full.getFullItem(JSON.parse(msg)));
                        popup.style.display = 'none';
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
										$("#main-pic img")
                                            .attr("src", $(this).attr("src"))
                                            .attr("data-zoom-image", $(this).attr('data-big-img'));
									});
									$.ionTabs(".product-popup-tabs",{
										type: "none"
									});
									$("#Button__product-popup-tabs__Tab_1_name").click();
									$('#gallery').owlCarousel({
                                        loop:true,
                                        margin:10,
                                        nav:true,
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
            $(document).on('click', '#main-pic img', function(event) {
                const $th = $(this);
                const currentImage = $th.attr('data-zoom-image');
                let currentNumber = 0;
                let counter = 0;
                $.each(bigImages, function(i, item){
                    if (item == currentImage) currentNumber = counter;
                    counter++;
                })
                let gallery = blueimp.Gallery(bigImages, {
                    index: currentNumber,
                    thumbnailIndicators: true,
                    onopened: function(){
                        $('#blueimp-gallery').addClass('blueimp-gallery-controls');
                    }
                });
            });
		},
		getFullItem: function (i){
			let src_small;
            let src_big;
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
							'<td>' + b.user_price + '</td>' +
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
							'<td>' + del.user_price + '</td>' +
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
			var is_photo = !!item.photo;
			var c_photos = Object.keys(i.photos).length;
			if (is_photo){
                bigImages = [];
				src_small = getImgUrl() + '/items/small/' + item.id + '/' + item.photo;
				src_big = getImgUrl() + '/items/big/' + item.id + '/' + item.photo;
				str += '' +
						'<div id="main-pic">' + 
							'<img src="'+ src_small + '" data-zoom-image="' + src_big + '">' + 
						'</div>';
				if (c_photos > 1){
					str += '<div class="items-gallery owl-carousel" id="gallery">';
					for (var k in i.photos){
                        src_small = getImgUrl() + '/items/small/' + item.id + '/' + i.photos[k];
                        src_big = getImgUrl() + '/items/big/' + item.id + '/' + i.photos[k];
                        bigImages.push(src_big);
						str += '<img src="' + src_small + '" data-big-img="' + src_big + '">';
					}
					str += '</div>';
				}
                else bigImages.push(src_big);
			} 
			else str += '' +
						'<div id="pic">' + 
							'<img src="/images/no_foto.png">' + 
						'</div>';
			var rating = item.rating ? item.rating - 1 : -1;
			var no_selectable = item.rating ? 'no_selectable' : '';
			s = is_photo ? '' : 'margin-top: 0px';
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
				/*if ($(document).height() > 700){
					if (c_photos) s = 'height: 304px';
					else s = 'height: 215px';
				}*/
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
				'<button href="/article/' + item.id + '-' + item.article + '/no-use-api"  id="search-same"><span class="icon_search"></span>Другие предложения</button>' +
				'<div class="clearfix"></div>' +
			'</div>';
			return str;
		}
	}
})(jQuery)
$(function(){
	item_full.init();
})
