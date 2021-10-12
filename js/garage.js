$(function(){
    $(document).on('click', '.edit-item', function(e){
        e.preventDefault();
        const modification_id = $(this).closest('div.item').attr('modification_id');
        $.ajax({
            url: '/ajax/parts-catalogs.php',
            type: 'post',
            data: {
                act: 'getGarageInfo',
                modification_id: modification_id
            },
            success: function (response){
                let garageInfo = JSON.parse(response);
                garageInfo.title = garageInfo.title_garage;
                garageInfo.act = 'addToGarage';
                showPopupAddGarage(garageInfo);
            }
        })
    })
    $(document).on('submit', '#to_garage__form', function (e){
        let formData = {};
        $.each($(this).serializeArray(), function(i, item){
            formData[item.name] = item.value;
        })
        const parameters = $('div[modification_id="' + formData.modification_id + '"] .parametrs');
        parameters.find('p:nth-child(1)').html(`
            <b>Телефон: </b> ${formData.phone}
        `);
        parameters.find('p:nth-child(3)').html(`
            <b>Владелец: </b> ${formData.owner}
        `);
        parameters.find('p:nth-child(4)').html(`
            <b>Год выпуска: </b> ${formData.year}
        `);
    })
	$('.add_ts').magnificPopup({
		type: 'inline',
		preloader: false,
		focus: '#name',

		// When elemened is focused, some mobile browsers in some cases zoom in
		// It looks not nice, so we disable it:
		callbacks: {
			beforeOpen: function() {
				if($(window).width() < 700) {
					this.st.focus = false;
				} else {
					this.st.focus = '#name';
				}
			}
		}
	});
    $('.filter-form .search-icon').on('click', function(){
        $('.item').removeClass('hidden');
        let activeTabClass;
        let modification_id;
        let search = $(this).prev('input').val();
        search = search.toLowerCase();
        const currentTab = $('div.option-panel a.active');
        if (currentTab.hasClass('active-switch')) activeTabClass = '.active-tab';
        else activeTabClass = '.not-active-tab';
        let elements = $(activeTabClass + ' .wrapper .item');
        $.each(elements, function(i, item){
            const th = $(item);
            const modification_id = th.attr('modification_id');
            let string = '';
            string += th.find('.model-name').text() + ' ';
            $.each(th.find('.parametrs p'), function(i, param){
                string += $(param).html()
            })
            string = string.toLowerCase();
            if (string.indexOf(search) === -1){
                $('[modification_id="' + modification_id + '"]').addClass('hidden');
            }
        })
    })
	$(".active-switch").click(function (event) {
		event.preventDefault;
		$(".not-active-switch").removeClass("active");
		$(this).addClass("active");
		$(".active-tab").show();
		$(".not-active-tab").hide();
	});
	$(".not-active-switch").click(function (event) {
		event.preventDefault;
		$(".active-switch").removeClass("active");
		$(this).addClass("active");
		$(".not-active-tab").show();
		$(".active-tab").hide();
	});
	$(".view-switch").click(function(event) {
		$(".view-switch").removeClass("active");
		$(this).addClass("active");
		switch_id = $(this).attr("id");
		if (switch_id == "mosaic-view-switch") {
			$(".mosaic-view").show();
			$(".list-view").hide();
		}else{
			$(".mosaic-view").hide();
			$(".list-view").show();
		}

	});
	$('div.filter-form form').on('submit', function(e){
        e.preventDefault();
        $('.filter-form .search-icon').click();
	})
	$(document).on('click', 'div.active-tab a.remove-item', function(e){
		e.preventDefault();
		var th = $(this).closest('[modification_id]');
		if(!confirm('Вы действительно хотите удалить?')) return false;
		let modification_id = th.attr('modification_id');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=modification_delete&modification_id=' + modification_id,
			success: function(response){
				$('div.not-active-tab .removable').remove();
				th.find('.remove-item').remove();
				th.find('div.clearfix:last-child').after(`
					<a href="#" class="remove-item">Удалить из гаража</a>
					<a href="#" class="restore-item">Восстановить</a>
				`);
				th.clone().prependTo('div.not-active-tab');
				th.remove();
			}
		})
	})
	$(document).on('click', 'a.restore-item', function(e){
		e.preventDefault();
		var th = $(this).closest('[modification_id]');
		var modification_id = th.attr('modification_id');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=modification_restore&modification_id=' + modification_id,
			success: function(response){
				$('div.active-tab .removable').remove();
				th.find('.remove-item').remove();
				th.find('.restore-item').remove();
				th.find('div.clearfix:last-child').after(`
					<a href="" class="remove-item">Удалить</a>
				`);
				th.clone().prependTo('div.active-tab');
				th.remove();
			}
		})
	})
	$(document).on('click', 'div.not-active-tab a.remove-item', function(e){
		e.preventDefault();
		if (!confirm('Вы действительно хотите удалить из гаража?')) return false;
		var th = $(this).closest('[modification_id]');
		var modification_id = th.attr('modification_id');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=modification_delete_fully&modification_id=' + modification_id,
			success: function(response){
				// console.log(response); return;
				th.remove();
				show_message('Успешно удалено из гаража');
			}
		})
	})
	$(document).on('click', '.active-tab [modification_id]', function(event){
		var t = $('a.remove-item');
		if (t.is(event.target)) return false;
        if ($(event.target).hasClass('edit-item')) return false;
		document.location.href = '/garage/' + $(this).attr('modification_id');
	})
})
