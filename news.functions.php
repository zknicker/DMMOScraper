<?php
/*
* ==============================================================================
* Disney MMORPG News Scraper Functions
*
* For use by DisneyMMO.com
* By Zach Knickerbocker
* ==============================================================================
*
* Those functions not related to the parsing callback function, but required
* for execution of the news.php parser, are included here.
*/

/*
* ==============================================================================
* Get New Articles
* ==============================================================================
* 
* Returns an array of new articles for a given game, as specified by the passed
* configuration array.
*/
function getNewArticles($config) {

	$articles = array();
	$new_count = 0;

	if($config['descend']) {

		$aggregation_page = file_get_html($config['url']);	// retrieve HTML
		
		foreach($aggregation_page->find($config['article_link']) as $article_link) {
		
			// Parse each article.
			$article_page = file_get_html(getQualifiedURL($article_link->href, $config['url']));
			$article_page->set_callback("convertHTML2BBCode");
			$article_block = $article_page->find($config['article_block'], 0);
			
			$article_title = getFormattedTitle($article_block->find($config['article_title'], 0));
			$article_body = getFormattedBody($article_block->find($config['article_body'], 0));
			
			// Exit early when latest news article is found to avoid double post.
			if($article_title == getLastArticleTitle($config)) { break; }
			
			// Record article data to return.
			$articles[$new_count++] = array(
			
				'title'		=>		$article_title,
				'body'		=>		$article_body
			
			);
		
			$article_page->clear();
		
		}
		
		$aggregation_page->clear();

	} else {

		$article_page = file_get_html($config['url']); // Retrieve article.
		$article_page->set_callback("convertHTML2BBCode");
		
		foreach($article_page->find($config['article_block']) as $article_block) {

			// Parse each article.
			$article_title = getFormattedTitle($article_block->find($config['article_title'], 0));
			$article_body = getFormattedBody($article_block->find($config['article_body'], 0));
			
			// Exit early when latest news article is found to avoid double post.
			if($article_title == getLastArticleTitle($config)) { break; }
			
			// Record article data to return.
			$articles[$new_count++] = array(
			
				'title'		=>		$article_title,
				'body'		=>		$article_body
			
			);
			
		}

		$article_page->clear();
		
	}
	
	return $articles;
	
}

/*
* ==============================================================================
* Get Formatted Title
* ==============================================================================
* 
* Retrieves the news title in a formatted BB code context.
*/
function getFormattedTitle($title) {

	// Clean output of excessive (more than 2) spaces and/or tabs.
	$output_s = preg_replace("/[[:blank:]]+/", " ", $title->find("text", 0));
	
	// Clean output of whitespace at the beginning and end of the text string.
	$output_t = trim($output_s);
	
	return $output_t;

}

/*
* ==============================================================================
* Get Formatted Body
* ==============================================================================
* 
* Retrieves the news article (body) in a formatted BB code context.
*/
function getFormattedBody($article) {

	// Clean output of excessive (more than 2) newlines.
	$output_n = preg_replace("/\n\s*\n/", "\n\n", trim($article->innertext));
	
	// Clean output of excessive (more than 2) spaces and/or tabs.
	$output_s = preg_replace("/[[:blank:]]+/", " ", $output_n);
	
	// Clean output of spaces at the beginning and end of each line.
	$output_b = join("\n", array_map("trim", explode("\n", $output_s)));
	
	return $output_b;

}

/*
* ==============================================================================
* Get Inner Text
* ==============================================================================
* 
* Return the inner text of an element excluding specific
* characters and strings.
*/
function getInnerText($element) {

	$find = array("<o:p>", "</o:p>", "&nbsp;");
	$replace = array("", "", " ");

	return str_replace($find, $replace, $element->innertext);

}

/*
* ==============================================================================
* Get Qualified URL
* ==============================================================================
* 
* Returns a fully qualified URL from a given (possibly relative) URL and the
* game's root URL. If the URL passed to this function is already full qualified,
* it will just be returned untouched.
*
* A URL is considered to be relative if it does not contain a ".com". All
* Disney MMORPG's use a .com domain. This function should be expanded upon if
* in the future Disney uses domains with different top level domain names.
*/
function getQualifiedURL($url, $url_root) {

	if(strposi($url, ".com") != false) {
		
		// Absolute URL.
		return $url;
	
	} else {
	
		// Relative URL.
		return $url_root . $url;
	
	}

}

