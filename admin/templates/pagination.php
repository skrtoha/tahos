<?function getChank($all, $perPage, $limitLinks, $page){
	if (!$all) return false;
	$pages = ceil($all / $perPage);
	for ($i = 0; $i < $pages; $i++) $pagesArr[$i + 1] = $i * $perPage;
	$allPages = array_chunk($pagesArr, $limitLinks, true);
	foreach ($allPages as $key => $value) {
		if (array_key_exists($page, $value)) return $value;
	}
}
function pagination($chank, $page, $pages, $href = ""){
	$count_chank = count($chank);
	if ($count_chank <= 1) return false;
	foreach ($chank as $k => $v){
		$first = $k;
		break;}?>
	<ul class="pagination">
		<?if ($page > $count_chank){
			$to_link = $first - 1;?>
			<li><a href="<?=$href?>1">&lt;&lt;</a></li>
			<li style="margin-right: 10px"><a href="<?=$href?><?=$to_link?>">&lt;</a></li>
		<?}?>
		<?foreach ($chank as $key => $value){
			if ($key == $page){?>
			<li><?=$key?></li>
			<?}
			else{?>
			<li><a href="<?=$href?><?=$key?>"><?=$key?></a></li>
		<?}
		}?>
		<?if ($first + $count_chank -1 < $pages){
			$to_link = $first + $count_chank;?>
			<li style="margin-left: 10px"><a href="<?=$href?><?=$to_link?>">&gt;</a></li>
			<li><a href="<?=$href?><?=$pages?>">&gt;&gt;</a></li>
		<?}?>
	</ul>
<?}?>