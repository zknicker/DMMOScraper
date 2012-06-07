<?php
/*
* ==============================================================================
* Disney MMORPG News Scraper Functions
*
* For use by DisneyMMO.com
* By Zach Knickerbocker
* ==============================================================================
*
* Functions required to run the news scraper.
*/

/*
* ==============================================================================
* Get New Articles
* ==============================================================================
* 
* Returns an array of new articles for a given game, as specified by the passed
* configuration array.
*/
function getNewArticles($scraper_config) {

    $articles = array();
    $new_count = 0;

    // Descend into article links on news aggregation page.
    if($scraper_config['descend']) {

        $aggregation_page = file_get_html($scraper_config['url']);    // retrieve HTML
        
        foreach($aggregation_page->find($scraper_config['article_link']) as $article_link) {
        
            // Parse each article.
            $article_page       =  file_get_html(getQualifiedURL($article_link->href, $scraper_config['url']));
            $article_page       -> set_callback("convertHTML2BBCode");
            $article_block      =  $article_page->find($scraper_config['article_block'], 0);
            
            $article_images     =  getFormattedImages($article_block->find($scraper_config['article_body'], 0)->find("img"));
            $article_preview    =  getFormattedPreview($article_block->find($scraper_config['article_body'], 0)->find("text"));
            $article_title      =  getFormattedTitle($article_block->find($scraper_config['article_title'], 0));
            $article_body       =  getFormattedBody($article_block->find($scraper_config['article_body'], 0));
            
            // Exit early when latest news article is found to avoid double post.
            if($article_title == getLastArticleTitle($scraper_config)) { break; }
            
            // Record article data to return.
            $articles[$new_count++] = array(
            
                'title'     =>      $article_title,
                'body'      =>      ($article_body . $article_images . $article_preview)
            
            );
        
            $article_page->clear();
        
        }
        
        $aggregation_page->clear();

    // Not a news aggregation page, just read news straight up.
    } else {

        $article_page = file_get_html($scraper_config['url']); // Retrieve article.
        $article_page->set_callback("convertHTML2BBCode");
        
        foreach($article_page->find($scraper_config['article_block']) as $article_block) {

            // Parse each article.
            $article_images     =  getFormattedImages($article_block->find($scraper_config['article_body'], 0)->find("img"));
            $article_preview    =  getFormattedPreview($article_block->find($scraper_config['article_body'], 0)->find("text"));
            $article_title      =  getFormattedTitle($article_block->find($scraper_config['article_title'], 0));
            $article_body       =  getFormattedBody($article_block->find($scraper_config['article_body'], 0));
            
            // Exit early when latest news article is found to avoid double post.
            if($article_title == getLastArticleTitle($scraper_config)) { break; }
            
            // Record article data to return.
            $articles[$new_count++] = array(
            
                'title'     =>      $article_title,
                'body'      =>      ($article_body . $article_images . $article_preview)
            
            );
            
        }

        $article_page->clear();
        
    }
    
    $articles = array_reverse($articles);
    return $articles;
    
}

/*
* ==============================================================================
* Get Formatted Title
* ==============================================================================
* 
* Retrieves the news title in a formatted BBCode context.
*/
function getFormattedTitle($title) {

    // Clean output of excessive (more than 2) spaces and/or tabs.
    $output_s = preg_replace("/[[:blank:]]+/", " ", $title->find("text", 0));
    
    // Clean output of whitespace at the beginning and end of the text string.
    $output_t = trim(getCleanedText($output_s));
    
    return $output_t;

}

/*
* ==============================================================================
* Get Formatted Body
* ==============================================================================
* 
* Retrieves the news article (body) in a formatted BBCode context.
*/
function getFormattedBody($article) {

    // Clean output of excessive (more than 2) newlines.
    $output_n = preg_replace("/\n\s*\n/", "\n\n", trim(getCleanedText($article->innertext)));
    
    // Clean output of excessive (more than 2) spaces and/or tabs.
    $output_s = preg_replace("/[[:blank:]]+/", " ", $output_n);
    
    // Clean output of spaces at the beginning and end of each line.
    $output_b = join("\n", array_map("trim", explode("\n", $output_s)));
    
    return $output_b;

}

