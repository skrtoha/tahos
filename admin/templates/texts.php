<?
use core\Setting;
$page_title = 'Тексты';
$status = '<a href="/">Главная</a> > Тексты';
$tab = isset($_GET['tab']) ? 'help' : $_GET['tab'];
$titles = json_decode(Setting::get('titles'), true);
$textClass = new Texts($db);
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || !empty($_POST)){
    switch ($_GET['tab']){
        case 4:
        case 1:
            switch($_GET['act']){
                case 'article_add':
                case 'article':
                    $id = $_GET['id'] ?? null;
                    $textClass->showHtmlArticle($id , $_GET['tab']);
                    break;
                case 'article_delete':
                    $db->delete('text_articles', "`id` = {$_GET['id']}");
                    header("Location: /admin/?view=texts&tab={$_GET['tab']}");
                    die();
                default:
                    $articleList = $textClass->getArticles($_GET['tab']);
                    $textClass->showHtmlArticleList(
                        $articleList,
                        $_GET['tab'],
                        'article',
                        false,
                        false,
                        "/admin/?view=texts&tab={$_GET['tab']}&act=article_add#tabs|texts:{$_GET['tab']}"
                    );
                }
            break;
        case 2:
            switch ($_GET['act']){
                case 'rubric_delete':
                    $db->delete('text_rubrics', "`id` = {$_GET['id']}");
                    header("Location: /admin/?view=texts&tab=2#tabs|texts:2");
                    die();
                case 'article_add':
                case 'article':
                    $id = $_GET['id'] ?? null;
                    $textClass->showHtmlArticle($id, 2, 'Добавление статьи');
                    break;
                case 'rubric_add':
                case 'rubric_change':
                    $id = $_GET['id'] ?? null;
                    $textClass->showHtmlTextRubricForm($id, 2);
                    break;
                case 'rubric':
                    $rubricInfo = $textClass->getRubrics(['id' => $_GET['id']]);
                    $rubricInfo = $rubricInfo[0];
                    $articles = $textClass->getArticleRubricList($_GET['id']);
                    $textClass->showHtmlArticleList(
                        $articles,
                        2,
                        'article',
                        "{$rubricInfo['title']}: список статей",
                        "/admin/?view=texts&tab=2#tabs|texts:2",
                        "/admin/?view=texts&tab=2&act=article_add&parent_id={$_GET['id']}#tabs|texts:2",
                        "/admin/?view=texts&tab=2&act=rubric_delete&id={$_GET['id']}"
                    );
                    break;
                default:
                    $rubrics = $textClass->getRubrics();
                    $textClass->showHtmlArticleList(
                        $rubrics,
                        2,
                        'rubric',
                        '',
                        '',
                        "/admin/?view=texts&tab=2&act=rubric_add#tabs|texts:{$_GET['tab']}"
                    );
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