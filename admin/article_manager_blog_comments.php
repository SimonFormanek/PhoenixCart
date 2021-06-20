<?php
/*
  $Id: authors.php, v1.0 2003/12/04 12:00:00 ra Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2009 http://www.oscommerce-solution.com

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  require_once('includes/functions/articles.php');

  $action = ($_REQUEST['action'] ?? '');

  if (tep_not_null($action)) {
    $articles_id = tep_db_prepare_input($_GET['comID']);
    $languages = tep_get_languages();

    switch ($action) {
        case 'setflag':
            if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
                tep_db_query("update articles_blog set comments_status = '" . $_GET['flag'] . "' where unique_id = '" . (int)$_GET['aID'] . "'");
            }
            
            tep_redirect(tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID='. $_GET['comID'] . '&action=edit'));
        break;     
      
        case 'reply': 
          $cID = $_POST['customer'];
          $customer_query = tep_db_query("select customers_firstname from customers where customers_id  = '" . (int)$cID . "'");
          $customer = tep_db_fetch_array($customer_query);
          $customer_name = $customer['customers_firstname'];
          
          if (! tep_not_null($customer_name)) {
              $messageStack->add_session(ERROR_INVALID_NAME, 'error');
              tep_redirect(tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] .'&comID=' . $_GET['comID'] . '&action=edit' ));
          } else { 
              for ($i = 0, $n=sizeof($languages); $i<$n; $i++) {
                  $language_id = $languages[$i]['id'];          
                  for ($c = 0; $c < count($_POST['comments']); ++$c) {
                      if (count($_POST['comments'][$language_id][$c])) {
                          if (tep_not_null($_POST['comments'][$language_id][$c])) {
                             
                              tep_db_query("insert into articles_blog (articles_id, customers_id,commenters_name,commenters_ip,comment_date_added,comments_status,comment,language_id) 
                                values (
                                  '" .(int)$_GET['comID'] . "',
                                  '" . (int)$cID . "',
                                  '" .  tep_db_input($customer_name) . "',
                                   INET_ATON( '" . $_SERVER['REMOTE_ADDR']. "' ), 
                                  '" . date('Y-m-d') . "',
                                  '" .   '1' . "',
                                  '" .  tep_db_input($_POST['comments'][$language_id][$c]) . "',
                                  '" . (int)$language_id . "'
                                )"); 
                          }
                      }
                  }                    
              } 
              
              tep_redirect(tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page']));
          }
        break;
        
        case 'insert':
        case 'save': 

          if (isset($_POST['delete_comment'])) {
              foreach ($_POST['delete_comment'][$_GET['comID']] as $key => $id) {
                 tep_db_query("delete from articles_blog where unique_id = " . (int)$id);
              }
          }
          else if (isset($_GET['comID'])) {

              for ($i = 0, $n=sizeof($languages); $i<$n; $i++) {
                  $language_id = $languages[$i]['id'];
                  
                  
                  for ($c = 1; $c < count($_POST['comments'][$language_id]) + 1; ++$c) {
                   
             
                      $commenters_name = tep_db_prepare_input($_POST['commenters_name_'.$c]);
                      $sql_data_array = array('commenters_name' => $commenters_name);

                      if (count($_POST['comments'][$language_id])) {
                       
                          $comments_array = $_POST['comments'][$language_id][$c];
                          
                          //$sql_data_array = array'comment' => tep_db_prepare_input($comments_array));
                          $sql_data_array['comment'] = tep_db_prepare_input($comments_array);
   
                          if ($action == 'insert') {
                              $insert_sql_data = array('articles_id' => $articles_id,
                                                       'comment_date_added' => 'now()',
                                                       'language_id' => $language_id
                                                       );

                              $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
                            //  tep_db_perform('articles_blog', $sql_data_array);
                          } else if ($action == 'save') {                                             
                              tep_db_perform('articles_blog', $sql_data_array, 'update', "unique_id = '" . (int)$_POST['unique_id'][$language_id][$c-1] . "'");
                          }
                      }    
                  }
              }
          }

          tep_redirect(tep_href_link('article_manager_blog_comments.php', (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'comID=' . $articles_id));
          break;
        case 'deleteconfirm':
          tep_remove_article($articles_id);

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('authors');
          }

          tep_redirect(tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page']));
          break;
      }
  }

  //display error message if at least one author doesn't exist
  if (isset($_GET['no_authors']) && $_GET['no_authors'] == 'true') {
      $messageStack->add(ERROR_NO_AUTHORS_FOUND, 'error');
  }

  require('includes/template_top.php');
  
  switch (ARTICLE_ENABLE_HTML_EDITOR) {
     case "CKEditor":
       echo '<script type="text/javascript" src="ckeditor/ckeditor.js"></script>'; ?>
       <script type="text/javascript" src="<?php echo tep_href_link('ext/ckeditor/adapters/jquery.js'); ?>"></script>
     <?php  
     break;
     case "FCKEditor":
     break;
     case "TinyMCE":
       // START tinyMCE Anywhere
       $storeGet = $_GET['action']; //kludge to work around poor coding used by previous programer
       if ( $_GET['action'] = 'new_topic' || $_GET['action'] = 'new_article' || $_GET['action'] = 'update-article' || $_GET['action'] = 'insert_article') {
           $languages = tep_get_languages(); // Get all languages
           $str = ''; // Build list of textareas to convert
           for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
               $str .= "topics_description[".$languages[$i]['id']."],articles_description[".$languages[$i]['id']."],articles_description[".$languages[$i]['id']."],";
           }  //end for each language
           
           $mce_str = rtrim ($str,","); // Removed the last comma from the string.
         // You can add more textareas to convert in the $str, be careful that they are all separated by a comma.
           echo '<script language="javascript" type="text/javascript" src="includes/javascript/tiny_mce/tiny_mce.js"></script>';
           include "includes/javascript/tiny_mce/general.php";
       } // END tinyMCE Anywhere
       $_GET['action'] = $storeGet;
       break;
       default: break;
  }
?>
<style type="text/css">
.circle-active {color:green;}
.circle-inactive {color:red;}

table.BorderedBoxWhite {border: ridge #ddd 3px; background-color: #eee; }
</style>


<table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr>
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if ($action == 'new') {
?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0" class="BorderedBoxWhite">
          <tr>
            <td class="pageHeading"><?php echo TEXT_HEADING_NEW_AUTHOR; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '100%', 5); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr><?php echo tep_draw_form('authors', 'article_manager_blog_comments.php', 'action=insert', 'post', 'enctype="multipart/form-data"'); ?>
        <td><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" colspan="2"><?php echo TEXT_NEW_INTRO; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_AUTHORS_NAME; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('authors_name', '', 'size="20"'); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_AUTHORS_IMAGE; ?></td>
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('authors_image[' . $languages[$i]['id'] . ']', ''); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <?php
          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          ?>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_AUTHORS_DESCRIPTION; ?></td>
            <td>
              <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;'; ?></td>
                  <?php if (FCK_EDITOR == 'true') { ?>
                    <td class="main"><?php echo tep_draw_fckeditor('authors_description[' . $languages[$i]['id'] . ']','700','300',''); ?></td>
                  <?php } else { ?>
                    <td class="main" valign="top"><?php echo tep_draw_textarea_field('authors_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', ''); ?></td>
                  <?php } ?>
                  
                <td class="smallText">
                <?php
                  if (ARTICLE_ENABLE_HTML_EDITOR == 'No Editor')
                    echo tep_draw_textarea_field('topics_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : tep_get_topic_description($tInfo->topics_id, $languages[$i]['id'])));
                  else
                  {
                    if (ARTICLE_ENABLE_HTML_EDITOR == 'FCKEditor') {
                        echo tep_draw_fckeditor('topics_description[' . $languages[$i]['id'] . ']','700','300',(isset($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : tep_get_topic_description($tInfo->topics_id, $languages[$i]['id'])));
                    } else if (ARTICLE_ENABLE_HTML_EDITOR == 'CKEditor') {
                        echo tep_draw_textarea_ckeditor('topics_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : tep_get_topic_description($tInfo->topics_id, $languages[$i]['id'])), 'id = "topics_description[' . $languages[$i]['id'] . ']" class="ckeditor"');
                    } else {
                        echo tep_draw_textarea_field('topics_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : tep_get_topic_description($tInfo->topics_id, $languages[$i]['id'])));
                    }
                  }
                ?>
                </td>                  
                </tr>
              </table>
            </td>
          <tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_AUTHORS_URL; ?></td>
            <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('authors_url[' . $languages[$i]['id'] . ']', '', 'size="30"'); ?></td>
          <tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<?php
  }
?>
          <tr>
            <td colspan="3" class="main" align="center"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . 
              tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn-success btn-block btn-lg') .
              tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&auID=' . $_GET['auID']));
            ?></td>
          </tr>
        </table></td>
      </form>
      </tr>
<?php
  } elseif ($action == 'edit') {

    $comments_query = tep_db_query("select articles_name from articles_description where articles_id = '" . (int)$_GET['comID'] . "'");
    $comments = tep_db_fetch_array($comments_query);    
?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0" class="BorderedBoxWhite">
          <tr>
            <td class="pageHeading"><?php echo TEXT_HEADING_EDIT_COMMENTS . ' ' . $comments['articles_name']; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td>
          </tr>
          <tr>
            <td colspan="4"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
      
          <?php echo tep_draw_form('comments', 'article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . (int)$_GET['comID'] . '&action=save', 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('action', 'save'); ?>
          <tr>
            <td><table border="0" cellspacing="0" cellpadding="2">
            <?php
            $languages = tep_get_languages();                
            $comments_query = tep_db_query("select unique_id, customers_id, commenters_name, commenters_ip, inet_ntoa(commenters_ip) as ip, comment_date_added, comments_status, comment, language_id from articles_blog where articles_id = " . (int)$_GET['comID'] . " order by comment_date_added, commenters_name");
            $comIdx = 0;       //count the comments for saving
            while ($comments = tep_db_fetch_array($comments_query)) {
    
                for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
                    if ($languages[$i]['id'] == $comments['language_id']) {
                        break;
                    }
                }
            ?>
                 <style>td.customer-details a {color:blue;}</style>
                 <tr>
                   <td>
                     <table border="0" cellspacing="0" cellpadding="0">
                       <tr>
                         <td class="main" style="white-space:nowrap;"><?php echo TEXT_ARTICLES_COMMENTER; ?></td>
                         <td class="main customer-details">
                           <?php 
                           echo tep_draw_separator('pixel_trans.gif', '24', '5') . '&nbsp;' . tep_draw_input_field('commenters_name_' . $comments['unique_id'], $comments['commenters_name'], 'size="20"'); 
                           
                           echo '&nbsp;&nbsp;' . TEXT_DATE_OF_COMMENT . tep_date_short($comments['comment_date_added']);
                           
                           if ($comments['customers_id'] > 0) {
                               echo '&nbsp;&nbsp;- <a href="' . tep_href_link('customers.php', 'cID=' . $comments['customers_id'] . '&action=edit') . '">' . TEXT_IS_CUSTOMER . '</a>'; 
                           }
                           
                           if ($comments['commenters_ip']) {
                               echo '&nbsp;&nbsp;- <a href="https://myip.ms/info/whois/' . $comments['ip'] . '" target="_blank">IP: ' . $comments['ip'] . '</a>'; 
                           }
                           
                           if ($comments['comments_status'] == '1') {
                               echo '&nbsp;&nbsp;-&nbsp;<a href="' . tep_href_link('article_manager_blog_comments.php', 'action=setflag&flag=0&aID=' . $comments['unique_id'] . tep_get_all_get_params(array('action'))) . '">' ;
                               echo '<i class="fas fa-circle circle-active" title="' . TEXT_STATUS_CHANGE . '"></i>';
                               echo '</a>';
                           } else {
                               echo '&nbsp;&nbsp;-&nbsp;<a href="' . tep_href_link('article_manager_blog_comments.php', 'action=setflag&flag=1&aID=' . $comments['unique_id'] . tep_get_all_get_params(array('action'))) . '">';
                               echo '&nbsp;<i class="fas fa-circle circle-inactive" title="' . TEXT_STATUS_CHANGE . '"></i>';
                               echo '</a>';
                           }                           
                           ?>
                         </td>                        
                         <td class="main" align="right"><?php echo TEXT_DELETE_COMMENT; ?></td>
                         <td><?php echo tep_draw_checkbox_field('delete_comment['.$_GET['comID'].'][' . $comments['unique_id'] . ']', $comments['unique_id'], false); ?> </td>
                       </tr>
                       <tr>
                         <td colspan="4"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                       </tr>
                       <tr>
                         <td class="main" valign="top"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;'; ?></td>
                         <?php echo tep_draw_hidden_field('unique_id[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', $comments['unique_id']); ?>
                         <td colspan="3" class="main" valign="top">
                          <?php
                       
                          if (ARTICLE_ENABLE_HTML_EDITOR == "No Editor") {
                              echo tep_draw_textarea_field('comments[' . $languages[$i]['id'] . ']'.'['.$comments['unique_id'].']', 'soft', '100', '3', $comments['comment']);
                          } else {
                              if (ARTICLE_ENABLE_HTML_EDITOR == "FCKEditor") {
                                  echo tep_draw_fckeditor('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']','700','300', $comments['comment']);
                              } else if (ARTICLE_ENABLE_HTML_EDITOR == "CKEditor") {
                                  echo tep_draw_textarea_ckeditor('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', 'soft', '100', '10', $comments['comment']);
                              } else {
                                  echo tep_draw_textarea_field('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', 'soft', '100', '10', $comments['comment']);
                              }
                          }                            
                          ?>
                        </td>
                       </tr>
                     </table>
                   </td>
                 </tr>  
                 <tr>
                   <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                 </tr>
                <?php
                 $comIdx++;
                }                
                ?>
                <tr>
                 <td colspan="3" class="smallText" align="center"><?php echo 
                 tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn btn-success btn-block btn-lg') .
                 tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . (int)$_GET['comID']));
                 ?></td>
               </tr>       
            </table></td>
          </tr>  
          </form>
        </table></td>
      </tr>      
      
      <?php 
       $customers = array();
       $customers[] = array('id' => '', 'text' => TEXT_SELECT_CUSTOMER);
       $mail_query = tep_db_query("select customers_id, customers_email_address, customers_firstname, customers_lastname from customers order by customers_lastname");
       while($customers_values = tep_db_fetch_array($mail_query)) {
         $customers[] = array('id' => $customers_values['customers_id'],
                              'text' => $customers_values['customers_lastname'] . ', ' . $customers_values['customers_firstname'] . ' (' . $customers_values['customers_email_address'] . ')');
       }                  
      ?>
      
      <?php echo tep_draw_form('comments-reply', 'article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $_GET['comID'] . '&action=reply', 'post', 'enctype="multipart/form-data"'); ?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0" class="BorderedBoxWhite">
          <tr>
            <td class="pageHeading"><?php echo TEXT_HEADING_REPLY_COMMENTS; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td>
          </tr>
        <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">      
          <tr>
            <td valign="top">&nbsp;</td>
            <td class="main"><?php echo tep_draw_pull_down_menu('customer', $customers); ?></td>
           </tr>  
           <?php
            $comIdx = 0;
            $languages = tep_get_languages();
            
            for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
             ?> 
                <tr>
                        <td class="main" valign="top"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;'; ?></td>
                        <?php echo tep_draw_hidden_field('unique_id[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', $comments['unique_id']); ?>                       

                         <td colspan="3" class="main" valign="top">
                          <?php
                          if (ARTICLE_ENABLE_HTML_EDITOR == "No Editor") {
                              echo tep_draw_textarea_field('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', 'soft', '100', '15', $comments['comment']);
                          } else {
                              if (ARTICLE_ENABLE_HTML_EDITOR == "FCKEditor") {
                                  echo tep_draw_fckeditor('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']','700','300', $comments['comment']);
                              } else if (ARTICLE_ENABLE_HTML_EDITOR == "CKEditor") {
                                  echo tep_draw_textarea_ckeditor('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', 'soft', '100', '10', $comments['comment']);
                              } else {
                                  echo tep_draw_textarea_field('comments[' . $languages[$i]['id'] . ']'.'['.$comIdx.']', 'soft', '100', '10', $comments['comment']);
                              }
                          }                            
                          ?>
                        </td>                        
                        
                </tr>
                <tr>
                  <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                </tr>                        
            <?php
            $comIdx++;        
            }             
          ?>
                                  
          <tr>
            <td colspan="3" class="main" align="center"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . 
              tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn-success btn-block btn-lg') .
              tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&auID=' . $_GET['auID']));
            ?></td>
          </tr>          
        </table></td>
      </tr>
      </td></table>
      </tr>
      </form>      
<?php } else { ?>
  <div class="row">
    <div class="col">
      <h1 class="display-4 mb-2"><?php echo HEADING_TITLE; ?></h1>
    </div>
  </div>  
  

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_ARTICLE_NAME; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_BLOG_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $comments_query_raw = "select *, count(ab.articles_id) as ttl_comments from articles a left join articles_description ad on a.articles_id = ad.articles_id inner join articles_blog ab on ad.articles_id = ab.articles_id and a.articles_is_blog = 1 and ad.language_id = " . (int)$languages_id . " group by ab.articles_id order by ab.comment_date_added";
  $comments_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $comments_query_raw, $comments_query_numrows);
  $comments_query = tep_db_query($comments_query_raw);
  $ttlComments = 0;;

  while ($comments = tep_db_fetch_array($comments_query)) {
      $ttlComments += $comments['ttl_comments'];
 
      if ((!isset($_GET['comID']) || (isset($_GET['comID']) && ($_GET['comID'] == $comments['articles_id']))) && !isset($comInfo) && (substr($action, 0, 3) != 'new')) {
          $comInfo = new objectInfo($comments);
      }
      
      if (isset($comInfo) && is_object($comInfo) && ($comments['articles_id'] == $comInfo->articles_id)) {
        echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $comments['articles_id'] . '&action=edit') . '\'">' . "\n";
      } else {
        echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $comments['articles_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo $comments['articles_name']; ?></td>
                <td class="dataTableContent"><?php echo $comments['comments_status']; ?></td>
                <td class="dataTableContent" align="right">
                <?php 
                if (isset($comInfo) && is_object($comInfo) && ($comments['articles_id'] == $comInfo->articles_id)) { 
                    echo tep_image('images/icon_arrow_right.gif'); 
                    echo '&nbsp;<i class="fas fa-circle circle-inactive" title="' . TEXT_STATUS_CHANGE . '"></i>';
                } else { 
                    echo '<a href="' . tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&auID=' . $comments['articles_id']) . '">' ;
                    echo '&nbsp;<i class="fas fa-circle circle-inactive" title="' . TEXT_STATUS_CHANGE . '"></i>';
                    echo '</a>'; 
                } 
                ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo TEXT_DISPLAY_NUMBER_OF_COMMENTS . $ttlComments; ?></td>
                    <td class="smallText" align="right"><?php echo $comments_split->display_links($comments_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_ARTICLE . '</b>');

      $contents = array('form' => tep_draw_form('articles', 'article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $comInfo->articles_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $comnfo->articles_name . '</b>');

      $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', null, 'primary', null, 'btn-danger btn-block btn-lg') .
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $comInfo->articles_id))];
  
      break;
    default:
      if (isset($comInfo) && is_object($comInfo)) {
        $heading[] = array('text' => '<b>' . $comInfo->articles_name . '</b>');

        $contents[] = ['align' => 'center', 'text' => 
 
        tep_draw_bootstrap_button(IMAGE_EDIT, 'fas fa-user-edit', tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $comInfo->articles_id . '&action=edit'), 'primary', null, 'btn-success btn-block btn-lg') .
        tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', tep_href_link('article_manager_blog_comments.php', 'page=' . $_GET['page'] . '&comID=' . $comInfo->articles_id . '&action=delete'), 'primary', null, 'btn-danger btn-block btn-lg')];
        
        
        $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($comInfo->comment_date_added));
        $contents[] = array('text' => '<br>' . TEXT_COMMENTS . ' ' . $comInfo->ttl_comments);
      }
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
    </table></td>
  </tr>
</table>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
