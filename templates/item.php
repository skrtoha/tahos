<?$item = $db->select('items', '*', "`href`='{$_GET['href']}'");
$item = $item[0];
$category = $db->select('categories', 'id,href,title_plural', "`id`={$item['category_id']}");
$category = $category[0];
print_r($category);
$page_title = $item['title'];
?>
<!-- Welcome Area
===================================== -->
<div id="" class="bg-gray pt60 pb60">
	<div class="container">
		<div class="row">
			<p class="lead text-center" id="navigation">
				<span><a href="/">Главная</a></span> > 
				<span><a href="/categories">Каталог</a></span> > 
				<span><a href="/category/<?=$category['href']?>"><?=$category['title_plural']?></a></span> > 
				<span><?=$page_title?></span>
			</p>
			<div class="col-md-12 text-center">
				<h1 class="font-size-normal">
					<?=$item['title']?>
					<small class="heading heading-solid center-block"></small>
				</h1>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12 text-center">
			<img class="img-responsive img-thumbnail" src="/catalog/<?=$item['category_id']?>/<?=$item['img']?>" alt="">
		</div>
		<div class="col-md-8 col-md-offset-2 mt20"><?=$item['description']?></div>
		<?if ($item['price']){?>
			<div id="price_item" class="col-md-8 col-md-offset-2 text-left">
				от <?=$item['price']?> руб.
			</div>
		<?}?>
		<div class="col-md-12 text-center">
				<h1 class="font-size-normal">
					Другие <?=$category['title_plural']?>
					<small class="heading heading-solid center-block"></small>
				</h1>
			</div>
		<div class="container">
			<div id="items" class="row mt50">
				<?$items = $db->select('items', '*', "`category_id`={$item['category_id']} AND `id`!={$item['id']} ORDER BY rand() LIMIT 6");
				if (count($items)){
					foreach ($items as $item) { 
						if (!$item['img']) continue;
						$src = "catalog/{$item['category_id']}/{$item['img']}"?>
						<div class="div_1 col-sm-4 col-xs-6 mb25">
							<a href="/item/<?=$item['href']?>" class="magnific-popup">
								<h2><?=$item['title']?></h2>
								<p class="desc_seo"><?=$item['description_seo']?></p>
								<p class="price">
									<?if ($item['price']){?>
										от <?=$item['price']?> руб.
									<?}?>
								</p>
							</a>
							<img style="width: 100%" src="/<?=$src?>" class="img-responsive">
						</div>
					<?}
				}?>
			</div>
		</div>
		<div class="container">
			<div class="col-md-12 text-center">
				<a href="/category/<?=$category['href']?>"" class="btn btn-default" role="button">Вcе <?=strtolower($category['title_plural'])?> </a>
			</div>
		</div>
	</div>
</div>
<div id="welcome" class="bg-light pt75 pb25">
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
				<h1 class="font-size-normal">
					Академия мебели
					<small class="heading heading-solid center-block"></small>
				</h1>
			</div>
			
			<!-- title description start -->
			<div class="col-md-8 col-md-offset-2 text-center">
				<p>
					На рынке корпусной мебели мы работаем более 10 лет. За это время поняли все особенности и тонкости мебельного дела. Уже более 5000 клиентов доверяют нам.
				</p>
			</div>
			<!-- title description end -->
		</div>
		
		<div class="row mt50">
			<!-- item one start -->
			<div class="col-md-3 col-sm-6 col-xs-6 animated" data-animation="fadeInLeft" data-animation-delay="100">
				<div class="content-box content-box-center">                        
					<span class="icon-gears color-pasific"></span>
						<h5>Свое производство</h5>
					<p>Мы продаем без наценок, т.к. у нас свое производство мебели.</p>
					
				</div>
			</div>
			<!-- item one end -->
			
			<!-- item two start -->
			<div class="col-md-3 col-sm-6 col-xs-6 animated" data-animation="fadeInLeft" data-animation-delay="200">
				<div class="content-box content-box-center">                        
					<span class="icon-briefcase color-pasific"></span>
						<h5>Опыт более 10 лет</h5>
					<p>За 10 лет работы на рынке мы выполнили более 5000 заказов.</p>
					
				</div>
			</div>
			<!-- item two end -->
			
			<!-- item three start -->
			<div class="col-md-3 col-sm-6 col-xs-6 animated" data-animation="fadeInRight" data-animation-delay="300">
				<div class="content-box content-box-center">                        
					<span class="icon-circle-compass color-pasific"></span>
						<h5>Бесплатный замер и доставка</h5>
					<p>Мы бесплатно приезжаем на замеры, доставляем и устанавливаем мебель.</p>
					
				</div>
			</div>
			<!-- item three end -->
			
			<!-- item four start -->
			<div class="col-md-3 col-sm-6 col-xs-6 animated" data-animation="fadeInRight" data-animation-delay="400">
				<div class="content-box content-box-center">                        
					<span class="icon-pricetags color-pasific"></span>
						<h5>Рассрочка и скидки</h5>
					<p>Возможна рассрочка платежа. Даем скидку при повторном заказе.</p>
					
				</div>
			</div>
			<!-- item four start -->                    
		</div>                
	</div>
</div>