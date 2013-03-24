<?php
/*
Plugin Name: Clipjet
Plugin URI: http://www.clipjet.co
Description: Clipjet rockets your videos to thousands of views.
Version: 0.1
Author: The Awesome Clipjet Team
License: GPL2
*/

/*  Copyright 2013 Clipjet

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
add_action('init','cj_init');
add_action('add_meta_boxes', 'cj_meta_box_add' ); 
add_action('save_post', 'cj_meta_box_save' ); 
add_action('admin_init', 'cj_admin_init');
add_action('admin_menu', 'cj_plugin_menu' );
add_filter('the_content', 'cj_show_video' );  

function do_get_request($url, $params, $verb = 'GET', $format = 'json')

  {
  $cparams = array(
    'http' => array(
      'method' => $verb,
      'ignore_errors' => true
    )
  );
  if ($params !== null) {
    $params = http_build_query($params);
    if ($verb == 'POST') {
      $cparams['http']['content'] = $params;
    } else {
      $url .= '?' . $params;
    }
  }

  $context = stream_context_create($cparams);
  $fp = fopen($url, 'rb', false, $context);
  if (!$fp) {
    $res = false;
  } else {
    // If you're trying to troubleshoot problems, try uncommenting the
    // next two lines; it will show you the HTTP response headers across
    // all the redirects:
    // $meta = stream_get_meta_data($fp);
    // var_dump($meta['wrapper_data']);
    $res = stream_get_contents($fp);
  }

  if ($res === false) {
    throw new Exception("$verb $url failed: $php_errormsg");
  }

  switch ($format) {
    case 'json':
      $r = json_decode($res);
      if ($r === null) {
        throw new Exception("failed to decode $res as json");
      }
      return $r;

    case 'xml':
      $r = simplexml_load_string($res);
      if ($r === null) {
        throw new Exception("failed to decode $res as xml");
      }
      return $r;
  }
  //return $res;
  return json_decode($res);
}


function cj_init() {
    wp_register_script( 'youtube-api','http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js');
    wp_register_script( 'clipjet-js', plugins_url( '/clipjet.js', __FILE__ ) );
    
    wp_enqueue_script('youtube-api');
    wp_enqueue_script('clipjet-js');
}

function cj_meta_box_add()  
{  
    add_meta_box( 'add-network-meta-box', 'Ad Network Tag', 'cj_meta_box_cb', 'post', 'normal', 'high' );  
}  


function cj_meta_box_cb( $post )
{
    
    $tags = do_get_request('http://www.clipjet.co/categories', array());
    
    //var_dump($tags); exit();
    
    $values = get_post_custom( $post->ID );
    $selected = isset( $values['clipjet-tag'] ) ? esc_attr( $values['clipjet-tag'][0] ) : '';
    	?>
	
	<p>
            <label for="clipjet-tag">Tag</label>
            <select name="clipjet-tag" id="clipjet-tag">
                <?php foreach($tags as $tag): ?>                    
                    <option value="<?php echo $tag->id ?>" <?php selected( $selected, $tag->id); ?>><?php echo $tag->name ?></option>
                <?php endforeach; ?>
            </select>
	</p>
	<?php	
}

function cj_meta_box_save( $post_id )  
{      
    // Bail if we're doing an auto save  
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 
         
    // if our current user can't edit this post, bail  
    if( !current_user_can( 'edit_post' ) ) return;  
      
    // now we can actually save the data  
    $allowed = array(   
        'a' => array( // on allow a tags  
            'href' => array() // and those anchors can only have href attribute  
        )  
    );  
      
    if( isset( $_POST['clipjet-tag'] ) )  
        update_post_meta( $post_id, 'clipjet-tag', esc_attr( $_POST['clipjet-tag'] ) );  
          
    // This is purely my personal preference for saving check-boxes  
    $chk = isset( $_POST['my_meta_box_check'] ) && $_POST['my_meta_box_select'] ? 'on' : 'off';  
    update_post_meta( $post_id, 'my_meta_box_check', $chk );  
}  


function cj_show_video( $content )  
{  
    // We only want this on single posts, bail if we're not in a single post  
    if( !is_single() ) return $content;  
      
    // We're in the loop, so we can grab the $post variable  
    global $post;  
      
    $tagId = get_post_meta( $post->ID, 'clipjet-tag', true );  

    // Bail if we don't have a quote;  
    if( empty( $tagId ) ) return $content;  
    
    //get video url from server
    
    $values = get_post_custom( $post->ID );
    
    $params = array(
        'email'       => get_option('clipjet_email'),
        //'category_id' => $values['clipjet-tag'][0],
        'category_id' => 1,
        'country_iso' => 'cl'
    );
    
    //var_dump($params); exit();
    
    $response = do_get_request('http://www.clipjet.co/videos/show', $params);
    
    //echo  $response->video_url;exit();
    
    preg_match('![?&]{1}v=([^&]+)!', $response->video_url . '&', $m);
    
    $video_id = $m[1];
    
    //echo $video_id; exit();
    
    $videoUrl = 'http://www.youtube.com/embed/'.$video_id.'?enablejsapi=1&rel=0&showinfo=0';

    //var_dump($videoUrl); 
    
    //error_log('videourl:'.$videoUrl);
    //exit();
    //$videoUrl = 'http://www.youtube.com/embed/q1dpQKntj_w?enablejsapi=1&rel=0&showinfo=0';
      
    //$out = wp_oembed_get($videoUrl, array('width' => 100)); 
    //$out = wp_oembed_get($videoUrl, array('id' => 'clipjetvideo')); 
    $width = get_option('medium_size_w') ? get_option('medium_size_w') : 600;
    $height = get_option('medium_size_h') ? get_option('medium_size_h') : 600;
        
    $out = '<div id="clipjet-hit" style="visibility:hidden;width:0px;height:0px;"></div>
        <div id="clipjet-advertiser" style="visibility:hidden;width:0px;height:0px;">'.$response->advertiser_id.'</div>
        <div id="clipjet-email" style="visibility:hidden;width:0px;height:0px;">'.get_option('clipjet_email').'</div>
        <div style="margin:0 auto 0 auto; width:'.$width.'px;">
            <iframe id="clipjet-video" type="text/html" style="width:'.$width.'px;height:'.$height.'px;" src="'.$videoUrl.'" frameborder="0" allowfullscreen ;noCachePlease='.uniqid().'></iframe>
        </div>';
    //$out .= '<script>function onYouTubePlayerReady(playerId) {ytplayer = document.getElementById("myytplayer"); alert(1);}</script>';
    
    return $out . $content;  
}  

function cj_plugin_menu() {
	add_options_page( 'Clipjet Options', 'Clipjet', 'manage_options', 'clipjet', 'cj_plugin_options' );
}

function cj_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	include('clipjet_admin_panel.php'); 
}

function cj_admin_init() {
    register_setting('clipjet-group', 'clipjet_email');
}