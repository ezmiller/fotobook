<?php

//--------------------//
//---FACEBOOK-CLASS---//
//--------------------//

class FacebookAPI {
    var $facebook    = null;
    var $sessions = array();
    var $fanpages = array();
    var $user       = null;
    var $error      = false;
    var $msg            = null;
    var $secret  = null;
    var $progress = 0;
    var $increment = null;

    function FacebookAPI($appId, $appSecret) {
        if(!class_exists('Facebook'))
            include_once('facebook-platform/facebook.php');

        $facebook = new Facebook(array(
          'appId'  => $appId,
          'secret' => $appSecret,
          'sharedSession' => false,
          'cookie' => true
        ));
        $this->facebook = $facebook;

        // Initialize global message variable
        global $fb_message;
        $this->msg = &$fb_message;


        // check if the facebook session is the structure from older
        // versions of Fotobook, if so remove it to start over
        $sessions = get_option('fb_facebook_session');
        if(isset($sessions['session_key'])) {
            update_option('fb_facebook_session', '');
        }

        // Set up sessions
        //update_option('fb_facebook_session', array());
        $this->set_sessions();
        if ( count($this->sessions) == 0 ) { // No sessions, so notify user.
            $this->msg = 'In order to begin using Fotobook, you must link it to at least one facebook account.';
        }

        // Set up fan pages.
        $this->set_fanpages();

        // determine how much to increment the progress bar after each request
        $this->progress  = get_option('fb_update_progress');
        $this->increment = count($this->sessions) > 0 ? 100 / (count($this->sessions) * 3) : 0;
        
    }

    function link_active() {
        return ( $this->user ) ? true : false;
    }

    function get_new_facebook_object() {
        return $facebook = new Facebook(array(
          'appId'  => $appId,
          'secret' => $appSecret,
          'sharedSession' => false,
          'cookie' => true
        ));
    }

    function get_login_url() {
        $params = array(
            'scope' => 'user_photos,user_groups',
            'redirect_uri' => get_bloginfo('url') . '/wp-admin/' . FB_OPTIONS_URL
        );
        return $this->facebook->getLoginUrl( $params );
    }

    function get_access_token() {
        $new_token = $this->facebook->getAccessToken();
        if( $new_token !== $this->token ) {
            $this->token = $new_token;
        }
    }

    function destroy_session() {
        $this->facebook->destroySession();
    }

    function get_user() {
        return $this->user;
    }

    function get_user_data() {
        echo 'response to getUser: '.$this->facebook->getUser().'<br/>';
        return $this->facebook->api('/me');
    }

    function set_user() {
        // Try to get the user. 
        //      Note: If the user has attempted to login, this funtion getUser() in the
        //            FB PHP SDK is smart enough to detect that and exchange the code that 
        //            FB returns after the login in the $_GET for the right access token. 
        try {
            $this->user = $this->facebook->getUser();
        } catch (FacebookApiException $e) {
            $this->msg = $e->getMessage();
            return false;
        }

        if ( $this->user ) { // User successfully found.
            // First check to see if user session is already saved
            $sessions = get_option('fb_facebook_session'); $session_exists = null;
            foreach ($sessions as $session) {
                $session_exists = ( $session['uid'] == $this->user ) ? true : false;
            }

            // If the user id does not exist, then save a new session.
            if ( !$session_exists ) {
                $user_profile = $this->facebook->api('/me');
                $new_session = array(
                    'uid'           => $this->user,
                    'access_token'  => $this->facebook->getAccessToken(),
                    'name'          => $user_profile['name'],
                    'link'          => $user_profile['link']
                );
                array_push($sessions, $new_session);
                update_option('fb_facebook_session', $sessions);
                $this->msg = 'Fotobook is now linked to '.$new_session['name'].'\'s Facebook account.   Now you need to <a href="'.FB_MANAGE_URL.'">import</a> your albums.';
            }

        } 

    }

    function get_sessions () {
        $sessions = $this->sessions;
        return ( count($sessions) > 0 ) ? $sessions : FALSE;
    }

