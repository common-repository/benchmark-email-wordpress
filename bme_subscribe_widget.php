<?php

function widget_bme_listbuilder($args)
{
  global $wpdb;
  extract($args);
  $options = get_option('widget_bme_listbuilder');
  $title = empty($options['title']) ? __('Subscribe') : $options['title'];
  $message = empty($options['thank_you']) ? __('Thank you for your subscription') : $options['thank_you'];
  $show_footer = empty($options['show_footer']) ? __(1) : $options['show_footer'];
  echo $title;
  if($_POST['email'] != null)
  {
    $pEmail = $wpdb->escape($_POST['email']);
    $pFName = $wpdb->escape($_POST['fname']);
    $pLName = $wpdb->escape($_POST['lname']);

    $data = $wpdb->get_results("SELECT id FROM `".$wpdb->prefix."bme_contacts` WHERE `email` = '". $pEmail ."' LIMIT 1",ARRAY_A);
    if($data == null) {
      //INSERT EMAIL
      $wpdb->query("INSERT INTO `".$wpdb->prefix."bmecontacts` (`fname`,`lname`,`email` , `log_date`) VALUES ('". $pFName ."','". $pLName ."','". $pEmail ."', NOW());") ;
    } else {
      $message = "You have already subscribed earlier";
    }

    if($_COOKIE['wp_old_email'] == null)
    {
      $prevemail = $_POST['email'];
    }
    else
    {
      $prevemail = $_COOKIE['wp_old_email'];
    }
  } else {
    $message = '';
  }
  echo '<b>' . $message . '</b>';
  echo "<div >";
  echo '<form name="wp_bme_listbuilder" method="POST" >';
  echo '<table border=0 cellspacing=0 cellpadding=5>';
  echo '<tr><td><div style="float:left;">Email </div><div style="float:left; padding-left:10px;"><input type="text" name="email" class="frm" value="' .  $prevemail . '" /></div></td></tr>';
  echo '<tr><td><div style="float:left;padding-top:7px;">First Name </div><div style="float:left; padding-left:10px;"><input type="text" name="fname" class="frm" /></div></td></tr>';
  echo '<tr><td><div style="float:left;padding-top:7px;">Last Name </div><div style="float:left; padding-left:10px;"><input type="text" name="lname" class="frm" /></div></td></tr>';
  echo '<tr><td><div style="float:left;padding-top:7px;"><input type="submit" name="subscribe" value="Subscribe" class="subscribe" /></div></td></tr>';
  if ( $show_footer == 1 ) {
    echo '<tr><td><div style="float:left;padding-top:7px;"><a href="http://www.benchmarkemail.com">Email marketing</a> by BenchmarkEmail</div></td></tr>';
  }
  echo '</table>';
  echo '</form>';
  echo '</div><br clear="all" />';

}

function widget_bme_listbuilder_control()
{
  $options = $newoptions = get_option('widget_bme_listbuilder');
  if ( $_POST["bme_listbuilder_add"] ) {
    $newoptions['title'] = strip_tags(stripslashes($_POST["bme_listbuilder_title"]));
  }
  if ( $options != $newoptions ) {
    $options = $newoptions;
    update_option('widget_bme_listbuilder', $options);
  }
  $title = htmlspecialchars($options['title'], ENT_QUOTES);
?>
  <p><label for="bme_listbuilder_title"><?php _e('Title:'); ?> <input style="width: 250px;" id="bme_listbuilder_title" name="bme_listbuilder_title" type="text" value="<?= $title ?>" /></label></p>
  <input type="hidden" id="bme_listbuilder_add" name="bme_listbuilder_add" value="1" />
<?
  }

function widget_bme_listbuilder_init()
{
  if(function_exists('register_sidebar_widget'))
  {
    register_sidebar_widget('BenchmarkEmail Subscribe', 'widget_bme_listbuilder');
    register_widget_control('BenchmarkEmail Subscribe', 'widget_bme_listbuilder_control', 300, 90);
  }
  else
  {
    add_action('wp_meta', 'bme_listbuilder');
  }
  return;
}
?>