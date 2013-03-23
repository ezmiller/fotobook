<?php

/*
Fotobook Options Panel
*/

// Attempt to get the AppID and App Secret
//update_option('fb_app_id', ''); update_option('fb_app_secret', '');
$fb_appId           = ( isset($_POST['appId']) ) ? $_POST['appId'] : get_option('fb_app_id');
$fb_appSecret       = ( isset($_POST['appSecret']) ) ? $_POST['appSecret'] : get_option('fb_app_secret');

// If they are not set, instruct the user that they must create an App and provide
// the AppID and App Secret in order to use the plugin.
if ( empty($fb_appId)  || empty($fb_appSecret) ) : ?>
<div class="wrap">
    <h2>Setup</h2>
    <form method="post" id="app-setup" action="<?php echo FB_OPTIONS_URL; ?>">
        <h3>Link to Facebok App</h3>
        <table class="form-table" style="clear:none;">
            <tr valign="top">
                <th scope="row">Application ID</th>
                <td>
                    <fieldset>
                        <label for="appId">
                        <input style="width: 200px;" type="text" name="appId" id="appId" value="<?php echo $fb_appId; ?>">
                        Your application's ID (default: )</label><br><br>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Application Secret</th>
                <td>
                    <fieldset> 
                        <label="" for="appSecret">
                        <input style="width: 200px;" type="text" name="appSecret" id="appSecret" value="<?php echo $fb_appSecret; ?>">
                        Your application's secret (default:)<br><br>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td><input type="submit" name="submit" class="button-secondary" value="Link It Up" /></td>
            </tr>
        </table>
    </form>
    <br/>
    <div class="updated" style="width: 55%">
        <h3>Instructions</h3>
        <p>
        In order to use this plugin you must link it to a Facebook "App" that you have created using Facebook's App Dashboard
        for developers. Here's how to do that:
        <ul>
            <li>
                1. Login to your facebook account and then click on <a href="https://developers.facebook.com/apps" target="_blank">
                this link</a>. 
            </li>
            <li>
                2. On the page, find the "Create New App" button at the top right, and click it to create a new Facebook App. 
            </li>
            <li>
                3. A box will pop up. Fill out the App Name field. You can select any app name that you like as long as FB 
                says that it is valid. If the name is valid, the word "Valid" should appear to the right of the field. When
                you are done click "Continue".
            </li>
            <li>
                4. Under the section "Select how your app integrates with Facebook," look for the field "Website with Facebook Login." Click on the check mark, 
                and then enter the following url into that field: "<strong><?php echo get_bloginfo('siteurl').'/'.$_SERVER['REQUEST_URI']; ?>"</strong>.
                Once you are done, save the changes.
            </li>
            <li>
                5. As the final step, copy the App ID and App Secret into the fields above, and then link fotobook to your new app.
            </li>
        </ul>
        </p>
    </div>
