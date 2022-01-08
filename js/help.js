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
	$("a[data-article-id]").click(function(event) {
		event.preventDefault();
		var th = $(this);
		$.ajax({
			type: 'post',
			url: '/ajax/help.php',
			data: 'id=' + th.data('article-id'),
			success: function(response){
				var t = JSON.parse(response);
				$('div.answer-block').html(
					'<h3>' + t.title + '</h3>' +
					t.text
				);
			}
		})
	});
});