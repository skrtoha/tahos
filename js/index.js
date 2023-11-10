let count_click = 0;
$(function(){
	/* actions slider */
	$(".actions_slider").owlCarousel({
		items: 1,
		smartSpeed: 500
	});
	$(".prev").click(function(){
		$(".actions_slider").trigger("prev.owl.carousel");
	});
	$(".next").click(function(){
		$(".actions_slider").trigger("next.owl.carousel");
	});
	$('.categories .category .left').each(function(){
		let ul = $(this);
		if (ul.height() > 250){
			let i = ul.find('li').size();
			while(ul.height() > 240){
				ul.find('li:nth-child(' + i + ')').addClass('hidden');
				i--;
			}
			ul.append('<li><a class="more" href="#">еще..<a></li>');
		}
	})
	$(document).on('click', 'a.more', function(e){
		e.preventDefault();
		let more = $(this);
		more.closest('ul').find('li').removeClass('hidden');
		more.remove();
	})
  $('div.selection.spare_parts_request > div.left > form').on('submit', (e) => {
    e.preventDefault();
    document.location.href = "/original-catalogs/legkovie-avtomobili#/carInfo?q=" + $('input[name=q]').val();
  })

  document.querySelector('#spare_parts_request').addEventListener('submit', e => {
    const captchaInput = document.querySelector('input[name="smart-token"]')
    if (!captchaInput.value){
      e.preventDefault()
    }
  })

  document.addEventListener('captchaSuccessed', e => {
    $('#spare_parts_request input[type=submit]').prop('disabled', false)
  })

});