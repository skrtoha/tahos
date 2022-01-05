<?
use core\Setting;
$page_title = 'Тексты';
$status = '<a href="/">Главная</a> > Тексты';
$tab = isset($_GET['tab']) ? 'help' : $_GET['tab'];
$titles = json_decode(Setting::get('titles'), true);
$textClass = new Texts($db);
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || !empty($_POST)){
    switch ($_GET['tab']){
        case 1:
            switch($_GET['act']){
                case 'text_add':
                case 'text':
                    $id = $_GET['id'] ?? null;
                    $textClass->showHtmlText($id , 1);
                    break;
                case 'text_delete':
                    $db->delete('texts', "`id` = {$_GET['id']}");
                    header("Location: /admin/?view=texts&tab=1");
                    die();
                default:
                    $textList = $textClass->getTexts(1);
                    $textClass->showHtmlTextList($textList, 1, 'text');
                }
            break;
        case 2:
            switch ($_GET['act']){
                case 'text_rubric_add':
                case 'text_rubric_change':
                    $id = $_GET['id'] ?? null;
                    $textClass->showHtmlTextRubricForm($id, 2);
                    break;
                case 'text_rubric':
                    $rubricInfo = $db->select_one('text_rubrics', '*', "`id` = {$_GET['id']}");
                    $textRubric = $textClass->getTextRubricList($_GET['id']);
                    $textClass->showHtmlTextList(
                        $textRubric,
                        2,
                        'text_rubric_change',
                        "{$rubricInfo['title']}: список статей",
                        "/admin/?view=texts&tab=2#tabs|texts:2",
                        '/admin/?view=texts&tab=2&act=text_rubric_add#tabs|texts:2'
                    );
                    break;
                default:
                    $rubrics = $textClass->getRubrics();
                    $textClass->showHtmlTextList($rubrics, 2, 'text_rubric');
            }
            break;
        case 4:
            break;
    }
    die();
}
?>
<div class="ionTabs" id="tabs_1" data-name="texts">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="1"><?=$titles[1]?></li>
		<li class="ionTabs__tab" data-target="2"><?=$titles[2]?></li>
		<li class="ionTabs__tab" data-target="4"><?=$titles[4]?></li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="1"></div>
		<div class="ionTabs__item" data-name="2"></div>
		<div class="ionTabs__item" data-name="4"></div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>