<?php
/*
Plugin Name: Weaver II Theme Extras
Plugin URI: http://weavertheme.com
Description: Weaver II Theme Extras
Author: Bruce Wampler
Author URI: http://weavertheme.com/about
Version: 1.2.5
License: GPL

GPL License: http://www.opensource.org/licenses/gpl-license.php

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

define ('WEAVER_MARKET',false);

function weaverii_ex_saverestore(){
    /* admin tab for saving and restoring theme */
    $weaverii_theme_dir = weaverii_f_uploads_base_dir() .'weaverii-theme/';
    $download_path = weaverii_relative_url('includes/download.php');
    $download_img_path = weaverii_relative_url('images/icons/download.png');
    $nonce = wp_create_nonce('wii_download');

?>
<h3 class="wvr-option-subheader" style="color:blue;">Save/Restore Current Theme Settings using Your Computer</h3>
<p>This option allows you to save and restore all current theme settings by uploading and downloading to your
own computer.</p>

<h4>Download Current Settings To Your Computer</h4>

<a href="<?php echo $download_path . '?_wpnonce=' . $nonce; ?>"><img src="<?php echo $download_img_path; ?>" />&nbsp; <strong>Download</strong>&nbsp;</a> - <strong><em>All</em></strong> current settings to file <strong>weaver-ii-backup-settings.w2b</strong> on your computer. (Full settings backup.)
<br />
<br />
<a href="<?php echo $download_path . '?_wpnoncet=' . $nonce;?>"><img src="<?php echo $download_img_path; ?>" />&nbsp;<strong>Download</strong></a>&nbsp; - <strong><em>Only theme related</em></strong> current settings to file <strong>weaver-ii-theme-settings.w2t</strong> on your computer.
<br />
<br />
<form enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
	<table>
            <tr><td><strong>Upload file saved on your computer</strong></td></tr>
		<tr valign="top">
			<td>Select theme/backup file to upload: <input name="uploaded" type="file" />
			<input type="hidden" name="uploadit" value="yes" />&nbsp;(Restores to current settings.)
                        </td>
		</tr>
                <tr><td><span class='submit'><input name="uploadtheme_ex" type="submit" value="Upload theme/backup" /></span>&nbsp;<small><strong>Upload and Restore</strong> a theme/backup from file on your computer. Will become current settings.</small></td></tr>
                <tr><td>&nbsp;</td></tr>
	</table>
	<?php weaverii_nonce_field('uploadtheme_ex'); ?>
    </form>
<?php
}

function weaverii_ex_loadtheme() {
    if (!(isset($_POST['uploadit']) && $_POST['uploadit'] == 'yes')) return;

   // upload theme from users computer
    // they've supplied and uploaded a file

	$ok = true;     // no errors so far

        if (isset($_FILES['uploaded']['name']))
            $filename = $_FILES['uploaded']['name'];
        else
            $filename = "";

        if (isset($_FILES['uploaded']['tmp_name'])) {
            $openname = $_FILES['uploaded']['tmp_name'];
        } else {
            $openname = "";
        }

	//Check the file extension
	$check_file = strtolower($filename);
	$ext_check = end(explode('.', $check_file));


	if ($filename == "") {
	    $errors[] = "You didn't select a file to upload.<br />";
	    $ok = false;
	}

	if ($ok && $ext_check != 'w2t' && $ext_check != 'w2b'){
	    $errors[] = "Theme files must have <em>.w2t</em> or <em>.w2b</em> extension.<br />";
	    $ok = false;
	}

        if ($ok) {
            if (!weaverii_f_exists($openname)) {
                $errors[] = '<strong><em style="color:red;">Sorry, there was a problem uploading your file.
		You may need to check your folder permissions or other server settings.</em></strong>'.
                    "<br />(Trying to use file '$openname')";
                $ok = false;
            }
        }
	if (!$ok) {
	    echo '<div id="message" class="updated fade"><p><strong><em style="color:red;">ERROR</em></strong></p><p>';
	    foreach($errors as $error){
		echo $error.'<br />';
	    }
	    echo '</p></div>';
	} else {    // OK - read file and save to My Saved Theme
            // $handle has file handle to temp file.
            $contents = file_get_contents($openname);

            if (!weaverii_ex_set_current_to_serialized_values($contents,'weaverii_uploadit:'.$openname)) {
                echo '<div id="message" class="updated fade"><p><strong><em style="color:red;">Sorry,
		there was a problem uploading your file. The file you picked was not a valid
		Weaver II theme file.</em></strong></p></div>';
	    } else {
                weaverii_save_msg('Weaver II theme options reset to uploaded theme.');
            }
        }
}