    function set_sessions() {
        $sessions = get_option('fb_facebook_session');

        if( count($sessions) == 0 ) { // There are not yet any saved sessions...
            // Try to find a logged in user (i.e. if there is an authorized session)
            $this->set_user();

        }
        else { // There are existing sessions...

            // The user might be trying to link a new user, so try to set new session with set_user.
            $this->set_user();

            // Check to see if existing accounts are still valid.
            $sessions = get_option('fb_facebook_session');  // Need to update because set_user() may have saved new session.
            $new_user = '';
            foreach ($sessions as $key => $session) {
                $this->facebook->destroySession();
                $this->facebook->setAccessToken($session['access_token']);
                $new_user = $this->facebook->getUser();
                if ( !$new_user ) {
                    $this->msg = 'The link to '.$sessions[$key]['name'].'\'s account was lost.   Please authorize the account again.';
                    unset($sessions[$key]);
                    update_option('fb_facebook_session', $sessions);
                }
            }

            // Now see if there are any sessions left, and then set $this->user somehow.
            if ( count($sessions) > 0 ) { 
                
                // Set $this->user to the first session.
                $this->facebook->setAccessToken($sessions[0]['access_token']);
                $this->user = $this->facebook->getUser();

            }
            else { // There are no more sessions, so try to get a user from the login process.

                $this->set_user();

            }

        }
        $this->sessions = (array) get_option('fb_facebook_session');
    }

    function select_session($uid) { 

        // Find the right session object
        $sessions = (array) $this->sessions;
        $selected = null;
        foreach ( $sessions as $session ) {
            if ( $session['uid'] == $uid ) $selected = $session;
        }

        if ( $selected ) { // Found the right session, now make it active.
            $this->facebook->destroySession();
            $this->facebook->setAccessToken($selected['access_token']);
            $this->user = $this->facebook->getUser();
        }

        return $this->user;

    }

    function set_fanpages() {

        // Check to see if the user has tried to link a new fan page.
        if ( isset($_POST['fanpage-id']) && !$this->sessions_exist() ) {
            $this->msg = "You must first link Fotobook to a Facebook account before you can add a fan page.";
        }
        elseif ( isset($_POST['fanpage-id']) && $this->sessions_exist() ) {

            $pid = $_POST['fanpage-id'];
            $found = $this->get_fanpage($pid);
            if ( $found ) { // It has...
                $this->msg = "You have already linked that fan page to Fotobook";
            } 
            else { 
                
                // Try to link the new fan page.
                $success = $this->set_fanpage($_POST['fanpage-id']);
                if ( $success ) { 
                    $this->fanpages = get_option('fb_facebook_fanpages'); 
                } 
                else {
                    $this->msg = "Fotobook was unable to link the specified fan page. Did you enter the right name or page id?";
                }

            }
            
        }
        
        // Set the object's fan page array.
        $this->fanpages = get_option('fb_facebook_fanpages');


    }

    function set_fanpage($pid) {

        // Try to get the fan page.
        try {
            $page_data = $this->facebook->api('/'.$pid);    
        } catch (FacebookApiException $e) {
            error_log('Fotobook Facebook API Error: '.$e->getMessage());
        }
        
        if ( $page_data ) { // The fan page data was successfully retreived, save a new fan page in fotobook.
            $fanpages = get_option('fb_facebook_fanpages');
            $fanpages[] = array ( 
                'pid'   => $page_data['id'],
                'username' => $page_data['username'],
                'name'  => $page_data['name'],
                'link'  => $page_data['link']
            );
            update_option('fb_facebook_fanpages', $fanpages);
            return true;
        } 
        else { return false; }

    }

    function get_fanpage($pid = 'all') {
        $fanpages = get_option('fb_facebook_fanpages');
        if ( count($fanpages) > 0 ) { // There are some fan pages...
            if ( $pid !== all ) { 
                $found = false;
                foreach ($fanpages as $fanpage) {
                    if ( $fanpage['id'] == $pid || $fanpage['username'] == $pid ) {
                        $found = $fanpage; 
                        break;
                    }
                }
                return $found;
            } else { return get_option('fb_facebook_fanpages'); }
        } 
        else { 
            return false; 
        }
    }

    function remove_user($key) {
        // remove all of this user's albums and photos
        global $wpdb;

        $albums = fb_get_album(0, $this->sessions[$key]['uid']);
        if(is_array($albums)) {
            foreach($albums as $album) {
                fb_delete_page($album['page_id']);
            }
        }

        $wpdb->query('DELETE FROM `'.FB_ALBUM_TABLE."` WHERE `owner` = '".$this->sessions[$key]['uid'] . "'");
        $wpdb->query('DELETE FROM `'.FB_PHOTO_TABLE."` WHERE `owner` = '".$this->sessions[$key]['uid'] . "'");

        $this->msg = 'The link to '.$this->sessions[$key]['name'].'\'s Facebook account has been removed.';

        unset($this->sessions[$key]);
        update_option('fb_facebook_session', $this->sessions);
    }

