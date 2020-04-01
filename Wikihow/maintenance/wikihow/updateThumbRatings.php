<?php
/*
 * This maintenance script runs nightly and removes ratings tips and warnings 
 * that have been removed from the articles
 */

require_once __DIR__ . '/../commandLine.inc';

require_once("$IP/extensions/wikihow/thumbratings/ThumbRatingsMaintenance.class.php");

$wgUser = User::newFromName('Votebot');
$trMaint = new ThumbRatingsMaintenance();
$trMaint->refreshArticleVotes();
