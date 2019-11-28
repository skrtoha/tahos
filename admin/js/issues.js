function init() {
	$('#popup_map').remove();
		var bool = false;
		var coords = $('#coords').val().split(',');
		if ($('#coords').val()){
			var coord_1 = parseFloat(coords[0]);
			var coord_2 = parseFloat(coords[1]);
			bool = true;
		}
		else {
				var coord_1 = 59.220473;
				var coord_2 = 39.891559;
		}
		var myPlacemark;
		var myMap = new ymaps.Map('map', {
				center: [coord_1, coord_2],
				zoom: 12
				}, {
				searchControlProvider: 'yandex#search'
				}
	);
	if (bool){
		var curr_coords = [coord_1, coord_2];
		var myPlacemark = createPlacemark(curr_coords);
		myMap.geoObjects.add(myPlacemark);
	}
		// Слушаем клик на карте.
		myMap.events.add('click', function (e) {
				var coords = e.get('coords');
				// Если метка уже создана – просто передвигаем ее.
				if (myPlacemark) {
				myPlacemark.geometry.setCoordinates(coords);
				}
				// Если нет – создаем.
				else {
				myPlacemark = createPlacemark(coords);
				myMap.geoObjects.add(myPlacemark);
				// Слушаем событие окончания перетаскивания на метке.
				myPlacemark.events.add('dragend', function () {
						$('#coords').val(myPlacemark.geometry.getCoordinates());
				});
				}
				$('#coords').val(coords);
		});
		// Создание метки.
		function createPlacemark(coords) {
			return new ymaps.Placemark(coords, {
			iconCaption: $('#coords').attr('login')
				}, {
				preset: 'islands#violetDotIconWithCaption',
				// draggable: true
				});
		}
}
$(function(){
	$.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=64b4b12b-f136-4cc3-bfe2-3418e1c7b59a', function(){
		ymaps.ready(init);
	})
	$('tr[href]').on('click', function(){
		document.location.href = $(this).attr('href');
	})
	$(document).on('keydown', function(e){
		if (e.keyCode == 13 && e.ctrlKey) $('form').submit();
	})

})