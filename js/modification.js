$(function(){
	// console.log(document.location);
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
	// item equal height
	$(".item").matchHeight();
	$(".item .img").matchHeight();
	//make tree
	// $(".tree-structure").jstree();
	$(".tree-structure").on('click', '.jstree-anchor', function (e) {
		$(this).jstree(true).toggle_node(e.target);
	})
	.jstree();
	$('.tree-structure').on("changed.jstree", function (e, data) {
		// console.log(data.node);
		var childs = '';
		for(var k in data.node.children_d){
			childs += data.node.children_d[k].replace('node_', '') + ',';
		}
		childs = childs.slice(0, -1);
		var brend = $('.content').attr('brend');
		if (data.node.children.length != 0){
			if (!data.node.state.opened) return false;
			$.ajax({
				method: 'post',
				url: '/ajax/original-catalogs.php',
				data: 
					'act=parent_nodes&childs=' + childs +
					'&brend=' + brend,
				beforeSend: function(){
					$('.list-view div.items.clearfix')
					.empty()
					.append('<div class="gif"></div>');
				},
				success: function(res){
					// console.log(res);
					var nodes = JSON.parse(res);
					// console.log(nodes);
					var str = '';
					for(var k in nodes){
						var n = nodes[k];
						str += 
						'<div class="item">' + 
							'<a href="' + document.location.href + '/' + n.id + '"></a>';
						str +=
							'<p>' + n.title + '</p>';
						if (n.is_img) str += 
							'<div class="img">' + 
								'<img src="/images/nodes/small/' + brend + '/' + n.id + '.jpg" alt="' + n.title + '">' +
							'</div>';
						str +=
						'</div>';
					}
					$('.list-view div.items.clearfix').html(str);
					// console.log(str);
				}
			})
		}
		else document.location.href = data.node.a_attr.href;
		// return false;
	});
	$('#to_garage button').on('click', function(){
		var act;
		if ($(this).hasClass('is_garaged')){
			act = 'from_modification_from_garage';
			show_message('Успешно удалено из гаража!');
		}
		else{
			act = 'from_modification_to_garage';
			show_message('Успешно добавлено в гараж!');
		}
		$(this).toggleClass('is_garaged');
		$.ajax({
			type: 'post',
			url: '/ajax/garage.php',
			data: 'act=' + act + '&user_id=' + $(this).attr('user_id') + '&modification_id=' + $(this).attr('modification_id'),
			success: function(response){
				console.log(response); return;
			}
		})
	})
});
