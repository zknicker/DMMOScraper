<?php
/*
* ==============================================================================
* Disney MMORPG News Scraper Configuration
*
* For use by DisneyMMO.com
* By Zach Knickerbocker
* ==============================================================================
*
* Configuration information for the news parser. A configuration array is
* returned via the provided getConfig() function.
*/

$config_cp = array(

	"url"					=>		"http://community.clubpenguin.com/blog/",
	"descend"				=>		false,
	"article_link"			=>		null,
	"article_block"			=>		".blog_entry",
	"article_title"			=>		".blog_head a",
	"article_body"			=>		".blog_copy",
	
	"last_article_path"		=>		"cp.last",
	"userid"				=>		762,
	"forumid"				=>		32,

);

$config_ph = array(

	"url"					=>		"http://blog.pixiehollow.go.com/blog/nevernews/",
	"descend"				=>		true,
	"article_link"			=>		".entry-content h3 a",
	"article_block"			=>		".blog",
	"article_title"			=>		"h2",
	"article_body"			=>		".entry",
	
	"last_article_path"		=>		"ph.last",
	"userid"				=>		848,
	"forumid"				=>		33,

);

$config_po = array(

	"url"					=>		"http://blog.piratesonline.go.com/blog/pirates/",
	"descend"				=>		false,
	"article_link"			=>		null,
	"article_block"			=>		".news_article",
	"article_title"			=>		".news_title",
	"article_body"			=>		".news_body",
	
	"last_article_path"		=>		"po.last",
	"userid"				=>		847,
	"forumid"				=>		34,

);

$config_tt = array(

	"url"					=>		"http://blog.toontown.com/blog/toontown/",
	"descend"				=>		true,
	"article_link"			=>		".blogBdy .readmore",
	"article_block"			=>		".blogEntries",
	"article_title"			=>		".blogTitle",
	"article_body"			=>		".blogBdy",
	
	"last_article_path"		=>		"tt.last",
	"userid"				=>		849,
	"forumid"				=>		35,

);


/*
* ============================================================
* Get Config
* ============================================================
* 
* When passed an abbreviation for a game (e.g. PH, CP, PO),
* a configuration array is returned.
*/
function getConfig($game_abbr) {

	/* declare globals */
	global $config_cp;
	global $config_ph;
	global $config_po;
	global $config_tt;

	/* return requested config file */
	if($game_abbr == "cp") { return $config_cp; }
	if($game_abbr == "ph") { return $config_ph; }
	if($game_abbr == "po") { return $config_po; }
	if($game_abbr == "tt") { return $config_tt; }
	
}