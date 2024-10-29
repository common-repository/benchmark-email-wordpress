<?php
include_once("bmeLib.php");

function bme_admin()
{


if ( function_exists('register_sidebar') )
register_sidebar();

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


if(isset($_POST['deleteList'])){
  $bmewp->deleteList($_POST['listid']);
  $opt = 2;
}

if(isset($_POST['createList'])){
  $bmewp->createList($_POST['listName']);
  $opt = 3;
}

if(isset($_POST['exportList'])){
  $bmewp->exportContacts($_POST['listid']);
  $opt = 2;
}

if(isset($_POST['importList'])){
  $bmewp->importContacts($_POST['listid']);
  $opt = 2;
}

if(isset($_POST['deleteContact'])){
  $bmewp->deleteContacts($_POST['bmewpcontactid']);
  $opt = 0;
}

add_filter( 'xmlrpc_methods', 'bme_xmlrpc_methods' );

define( 'STATS_VERSION', '3' );
define( 'STATS_XMLRPC_SERVER', 'http://wordpress.com/xmlrpc.php' );
?>
<div class='wrap'>
  <div class="icon32" id="icon-edit-comments"><br/></div>
  <h2> Benchmark Email - Contacts</h2>
  <div id='dashboard-widgets' class='metabox-holder has-right-sidebar'>

    <!-- SideBar -->
    <div class="inner-sidebar">

      <!-- Token -->
      <div class="postbox sidebar">
        <h3 class="hndle"><span>Token </span></h3>
        <div class="inside">
          <?
          if ( $opt == 1 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>">
            <p>
              <label class='standardLabel' for='apiToken' title='Enter your Benchmark Email token. You can get it from the "Account Settings" screen'>Token: </label>
              <input type='text' size='36' maxlength=36 value = '<?php echo $bmewp->apiToken ?>' class='wp100' name='apiToken' id='apiToken' />
            </p>
            <p><input class='button rbutton' type='submit' name='updateToken' id='updateToken' value='Update' /></p>
          </form>
        </div>
      </div>
      <? if ( $bmewp->validToken ) { ?>
      <div class="postbox sidebar">
        <h3 class="hndle"><span>Manage Lists</span></h3>
        <div class="inside">
          <?
          if ( $opt == 2 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <h4 title="Export your WordPress contacts into the selected BenchmarkEmail contact list">Export to List:</h4>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
            <? $bmewp->displayAllLists($_POST['listid']); ?>
            <input class='button rbutton' type='submit' name='exportList' id='exportList' value='Export to BenchmarkEmail' />
          </form>
          <h4 title="Add contacts from the selected BenchmarkEmail list into your WordPress account">Import From List:</h4>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
          <? $bmewp->displayAllLists($_POST['listid']); ?>
          <input class='button rbutton' type='submit' name='importList' id='importList' value='Import from BenchmarkEmail' />
          </form>
          <h4 title="Delete the selected BenchmarkEmail contact list">Delete List:</h4>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>" style='display:inline;'>
              <? $bmewp->displayAllLists($_POST['listid']); ?>
              <input class='button rbutton' type='submit' name='deleteList' id='deleteList' value='Delete List' />
          </form>
        </div>
      </div>

      <div class="postbox sidebar">
        <h3 class="hndle" title="Create a new contact list in your BenchmarkEmail account"><span>Create Lists</span></h3>
        <div class="inside">
          <?
          if ( $opt == 3 ) {
            echo $bmewp->message;
            echo $bmewp->error;
          }
          ?>
          <form method='post' action="<?php $_SERVER['REQUEST_URI'] ?>">
            <p>
              <label class='standardLabel' for='listName' title="Enter the name of the list. For your reference only">List Name: </label>
              <input type='text' size='32' maxlength=50 value="" name='listName' class='wp100' id='listName' />
              <input class='button rbutton' type='submit' name='createList' id='createList' value='Create List' />

            </p>

          </form>
        </div>
      </div>
      <? } ?>
    </div>

     <div id="post-body">
      <div id="post-body-content">
           <?
            if ( $opt == 0 ) {
              echo $bmewp->message;
              echo $bmewp->error;
            }
            echo $bmewp->printContacts();
           ?>
          <div class="clear"> </div>
          <br /><br />
          <div class="clear"> </div>
      </div>
    </div>
  </div>
</div>

<?
}
?>