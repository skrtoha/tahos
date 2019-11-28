<?
$help = new Help($db);
// debug($help);
switch($_GET['act']){
	case 'theme_add': 
	case 'theme_change': $help->theme(); break;
	case 'theme_delete': $help->theme_delete(); break;
	case 'rubrics': $help->rubrics(); break;
	case 'rubric_add':
	case 'rubric_change': $help->rubric(); break;
	case 'rubric_delete': $help->rubric_delete(); break;
	case 'help_main': $help->help_main(); break;
	default: $help->view();
}
$page_title = $help->page_title;
$status = $help->status;
?>