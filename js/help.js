$(function(){
	//accordeon
	var acc = document.getElementsByClassName("accordion");
	var i;
	for (i = 0; i < acc.length; i++) {
		acc[i].onclick = function(){
			this.classList.toggle("active");
			this.nextElementSibling.classList.toggle("show");
		}
	}
	$("a[text_id]").click(function(event) {
		event.preventDefault();
		// $('html, body').animate({
		// 	scrollTop: $('.answer-block').offset().top - 75
		// }, 500);
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/ajax/help.php',
			data: 'id=' + th.attr('text_id'),
			success: function(response){
				// console.log(response); return;
				var t = JSON.parse(response);
				$('div.answer-block').html(
					'<h3>' + t.title + '</h3>' +
					t.text
				);
			}
		})
	});
});