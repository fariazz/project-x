<div class="wrap"> 
    <h2>Clipjet Options</h2> 
    <form method="post" action="options.php"> 
        <?php @settings_fields('clipjet-group'); ?> 
        <?php @do_settings_fields('clipjet-group'); ?> 
        <table class="form-table"> 
            <tr valign="top"> 
                <th scope="row"><label for="clipjet_email">Email address</label></th> 
                <td>
                    <input type="text" name="clipjet_email" id="clipjet_email" value="<?php echo get_option('clipjet_email'); ?>" />
                </td>
                <small>Use the same email address that you used to create your Clipjet account</small>
            </tr> <tr valign="top"> 
        </table> <?php @submit_button(); ?> 
    </form> 
</div>