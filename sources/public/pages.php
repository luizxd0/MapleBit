<?php
if(basename($_SERVER["PHP_SELF"]) == "pages.php") {
	die("403 - Access Forbidden");
}
$query = $mysqli->query("SELECT * FROM ".$prefix."pages WHERE slug = '".$main."'");
if($query->num_rows == 0) {
	echo "<div class=\"alert alert-danger\">This page doesn't exist.</div>";
	redirect_wait5("?base=main");
}
else {
	$p = $query->fetch_assoc();
	require_once 'assets/libs/maple_html.php';
	$clean_html = maple_clean_html($p['content'], true);
	echo "
		<h2 class=\"text-left\">".$p['title']."</h2>
		<hr/>
		".$clean_html."
	";
}
