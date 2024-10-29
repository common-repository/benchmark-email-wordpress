<?
class BMEWPWrapper{
  var $apiURL;
  var $apiToken;
  var $listID;
  var $error;
  var $message;
  var $checked = -1;
  var $validToken = false;
  var $MONTHS = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

  function UpdateError(){
    if (!(stristr($this->error, 'Invalid Token') === FALSE)) {
      $this->error = $this->error . '<br /> Get your token from your BenchmarkEmail account. You can get generate your token from your "Account Settings" screen in the "My Account" section';
      $this->validToken = false;
    } else if (!(stristr($this->error, 'Too many calls') === FALSE)) {
      $this->error = $this->error . '<br /> Too many invalid calls have been detected. Please try after a few minutes. You can get generate a new token from your BenchmarkEmail "Account Settings" screen in the "My Account" section';
      $this->validToken = false;
    }
  }

  function getXMLRPC() {
    $this->error = false;
    require_once( ABSPATH . WPINC . '/class-IXR.php' );
    $XMLRPC = new IXR_Client( $this->apiURL );
    // $XMLRPC->debug = true;
    return $XMLRPC;
  }

  function checkToken() {
    $this->error = false;
    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('listGet', $this->apiToken, "", 1, 1, "", "");
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->apiToken = "";
      delete_option('token');
      return;
    } else {
      $this->validToken = true;
    }
    return;
  }

  function BMEWPWrapper() {
    global $wpdb;
    $this->apiURL = 'http://api.benchmarkemail.com/';
    if(get_option('token')){
      $this->apiToken = get_option('token');
      $this->checkToken();
    }else{
      $this->apiToken = '';
    }

    if(get_option('listID')){
      $this->listID = get_option('listID');
    }else{
      $this->listID = '';
    }
    $this->message = '';
  }





  function printContacts() {
    global $wpdb;
    if ( $this->validToken ) {
      $results = $wpdb->get_results("SELECT id, email, fname, lname FROM `".$wpdb->prefix."bmelistbuilder` ");
      $html = "<h4> Your  Contacts in WordPress </h4>";
      if ( count($results) > 0 ) {
        $html .= "<form method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\" id=frmContacts>";
        $html .= "<input class='button rbutton' type='submit' name='deleteContact' id='deleteContactTop' value='Delete Selected' /><br />";
        $html .= "<table cellspacing='0' cellpadding='0' class='widefat fixed' style='float:left;'><thead><tr>";
        $html .= "<th align=left width='5%' ><input style='margin:0px !important;' type=checkbox onClick=\"javascript:switchState('bmewpcontactid[]', this)\" /></th><th align=left>Email</th><th align=left>First Name</th><th align=left>Last Name</th></tr></thead><tbody>";
        $num = 0;
        foreach($results as $result){
          $alternate = "";
          $num++;
          if(($num % 2) == 0){
            $alternate = "class='alternate'";
          }
          $html .= "<tr $alternate>";
          $html .= "<td align=left><input type=checkbox name='bmewpcontactid[]' value='" . $result->id . "'></td>";
          $html .= "<td align=left>" . $result->email . "</td>";
          $html .= "<td align=left>".$result->fname . "</td><td align=left>".$result->lname."</td></tr>";
        }
        $html .= "</tbody></table>";
        $html .= "<br /><input class='button rbutton' type='submit' name='deleteContact' id='deleteContactBot' value='Delete Selected' /></form>";
      } else {
        $html .= "No contacts added yet";
      }
      return $html;
    }
  }


  function loadEmail($emailID) {
    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('emailGetDetail', $this->apiToken, $emailID);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $result = $xmlrpcobj->getResponse();
    $content = $result["templateContent"];

    wp_print_scripts('editor');
    wp_print_scripts('media-upload');
    if (function_exists('wp_tiny_mce')) wp_tiny_mce();
  ?>
  <form method='post' action="<?php echo $_SERVER['REQUEST_URI'] ?>">
  <div id="poststuff" class="postbox">
    <table cellspacing="0" cellpadding="0" style="float: left;" class="widefat fixed">
      <tbody>
        <tr>
          <td width='30%'><label class='standardLabel' for='subject'>Name: </label></td>
          <td><input type='text' size='32' maxlength=100 name='emailName' id='emailName' value="<?= $result["emailName"] ?>" /></td>
        </tr>
        <tr>
          <td width='30%'><label class='standardLabel' for='subject'>Subject: </label></td>
          <td><input type='text' size='32' maxlength=100 name='subject' id='subject' value="<?= $result["subject"] ?>" /></td>
        </tr>
        <tr>
          <td><label class='standardLabel' for='fromName'>From Name: </label></td>
          <td><input type='text' size='32' maxlength=100  name='fromName' id='fromName' value="<?= $result["fromName"] ?>" /></td>
        </tr>
        <tr>
          <td><label class='standardLabel' for='fromEmail'>From Email: </label></td>
          <td><input type='text' size='32' maxlength=100  name='fromEmail' id='fromEmail' value="<?= $result["fromEmail"] ?>" /></td>
        </tr>
        <tr>
          <td><label class='standardLabel' for='replyEmail'>Reply To: </label></td>
          <td><input type='text' size='32' maxlength=100 name='replyEmail' id='replyEmail' value="<?= $result["replyEmail"] ?>" /></td>
        </tr>
        <tr>
          <td colspan=2>
            <div id="postdivrich" class=postarea >
            <?php the_editor($content, $id = 'contenthtml', $media_buttons = false, $tab_index = 2); ?>
            </div>
          </td>
        </tr>
        <tr>
          <td><label class='standardLabel' for='listid'>Send To: </label></td>
          <td><? $this->displayAllLists($result['toListID']); ?></td>
        </tr>
        <tr>
          <td><label class='standardLabel' for='relyEmail'>Schedule: </label></td>
          <td>
          <?
            if ($result["scheduleDate"] != '' )  {
              $newDatetime = str_replace(",","",$result["scheduleDate"]);
              $time = strtotime($newDatetime);

              $mm = date("n", $time);
              $jj = date("j", $time);
              $aa = date("Y", $time);
              $hh = date("H", $time);
              $mn = date("i", $time);

            }

          ?>
          <div id='timestampdiv'>
              <?

              echo "<select name='mm'><option></option>";
              for ($i = 1; $i < 13; $i++ ) { echo "<option value=\"" . $i . "\" ";
                if ( $i + 0 == $mm + 0 ) { echo "selected"; }
                echo ">" . $this->MONTHS[$i -1 ] . "</option>";
              }
              echo "</select>";

              echo '<select name="jj"><option></option>';
              for ($i = 1; $i < 32; $i++ ) { echo "<option value=\"" . $i . "\" ";
                if ( $i == $jj ) { echo "selected"; }
                echo ">" . $i . "</option>";
              }
              echo '</select>';


              echo '<select name="aa"><option></option>';
              for ($i = date('Y') - 5, $l = $i + 15; $i < $l; $i++ ) { echo "<option value=\"" . $i . "\" ";
                if ( $i == $aa ) { echo "selected"; }
                echo ">" . $i . "</option>";
              }
              echo '</select>';

              echo '@';
              echo '<select name="hh">';
              for ($i = 0; $i < 24; $i++ ) { echo "<option value=\"" . sprintf("%02d", $i) . "\" ";
                if ( $i == $hh ) { echo "selected"; }
                echo ">" . sprintf("%02d", $i) . "</option>";
              }
              echo '</select>';

              echo '<select name="mn">';
              for ($i = 0; $i < 60; $i = $i + 15 ) { echo "<option value=\"" . sprintf("%02d", $i) . "\" ";
                if ( $i == $mn ) { echo "selected"; }
                echo ">" . sprintf("%02d", $i) . "</option>";
              }
              echo '</select>';
            ?>
          </div>
          <?
          $timezone_format = _x('M d Y G:i', 'timezone date format');
          ?>
          <br />Current time: <? echo date_i18n($timezone_format); ?>
          </td>
        </tr>
        <tr>
          <td colspan=2>
            <input class='button rbutton' type='submit' name='saveEmail' id='saveEmail' value='Update' />
            <input class='button rbutton' type='submit' name='cancel' id='cancel' value='Cancel' />
          </td>
        </tr>
      </tbody>
    </table>
    </div>
    <input type=hidden name='act' id='act' value='edit' />
    <input type=hidden name='emailID' id='emailID' value="<?= $emailID ?>" />
    </form>
  <?
  }

  function saveEmail($postData, $emailID) {
    global $wpdb;

    $scheduleDate = "";
    $aa = 0 + $postData['aa'] + 0;
    $mm = 0 + $postData['mm'] + 0;
    $jj = 0 + $postData['jj'] + 0 ;
    $hh = 0 + $postData['hh'] + 0;
    $mn = 0 + $postData['mn'] + 0 ;
    $ss = 0 + $postData['ss'] + 0 ;

    if ( $aa > 0 && $mm > 0 && $jj > 0 && $jj < 32 ) {
      $aa = ($aa <= 0 ) ? date('Y') : $aa;
      $mm = ($mm <= 0 ) ? date('n') : $mm;
      $jj = ($jj > 31 ) ? 31 : $jj;
      $jj = ($jj <= 0 ) ? date('j') : $jj;
      $hh = ($hh > 23 ) ? $hh - 24 : $hh;
      $mn = ($mn > 59 ) ? $mn - 60 : $mn;
      $ss = ($ss > 59 ) ? $ss - 60 : $ss;

      // Convert to normal
      $scheduleDate = gmmktime($hh, $mn, $ss, $mm, $jj, $aa);

      // Convert to GMT
      $scheduleDate = gmdate('Y-m-d H:i:s', $scheduleDate - get_option('gmt_offset') * 3600);
    }

    $xmlrpcobj = $this->getXMLRPC();
    $content = str_replace('\"','"', $postData["contenthtml"]);
    $content = str_replace('\r\n','<br />', $content);

    $emailDetail['id'] = $emailID;
    $emailDetail['fromEmail'] = $postData["fromEmail"];
    $emailDetail['fromName'] = $postData["fromName"];
    $emailDetail['emailName'] = $postData["emailName"];
    $emailDetail['replyEmail'] = $postData["replyEmail"];
    $emailDetail['subject'] = $postData["subject"];
    $emailDetail['templateContent'] = $content;
    $emailDetail['toListID'] =  $postData["listid"] + 0;
    $emailDetail['scheduleDate'] = $scheduleDate;
    $xmlrpcobj = $this->getXMLRPC();
    $xmlrpcobj->query('emailUpdate', $this->apiToken, $emailDetail);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $this->message = "Email Saved";
    return;
  }

  function printEmails() {
    global $wpdb;
    $siteurl = get_option('siteurl');


    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('emailGet', $this->apiToken, "", "", 1, 100, "", "");
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $results = $xmlrpcobj->getResponse();
    $html = "<h4> Your Emails in Benchmark Email</h4>";
    if ( count($results) > 0 ) {
      $html .= "<form method='post' action=\"" . $_SERVER['REQUEST_URI'] . "\" id=frmEmails>";
      $html .= "<input class='button rbutton' type='submit' name='deleteEmail' id='deleteEmailTop' value='Delete Selected' /><br />";
      $html .= "<table cellspacing='0' cellpadding='0' class='widefat fixed' style='float:left;'><thead><tr>";
      $html .= "<th align=left width='5%' ><input style='margin:0px !important;' type=checkbox onClick=\"javascript:switchState('bmewpemailid[]', this)\" /></th>";
      $html .= "<th align=left>Email</th><th align=left>Subject</th><th align=left>Schedule</th><th> </th></tr></thead><tbody>";
      $num = 0;
      foreach($results as $result){
        $alternate = "";
        $num++;
        if(($num % 2) == 0){
          $alternate = "class='alternate'";
        }
        $html .= "<tr $alternate>";
        $html .= "<td align=left><input type=checkbox name='bmewpemailid[]' value='" . $result["id"] . "'></td>";
        $html .= "<td align=left>" . $result["emailName"] . " <br /> to: " . $result["toListName"] . "</td>";
        $html .= "<td align=left>" . $result["subject"] . "</td>";
        if ( $result["status"] == "Scheduled" || $result["status"] == "Sent" ) {
          $html .= "<td align=left>" . $result["scheduleDate"] . "</td>";
        } else {
          $html .= "<td align=left>" . $result["status"] . "</td>";
        }
        if ( $result["status"] == "Sent" ) {
          $html .= "<td align=left> </td>";
        } else {
          $html .= "<td align=left><a href='#' onclick='javascript:editEmail(" . $result["id"] .");'>Edit</a></td>";
        }
        $html .= "</tr>";
      }
      $html .= "</tbody></table>";
      $html .= "<br /><input class='button rbutton' type='submit' name='deleteEmail' id='deleteEmailBot' value='Delete Selected' />";
      $html .= "</form>";
    } else {
      $html .= "No emails added yet";
    }
    return $html;
  }

  function exportContacts($listID) {
    global $wpdb;
    $results = $wpdb->get_results("SELECT id, email, fname, lname FROM `".$wpdb->prefix."bmelistbuilder` ");
    $len = count($results);
    $cntr = 0;
    foreach($results as $result){
      $records[$cntr]['email'] = $result->email;
      $records[$cntr]['firstname'] = $result->fname;
      $records[$cntr]['lastname'] = $result->lname;
      $cntr++;
    }
    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('listAddContacts', $this->apiToken, $listID, $records);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $results = $xmlrpcobj->getResponse();
    $this->message =  $results . " contacts added.";

    return;
  }

  function deleteContacts($contacts) {
    global $wpdb;
    $strID = "";
    $count = count($contacts);
    if ( $count > 0 ) { $strID = $contacts[0]; }
    for($i= 1;$i<$count;$i++) { $strID = $strID . ", " . $contacts[$i];}
    $wpdb->query("DELETE from `".$wpdb->prefix."bmelistbuilder` where id in (" . $strID . ")");
    $this->message = "Contacts Deleted";
    return;
  }

  function deleteEmails($emails) {
    global $wpdb;
    $strID = "";
    $count = count($emails);
    $xmlrpcobj = $this->getXMLRPC();
    for($i= 0;$i<$count;$i++) {
      $xmlrpcobj->query('emailDelete', $this->apiToken, $emails[$i]);
    }
    $this->message = "Emails Deleted";
    return;
  }

  function testEmail($emailAddress, $emailID) {
    global $wpdb;
    $xmlrpcobj = $this->getXMLRPC();
    $xmlrpcobj->query('emailSendTest', $this->apiToken, $emailID, $emailAddress);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $this->message = "Test Email Sent";
    return;
  }

  function sendNow($emailID) {
    global $wpdb;
    $xmlrpcobj = $this->getXMLRPC();
    $xmlrpcobj->query('emailSendNow', $this->apiToken, $emailID);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $this->message = "Email is scheduled to be sent immediately";
    return;
  }

  function scheduleEmail($postData, $emailID) {
      global $wpdb;

      $aa = $postData['aa'];
      $mm = $postData['mm'];
      $jj = $postData['jj'];
      $hh = $postData['hh'];
      $mn = $postData['mn'];
      $ss = $postData['ss'];
      $aa = ($aa <= 0 ) ? date('Y') : $aa;
      $mm = ($mm <= 0 ) ? date('n') : $mm;
      $jj = ($jj > 31 ) ? 31 : $jj;
      $jj = ($jj <= 0 ) ? date('j') : $jj;
      $hh = ($hh > 23 ) ? $hh -24 : $hh;
      $mn = ($mn > 59 ) ? $mn -60 : $mn;
      $ss = ($ss > 59 ) ? $ss -60 : $ss;
      // Convert to normal
      $scheduleDate = gmmktime($hh, $mn, $ss, $mm, $jj, $aa);

      // Convert to GMT
      $scheduleDate = gmdate('Y-m-d H:i:s', $scheduleDate - get_option('gmt_offset') * 3600);


      $xmlrpcobj = $this->getXMLRPC();
      $xmlrpcobj->query('emailSchedule', $this->apiToken, $emailID, $scheduleDate);

      if ( $xmlrpcobj->isError() ) {
        $this->error = $xmlrpcobj->getErrorMessage();
        $this->UpdateError();
        $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
        return;
      }
      $this->message = "Email Scheduled";
      exit();
      return;
  }

  function importContacts($listID) {
    global $wpdb;
    $xmlrpcobj = $this->getXMLRPC();
    $xmlrpcobj->query('listGetContacts', $this->apiToken, $listID, "",1, 1000,"","");
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $results = $xmlrpcobj->getResponse();
    foreach ( $results as $result ) {
      $pFName = $wpdb->escape($result["firstname"]);
      $pLName = $wpdb->escape($result["lastname"]);
      $pEmail = $wpdb->escape($result["email"]);


      $wpdb->query("INSERT INTO `".$wpdb->prefix."bmelistbuilder` (`fname`,`lname`,`email` , `log_date`) VALUES ('". $pFName ."','". $pLName ."','". $pEmail ."', NOW()) ON DUPLICATE KEY UPDATE fname = VALUES(fname), lname = VALUES(lname) ") ;
    }
    return;
  }

  function displayAllLists($selListID) {
    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('listGet', $this->apiToken, "", 1, 100, "", "");
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $results = $xmlrpcobj->getResponse();
    echo "<select name='listid' style='width:95%;'>";
    foreach ( $results as $result ) {
      echo "<option value=\"" . $result["id"] . "\" ";
      if ( $selListID  == $result["id"] ) { echo "selected" ; }
      echo ">" . $result["listname"] . " (" . $result["contactcount"] . ") </option>";
    }
    echo "</select>";
  }

  function displayAllPosts($selPostID) {
    global $wpdb;
    require_once( ABSPATH . WPINC . '/post.php' );
    $results = wp_get_recent_posts(100);
    echo "<select name='postID' style='width:95%;'>";
    foreach ( $results as $result ) {
      echo "<option value=\"" . $result["ID"] . "\" ";
      if ( $selListID  == $result["id"] ) { echo "selected" ; }
      echo ">" . $result["post_title"] . " </option>";
    }
    echo "</select>";
  }

  function displayAllEmails($selEmailID) {
      $xmlrpcobj = $this->getXMLRPC();

      $html = "<select name='emailID' style='width:95%;'>";

      $result = $xmlrpcobj->query('emailGet', $this->apiToken, "", "1", 1, 100, "", "");
      if ( $xmlrpcobj->isError() ) {
        $this->error = $xmlrpcobj->getErrorMessage();
        $this->UpdateError();
        $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
        return;
      }
      $results = $xmlrpcobj->getResponse();

      foreach ( $results as $result ) {
        $html .=  "<option value=\"" . $result["id"] . "\" ";
        if ( $selEmailID  == $result["id"] ) { $html .= "selected" ; }
        $html .= ">" . $result["emailName"] . "</option>";
      }
      $result = $xmlrpcobj->query('emailGet', $this->apiToken, "", "0", 1, 100, "", "");
      if ( $xmlrpcobj->isError() ) {
        $this->error = $xmlrpcobj->getErrorMessage();
        $this->UpdateError();
        $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
        return;
      }
      $results = $xmlrpcobj->getResponse();
      foreach ( $results as $result ) {
        $html .=  "<option value=\"" . $result["id"] . "\" ";
        if ( $selEmailID  == $result["id"] ) { $html .=  "selected" ; }
        $html .=  ">" . $result["emailName"] . "</option>";
      }
      $html .= "</select>";
      echo $html;
  }


  function createEmail($emailName, $listID, $selPostID) {
    global $wpdb;
    require_once( ABSPATH . WPINC . '/post.php' );
    $post = wp_get_single_post($selPostID, ARRAY_A);

    $content = $post["post_content"];
    $content = wpautop( $content );


    $content = do_shortcode($content);
    $subject = $post["post_title"];
    $content = str_replace('\r\n','<br />', $content);

    $emailDetail['subject'] = $subject;
    $emailDetail['templateContent'] = $content;
    $emailDetail['toListID'] = $listID + 0;
    $emailDetail['webpageVersion'] = true;
    $emailDetail['emailName'] = $emailName;
    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('emailCreate', $this->apiToken, $emailDetail);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $this->message = 'Email created';
  }

  function createList($listName){
    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('listCreate', $this->apiToken, $listName);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $this->message = 'List created';
    return $xmlrpcobj->getResponse();
  }

  function deleteList($listID){
    $xmlrpcobj = $this->getXMLRPC();
    $this->error = false;
    $result = $xmlrpcobj->query('listDelete', $this->apiToken, $listID);
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $this->message = 'List deleted successfully.';
  }

  function getListID(){
    global $wpdb;
    if ( $this->listID == '' ) {
      $xmlrpcobj = $this->getXMLRPC();
      $result = $xmlrpcobj->query('listGet', $this->apiToken, "WordPress Contacts", 1, 10, "", "");

      if ( $xmlrpcobj->isError() ) {
        $this->error = $xmlrpcobj->getErrorMessage();
        $this->UpdateError();
        $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
        return;
      }
      $result = $xmlrpcobj->getResponse();

      if ( count($result) == 0 || !is_array($result)) {
        $this->listID = $this->createList("WordPress Contacts");
      } else {
        $this->listID = $result[0]["id"];
      }
      if(get_option('listID')){
        update_option('listID', $this->listID);
      }else{
        add_option('listID', $this->listID);
      }
    }
    return $this->listID;
  }

  function getList(){
    $this->getListID();

    $xmlrpcobj = $this->getXMLRPC();
    $result = $xmlrpcobj->query('listGetContacts', $this->apiToken, $this->listID, "",1, 1000,"","");
    if ( $xmlrpcobj->isError() ) {
      $this->error = $xmlrpcobj->getErrorMessage();
      $this->UpdateError();
      $this->message = "<b color=red>" . $xmlrpcobj->getErrorMessage() . "</b>";
      return;
    }
    $results = $xmlrpcobj->getResponse();
    $html = "<h4> Your Benchmark Email Contacts in \"WordPress Contacts\" </h4>";
    $html .= "<table cellspacing='0' cellpadding='0' class='widefat fixed' style='float:left;'><thead><tr>";
    $html .= "<th align=left>Email</th><th align=left>First Name</th><th align=left>Last Name</th></tr></thead><tbody>";
    $num = 0;
    foreach($results as $result){
      $alternate = "";
      $num++;
      if(($num % 2) == 0){
        $alternate = "class='alternate'";
      }
      $html .= "<tr $alternate><td align=left>" . $result->email . "</td>";
      $html .= "<td align=left>".$result->firstname . "</td><td align=left>".$result->lastname."</td></tr>";
    }
    $html .= "</tbody></table>";
    return $html;

  }



}
?>