function weaverii_ex_set_current_to_serialized_values($contents)  {
    global $weaverii_opts_cache;	// need to mess with the cache

    if (substr($contents,0,10) == 'W2T-V01.00')
	$type = 'theme';
    else if (substr($contents,0,10) == 'W2B-V01.00')
	$type = 'backup';
    else
	return weaverii_f_fail(weaverii_t_("Wrong theme file format version" /*a*/ )); 	/* simple check for one of ours */
    $restore = array();
    $restore = unserialize(substr($contents,10));

    if (!$restore) return weaverii_f_fail("Unserialize failed");

    $version = weaverii_getopt('wii_version_id');	// get something to force load

    if ($type == 'theme') {
	// need to clear some settings
	// first, pickup the per-site settings that aren't theme related...
	$new_cache = array();
	foreach ($weaverii_opts_cache as $key => $val) {
	    if ($key[0] == '_')	// these are non-theme specific settings
		$new_cache[$key] = $val;	// keep
	}
	$opts = $restore['weaverii_base'];	// fetch base opts
	weaverii_delete_all_options();

	foreach ($opts as $key => $val) {
	    if ($key[0] != '_')
		weaverii_setopt($key, $val, false);	// overwrite with saved theme values
	}

	foreach ($new_cache as $key => $val) {	// set the values we need to keep
	    weaverii_setopt($key,$val,false);
	}
    } else if ($type == 'backup') {
	weaverii_delete_all_options();

	$opts = $restore['weaverii_base'];	// fetch base opts
	foreach ($opts as $key => $val) {
	    weaverii_setopt($key, $val, false);	// overwrite with saved values
	}
	global $weaverii_pro_opts;
	$weaverii_pro_opts = false;
	$weaverii_pro_opts = $restore['weaverii_pro'];
        weaverii_wpupdate_option('weaverii_pro',$weaverii_pro_opts);
    }
    weaverii_setopt('wii_version_id',$version); // keep version, force save of db
    weaverii_save_opts('loading theme');	// OK, now we've saved the options, update them in the DB
    return true;
}


if (WEAVER_MARKET)
    add_filter('weaverii_child_extrathemes','weaverii_child_extrathemes_filter');
function weaverii_child_extrathemes_filter($msg) {
    return '';
}

//===============================

if (WEAVER_MARKET)
    add_action('weaverii_child_show_extrathemes','weaverii_child_show_extrathemes_action');

function weaverii_child_show_extrathemes_action() {
    echo '<h3 class="wvr-option-subheader">Select an Add-on Subtheme You Have Uploaded</h3>';
    $addon_dir = weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/';
    $addon_url = weaverii_f_uploads_base_url() . 'weaverii-subthemes/addon-subthemes/';

    $addon_list = array();
    if($media_dir = @opendir($addon_dir)){	    // build the list of themes from directory
	while ($m_file = readdir($media_dir)) {
	    $len = strlen($m_file);
	    $base = substr($m_file,0,$len-4);
	    $ext = $len > 4 ? substr($m_file,$len-4,4) : '';
	    if($ext == '.w2t' ) {
		$addon_list[] = $base;
	    }
	}
    }


    if (!empty($addon_list)) {
	natcasesort($addon_list);

	$cur_addon = weaverii_getopt('wii_addon_name');
	if ($cur_addon)
	    echo '<h3>Currently selected Add-on Subtheme: ' . ucwords(str_replace('-',' ',$cur_addon)) . '</h3>';

?>
<form enctype="multipart/form-data" name='pick_added_theme' method='post'>

 <h4>Select an add-on subtheme: </h4>

<?php
    foreach ($addon_list as $addon) {
	$name = ucwords(str_replace('-',' ',$addon));
?>
	<div style="float:left; width:200px;">
	    <label><input type="radio" name="wii_addon_name"
<?php	    echo 'value="' . $addon . '"' . (weaverii_getopt('wii_addon_name') == $addon ? 'checked' : '') .
		'/> <strong>' . $name . '</strong><br />
		<img style="border: 1px solid gray; margin: 5px 0px 10px 0px;" src="' . $addon_url . $addon . '.jpg" width="150px" height="113px" /><label></div>' . "\n";

    }
?>
    <div style="clear:both;"></div>
    <br /><span class='submit'><input name="set_added_subtheme" type="submit" value="Set to Selected Add-on Subtheme" /></span>&nbsp;
    <span style="color:#b00;"><strong>Note:</strong> Selecting a new subtheme will change only theme related settings. Most Advanced Options will be retained.
    You can use the Save/Restore tab to save a copy of all your current settings first.</span>

	<?php weaverii_nonce_field('set_added_subtheme'); ?>

	<br /><br /><span class='wvr-small-submit' style="margin-left:100px;"><input name="delete_added_subtheme" type="submit" value="Delete Selected Add-on Subtheme" /></span> &nbsp;<small>This will delete the selected Add-on Subtheme from the Add-on directory</small>
	<?php weaverii_nonce_field('delete_added_subtheme'); ?>
    </form>
<?php
    } else {
?>
	<p>No Add-on Subthemes available.</p>
<?php
    }
echo '<h3 class="wvr-option-subheader">Upload an Add-on Subtheme From Your Computer</h3>';
?>
<form name='form_added_theme' enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
    <table>
	<tr valign="top">
	    <td><strong>Select Add-on Subtheme .zip file to upload:</strong>
		<input name="uploaded_addon" type="file" />
		<input type="hidden" name="uploadaddon" value="yes" />
            </td>
	</tr>
        <tr><td>
	    <span style="margin-left:50px;" class='submit'>
		<input name="upload_addon" type="submit" value="Upload Add-on Subtheme" />
	    </span>&nbsp;<small><strong>Upload and Save</strong> an Add-on Subtheme or Subtheme collection from .zip file on your computer. Will be saved on your site's filesystem.</small>
	</td></tr>
    </table>
    <?php weaverii_nonce_field('upload_addon'); ?>
</form>

<?php
}

