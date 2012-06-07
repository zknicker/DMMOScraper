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
*    - Club Penguin
*    - Pixie Hollow
*    - Pirates Online
*    - ToonTown
*/

// Parameters.
$arg_game = isset($argv[1]) ? $argv[1] : exit("ERROR: No MMO abbreviation specified.");

// Includes.
chdir("/home/xiris/bots/news");
include "php/simple_html_dom.php";
include "php/news.config.php";
include "php/news.functions.php";
include "php/news.log.php";

// Get configuration information.
$scraper_config = getConfig($arg_game);

// Let's start. Open log.
openLogInstance();
postToLog("Beginning parse of " . $arg_game . " news.");

// Get new articles to be posted.
$articles = getNewArticles($scraper_config);
postToLog("Retrieved " . (count($articles) != 0 ? count($articles) : "no") . " new articles to post.");

if(count($articles) > 0) {

    // Post articles to PHPBB.
    include "news.phpbb.php";
    postArticlesToPHPBB($scraper_config, $articles);
    postToLog("Posted " . (count($articles)) . " new articles to PHPBB.");

    // Updates record of last article posted to PHPBB.
    updateLastArticleTitle($scraper_config, $articles[sizeof($articles) - 1]['title']);
    postToLog("Updated last news article posted to most recent.");
    
} else {

    postToLog("No new articles posted to PHPBB.");
    postToLog("Last news article record is already up to date.");

}

// All done. Close log.
postToLog("Parsing completed. Exiting normally.");
closeLogInstance();

/*
* ==============================================================================
* Output News Articles (Debugging)
* ==============================================================================
* 
* Outputs news articles to the console. Use this to debug.
*/
function outputNewsArticles() {

    global $articles;

    foreach($articles as $article) {

        echo "----------\n\n" . $article['title'] . "\n\n";
        echo $article['body'] . "\n\n";

    }

}

?>