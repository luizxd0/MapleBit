<?php
if(basename($_SERVER["PHP_SELF"]) == "main-news.php") {
	die("403 - Access Forbidden");
}
echo "
	<section class=\"home-widget news-widget\">
	<div class=\"widget-heading\">
		<span class=\"widget-icon\"><i class=\"fa fa-bullhorn\" aria-hidden=\"true\"></i></span>
		<div><small>From the team</small><h3>Latest news</h3></div>
		<a href=\"?base=main&amp;page=news\">View all <i class=\"fa fa-angle-right\" aria-hidden=\"true\"></i></a>
	</div>
	<div class=\"widget-list\">
";

$gn = $mysqli->query("SELECT * FROM ".$prefix."news ORDER BY id DESC LIMIT 4");
if($gn && $gn->num_rows) {
	while($n = $gn->fetch_assoc()) {
		$gc = $mysqli->query("SELECT * FROM ".$prefix."ncomments WHERE nid='".$n['id']."' ORDER BY id ASC");
		$cc = $gc->num_rows;
		echo '<a class="widget-row" href="?base=main&amp;page=news&amp;id=' . (int) $n['id'] . '">'
			. '<span class="news-type"><img src="assets/img/news/'
			. htmlspecialchars($n['type'], ENT_QUOTES, 'UTF-8') . '.gif" alt=""></span>'
			. '<span><strong>' . htmlspecialchars(ellipsize($n['title'], 34, 1, '...'), ENT_QUOTES, 'UTF-8')
			. '</strong><small>' . htmlspecialchars($n['date'], ENT_QUOTES, 'UTF-8') . '</small></span>'
			. '<span class="comment-count"><i class="fa fa-comment-o" aria-hidden="true"></i> ' . $cc . '</span>'
			. '</a>';
	}
}
else {
	echo "<div class=\"alert alert-info\">No news posted.</div>";
}
echo "</div></section>";