/*
* ==============================================================================
* Get Formatted Preview
* ==============================================================================
* 
* Retrieves the news article (body) in a formatted BBCode context.
*/
function getFormattedPreview($text) {

    $result = "";

    foreach($text as $line) {
    
        $result .= trim(getCleanedText($line)) . " ";
    
    }
    
    // Clean result of extraneous spaces between words.
    $result_s = preg_replace("/[[:blank:]]+/", " ", $result);
    
    // Clean result of extraneous spaces at the beginning and end.
    $result_c = trim($result_s);
    
    // Shorten result to a workable length and append ellipses.
    $result_e = getShortenedText($result_c, 380);
    
    return "[preview]" . $result_e . "[/preview]";

}

/*
* ==============================================================================
* Get Formatted Images
* ==============================================================================
* 
* Retrieves all images from the news article in a formatted, gallery-like
* BBCode context.
*/
function getFormattedImages($images) {

    $result = "";
    
    if(count($images) != 0) {
    
        $result .= "\n\n[gallery]";

        foreach($images as $image) {
        
            // Skip extraneous images and button images from game news.
            if(strposi($image->src, "signatures/") != false) { continue; }
            if(strposi($image->src, "btns/") != false) { continue; }
            if(strposi($image->src, "btn_upgrade") != false) { continue; }
        
            $parent = $image->parent();
        
            if($parent->tag == "a") {
            
                $result .= "\n\t[gallery-thumb=" .
                           getQualifiedURL($parent->href, $config['url']) .
                           "]" .
                           getQualifiedURL($image->src, $config['url']) .
                           "[/gallery-thumb]";
            
            }
            else {
            
                $qualified_url = getQualifiedURL($image->src, $config['url']);
            
                $result .= "\n\t[gallery-thumb=" .
                           $qualified_url .
                           "]" .
                           $qualified_url .
                           "[/gallery-thumb]";
            
            }
            
        }
        
        $result .= "\n[/gallery]";
    
    }
    
    return $result;

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

    if($element->tag == "div")      {    convertDiv($element);           }
    if($element->tag == "span")     {    convertSpan($element);          }
    if($element->tag == "br")       {    convertBreak($element);         }
    if($element->tag == "p")        {    convertParagraph($element);     }
    if($element->tag == "a")        {    convertAnchor($element);        }
    if($element->tag == "img")      {    convertImg($element);           }
    
    if($element->tag == "h1")       {    convertH1($element);            }
    if($element->tag == "h2")       {    convertH2($element);            }
    if($element->tag == "h3")       {    convertH3($element);            }
    if($element->tag == "h4")       {    convertH4($element);            }
    if($element->tag == "h5")       {    convertH5($element);            }
    
    if($element->tag == "b")        {    convertBold($element);          }
    if($element->tag == "strong")   {    convertBold($element);          }
    if($element->tag == "i")        {    convertItalics($element);       }
    if($element->tag == "em")       {    convertItalics($element);       }
    if($element->tag == "u")        {    convertUnderline($element);     }
    if($element->tag == "strike")   {    convertStrike($element);        }
    if($element->tag == "font")     {    convertFont($element);          }
    
    if($element->tag == "ol")       {    convertOList($element);         }
    if($element->tag == "ul")       {    convertUList($element);         }
    if($element->tag == "li")       {    convertListItem($element);      }
    
    if($element->tag == "table")    {    convertTable($element);         }
    if($element->tag == "thead")    {    convertThead($element);         }
    if($element->tag == "tbody")    {    convertTbody($element);         }
    if($element->tag == "tr")       {    convertTr($element);            }
    if($element->tag == "td")       {    convertTd($element);            }
    
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
    
    // <div> tag general scenario.
    else {

        $element->outertext = getCleanedText($element->innertext) . "\n";
    }
}

