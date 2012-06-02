<?php
/*
* ==============================================================================
* Disney MMORPG News Scraper
*
* For use by DisneyMMO.com
* By Zach Knickerbocker
* ==============================================================================
*
* (!) This file should not be served by the web server, and should not be
*     publicly accessible.
*
* This application scrapes news from Disney's websites (see: news.config.php)
* and converts HTML to BBCode before posting it to the PHPBB forum at
* DisneyMMO.com/Community. Additional logging functionality is provided.
*
* Supported MMORPG's:
*
*	- Club Penguin
*	- Pixie Hollow
*	- Pirates Online
*	- ToonTown
*/

include "simple_html_dom.php";
include "news.config.php";
include "news.functions.php";
include "news.phpbb.php";
include "news.log.php";

// Parameters.
$arg_game = isset($argv[1]) ? $argv[1] : exit("ERROR: No MMO abbreviation specificed.");

// Get configuration information.
$config = getConfig($arg_game);

// Let's start. Open log.
openLogInstance();
postToLog("Beginning parse of " . $arg_game . " news.");

// Get new articles to be posted.
$articles = getNewArticles($config);
postToLog("Retrieved " . (count($articles) != 0 ? count($articles) : "no") . " new articles to post.");

// Post news articles to PHPBB.
postNewsToPHPBB($config, $articles);
postToLog("Posted " . (count($articles) != 0 ? count($articles) : "no") . " new articles to PHPBB.");

// Update "last news article" log.
if(count($articles) > 0 && false) {

	updateLastArticleTitle($config, $articles[0]['title']);
	postToLog("Updated last news article posted to most recent.");
	
} else {

	postToLog("Last news article record is already up to date.");

}

// All done. Close log.
closeLogInstance();

function postNewArticles() {

	global $articles;

	foreach($articles as $article) {

		echo "----------\n\n" . $article['title'] . "\n\n";
		echo $article['body'] . "\n\n";

	}

}

?>