</div>
<?php 
elseif ( !empty($fb_appId) && !empty($fb_appSecret) ) : 
    // If the App Id and App Secret are already set then  either the user just set them, or they
    // have already been set and Fotobook's Facebook API will have been successfully initiated.
    
    // First check to see if the user was trying to set the App Id & Secret.
    // If so, set them.
    if ( isset($_POST) && isset($_POST['appId']) && isset($_POST['appSecret']) ) {
            //echo 'about to set the app data<br/>';
            // Save the App Id & Secret in the Wordpress options.
            if ( $_POST['appId'] !== get_option('fb_app_id') ) 
                update_option('fb_app_id', $_POST['appId']);
            if ( $_POST['appSecret'] !== get_option('fb_app_secret')) 
                update_option('fb_app_secret', $_POST['appSecret']);

            // Now force the page to reload, so that the plugin can load Fotobook's Facebook API
            // before the headers are sent.
            $url = get_bloginfo('url') . '/wp-admin/' . FB_OPTIONS_URL;
            echo '<script type="text/javascript">';
            echo 'window.location.href="'.$url.'";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
            exit;
    }

    // Now see if Fotobook's Facebook API was loaded correctly. 
    //      Note: Fotobook attempts to load the class earlier via a hooked action,
    //            see the function load_facebook_API in fotobook.php
    global $fb_facebook;
    $facebook = $fb_facebook;
    if ( !isset($facebook) ) { 
        echo '<div class="error">There was a problem loading the Fotobook Facebook API. Contact your administrator.</div>';
        exit;
    }

    // Check to see if the user wants to de-link an account or a fan page. if so, remove the user or fan page.
    if ( isset($_GET['deactivate-facebook']) && isset($facebook->sessions[$_GET['deactivate-facebook']]) ) {
        $facebook->remove_user($_GET['deactivate-facebook']);
    }
    if ( isset($_GET['deactivate-fanpage']) && isset($facebook->fanpages[$_GET['deactivate-fanpage']]) ) {
        $facebook->remove_fanpage($_GET['deactivate-fanpage']);
    }

    // Save current page url
    $this_page = $_SERVER['PHP_SELF'].'?page='.$_GET['page'];

    // Get Styles
    $styles = fb_get_styles();

    // Update options if a form has been submitted
    if (isset($_POST['submit'])) {
        fb_options_update_albums_page($_POST['fb_albums_page']);    
        update_option('fb_number_rows', $_POST['fb_number_rows']);
        echo 'style: '.$_POST['fb_style'];
        update_option('fb_style', $_POST['fb_style']);
        if($_POST['fb_number_cols'] != 0) {
            update_option('fb_number_cols', $_POST['fb_number_cols']);
        }
        if(is_numeric($_POST['fb_embedded_width'])) {
            update_option('fb_embedded_width', $_POST['fb_embedded_width']);
        }
        update_option('fb_thumb_size', $_POST['fb_thumb_size']);
        update_option('fb_albums_per_page', $_POST['fb_albums_per_page']);
        update_option('fb_hide_pages', isset($_POST['fb_hide_pages']) ? 1 : 0);
        if(isset($_POST['fb_album_cmts'])) {
            fb_options_toggle_comments(true);
            update_option('fb_album_cmts', 1);
        } else {
            fb_options_toggle_comments(false);
            update_option('fb_album_cmts', 0);
        }
        foreach($styles as $style) {
            $stylesheet = FB_PLUGIN_PATH.'styles/'.$style.'/style.css';
            if(is_writable($stylesheet)) {
                file_put_contents($stylesheet, $_POST[$style.'_stylesheet']);
            }       
        }
        $sidebar_stylesheet = FB_PLUGIN_PATH.'styles/sidebar-style.css';
        if(is_writable($sidebar_stylesheet)) {
            file_put_contents($sidebar_stylesheet, $_POST['sidebar_stylesheet']);
        }
    }

    // Add a photo album page if there is none
    if( ( get_option('fb_albums_page') == 0 ) || 
        ( get_page( get_option('fb_albums_page') ) == null ) ) {
        $page = array(
            'post_author'       => 1,
            'post_content'   =>'',
            'post_title'         =>'Photos',
            'post_name'         =>'photos',
            'comment_status' =>1,
            'post_parent'       =>0
        );
        // add a photo album page 
        if(get_bloginfo('version') >= 2.1) {    
            $page['post_status'] = 'publish';
            $page['post_type']   = 'page';
        } else {
            $page['post_status'] = 'static';
        }
        $page_id = wp_insert_post($page);
        update_option('fb_albums_page', $page_id);
        echo 'fb_albums_page now: '.get_option('fb_albums_page');
    }

    // Get options to fill in input fields
    $fb_sessions        = $facebook->get_sessions();
    $fb_fanpages        = $facebook->get_fanpage('all');
    $fb_albums_page     = get_option('fb_albums_page');
    $fb_number_rows     = get_option('fb_number_rows');
    $fb_number_cols     = get_option('fb_number_cols');
    $fb_album_cmts      = get_option('fb_album_cmts');
    $fb_thumb_size      = get_option('fb_thumb_size');
    $fb_albums_per_page = get_option('fb_albums_per_page');
    $fb_style           = get_option('fb_style');
    $fb_embedded_width  = get_option('fb_embedded_width');
    $fb_hide_pages      = get_option('fb_hide_pages');


    ?>

