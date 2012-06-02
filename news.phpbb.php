<?php
/*
* ==============================================================================
* Disney MMORPG News Scraper - Post to PHPBB
*
* For use by DisneyMMO.com
* By Zach Knickerbocker
* ==============================================================================
*
* Coordinates posting of news articles to PHPBB.
*/

function postNewsToPHPBB($configg, $articles) {

	define('IN_PHPBB', true);
	$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../../../public_html/community/';
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	
	// Includes.
	include($phpbb_root_path . 'common.' . $phpEx);
	include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
	 
	// Start session management.
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup();
	
	// Authenticate as a new user.	
	$sql= 'SELECT u.* FROM '. USERS_TABLE . ' u WHERE u.user_id = ' . $configg['userid'];
	$result= $db->sql_query($sql);
	
	// Populate user data with fetched data.
	if ($row= $db->sql_fetchrow($result)) {
	
		foreach( $row as $k1 => $v1 ) {
		
			if( isset($user->data[$k1])) {
			
				$user-> data[$k1] = $v1;
		
			}
		}
	
	};

	$db-> sql_freeresult($result);
	$auth-> acl($user->data); // permissions
	
	// Post articles to PHPBB.
	foreach($articles as $article) {
	
		submitPost($article['title'], $article['body']);
	
	}
	
}

function submitPost($title, $subject) {

	// note that multibyte support is enabled here 
	$subject = utf8_normalize_nfc(request_var('subject', $title, true));
	$text    = utf8_normalize_nfc(request_var('text', $body, true));

	// variables to hold the parameters for submit_post
	$poll = $uid = $bitfield = $options = ''; 

	generate_text_for_storage($subject, $uid, $bitfield, $options, false, false, false);
	generate_text_for_storage($text, $uid, $bitfield, $options, true, true, true);

	$data = array( 
		'forum_id'      	=> $configg['forumid'],
		'icon_id'       	=> false,

		'enable_bbcode'     => true,
		'enable_smilies'    => true,
		'enable_urls'       => true,
		'enable_sig'        => true,

		'message'       	=> $text,
		'message_md5'   	=> md5($text),
			
		'bbcode_bitfield'   => $bitfield,
		'bbcode_uid'        => $uid,

		'post_edit_locked'  => 0,
		'topic_title'       => $subject,
		'notify_set'        => false,
		'notify'            => false,
		'post_time'         => 0,
		'forum_name'        => '',
		'enable_indexing'   => true,
	);
	
	echo $title;
	//submit_post('post', $subject, '', POST_NORMAL, $poll, $data);

}

?>	