add_action('weaverii_child_process_options','weaverii_child_process_options');
function weaverii_child_process_options() {

    if (weaverii_submitted('set_added_subtheme') ) {	// Set to selected addon - theme
	if (isset($_POST['wii_addon_name']))
	{
	    $name = $_POST['wii_addon_name'];

	    $openname = weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/' . $name . '.w2t';
	    $contents = file_get_contents($openname);

            if (!weaverii_ex_set_current_to_serialized_values($contents,'weaverii_uploadit:'.$openname)) {
                echo '<div id="message" class="updated fade"><p><strong><em style="color:red;">Sorry,
		there was a problem uploading your add on theme. The name you picked did not have a valid
		Weaver II theme file in  the /wevaerii-subthemes/addon-subthemes directory.</em></strong></p></div>';
	    } else {
                weaverii_save_msg('Weaver II theme options reset to ' .
		    ucwords(str_replace('-',' ',$name )) . ' add-on subtheme.');
		weaverii_setopt('wii_addon_name',$name);
            }
	}
    }

    else if (weaverii_submitted('delete_added_subtheme') ) {	// Delete selected addon theme
	if (isset($_POST['wii_addon_name']))
	{
	    $name = $_POST['wii_addon_name'];
	    @unlink(weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/' . $name . '.w2t');
	    @unlink(weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/' . $name . '.jpg');
	    weaverii_save_msg('Deleted ' .
		    ucwords(str_replace('-',' ',$name )) . ' add-on subtheme.');
	}
    }

    else if (weaverii_submitted('upload_addon')
	&& isset($_POST['uploadaddon'])
	&& $_POST['uploadaddon'] == 'yes') {
	// upload theme from users computer
	// they've supplied and uploaded a file
	$ok = weaverii_unpackzip('uploaded_addon', weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/');
    }

    else if (weaverii_submitted('upload_theme')) {
	// upload theme from users computer
	// they've supplied and uploaded a file

	if (isset($_FILES['uploaded_theme']['name']))	// uploaded_theme
	    $filename = $_FILES['uploaded_theme']['name'];
	else
	    $filename = "";

	$to = weaverii_f_themes_dir();

	if (strpos($filename,'weaver-ii-pro-') === false && strpos($filename, 'weaver-ii-') === false)
	{
?>
	    <div id="message" class="updated fade"><p><strong><em style="color:red;">ERROR</em></strong></p>
		<p>You did not select a Weaver II theme .zip file: "<?php echo $filename;?>".
		The theme file name must start with 'weaver-ii-'
		or 'weaver-ii-pro-'. Please use a file you downloaded from WeaverTheme.com.</p>
	    </div>
<?php
	    return;
	}
	$ok = weaverii_unpackzip('uploaded_theme',$to);
	if ($ok)
	    weaverii_save_msg('Your Weaver II Theme has been successfully updated. Please click "Clear Messages"
button right now to refresh the scrren and start using the updated version.');
    }

}

function weaverii_unpackzip($uploaded, $to_dir) {
    // upload theme from users computer
    // they've supplied and uploaded a file

    $ok = true;     // no errors so far

    if (isset($_FILES[$uploaded]['name']))	// uploaded_addon
        $filename = $_FILES[$uploaded]['name'];
    else
        $filename = "";

    if (isset($_FILES[$uploaded]['tmp_name'])) {
        $openname = $_FILES[$uploaded]['tmp_name'];
    } else {
        $openname = "";
    }

    //Check the file extension
    $check_file = strtolower($filename);
    $ext_check = end(explode('.', $check_file));

    if (false && !weaverii_f_file_access_available()) {
	$errors[] = "Sorry - Weaver II unable to access files.<br />";
	$ok = false;
    }

    if ($filename == "") {
	$errors[] = "You didn't select a file to upload.<br />";
	$ok = false;
    }

    if ($ok && $ext_check != 'zip'){
	$errors[] = "Uploaded files must have <em>.zip</em> extension.<br />";
	$ok = false;
    }

    if ($ok) {
        if (!weaverii_f_exists($openname)) {
            $errors[] = '<strong><em style="color:red;">
Sorry, there was a problem uploading your file. You may need to check your folder permissions
or other server settings.</em></strong><br />' . "(Trying to use file '$openname')";
            $ok = false;
        }
    }

    if ($ok) {
	// should be ready to go, but check out WP_Filesystem
	if (! WP_Filesystem()) {
	    function weaveriiex_return_direct() { return 'direct'; }
	    add_filter('filesystem_method', 'weaveriiex_return_direct');
	    $try2 = WP_Filesystem();
	    remove_filter('filesystem_method', 'weaveriiex_return_direct');
	    if (!$try2) {
		$errors[] = 'Sorry, there\'s a problem trying to use the WordPress unzip function. Please
see the FAQ at weavertheme.com support for more information.';
		$ok = false;
	    }
	}
    }
    if ($ok) {
	// $openname has uploaded .zip file to use
	// $filename has name of file uploaded
	$is_error = unzip_file( $openname, $to_dir );
	if ( !is_wp_error( $is_error ) ) {
	    weaverii_save_msg('File ' . $filename . ' successfully uploaded and unpacked to: <br />' . $to_dir);
	    @unlink($openname);	// delete temp file...
	} else {
	    $errors[] = "Sorry, unpacking the .zip you selected file failed. You may have a corrupt .zip file, or there many a file permissions problem on your WordPress installation.";
	    $errors[] = $is_error->get_error_message();
	    $ok = false;
	}
    }
    if (!$ok) {
	echo '<div id="message" class="updated fade"><p><strong><em style="color:red;">ERROR</em></strong></p><p>';
	foreach($errors as $error){
	    echo $error.'<br />';
	}
	echo '</p></div>';
    }
    return $ok;
}

if (WEAVER_MARKET)
    add_action('weaverii_child_saverestore','weaverii_child_saverestore_action');
function weaverii_child_saverestore_action() {
    echo '<h3 class="wvr-option-subheader" style="font-style:italic">Use the <em>Weaver II Themes</em>
 tab to upload Add-on Subthemes.</h3><p>You can upload extra add-on subthemes you\'ve downloaded using the
 Weaver II Themes tab. Note: the Save and Restore options on this page are for the custom settings you
 have created. These save/restore options are not related to Add-on Subthemes, although you can
 modify an Add-on Subtheme, and save your changes here.</p>';
}

add_action('weaverii_child_update','weaverii_child_update_action');
function weaverii_child_update_action() {
    echo '<h3 class="wvr-option-subheader">*** Update Weaver II theme from .zip file on your computer. ***</h3>';
    if ((!is_multisite() && current_user_can('install_themes')) || (is_multisite() && current_user_can('manage_network_themes')))
     {
?>
<form  enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
<p style="font-weight:bold;">This action will update the version of Weaver II you are using right now with a version you've
downloaded from <em>WeaverTheme.com</em> or <em>Pro.WeaverTheme.com</em>. This option is most commonly used to update your version of Weaver II Pro, but you can also upgrade the free Weaver II version.</p>
    <table>
	<tr valign="top">
	    <td>
		<input name="uploaded_theme" type="file" />
		&nbsp;<strong>Select Weaver II or Weaver II Pro .zip file with version to update.</strong>
            </td>
	</tr>
        <tr><td>
	    <span class='submit'>
		<input name="upload_theme" type="submit" value="Update Weaver II Theme" />
	    </span>&nbsp;<strong>Update Weaver II</strong> -- Upload 'weaver-ii' or 'weaver-ii-pro' .zip file and upgrade theme.
	</td></tr>
    </table>
<?php
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($max_upload, $max_post, $memory_limit);
    if ($upload_mb < 2) {
	echo '<p><strong style="color:red">WARNING! -- It appears your system upload
file size limit is less than 2Mb, which is too small for the Weaver II theme .zip file. The upload
is likely to hang and fail if you continue. If your system limit is indeed less than 2Mb, you will need to have
it raised before you proceed. This may involve contacting your hosting company.</strong></p>';
    }
    weaverii_nonce_field('upload_theme');
?>
</form>
<?php
     } else {
	echo '<p>You must be an Admin or Super-Admin to update Weaver II.</p>';
     }
}
?>
