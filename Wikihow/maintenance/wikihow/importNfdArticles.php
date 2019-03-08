<?php
/*
 * Imports articles that have a NFD template on them. Checks
 * to see if they already exist in the NFD table.
 */

global $IP;
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/nfd/NFDGuardian.body.php");

NFDGuardian::importNFDArticles();

NFDGuardian::checkArticlesInNfdTable();