<?php if($facebook->msg): ?>
<div id="message" class="<?php echo $facebook->error ? 'error' : 'updated' ?> fade"><p><?php echo $facebook->msg ?></p></div>
<?php endif; ?>

<div class="wrap">
    <div id="fb-panel">
        <?php fb_info_box() ?>
        <h2 style="clear: none"><?php _e('Fotobook &rsaquo; Settings') ?> <span><a href="<?php echo FB_MANAGE_URL ?>">Manage Albums &raquo;</a></span></h2>

        <h3>Facebook</h3><br/>

        <!-- Code for linking facebook accounts -->
        <?php if ( $fb_sessions ) : // There are existing linked accounts, so show them. ?>
        <table class="accounts">
            <tr>
                <td>
                    <h3><?php _e('Linked Accounts'); ?></h3>
                    <table>
                    <?php foreach ($fb_sessions as $key=>$session) : ?>
                        <tr>
                         <td style="width: 10%">
                            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="get" style="display:inline;">
                                <input type="hidden" name="deactivate-facebook" value="<?php echo $key ?>">
                                <input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
                                <input type="submit" class="button-secondary" value="Remove" onclick="return confirm('Removing an account also removes all of the photos associated with the account.  Would you like to continue?')">
                            </form>
                        </td>
                        <td>
                            <img src="http://www.facebook.com/favicon.ico" />
                            <a href="<?php echo $session['link']; ?>" target="_blank"><?php echo $session['name'] ?></a>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <?php if($facebook): ?>
                        <p><input type="button" class="button-secondary" name="link-new-account" value="Link new Facebook Account"></p>
                    <?php else: ?>
                        <?php _e('Unable to get authorization token.'); ?>
                    <?php endif ?>
                </td>
            </tr>
            <tr>
                <td>
                    <p><small>Please Note: This plugin has been given access to data from your Facebook account. You can revoke this access at any time by clicking remove above or by changing your <a href="http://www.facebook.com/privacy.php?view=platform&tab=ext" target="_blank">privacy</a> settings.</small></p>
                </td>
            </tr>
        </table>
        <?php else : // There are no existing sessions, so just instruct the user to ad an account. ?>
           <table class="accounts">
                <tr>
                    <td valign="top" width="170">
                        <h3><?php _e('Add an Account'); ?></h3>
                    </td>
                    <td>
                        <?php if($facebook): ?>
                            <p><input type="button" class="button-secondary" name="link-new-account" value="Link to Facebook Account"></p>
                        <?php else: ?>
                            <?php _e('Unable to get authorization token.'); ?>
                        <?php endif ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
        <br/>

        <!-- Code for linking Facebook Fan Pages -->
        <?php if ( $fb_fanpages ) : ?>
            <table class="accounts">
                <tr>
                    <td>
                        <h3><?php _e('Linked Fan Pages'); ?></h3>
                        <table>
                        <?php foreach ($fb_fanpages as $key=>$fanpage) : ?>
                            <tr>
                                <td style="width: 10%">
                                    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="get" style="display:inline;">
                                        <input type="hidden" name="deactivate-fanpage" value="<?php echo $key ?>">
                                        <input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
                                        <input type="submit" class="button-secondary" value="Remove" onclick="return confirm('Removing a fan page also removes all of the photos associated with that page.  Would you like to continue?')">
                                    </form>
                                </td>
                                <td>
                                    <img src="http://www.facebook.com/favicon.ico" />
                                    <a href="<?php echo $fanpage['link']; ?>" target="_blank"><?php echo $fanpage['name'] ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <tr>
                                    <form method="POST" action="<?php echo $this_page; ?>">
                                    <table>
                                    <tr>
                                        <td style="width:40%">
                                            <p>
                                            <label for="fanpage-id">Fan Page ID:</label>
                                            <input type="text" id="fanpage-id" name="fanpage-id" style="width:70%;" value=""/>
                                            </p>
                                        </td>
                                        <td>
                                            <p><input type="submit" class="button-secondary" name="link-fanpage" value="Link New Fan Page" /></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <span style="font-size:10px">
                                            <p>
                                            Enter here the numerical id or name of a Fan Page whose albums you wish to be able to display using Fotobook. The name of the
                                            page is the value that comes after the facebook address when viewing the page, e.g. in facebook.com/okgo, 'okgo', is the page
                                            name. The numerical id of the page can be located using the facebook graph. Enter graph.facebook.com/ + the page name (e.g. 
                                            graph.facebook.com/okgo) and then look for the 'id'.
                                            </p>
                                            </span>
                                        </td>
                                    </tr>
                                    </table>
                                    </form>
                                </tr>
                        </table>
                    </td>
                </tr>
            </table>
        <?php else : ?>
            <form method="POST" action="<?php echo $this_page; ?>">
                <table class="accounts">
                    <tr>
                        <td>
                            <h3><?php _e('Add Fan Page'); ?></h3>
                            <table>
                            <tr>
                                <td style="width:40%">
                                    <p>
                                    <label for="fanpage-id">Fan Page ID:</label>
                                    <input type="text" id="fanpage-id" name="fanpage-id" style="width:70%;" value=""/>
                                    </p>
                                </td>
                                <td>
                                    <p><input type="submit" class="button-secondary" name="link-fanpage" value="Link" /></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <span style="font-size:10px">
                                    <p>
                                    Enter here the numerical id or name of a Fan Page whose albums you wish to be able to display using Fotobook. The name of the
                                    page is the value that comes after the facebook address when viewing the page, e.g. in facebook.com/okgo, 'okgo', is the page
                                    name. The numerical id of the page can be located using the facebook graph. Enter graph.facebook.com/ + the page name (e.g. 
                                    graph.facebook.com/okgo) and then look for the 'id'.
                                    </p>
                                    </span>
                                </td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>
        <?php endif; ?>
        
    <form method="post" action="<?php echo $this_page ?>"> 
        <h3><?php _e('General') ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Albums Page') ?></th>
                    <td>
                        <select name="fb_albums_page">
                            <?php if(!fb_albums_page_is_set()): ?>
                            <option value="0" selected>Please select...</option>
                            <?php endif; ?>
                            <?php fb_parent_dropdown($fb_albums_page); ?>
                        </select><br />
                        <small>Select the page you want to use to display the photo albums.</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Albums Per Page') ?></th>
                    <td>
                        <input name="fb_albums_per_page" type="text" value="<?php echo $fb_albums_per_page; ?>" size="3" />
                        <small><?php _e('Number of albums to display on each page of the main gallery. Set to \'0\' to show all.') ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Number of Rows') ?></th>
                    <td>
                        <input name="fb_number_rows" type="text" value="<?php echo $fb_number_rows; ?>" size="3" />
                        <small><?php _e('Set to \'0\' to display all.') ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Number of Columns') ?></th>
                    <td>
                        <input name="fb_number_cols" type="text" value="<?php echo $fb_number_cols; ?>" size="3" />
                        <small><?php _e('The number of columns of pictures.') ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Display Style') ?></th>
                    <td>
                        <select name="fb_style">
                            <?php foreach($styles as $style): 
                            $selected = $style == $fb_style ? ' selected' : null; ?>
                            <option value="<?php echo $style ?>"<?php echo $selected; ?>><?php echo $style ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small><?php _e('Select the style you want to use to display the albums.') ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Embedded Width') ?></th>
                    <td>
                        <input name="fb_embedded_width" type="text" value="<?php echo $fb_embedded_width; ?>" size="3" />px
                        <small><?php _e('Restrain the width of the embedded photo if it is too wide for your theme. Set to \'0\' to display the full size.') ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Max Thumbnail Size') ?></th>
                    <td>
                        <input name="fb_thumb_size" type="text" value="<?php echo $fb_thumb_size; ?>" size="3" />px
                        <small><?php _e('The maximum size of the thumbnail. The default is 130px.') ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Album Commenting') ?></th>
                    <td>
                        <label><input name="fb_album_cmts" type="checkbox" value="1" <?php if($fb_album_cmts) echo 'checked'; ?> />
                        <small><?php _e('Allow commenting on individual albums. This must be supported by your theme.') ?></small></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Hide Album Pages') ?></th>
                    <td>
                        <label><input name="fb_hide_pages" type="checkbox" value="1" <?php if($fb_hide_pages) echo 'checked'; ?> />
                        <small><?php _e('Exclude album pages from being displayed in places where pages are listed.') ?></small></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Cron URL') ?></th>
                    <td>To setup automatic updates of your albums, create a cron job that regularly loads the following URL.    If you are unsure how to setup a cron job, <a href="http://www.google.com/search?q=cron">Google</a> is your friend.<br /> <small><?php echo fb_cron_url() ?></small></td>
                </tr>
            </table>

            <h3><?php _e('Stylesheets') ?></h3>
            <table class="form-table">
                <tr><td>
                <div id="fb-stylesheets" class="editform" style="width: 98%">
                    <p>Select:
                        <select>
                            <?php 
                            $styles[] = 'sidebar';
                            foreach($styles as $style): 
                            $selected = $style == $fb_style ? ' selected' : null; 
                            ?>
                            <option value="<?php echo $style ?>"<?php echo $selected; ?>><?php echo $style ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <?php 
                    foreach($styles as $style): 
                    $stylesheet = FB_PLUGIN_PATH.'styles/'.$style.'/style.css';
                    if($style == 'sidebar') $stylesheet = FB_PLUGIN_PATH.'styles/sidebar-style.css';
                    ?>
                    <div id="<?php echo $style ?>-stylesheet"<?php echo $style != $fb_style ? ' style="display: none"' : '' ?>>
                        <textarea name="<?php echo $style ?>_stylesheet" style="width: 100%; height: 250px"<?php echo is_writable($stylesheet) ? '' : ' disabled="true"' ?>><?php echo file_get_contents($stylesheet) ?></textarea>
                        <?php echo is_writable($stylesheet) ? '' : '<em>This file is not writable.</em>' ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                </td></tr>
            </table>
            
            <p><strong><a href="#" id="fb-debug">View Debug Info &raquo;</a></strong></p>
            <table class="form-table" id="fb-debug-info" style="display: none">
                <tr>
                    <th>Fotobook Version</th>
                    <td><?php echo FB_VERSION ?></td>
                </tr>
                <tr>
                    <th>WordPress Version</th>
                    <td><?php bloginfo('version') ?></td>
                </tr>
                <tr>
                    <th>PHP Version</th>
                    <td><?php echo PHP_VERSION ?></td>
                </tr>
                <tr>
                    <th>Allow URL fopen</th>
                    <td><?php echo ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled' ?></td>
                </tr>
                <tr>
                    <th>Curl</th>
                    <td><?php echo extension_loaded('curl') ? 'Installed' : 'Not Installed' ?></td>
                </tr>
                <tr>
                    <th>Safe Mode</th>
                    <td><?php echo ini_get('safe_mode') ? 'Enabled' : 'Disabled' ?></td>
                </tr>
                <tr>
                    <th>Max Execution Time</th>
                    <td><?php echo ini_get('max_execution_time') ?> seconds</td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
            </p>
    </form>
    
        
<?php
endif;
?>
