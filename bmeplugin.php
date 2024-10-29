<?php
/*
Plugin Name: Wordpress BenchmarkEmail
Plugin URI: http://www.benchmarkemail.com/resources/WordPress
Description: Allows you to add a widget to your pages that enables visitors to subscribe. You can then export/import these contacts to your Benchmark Email account. You can also create email campaigns based on your WordPress blogs, test, fine tune and schedule your campaign to your lists.
Author: Mark Menezes
Version: 2.3
Author URI: http://www.benchmarkemail.com/
*/
include_once("bme_admin.php");
include_once("bme_admin_email.php");

if (!class_exists("WordPressBMEPlugin")) {
    class WordPressBMEPlugin {
        function WordPressBMEPlugin() {
        }

        function setup() {
          global $wpdb;
          $first_install = false;
          $result = mysql_list_tables(DB_NAME);
          $tables = array();
          while($row = mysql_fetch_row($result)) {
              $tables[] = $row[0];
          }
          if(!in_array($table_name, $tables)) {
              $first_install = true;
          }
          $lbtable_name = $wpdb->prefix . "bmelistbuilder";
          $sqlcreate = "CREATE TABLE ".$lbtable_name . " (
          `id` bigint(20) unsigned NOT NULL auto_increment,
          `email` varchar(255) NOT NULL default '',
          `fname` varchar(255) NOT NULL default '',
          `lname` varchar(255) NOT NULL default '',
          `log_date` datetime NOT NULL default '0000-00-00 00:00:00',
          PRIMARY KEY  (`id`),
          UNIQUE KEY `email` (`email`)
          ) TYPE=MyISAM AUTO_INCREMENT=1 ;";

          require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
          maybe_create_table($lbtable_name,$sqlcreate);
        }
    }
}

if ( !function_exists("bme_adminhead" ) ) {
  function bme_adminhead() {
    $siteurl = get_option('siteurl');
    ?>
<link rel='stylesheet' href='<?= $siteurl ?>/wp-content/plugins/benchmark-email-wordpress/bmeadmin.css' type='text/css' media='all' />
<script src='<?= $siteurl ?>/wp-content/plugins/benchmark-email-wordpress/bme_admin.js' type='text/javascript'></script>
    <?
  }
}

if ( !function_exists("widget_bme_listbuilder" ) ) {
  function widget_bme_listbuilder($args) {
    global $wpdb;
    extract($args);
    $options = get_option('widget_bme_listbuilder');
    $title = empty($options['title']) ? __('Subscribe') : $options['title'];
    $thank_you = empty($options['thank_you']) ? __('Thank you for your subscription') : $options['thank_you'];
    $show_footer = $options['show_footer'];
    if ( $show_footer == "" ) { $show_footer = 1; }
    if($_POST['email'] != null) {
      $pEmail = $wpdb->escape($_POST['email']);
      $pFName = $wpdb->escape($_POST['fname']);
      $pLName = $wpdb->escape($_POST['lname']);

      $data = $wpdb->get_results("SELECT id FROM `".$wpdb->prefix."bmelistbuilder` WHERE `email` = '". $pEmail ."' LIMIT 1",ARRAY_A);
      if($data == null) {
        //INSERT EMAIL
        $wpdb->query("INSERT INTO `".$wpdb->prefix."bmelistbuilder` (`fname`,`lname`,`email` , `log_date`) VALUES ('". $pFName ."','". $pLName ."','". $pEmail ."', NOW());") ;
        $message = "<b>" . $thank_you .  "</b>";
      } else {
        $message = "<b>You have already subscribed earlier</b>";
      }

      if($_COOKIE['wp_old_email'] == null)
      {
        $prevemail = $_POST['email'];
      }
      else
      {
        $prevemail = $_COOKIE['wp_old_email'];
      }
    }
    echo $before_widget;
    echo "<div>";
    echo $before_title . $title . $after_title;
    echo $message;
    echo '<form name="wp_bme_listbuilder" method="POST" > ';
    echo '<table cellspacing=0 cellpadding=5 border=0>';
    echo '<tr><td><div style="float:left; padding-right:10px;width:80px;">Email </div><div style="float:left;"><input type="text" name="email" class="frm" value="' .  $prevemail . '" /></div></td></tr>';
    echo '<tr><td><div style="float:left; padding-right:10px;width:80px;padding-top:7px;">First Name </div><div style="float:left;"><input type="text" name="fname" class="frm" /></div></td></tr>';
    echo '<tr><td><div style="float:left; padding-right:10px;width:80px;padding-top:7px;">Last Name </div><div style="float:left;"><input type="text" name="lname" class="frm" /></div></td></tr>';
    echo '<tr><td><div style="float:left;padding-top:7px;"><input type="submit" name="subscribe" value="Subscribe" class="subscribe" /></div></td></tr>';
    if ( $show_footer == 1 ) {
      echo '<tr><td><div style="float:left;padding-top:7px;"><a href="http://www.benchmarkemail.com">Email marketing</a> by BenchmarkEmail</div></td></tr>';
    }
    echo '</table>';
    echo '</form>';
    echo '</div><br clear="all" />';
    echo $after_widget;
  }
}

