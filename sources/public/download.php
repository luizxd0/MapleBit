<?php
if(basename($_SERVER["PHP_SELF"]) == "download.php") {
	die("403 - Access Forbidden");
}
?>
<h2 class="text-left">Downloads</h2><hr/>
<p class="page-lead">Everything you need to enter the local v83 world.</p>
<div class="download-grid">
	<a class="download-card" href="<?php echo htmlspecialchars($server, ENT_QUOTES, 'UTF-8'); ?>">
		<img src="assets/img/DL/setup.png" alt="" class="img-fluid">
		<span><small>Step one</small><strong>Server setup</strong><em>Install the required local files</em></span>
		<i class="fa fa-arrow-circle-down" aria-hidden="true"></i>
	</a>
	<a class="download-card" href="<?php echo htmlspecialchars($client, ENT_QUOTES, 'UTF-8'); ?>">
		<img src="assets/img/DL/client.png" alt="" class="img-fluid">
		<span><small>Step two</small><strong>Game client</strong><em>Launch the GMS v83 client</em></span>
		<i class="fa fa-arrow-circle-down" aria-hidden="true"></i>
	</a>
</div>