    function remove_fanpage($key) {
        global $wpdb;

        $albums = fb_get_album(0, $this->fanpages[$key]['pid']);
        if ( is_array($albums) ) {
            foreach ($albums as $album) {
                fb_delete_page($album['page_id']);
            }
        }

        $wpdb->query('DELETE FROM `'.FB_ALBUM_TABLE."` WHERE `owner` = '".$this->fanpages[$key]['pid'] . "'");
        $wpdb->query('DELETE FROM `'.FB_PHOTO_TABLE."` WHERE `owner` = '".$this->fanpages[$key]['pid'] . "'");

        $this->msg = 'The link to '.$this->fanpages[$key]['name'].' has been removed.';

        unset($this->fanpages[$key]);
        update_option('fb_facebook_fanpages', $this->fanpages);
    }

    function update_progress($reset = false) {
        if($reset == true) {
            $this->progress = 0;
        }
        else {
            $this->progress = $this->progress + $this->increment;
        }
        if($this->progress > 100) {
            $this->progress = 100;
        }
        update_option('fb_update_progress', $this->progress);
        return $this->progress;
    }

    function fbquery($sql) {
        $results = $this->facebook->fql_query($sql);
        return $results;
    }

    function increase_time_limit() {
        // allow the script plenty of time to make requests
        if(!ini_get('safe_mode') && !strstr(ini_get('disabled_functions'), 'set_time_limit'))
            set_time_limit(500);
    }

    function sessions_exist() {
        $sessions = $this->sessions;
        if ( count($sessions) > 0 ) {
            return true;
        } else { return false; }
    }

