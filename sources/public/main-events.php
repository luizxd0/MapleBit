<?php
if(basename($_SERVER["PHP_SELF"]) == "main-events.php") {
	die("403 - Access Forbidden");
}
echo "
	<section class=\"home-widget event-widget\">
	<div class=\"widget-heading\">
		<span class=\"widget-icon event-icon\"><i class=\"fa fa-calendar\" aria-hidden=\"true\"></i></span>
		<div><small>What's happening</small><h3>Events</h3></div>
		<a href=\"?base=main&amp;page=events\">View all <i class=\"fa fa-angle-right\" aria-hidden=\"true\"></i></a>
	</div>
	<div class=\"widget-list\">
";

$ge = $mysqli->query("SELECT * FROM ".$prefix."events ORDER BY id DESC LIMIT 4");
if($ge && $ge->num_rows) {
	while($e = $ge->fetch_assoc()) {
		$gc = $mysqli->query("SELECT * FROM ".$prefix."ecomments WHERE eid='".$e['id']."' ORDER BY id ASC");
		$cc = $gc->num_rows;
		echo '<a class="widget-row" href="?base=main&amp;page=events&amp;id=' . (int) $e['id'] . '">'
			. '<span class="event-date"><strong>' . htmlspecialchars(substr($e['date'], 0, 2), ENT_QUOTES, 'UTF-8')
			. '</strong><small>event</small></span>'
			. '<span><strong>' . htmlspecialchars(ellipsize($e['title'], 34, 1, '...'), ENT_QUOTES, 'UTF-8')
			. '</strong><small>' . htmlspecialchars($e['date'], ENT_QUOTES, 'UTF-8') . '</small></span>'
			. '<span class="comment-count"><i class="fa fa-comment-o" aria-hidden="true"></i> ' . $cc . '</span>'
			. '</a>';
	}
}
else {
	echo "<div class=\"alert alert-info\">No events posted.</div>";
}
echo "</div></section>";
