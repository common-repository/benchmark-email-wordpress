<?php
include_once("bmeLib.php");

function bme_admin_email()
{


add_filter('admin_head','zd_multilang_tinymce');
$bmewp = new BMEWPWrapper();

if ( !function_exists("admin_bme_init") ) {
  function admin_bme_init(){
    if(isset($_POST["apiToken"])){
      $client_token = $_POST["apiToken"];
      $bmewp->apiToken = $client_token;

      if(get_option('token')){
        update_option('token', $client_token);
      }else{
        add_option('token', $client_token);
      }
      $bmewp->checkToken;
    }
  }
}

function bme_xmlrpc_methods( $methods ) {
  $my_methods = array(
    'wpStats.get_posts' => 'stats_get_posts',
    'wpStats.get_blog' => 'stats_get_blog'
  );

  return array_merge( $methods, $my_methods );
}

$opt = 0;

if(isset($_POST['updateToken'])){
  delete_option('token');
  admin_bme_init();
  $opt = 1;
  if ( $bmewp->apiToken != "" ) {
    $bmewp->message = "Token Validated";
  }
}

if(isset($_POST['createEmail'])){
  $bmewp->createEmail($_POST['emailName'], $_POST['listid'], $_POST['postID']);
  $opt = 2;
}

if(isset($_POST['scheduleEmail'])){
  $bmewp->scheduleEmail($_POST, $_POST['emailID']);
  $opt = 4;
}
if(isset($_POST['sendNow'])){
  $bmewp->sendNow($_POST['emailID']);
  $opt = 5;
}
if(isset($_POST['sendTest'])){
  $bmewp->testEmail($_POST['testEmail'], $_POST['emailID']);
  $opt = 3;
}

if(isset($_POST['deleteEmail'])){
  $bmewp->deleteEmails($_POST['bmewpemailid']);
  $opt = 0;
}

if(isset($_POST['saveEmail'])){
  $bmewp->saveEmail($_POST, $_POST['emailID']);
  $opt = 0;
}

$mode = "";
$defaultEmailID = "";
if(isset($_POST['act'])){
  if ( $_POST['act'] == "edit" ) {
    $mode = "Edit";
    $defaultEmailID =  $_POST['emailID'];
  }
}

if(isset($_POST['cancel'])){
  $mode = "";
}

add_filter( 'xmlrpc_methods', 'bme_xmlrpc_methods' );

define( 'STATS_VERSION', '3' );
define( 'STATS_XMLRPC_SERVER', 'http://wordpress.com/xmlrpc.php' );
?>
<div class='wrap'>
  <div class="icon32" id="icon-edit-comments"><br/></div>
  <h2> Benchmark Email - Emails</h2>
  <div id='dashboard-widgets' class='metabox-holder has-right-sidebar'>
    <div id="post-body">
       <div id="post-body-content">
       <?
        if ( $opt == 0 ) {
          echo $bmewp->message;
          echo $bmewp->error;
        }
      ?>
      <form id='frmbmemain' method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
        <input type=hidden name='act' value='<?= $mode ?>' />
        <input type=hidden name='emailID' value='<?= $defaultEmailID ?>' />
      </form>
      <?
      if ( $mode == "" ) {
        echo $bmewp->printEmails();
      } else {
        $bmewp->loadEmail($defaultEmailID);
      }
      ?>
      <div class="clear"> </div>
      <br /><br />
      </div>
    </div>

    <!-- SideBar -->
    <div class="inner-sidebar">
      <?
      if (  $mode == "" && $bmewp->validToken ) {
      ?>
      <div class="postbox sidebar">
        <h3 class="hndle"><span>Create Email</span></h3>
        <div class="inside">
          <?
          if ( $opt == 2 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
              <p>
                <label class='standardLabel' for='emailName' title='This is not displayed to the receipient. For your reference only'>Name of your Email: </label>
                <input type='text' size='32' maxlength=100 value='' name='emailName' class='wp100' id='emailName' />
              </p>
              <p>
                <label class='standardLabel' for='listid' title='The campaign will be sent to contacts in this list on the date you schedule your campaign'>Send To : </label>
                <? $bmewp->displayAllLists($_POST['listid']); ?>
              </p>
              <p>
                <label class='standardLabel' for='postID' title='The content and settings from this email will be duplicated'>Copy From: </label>
                <? $bmewp->displayAllPosts($_POST['postID']); ?>
              </p>
              <input class='button rbutton' type='submit' name='createEmail' id='createEmail' value='Create Email' />
          </form>
        </div>
      </div>
      <div class="postbox sidebar">
        <h3 class="hndle"><span>Test Email</span></h3>
        <div class="inside">
          <?
          if ( $opt == 3 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
              <p>
                <label class='standardLabel' for='testEmail' title='Enter the email address you want to send the test email to'>Send Email To: </label>
                <input type='text' size='32' maxlength=100 value='' name='testEmail' class='wp100' id='testEmail' />
              </p>
              <p>
                <label class='standardLabel' for='emailID' title='This email will be sent to the above email address as a test'>Select Email: </label>
                <? $bmewp->displayAllEmails($_POST['emailID']); ?>
              </p>
              <input class='button rbutton' type='submit' name='sendTest' id='sendTest' value='Send Test' />
          </form>
        </div>
      </div>
      <div class="postbox sidebar">
        <h3 class="hndle"><span>Send Email Immediately</span></h3>
        <div class="inside">
          <?
          if ( $opt == 5 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
              <p>
                <label class='standardLabel' for='emailID' title='Select the email to schedule'>Select Email: </label>
                <? $bmewp->displayAllEmails($_POST['emailID']); ?>
              </p>
              <input class='button rbutton' type='submit' name='sendNow' id='sendNow' value='Send Immediately' />
          </form>
        </div>
      </div>
      <div class="postbox sidebar">
        <h3 class="hndle"><span>Schedule Email</span></h3>
        <div class="inside">
          <?
          if ( $opt == 4 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
              <p>
                <label class='standardLabel' for='testEmail' title='The email campaign will be sent on the given date and time'>Send Email On: </label>
                <br /><div id='timestampdiv'><?php touch_time(1,0,4,1); ?></div>
                <br />Current time:
                <?
                $timezone_format = _x('M d Y G:i', 'timezone date format');
                echo date_i18n($timezone_format);
                ?>
              </p>
              <p>
                <label class='standardLabel' for='emailID' title='Select the email to schedule'>Select Email: </label>
                <? $bmewp->displayAllEmails($_POST['emailID']); ?>
              </p>
              <input class='button rbutton' type='submit' name='scheduleEmail' id='scheduleEmail' value='Schedule' />
          </form>
        </div>
      </div>
      <? } ?>
</div>

<?
}
?>