    function update_albums() {
        global $wpdb;

        $this->increase_time_limit();

        // reset album import progress
        $this->update_progress(true);

        // If this is the first import then reset the order at the end to make the newest on top
        $reset_order = count(fb_get_album()) > 0 ? false : true;

        // Create an array including all the user/sessions and fan pages.
        $sources = $this->get_sessions();
        $sources = array_merge($sources, $this->get_fanpage('all'));

        // Get albums for each user/session and fan page from Facebook
        $fb_albums = array(); $fb_photos = array();
        $user_albums = array(); $user_photos = array();
        foreach($sources as $key=>$source) {
            // setup general info
            $id = '';
            if ( $source['uid'] ) {
                $id = $source['uid'];
                $this->select_session($uid);
            } else { 
                $id = $source['pid']; 
            }

            try {
                $fql = "SELECT aid, owner, cover_pid, name, description, location, link, created, modified, size FROM album WHERE owner = " . $id;
                $result = $this->facebook->api( array('method' => 'fql.query', 'query' => $fql) );
                $user_albums = $result;

                if( count($user_albums) == 0 ) // the current user has no photos so move on
                    continue;

                // Update the progress bar
                $this->update_progress();

                
                // Get photos album by album...
                //      Note: Getting album by album in case there is a limit on how many photos can be returned at once.
                //            This should avoid that problem.
                foreach ($user_albums as $album) {
                    $fql = "SELECT pid, aid, owner, src, src_big, src_small, link, caption, created FROM photo WHERE aid = '" . $album['aid'] . "'";
                    $photos = $this->facebook->api( array('method' => 'fql.query', 'query' => $fql) );
                    $user_photos = array_merge($user_photos, (array) $photos);
                }
                $this->update_progress();

                // Get photos of the current user in loop
                $fql = "SELECT pid, aid, owner, src, src_big, src_small, link, caption, created FROM photo WHERE pid IN (SELECT pid FROM photo_tag WHERE subject='" . $uid . "')";
                $fb_user_photos = $this->facebook->api( array('method' => 'fql.query', 'query' => $fql) );
                if($fb_user_photos) {
                    foreach($fb_user_photos as $k=>$v) $fb_user_photos[$k]['aid'] = $uid;
                    $user_photos = array_merge($user_photos, (array)$fb_user_photos);
                    $new_album = array(
                        'aid'=>$id,
                        'cover_pid'=>$fb_user_photos[0]['pid'],
                        'owner'=>$id,
                        'name'=>'Tagged Photos for '.(count($this->sessions) > 1 ? $session['name'] : 'Me'),
                        'created'=>time(),
                        'modified'=>time(),
                        'description'=>'',
                        'location'=>'',
                        'link'=>"http://www.facebook.com/photo_search.php?id=$uid",
                        'size'=>count($fb_user_photos)
                    );
                    array_push($user_albums, $new_album);
                }
                $fb_albums = array_merge($fb_albums, $user_albums);
                $fb_photos = array_merge($fb_photos, $user_photos);

            } 
            catch (FacebookApiException $e) {
               if ($e->getCode() == 102) {
                    unset($this->sessions[$key]);
                    update_option('fb_facebook_session', $this->sessions);
                    $this->msg = "The account for {$session['name']} is no longer active.  Please add the account again from the settings panel.";
                }
                else {
                    $this->msg = "There was an error while retrieving your photos: {$e->getMessage()} [Error #{$e->getCode()}]";
                }
                return false;
            }

        }

        // Get all the existing albums from the Wordpress DB and then put them in an arrray 
        // with the album id ($aid) as the key
        $albums = fb_get_album(); $existing_albums;
        if($albums) {
            foreach($albums as $album) {
                $existing_albums[$album['aid']] = $album;
            }
        }

        // If any of the newly received albums are not yet saved, then add them.
        foreach($fb_albums as $album) {
            
            // Collect ablum data.
            $album_data = array(
                'cover_pid' => $album['cover_pid'],
                'owner' => $album['owner'],
                'name' => $album['name'],
                'created' => !empty($album['created']) ? date('Y-m-d H:i:s', $album['created']) : '',
                'modified' => !empty($album['modified']) ? date('Y-m-d H:i:s', $album['modified']) : '',
                'description' => $album['description'],
                'location' => $album['location'],
                'link' => $album['link'],
                'size' => $album['size']
            );

            // Check to see if the album has already been saved.
            $saved_album = isset($existing_albums[$album['id']]) ? $existing_albums[$album['id']] : false;

            if ( $saved_album ) {  // Album already exits, so just update data where necessary.
                
                if(fb_page_exists($saved_album['page_id'])) { // Page already exists, so update data.
                    $album_data['page_id'] = $saved_album['page_id'];
                    if($album['name'] != $saved_album['name']) {
                        fb_update_page($saved_album['page_id'], $album['name']);
                    }
                }
                else {

                    $album_data['page_id'] = fb_add_page($album['name']);

                }
                $wpdb->update(FB_ALBUM_TABLE, $album_data, array('aid' => $album['aid']));
            } 
            else { // Album does not already exists in Fotobook, so add it.

                $album_data['aid'] = $album['aid'];
                $album_data['page_id'] = fb_add_page($album['name']);
                $album_data['hidden'] = 0;
                $album_data['ordinal'] = fb_get_next_ordinal();
                $wpdb->insert(FB_ALBUM_TABLE, $album_data);

            }
        }
        
        // Update the photos
        $wpdb->query('DELETE FROM '.FB_PHOTO_TABLE);
        $ordinal = 1;
        foreach($fb_photos as $photo) {
            if($last_aid !== $photo['aid']) { // reset ordinal if we're on a new album now
                $ordinal = 1;
            }
            $photo_data = array(
                'pid' => $photo['pid'],
                'aid' => $photo['aid'],
                'owner' => $photo['owner'],
                'src' => $photo['src'],
                'src_big' => $photo['src_big'],
                'src_small' => $photo['src_small'],
                'link' => $photo['link'],
                'caption' => $photo['caption'],
                'created' => date('Y-m-d H:i:s', $photo['created']),
                'ordinal' => $ordinal
            );
            $wpdb->insert(FB_PHOTO_TABLE, $photo_data);

            // handle ordinal
            $last_aid = $photo['aid'];
            $ordinal++;
        }

        // Now remove any albums that have been removed from Facebook
        foreach($fb_albums as $fb_album) {
            $album_ids[] = $fb_album['aid'];
        }

        if(count($existing_albums) > 0) { // There are existing albums in Fotobook

            // Delete albums that have been removed from Facebook
            foreach($existing_albums as $aid=>$album) {
                if(!@in_array($aid, $album_ids)) {
                    fb_delete_page($album['page_id']);
                    $wpdb->query('DELETE FROM `'.FB_ALBUM_TABLE."` WHERE `aid` = '".$aid."'");
                }
            }

            // Delete superfluous pages
            foreach($existing_albums as $album) {
                $album_pages[] = $album['page_id'];
            }
            $wp_pages = $wpdb->get_results('SELECT `ID` FROM `'.FB_POSTS_TABLE."` WHERE `post_parent` = '".get_option('fb_albums_page')."'", ARRAY_A);
            foreach($wp_pages as $page) {
                if(!in_array($page['ID'], $album_pages)) {
                    fb_delete_page($page['ID']);
                }
            }

        }

        // Now reset the album order if needed
        if($reset_order) {
            fb_reset_album_order();
        }

        if(!$this->msg) {
            $this->msg = 'Albums imported successfully.';
        }
        $this->update_progress(true);
        
        die();
    }

}
?>