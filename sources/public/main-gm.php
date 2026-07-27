<?php
if(basename($_SERVER["PHP_SELF"]) == "main-gm.php") {
	die("403 - Access Forbidden");
}
echo "
	<section class=\"home-widget gm-widget\">
	<div class=\"widget-heading\">
		<span class=\"widget-icon gm-icon\"><i class=\"fa fa-pencil\" aria-hidden=\"true\"></i></span>
		<div><small>Behind the scenes</small><h3>GM journal</h3></div>
		<a href=\"?base=main&amp;page=gmblog\">View all <i class=\"fa fa-angle-right\" aria-hidden=\"true\"></i></a>
	</div>
	<div class=\"widget-list\">
";

$gb = $mysqli->query("SELECT * FROM ".$prefix."gmblog ORDER BY id DESC LIMIT 4");
if($gb && $gb->num_rows) {
	while($b = $gb->fetch_assoc()) {
		$gc = $mysqli->query("SELECT * FROM ".$prefix."bcomments WHERE bid='".$b['id']."' ORDER BY id ASC");
		$cc = $gc->num_rows;
		echo '<a class="widget-row" href="?base=main&amp;page=gmblog&amp;id=' . (int) $b['id'] . '">'
			. '<span class="journal-mark"><i class="fa fa-leaf" aria-hidden="true"></i></span>'
			. '<span><strong>' . htmlspecialchars(ellipsize($b['title'], 34, 1, '...'), ENT_QUOTES, 'UTF-8')
			. '</strong><small>' . htmlspecialchars($b['date'], ENT_QUOTES, 'UTF-8') . '</small></span>'
			. '<span class="comment-count"><i class="fa fa-comment-o" aria-hidden="true"></i> ' . $cc . '</span>'
			. '</a>';
	}
}
else {
	echo "<div class=\"alert alert-info\">No blogs posted.</div>";
}
echo "</div></section>";
