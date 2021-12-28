<?
use core\Setting;
$page_title = 'Тексты';
$status = '<a href="/">Главная</a> > Тексты';
$tab = isset($_GET['tab']) ? 'help' : $_GET['tab'];
$titles = json_decode(Setting::get('titles'), true);
$textClass = new Texts($db);
//debug($_GET);?>
<div class="ionTabs" id="tabs_1" data-name="texts">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="1"><?=$titles[1]?></li>
		<li class="ionTabs__tab" data-target="2"><?=$titles[2]?></li>
		<li class="ionTabs__tab" data-target="4"><?=$titles[4]?></li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="1">
            <?switch($_GET['act']){
                case 'text_add':
                case 'text':
                    $id = $_GET['id'] ?? null;
                    $textClass->getHtmlText($id , 1);
                    break;
                case 'text_delete':
                    $db->delete('texts', "`id` = {$_GET['id']}");
                    header("Location: /admin/?view=texts&tab=1");
                    die();
                default:
                    $textList = $textClass->getTexts(1);
                    $textClass->getHtmlTextList($textList, 1);
            }?>
		</div>
		<div class="ionTabs__item" data-name="2">
            <?switch ($_GET['act']){
                default:
                    $rubrics = $textClass->getRubrics();
                    $textClass->getHtmlTextList($rubrics, 2);
            }?>
        </div>
		<div class="ionTabs__item" data-name="4"></div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>