/*
* ==============================================================================
* Convert HTML2BBCode (Callback)
* ==============================================================================
* 
* Callback function called by "Simple HTML DOM" parser
* when outputting an element's text. This function delegates
* responsibilities of converting specific tags to relevant
* functions. In the end, a *rough* conversion from HTML to BBCode
* is achieved. This process is not perfect, especially given the
* unfortunately inconsistent state of formatting in Disney's news
* posts across all of the MMOs.
*/
function convertHTML2BBCode($element) {

	if($element->tag == "div")		{	convertDiv($element);			}
	if($element->tag == "span")		{	convertSpan($element);			}
	if($element->tag == "br")		{	convertBreak($element);			}
	if($element->tag == "p") 		{	convertParagraph($element);		}
	if($element->tag == "a") 		{	convertAnchor($element);		}
	if($element->tag == "img") 		{	convertImg($element);			}
	
	if($element->tag == "h1")		{	convertH1($element);			}
	if($element->tag == "h2")		{	convertH2($element);			}
	if($element->tag == "h3")		{	convertH3($element);			}
	if($element->tag == "h4")		{	convertH4($element);			}
	if($element->tag == "h5")		{	convertH5($element);			}
	
	if($element->tag == "b")		{	convertBold($element);			}
	if($element->tag == "strong") 	{	convertBold($element);			}
	if($element->tag == "i")		{	convertItalics($element);		}
	if($element->tag == "em")		{	convertItalics($element);		}
	if($element->tag == "u") 		{	convertUnderline($element);		}
	if($element->tag == "strike") 	{	convertStrike($element);		}
	if($element->tag == "font") 	{	convertFont($element);			}
	
	if($element->tag == "ol")		{	convertOList($element);			}
	if($element->tag == "ul") 		{	convertUList($element);			}
	if($element->tag == "li") 		{	convertListItem($element);		}
	
	if($element->tag == "table")	{	convertTable($element);			}
	if($element->tag == "thead") 	{	convertThead($element);			}
	if($element->tag == "tbody") 	{	convertTbody($element);			}
	if($element->tag == "tr") 		{	convertTr($element);			}
	if($element->tag == "td") 		{	convertTd($element);			}
	
}

/*
* ==============================================================================
* Convert <div>
* ==============================================================================
*/
function convertDiv($element) {

	// Empty <div> tag.
	if($element->innertext == "") {
	
		$element->outertext = "";
	}
	
	// Ignore PO "upgrade" text.
	else if(strposi($element->innertext, "Upgrade to Unlimited Access") != false) {
	
		$element->outertext = "";
	}
	
	// Ignore TT "member" text.
	else if(strposi($element->innertext, "Become a Member") != false) {
	
		$element->outertext = "";
	}
	
	// <div> tag with a closing break.
	else if($element->last_child()->tag == "br") {
	
		$element->outertext = getInnerText($element);
	
	}
	
	// <div> tag general scenario.
	else {

		$element->outertext = getInnerText($element) . "\n";
	}
}

/*
* ==============================================================================
* Convert <span>
* ==============================================================================
*/
function convertSpan($element) {

	$element->outertext = trim($element->innertext);

}

/*
* ==============================================================================
* Convert <br>
* ==============================================================================
*/
function convertBreak($element) {

	// <br> tag with image sibling preceeding it.
	if($element->prev_sibling()->tag == "img") {
	
		$element->outertext = "";
	}
	
	// <br> tag general scenario.
	else {
	
		$element->outertext = "\n";
	}
}

/*
* ==============================================================================
* Convert <p>
* ==============================================================================
*/
function convertParagraph($element) {

	// Ignore empty <p> tag.
	if($element->innertext == "") {
	
		$element->outertext = "";
	}
	
	// Replace <p> tag containing an img and other junk, with just the img.
	if($element->find("text") == null
		&& $element->find("img", 0) != null) {
	
		$element->outertext = $element->find("img", 0)->outertext;
	}

	// Ignore PO "upgrade" text.
	else if(strposi($element->innertext, "Upgrade to Unlimited Access") != false) {
	
		$element->outertext = "";
	}
	
	// Ignore TT "member" text.
	else if(strposi($element->innertext, "Become a Member") != false) {
	
		$element->outertext = "";
	}
	
	// Ignore unimportant PH content.
	else if(strposi($element->innertext, "Leave a Comment") != false	/* "leave a comment" content */
		 || $element->class == "read-more"								/* "read more" content */
		 || $element->class == "pager-permalink"						/* pagination content */
		 || $element->class == "pager-permalink-adjust") {				/* pagination content */
	
		$element->outertext = "";
	
	}
	
	// <p> tag general scenario.
	else {

		$element->outertext = "\n" . getInnerText($element) . "\n";
	}
}

/*
* ==============================================================================
* Convert <a>
* ==============================================================================
*/
function convertAnchor($element) {

	// Remove <a> tag indicating comments count on PH.
	if($element->class == "commentsLink") {
	
		$element->outertext = "";
	
	}
	
	// <a> tag general scenario.
	else {

		// Form anchor style to BBCode equivalent.
		$element->outertext = "[url=" . $element->href . "]" . getInnerText($element) . "[/url]";
	
	}
}

/*
* ==============================================================================
* Convert <img>
* ==============================================================================
*/
function convertImg($element) {

	// Remove <img> tag with a left/right alignment.
	if($element->align != null) {
		
		$element->outertext = "";
	
	}
	
	// Translate PH signature <img> into text via alt attribute.
	if(strposi($element->src, "signatures/") != false) {
	
		$element->outertext = $element->alt;
	
	}
	
	// Remove <img> tag in general scenario.
	else {
	
		$element->outertext = "";
	
	}
}

