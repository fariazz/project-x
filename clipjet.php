<?php
/*
Plugin Name: Clipjet
Plugin URI: http://www.clipjet.co
Description: Clipjet rockets your videos to thousands of views.
Version: 0.2
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
//add_filter('the_content', 'cj_show_video' );  

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
    //call api
    $tags = do_get_request('http://www.clipjet.co/categories', array());
    $values = get_post_custom( $post->ID );
    $selected = isset( $values['clipjet-tag'] ) ? esc_attr( $values['clipjet-tag'][0] ) : get_option('clipjet_tag');
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
    register_setting('clipjet-group', 'clipjet_tag');
}

class ClipjetWidget extends WP_Widget {
  function ClipjetWidget() {
    parent::WP_Widget( false, $name = 'Clipjet' );
  }
 
  function widget( $args, $instance ) {
    extract( $args );
    $title = apply_filters( 'widget_title', $instance['title'] );
    
    // We're in the loop, so we can grab the $post variable  
    global $post;  
    $tagId = get_post_meta( $post->ID, 'clipjet-tag', true ) ? get_post_meta( $post->ID, 'clipjet-tag', true ) : get_option('clipjet_tag');  

    if($tagId) {
        $values = get_post_custom( $post->ID );
        $params = array(
            'email'       => get_option('clipjet_email'),
            'category_id' => $values['clipjet-tag'][0],
            'country_iso' => substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],2,2)
        );

        $response = do_get_request('http://www.clipjet.co/videos/show', $params);
        preg_match('![?&]{1}v=([^&]+)!', $response->video_url . '&', $m);    
        $video_id = $m[1];
        
        if(!$video_id)
            return;
        
        $videoUrl = 'http://www.youtube.com/embed/'.$video_id.'?enablejsapi=1&rel=0&showinfo=0';
        $width = get_option('small_size_w') ? get_option('small_size_w') : 200;
        $height = get_option('small_size_h') ? get_option('small_size_h') : 200;
        $out = '<div id="clipjet-hit" style="visibility:hidden;width:0px;height:0px;"></div>
        <div id="clipjet-advertiser" style="visibility:hidden;width:0px;height:0px;">'.$response->advertiser_id.'</div>
        <div id="clipjet-email" style="visibility:hidden;width:0px;height:0px;">'.get_option('clipjet_email').'</div>
        <div style="margin:0 auto 0 auto; width:'.$width.'px;">
            <iframe id="clipjet-video" type="text/html" style="width:'.$width.'px;height:'.$height.'px;" src="'.$videoUrl.'" frameborder="0" allowfullscreen ;noCachePlease='.uniqid().'></iframe>
        </div>';
        
        echo $before_widget;
        echo $before_title . ($title ? $title : 'Clipjet') . $after_title. $out;
        echo $after_widget;
    } 
  }
 
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }
 
  function form( $instance ) {
    $title = esc_attr( $instance['title'] );
    ?>
 
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
      </label>
    </p>    
    <?php
  }
}
 
add_action( 'widgets_init', 'MyWidgetInit' );
function MyWidgetInit() {
  register_widget( 'ClipjetWidget' );
}