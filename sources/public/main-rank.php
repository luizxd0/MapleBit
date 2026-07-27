<?php
if (basename($_SERVER['PHP_SELF']) === 'main-rank.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

if ($servertype == 1) {
    $first = 'reborns';
    $second = 'level';
} else {
    $first = 'level';
    $second = 'exp';
}

$rankingResult = $mysqli->query(
    "SELECT c.{$first}, c.{$second}, c.name, c.accountid "
    . 'FROM characters c LEFT JOIN accounts a ON c.accountid = a.id '
    . "WHERE c.gm < '{$gmlevel}' AND COALESCE(a.banned, 0) = 0 "
    . "ORDER BY c.{$first} DESC, c.{$second} DESC LIMIT 5"
);
$players = $rankingResult ? $rankingResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<section class="home-widget ranking-widget">
    <div class="widget-heading">
        <span class="widget-icon rank-icon"><i class="fa fa-trophy" aria-hidden="true"></i></span>
        <div><small>Hall of fame</small><h3>Top Maplers</h3></div>
        <a href="?base=main&amp;page=rankings">Full rankings <i class="fa fa-angle-right" aria-hidden="true"></i></a>
    </div>
    <?php if ($players): ?>
        <?php $leaderName = $players[0]['name']; ?>
        <div class="ranking-showcase">
            <div class="ranking-leader">
                <span class="leader-crown"><i class="fa fa-star" aria-hidden="true"></i> #1</span>
                <img src="assets/img/GD/create.php?name=<?php echo rawurlencode($leaderName); ?>"
                     alt="<?php echo htmlspecialchars($leaderName, ENT_QUOTES, 'UTF-8'); ?>"
                     id="top5" class="rank_img">
                <strong><?php echo htmlspecialchars($leaderName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <small><?php echo ucfirst($first); ?> <?php echo number_format((int) $players[0][$first]); ?></small>
            </div>
            <ol class="ranking-list">
                <?php foreach ($players as $position => $player): ?>
                    <?php $playerName = $player['name']; ?>
                    <li>
                        <span class="rank-position"><?php echo $position + 1; ?></span>
                        <a href="?base=main&amp;page=character&amp;n=<?php echo rawurlencode($playerName); ?>"
                           onmouseover="roll('top5', 'assets/img/GD/create.php?name=<?php echo rawurlencode($playerName); ?>')">
                            <?php echo htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <span><small><?php echo ucfirst($first); ?></small>
                            <strong><?php echo number_format((int) $player[$first]); ?></strong></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php else: ?>
        <div class="widget-list"><div class="alert alert-info">No characters found.</div></div>
    <?php endif; ?>
</section>
