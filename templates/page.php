<?
switch($_GET['type']){
	case 'agreement': $title = 'Пользовательское соглашение'; break;
	case 'about_company': $title = 'О компании'; break;
	case 'partner_programs': $title = 'Партнерские программы'; break;
	case 'for_regions': $title = 'Регионам'; break;
	case 'for_providers': $title = 'Поставщикам'; break;
	case 'for_wholesellers': $title = 'Оптовикам'; break;
	
}?>
<h2><?=$title?></h2>
<?=core\Setting::get('texts', $_GET['type'])?>