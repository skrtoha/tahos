<?
$page_title = 'Тексты';
$status = '<a href="/">Главная</a> > Тексты';
$tab = isset($_GET['tab']) ? 'help' : $_GET['tab'];
//debug($_GET);?>
<div class="ionTabs" id="tabs_1" data-name="texts">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="help">Помощь</li>
		<li class="ionTabs__tab" data-target="agreement">Пользовательское соглашение</li>
		<li class="ionTabs__tab" data-target="for_providers">Поставщикам</li>
		<li class="ionTabs__tab" data-target="for_wholesellers">Оптовикам</li>
		<li class="ionTabs__tab" data-target="about_company">О компании</li>
		<li class="ionTabs__tab" data-target="partner_programs">Партнерские программы</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="help">
			<input type="hidden" name="act" value="<?=$_GET['act']?>">
			<input type="hidden" name="id" value="<?=$_GET['id']?>">
			<?$texts = new Texts($db);
			$act = isset($_GET['act']) ? $_GET['act'] : '';
			switch($_GET['act']){
				case 'themes': $texts->themes(); break;
				case 'theme_add': $texts->theme_add(); break;
				case 'theme_change': $texts->theme(); break;
				case 'theme_delete': $texts->theme_delete(); break;
				case 'rubrics': $texts->rubrics(); break;
				case 'rubric_add':
				case 'rubric_change': $texts->rubric(); break;
				case 'rubric_delete': $texts->rubric_delete(); break;
				case 'help_main': $texts->settings('help_main', 'Стартовый текст при открытии помощи'); break;
				default: $texts->themes();
			}?>
		</div>
		<div class="ionTabs__item" data-name="agreement">
			<?$texts->settings('agreement')?>
		</div>
		<div class="ionTabs__item" data-name="for_providers">
			<?$texts->settings('for_providers')?>
		</div>
		<div class="ionTabs__item" data-name="for_wholesellers">
			<?$texts->settings('for_wholesellers')?>
		</div>
		<div class="ionTabs__item" data-name="about_company">
			<?$texts->settings('about_company')?>
		</div>
		<div class="ionTabs__item" data-name="partner_programs">
			<?$texts->settings('partner_programs')?>
		</div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>