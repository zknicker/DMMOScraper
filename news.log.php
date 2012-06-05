<?php
/*
* ==============================================================================
* Disney MMORPG News Scraper Logger
*
* For use by DisneyMMO.com
* By Zach Knickerbocker
* ==============================================================================
*
* Functionality to log actions taken by the parser.
*/

$log_file_path = "log.txt";
$log_file = null;

/*
* ==============================================================================
* Post To Log
* ==============================================================================
* 
* Post a message to the log with proper formatting.
*/
function postToLog($message) {

	global $log_file;

	// Get formatted timestamp.
	$timestamp = date("[m.d.y] g:ia");

	// Prepend timestamp to message.
	$message = "\n" . $timestamp . ": " . $message;
	
	// Post message to log.
	fwrite($log_file, $message);

}

/*
* ==============================================================================
* Open Log Instance
* ==============================================================================
* 
* Opens the log for writing. It needs to be closed via closeLogInstance().
*/
function openLogInstance() {

	global $log_file_path;
	global $log_file;

	$message = "\n";

	$log_file = fopen($log_file_path, 'a') or die("Failed to open log file.");
	fwrite($log_file, $message);

}

/*
* ==============================================================================
* Close Log Instance
* ==============================================================================
* 
* Closes the log. It needs to first be opened with openLogInstance().
*/
function closeLogInstance() {

	global $log_file_path;
	global $log_file;
	
	fclose($log_file);

}

/*
* ==============================================================================
* Update Last Article
* ==============================================================================
* 
* Update last article posted. This is game specific.
*/
function updateLastArticleTitle($scraper_config, $article_name) {

	$last_file = fopen($scraper_config['last_article_path'], 'w') or die("Failed to open \"last article\" file.");
	fwrite($last_file, $article_name);
	fclose($last_file);
	
}

/*
* ==============================================================================
* Get Last Article
* ==============================================================================
* 
* Return title of last article posted. This is game specific.
*/
function getLastArticleTitle($scraper_config) {

	$last_file = fopen($scraper_config['last_article_path'], 'r') or die("Failed to open \"last article\" file.");
	$article_name = fgets($last_file);
	fclose($last_file);
	
	return $article_name;
	
}

?>