<?php

/******************
 * Updates the adexclusion table to include all translations
 * of english articles that are already excluded.
 ******************/

require_once __DIR__ . '/../commandLine.inc';

ArticleAdExclusions::updateEnglishArticles();
