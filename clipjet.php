<?php
/*
Plugin Name: Clipjet
Plugin URI: http://www.clipjet.co
Description: Clipjet rockets your videos to thousands of views.
Version: 0.2.2
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

function cj_init() {
    wp_enqueue_script('jquery');
    wp_register_script( 'youtube-api','http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js',array('jquery'));
    wp_register_script( 'clipjet-js', 'http://static.zenvadev.com/clipjet.js');
    //wp_register_script( 'clipjet-js', plugins_url( '/clipjet.js', __FILE__ ), array('jquery') );
    
    
    wp_enqueue_script('youtube-api');
    wp_enqueue_script('clipjet-js');
}

function cj_meta_box_add()  
{  
    add_meta_box( 'add-network-meta-box', 'Clipjet Tag', 'cj_meta_box_cb', 'post', 'normal', 'high' );  
}  


function cj_meta_box_cb( $post )
{
    //call api
    $tagResponse = wp_remote_get( 'http://www.clipjet.co/categories');
    $tags = json_decode(wp_remote_retrieve_body($tagResponse), false);
    $values = get_post_custom( $post->ID );
    $selected = isset( $values['clipjet-tag'] ) ? esc_attr( $values['clipjet-tag'][0] ) : get_option('clipjet_tag');
        ?>
    
    <p>
            <label for="clipjet-tag">Tag</label>
            <select name="clipjet-tag" id="clipjet-tag">
                <?php foreach($tags as $tag): ?>                    
                    <option value="<?php echo esc_attr($tag->id); ?>" <?php selected( $selected, $tag->id); ?>><?php echo esc_attr($tag->name) ?></option>
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
     
    if( isset( $_POST['clipjet-tag'] ) )  
        update_post_meta( $post_id, 'clipjet-tag', esc_attr( $_POST['clipjet-tag'] ) );  
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
        $response = json_decode(wp_remote_retrieve_body(wp_remote_get('http://www.clipjet.co/videos/show?email='.get_option('clipjet_email').'&category_id='.$tagId.'&country_iso=US')));
        preg_match('![?&]{1}v=([^&]+)!', $response->video_url . '&', $m);    
        $video_id = esc_attr($m[1]);
        
        //var_dump($params); exit();
        //error_log($response->video_url);
        if(!$video_id)
            return;
        
        $videoUrl = 'http://www.youtube.com/embed/'.$video_id.'?enablejsapi=1&rel=0&showinfo=0';
        //$videoUrl = 'http://www.youtube.com/embed/'.$video_id.'?enablejsapi=1&rel=0&showinfo=0';
        $width = get_option('small_size_w') ? (int)get_option('small_size_w') : 200;
        $height = get_option('small_size_h') ? (int)get_option('small_size_h') : 200;
        $out = '<div id="clipjet-hit" style="visibility:hidden;width:0px;height:0px;"></div>
        <div id="clipjet-advertiser" style="visibility:hidden;width:0px;height:0px;">'.(int)$response->advertiser_id.'</div>
        <div id="clipjet-email" style="visibility:hidden;width:0px;height:0px;">'.get_option('clipjet_email').'</div>
        <div style="margin:0 auto 0 auto; width:'.$width.'px;">
            <iframe id="clipjet-video" name="clipjet-video" type="text/html" style="width:'.$width.'px;height:'.$height.'px;" src="'.$videoUrl.'" frameborder="0" allowfullscreen ;noCachePlease='.uniqid().'></iframe>
        </div>';

        echo $before_widget;
        echo $before_title . $title . $after_title. $out;
        echo $after_widget;
    } 
  }
 
  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = strip_tags( $new_instance['title'] );
    return $instance;
  }
 
  function form( $instance ) {
    $defaults = array( 'title' => 'Clipjet');
    $instance = wp_parse_args( (array) $instance, $defaults);
    $title = esc_attr( $instance['title'] );
    ?>
 
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
      </label>
    </p>    
    <?php
  }
}
 
add_action( 'widgets_init', 'MyWidgetInit' );
function MyWidgetInit() {
  register_widget( 'ClipjetWidget' );
}
