<?php
    //call api
    $tags = do_get_request('http://www.clipjet.co/categories', array());
    //$selected = isset(get_option('clipjet_tag')) ? (get_option('clipjet_tag')) : '';
?>  
<div class="wrap"> 
    <?php screen_icon(); ?>
    <h2>Clipjet Options</h2> 
    <form method="post" action="options.php"> 
        <?php @settings_fields('clipjet-group'); ?> 
        <?php @do_settings_fields('clipjet-group'); ?> 
        <table class="form-table"> 
            <tr valign="top"> 
                <th scope="row"><label for="clipjet_email">Email address</label></th> 
                <td>
                    <input type="text" name="clipjet_email" id="clipjet_email" value="<?php echo get_option('clipjet_email'); ?>" />
                    <br/><small>Use the same email address that you used to create your Clipjet account</small>
                </td>                
            </tr> <tr valign="top"> 
            <tr>
                <th scope="row"><label for="clipjet_tag">Default Tag</label>                    
                </th> 
                <td>
                    <select name="clipjet_tag" id="clipjet_tag">
                        <?php foreach($tags as $tag): ?>                    
                            <option value="<?php echo $tag->id ?>" <?php if(get_option('clipjet_tag')== $tag->id) echo "selected"; ?>><?php echo $tag->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br/><small>You can also specify a tag in each article of your site</small>
                </td>                
            </tr>
        </table> <?php @submit_button(); ?> 
    </form> 
</div>