/*
* ==============================================================================
* Convert <span>
* ==============================================================================
*/
function convertSpan($element) {

    $element->outertext = getCleanedText($element->innertext);

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
    else if(strposi($element->innertext, "Leave a Comment") != false
         || $element->class == "read-more"
         || $element->class == "pager-permalink"
         || $element->class == "pager-permalink-adjust") {
    
        $element->outertext = "";
    
    }
    
    // <p> tag general scenario.
    else {

        $element->outertext = "\n" . getCleanedText($element->innertext) . "\n";
    }
}

/*
* ==============================================================================
* Convert <a>
* ==============================================================================
*/
function convertAnchor($element) {

    // Remove <a> tags, but preserve contents, with img content (i.e. thumbnails)
    if($element->find("img") != null) {
    
        $element->outertext = getCleanedText($element->innertext);
    
    }
    
    // Remove <a> tag indicating comments count on PH.
    else if($element->class == "commentsLink") {
    
        $element->outertext = "";
    
    }
    
    // <a> tag general scenario.
    else {

        // Form anchor style to BBCode equivalent.
        $element->outertext = "[url=" . $element->href . "]" . getCleanedText($element->innertext) . "[/url]";
    
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
    $element->outertext = "[size=120][b]" . getCleanedText($element->innertext) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <h2>
* ==============================================================================
*/
function convertH2($element) {

    // Form H2 style to BBCode equivalent.
    $element->outertext = "[size=140][b]" . getCleanedText($element->innertext) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <h3>
* ==============================================================================
*/
function convertH3($element) {

    // Form H3 style to BBCode equivalent.
    $element->outertext = "[size=160][b]" . getCleanedText($element->innertext) . "[/b][/size]";

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
    
        $element->outertext = "[size=180][b]" . getCleanedText($element->innertext) . "[/b][/size]";

    }
    
}

/*
* ==============================================================================
* Convert <h5>
* ==============================================================================
*/
function convertH5($element) {

    // Form H5 style to BBCode equivalent.
    $element->outertext = "[size=200][b]" . getCleanedText($element->innertext) . "[/b][/size]";

}

/*
* ==============================================================================
* Convert <b>, <strong>
* ==============================================================================
*/
function convertBold($element) {

    // Don't include the tag if it has no text contents.
    if($element->find("text") == null) {
    
        $element->outertext = getCleanedText($element->innertext);
    
    }
    
    // Form bold style to BBCode equivalent.
    else {
    
        $element->outertext = "[b]" . getCleanedText($element->innertext) . "[/b]";
    
    }

}

/*
* ==============================================================================
* Convert <i>, <em>
* ==============================================================================
*/
function convertItalics($element) {

    // Don't include the tag if it has no text contents.
    if($element->find("text") == null) {
    
        $element->outertext = getCleanedText($element->innertext);
    
    }
    
    // Form italics style to BBCode equivalent.
    else {
    
        $element->outertext = "[i]" . getCleanedText($element->innertext) . "[/i]";
        
    }

}

/*
* ==============================================================================
* Convert <u>
* ==============================================================================
*/
function convertUnderline($element) {

    // Don't include the tag if it has no text contents.'
    if($element->find("text") == null) {
    
        $element->outertext = getCleanedText($element->innertext);
    
    }
    
    // Form underline style to BBCode equivalent.
    else {
    
        $element->outertext = "[u]" . getCleanedText($element->innertext) . "[/u]";

    }
}

/*
* ==============================================================================
* Convert <s>
* ==============================================================================
*/
function convertStrike($element) {

    // Form strike style to BBCode equivalent.
    $element->outertext = trim(getCleanedText($element->innertext));

}

/*
* ==============================================================================
* Convert <font>
* ==============================================================================
*/
function convertFont($element) {

    // Remove font tag if a font tag is nested within.
    if($element->find("font") != null) {
    
        $element->outertext = getCleanedText($element->innertext);
    
    }

    // Form font style with size to BBCode equivalent.
    else if($element->size != null) {
    
        $element->outertext = "[size=" . ($element->size * 35) . "]" . trim(getCleanedText($element->innertext)) . "[/size]";
        
    }
    
    // Form badly construct font tag to generic text.
    else {
    
        $element->outertext = getCleanedText($element->innertext);
    
    }

}

/*
* ==============================================================================
* Convert <ol>
* ==============================================================================
*/
function convertOList($element) {

    // Form ordered list style to BBCode equivalent.
    $element->outertext = "[list=1]" . getCleanedText($element->innertext) . "[/list]";

}

/*
* ==============================================================================
* Convert <ul>
* ==============================================================================
*/
function convertUList($element) {

    // Form unordered list style to BBCode equivalent.
    $element->outertext = "\n\n[list]" . getCleanedText($element->innertext) . "[/list]\n\n";

}

/*
* ==============================================================================
* Convert <li>
* ==============================================================================
*/
function convertListItem($element) {

    // Form list item style to BBCode equivalent.
    $element->outertext = "[*]" . getCleanedText($element->innertext);

}

/*
* ==============================================================================
* Convert <table>
* ==============================================================================
*/
function convertTable($element) {

    // Form table style to BBCode equivalent.
    $element->outertext = "\n\n[table]" . trim(getCleanedText($element->innertext)) . "[/table]\n\n";

}

/*
* ==============================================================================
* Convert <thead>
* ==============================================================================
*/
function convertThead($element) {

    // Form thead style to BBCode equivalent.
    $element->outertext = "[thead]" . trim(getCleanedText($element->innertext)) . "[/thead]";

}

/*
* ==============================================================================
* Convert <tbody>
* ==============================================================================
*/
function convertTbody($element) {

    // Form tbody style to BBCode equivalent.
    $element->outertext = "[tbody]" . trim(getCleanedText($element->innertext)) . "[/tbody]";

}

/*
* ==============================================================================
* Convert <tr>
* ==============================================================================
*/
function convertTr($element) {

    // Form tr style to BBCode equivalent.
    $element->outertext = "[tr]" . trim(getCleanedText($element->innertext)) . "[/tr]";

}

/*
* ==============================================================================
* Convert <td>
* ==============================================================================
*/
function convertTd($element) {

    // Form td style to BBCode equivalent.
    $element->outertext = "[td]" . trim(getCleanedText($element->innertext)) . "[/td]";

}

/*
* ==============================================================================
* Get Cleaned Text
* ==============================================================================
* 
* Returns the text parameter sans miscellaneous text strings which need to be
* replaced with alternatives. Inspired by Club Penguin's inane tags and other
* inconsistent practices.
*/
function getCleanedText($text) {

    $find = array("<o:p>", "</o:p>", "&nbsp;");
    $replace = array("", "", " ");

    return str_replace($find, $replace, $text);

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
* Get Shortened Text (And Add Ellipses)
* ==============================================================================
* 
* Returns passed text with an ellipses placed at a location as close to the
* desired text length as possible without interrupting a word.
*
* Based off of an implementation by Elliott Brueggeman:
* http://www.ebrueggeman.com/blog/abbreviate-text-without-cutting-words-in-half
*/
function getShortenedText($input, $length) {
  
    // If text is already within length, return it.
    if (strlen($input) <= $length) {
    
        return $input;
    
    }
  
    // Trim text at position of last space within desired text length.
    $last_space = strrpos(substr($input, 0, $length), ' ');
    $trimmed_text = substr($input, 0, $last_space);
  
    // Add ellipses.
    $trimmed_text .= "&#8230";
  
    return $trimmed_text;
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

    $haystack = strtolower($haystack); 
    $needle   = strtolower($needle); 
    
    return strpos($haystack, $needle);

}

?>