if ( !function_exists("widget_bme_listbuilder_control" ) ) {
  function widget_bme_listbuilder_control() {
    $options = $newoptions = get_option('widget_bme_listbuilder');
    if ( $_POST["bme_listbuilder_add"] ) {
      $newoptions['title'] = strip_tags(stripslashes($_POST["bme_listbuilder_title"]));
      $newoptions['thank_you'] = strip_tags(stripslashes($_POST["bme_listbuilder_thankyou"]));
      $newoptions['show_footer'] = strip_tags(stripslashes($_POST["bme_listbuilder_showfooter"]));
      if ( $newoptions['show_footer'] == "" ) { $newoptions['show_footer'] = "0";}
    }
    if ( $options != $newoptions ) {
      $options = $newoptions;
      update_option('widget_bme_listbuilder', $options);
    }
    $title = htmlspecialchars($options['title'], ENT_QUOTES);
    $thank_you = htmlspecialchars($options['thank_you'], ENT_QUOTES);
    $show_footer  = $options['show_footer'];
    echo '<p><label for="bme_listbuilder_title">' . _e('Title:') ;
    echo '<input style="width: 250px;" id="bme_listbuilder_title" name="bme_listbuilder_title" type="text" value="' . $title . '" /></label></p>';
    echo '<p><label for="bme_listbuilder_thankyou">' . _e('Thank You:') ;
    echo '<input style="width: 250px;" id="bme_listbuilder_thankyou" name="bme_listbuilder_thankyou" type="text" value="' . $thank_you . '" /></label></p>';
    echo '<p><label for="bme_listbuilder_showfooter">' . _e('Show Footer:') ;
    echo '<input id="bme_listbuilder_showfooter" name="bme_listbuilder_showfooter" type="checkbox" value="1" ';
    if ( $show_footer == 1 ) { echo ' checked '; }
    echo ' /></label></p>';
    echo '<input type="hidden" id="bme_listbuilder_add" name="bme_listbuilder_add" value="1" />';
  }
}

if ( !function_exists("bme_listbuilder" ) ) {
  function bme_listbuilder() {
    global $wpdb;
    extract($args);
    $options = get_option('widget_bme_listbuilder');
    if($_POST['email'] != null) {
      $title = empty($options['title']) ? __('Subscribe') : $options['title'];
      $thank_you = empty($options['thank_you']) ? __('Thank you for your subscription') : $options['thank_you'];
      $show_footer = $options['show_footer'];
      if ( $show_footer == "" ) { $show_footer = 1; }
      $pEmail = $wpdb->escape($_POST['email']);
      $pFName = $wpdb->escape($_POST['fname']);
      $pLName = $wpdb->escape($_POST['lname']);

      $data = $wpdb->get_results("SELECT id FROM `".$wpdb->prefix."bmelistbuilder` WHERE `email` = '". $pEmail ."' LIMIT 1",ARRAY_A);
      if($data == null) {
        $wpdb->query("INSERT INTO `".$wpdb->prefix."bmelistbuilder` (`fname`,`lname`,`email` , `log_date`) VALUES ('". $pFName ."','". $pLName ."','". $pEmail ."', NOW());") ;
        $message = "<b>" . thank_you . "</b>";
      } else {
        $message = "<b>You have already subscribed earlier</b>";
      }

      if($_COOKIE['wp_old_email'] == null)
      {
        $prevemail = $_POST['email'];
      }
      else
      {
        $prevemail = $_COOKIE['wp_old_email'];
      }
    }
    echo $message;
    echo "<div >";
    echo '<form name="wp_bme_listbuilder" method="POST" > ';
    echo '<table cellspacing=0 cellpadding=5 border=0>';
    echo '<tr><td><div style="float:left; padding-right:10px;width:80px;">Email </div><div style="float:left;"><input type="text" name="email" class="frm" value="' .  $prevemail . '" /></div></td></tr>';
    echo '<tr><td><div style="float:left; padding-right:10px;width:80px;padding-top:7px;">First Name </div><div style="float:left;"><input type="text" name="fname" class="frm" /></div></td></tr>';
    echo '<tr><td><div style="float:left; padding-right:10px;width:80px;padding-top:7px;">Last Name </div><div style="float:left;"><input type="text" name="lname" class="frm" /></div></td></tr>';
    echo '<tr><td><div style="float:left;padding-top:7px;"><input type="submit" name="subscribe" value="Subscribe" class="subscribe" /></div></td></tr>';
    if ( $show_footer == 1 ) {
      echo '<tr><td><div style="float:left;padding-top:7px;"><a href="http://www.benchmarkemail.com">Email marketing</a> by BenchmarkEmail</div></td></tr>';
    }
    echo '</table>';
    echo '</form>';
    echo '</div><br clear="all" />';
  }
}

if ( !function_exists("widget_bme_listbuilder_init" ) ) {
 function widget_bme_listbuilder_init() {
    if(function_exists('register_sidebar_widget')) {
      register_sidebar_widget('BenchmarkEmail Subscribe', 'widget_bme_listbuilder');
      register_widget_control('BenchmarkEmail Subscribe', 'widget_bme_listbuilder_control', 300, 90);
    } else {
      add_action('wp_meta', 'bme_listbuilder');
    }
    return;
  }
}

if ( !function_exists("bme_install") ) {
  function bme_install(){
    if (class_exists("WordPressBMEPlugin")) {
      $bmeplugin = new WordPressBMEPlugin();
    }
    $bmeplugin->setup();
  }
}


if ( !function_exists("bme_admin_pages") ) {
  function bme_admin_pages() {
    if(function_exists('add_options_page')) {
      add_menu_page('Benchmark Email', 'Benchmark Email', 9, 'bme_admin', 'bme_admin' );
      add_submenu_page('bme_admin', 'View Contacts', 'View Contacts', 9, 'bme_admin', 'bme_admin');
      add_submenu_page('bme_admin', 'Emails', 'Emails', 9, 'bme_admin_email' , 'bme_admin_email');
    }
  }
}

add_action('init', 'bme_install');
add_action('plugins_loaded', 'widget_bme_listbuilder_init');
add_action('admin_menu', 'bme_admin_pages');
add_action('admin_head', 'bme_adminhead');
wp_enqueue_script('post');
wp_enqueue_script('editor');
add_thickbox();