/*
* ==============================================================================
* Convert <h1>
* ==============================================================================
*/
function convertH1($element) {

	// Form H1 style to BBCode equivalent.
	$element->outertext = "[size=120][b]" . getInnerText($element) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <h2>
* ==============================================================================
*/
function convertH2($element) {

	// Form H2 style to BBCode equivalent.
	$element->outertext = "[size=140][b]" . getInnerText($element) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <h3>
* ==============================================================================
*/
function convertH3($element) {

	// Form H3 style to BBCode equivalent.
	$element->outertext = "[size=160][b]" . getInnerText($element) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <h4>
* ==============================================================================
*/
function convertH4($element) {

	// Ignore PH author & date content.
	if(strposi($element->innertext, "Posted by")) {
	
		$element->outertext = "";
	
	}

	// Form H4 style to BBCode equivalent.
	else {
	
		$element->outertext = "[size=180][b]" . getInnerText($element) . "[/b][/size]";

	}
	
}

/*
* ==============================================================================
* Convert <h5>
* ==============================================================================
*/
function convertH5($element) {

	// Form H5 style to BBCode equivalent.
	$element->outertext = "[size=200][b]" . getInnerText($element) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <b>, <strong>
* ==============================================================================
*/
function convertBold($element) {

	// Form bold style to BBCode equivalent.
	$element->outertext = "[b]" . getInnerText($element) . "[/b]";

}

/*
* ==============================================================================
* Convert <i>, <em>
* ==============================================================================
*/
function convertItalics($element) {

	// Form itlaics style to BBCode equivalent.
	$element->outertext = "[i]" . getInnerText($element) . "[/i]";

}

/*
* ==============================================================================
* Convert <u>
* ==============================================================================
*/
function convertUnderline($element) {

	// Form underline style to BBCode equivalent.
	$element->outertext = "[u]" . getInnerText($element) . "[/u]";

}

/*
* ==============================================================================
* Convert <s>
* ==============================================================================
*/
function convertStrike($element) {

	// Form strike style to BBCode equivalent.
	$element->outertext = trim(getInnerText($element));

}

/*
* ==============================================================================
* Convert <font>
* ==============================================================================
*/
function convertFont($element) {

	// Form font style with size to BBCode equivalent.
	if($element->size != null) {
	
		$element->outertext = "[size=" . ($element->size * 35) . "]" . trim($element->innertext) . "[/size]";
		
	}
	
	// Form font style to generic text.
	else {
	
		$element->outertext = $element->innertext;
	
	}

}

/*
* ==============================================================================
* Convert <ol>
* ==============================================================================
*/
function convertOList($element) {

	// Form ordered list style to BBCode equivalent.
	$element->outertext = "[list=1]" . trim($element->innertext) . "[/list]";

}

/*
* ==============================================================================
* Convert <ul>
* ==============================================================================
*/
function convertUList($element) {

	// Form unordered list style to BBCode equivalent.
	$element->outertext = "\n\n[list]" . trim($element->innertext) . "[/list]\n\n";

}

/*
* ==============================================================================
* Convert <li>
* ==============================================================================
*/
function convertListItem($element) {

	// Form list item style to BBCode equivalent.
	$element->outertext = "[*]" . trim($element->innertext) . "[*]";

}

/*
* ==============================================================================
* Convert <table>
* ==============================================================================
*/
function convertTable($element) {

	// Form table style to BBCode equivalent.
	$element->outertext = "\n\n[table]" . trim(getInnerText($element)) . "[/table]\n\n";

}

/*
* ==============================================================================
* Convert <thead>
* ==============================================================================
*/
function convertThead($element) {

	// Form thead style to BBCode equivalent.
	$element->outertext = "[thead]" . trim(getInnerText($element)) . "[/thead]";

}

/*
* ==============================================================================
* Convert <tbody>
* ==============================================================================
*/
function convertTbody($element) {

	// Form tbody style to BBCode equivalent.
	$element->outertext = "[tbody]" . trim(getInnerText($element)) . "[/tbody]";

}

/*
* ==============================================================================
* Convert <tr>
* ==============================================================================
*/
function convertTr($element) {

	// Form tr style to BBCode equivalent.
	$element->outertext = "[tr]" . trim(getInnerText($element)) . "[/tr]";

}

/*
* ==============================================================================
* Convert <td>
* ==============================================================================
*/
function convertTd($element) {

	// Form td style to BBCode equivalent.
	$element->outertext = "[td]" . trim(getInnerText($element)) . "[/td]";

}

/*
* ==============================================================================
* String Position - Case Insensitive
* ==============================================================================
* 
* Compares two strings, each of which is converted to lowercase,
* with the built-in PHP function strpos.
*/
function strposi($haystack, $needle) {

	$haystack = strtolower( $haystack ); 
    $needle   = strtolower( $needle   ); 
	
	return strpos($haystack, $needle);

}

?>