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
  require('includes/functions/articles.php');

  $action = ($_REQUEST['action'] ?? '');
   
  if (tep_not_null($action)) {

    switch ($action) {
      case 'insert':
      case 'save':
        if (isset($_POST['auID'])) $authors_id = (int)$_POST['auID'];
        
        $authors_name = tep_db_prepare_input($_POST['authors_name']);
        $authorsImg = '';
        $authors_image = new upload('authors_image');
        $authors_image->set_destination(DIR_FS_CATALOG_IMAGES .'article_manager_uploads/');

        if ($authors_image->parse() && $authors_image->save()) {
            $authorsImg = tep_db_input($authors_image->filename);
        }

        $sql_data_array = ['authors_name' => $authors_name, 'authors_image' => $authorsImg];

        if ($action == 'insert') {
          $insert_sql_data = ['date_added' => 'now()'];

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform('authors', $sql_data_array);
          $authors_id = tep_db_insert_id();
        } elseif ($action == 'save') {
          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform('authors', $sql_data_array, 'update', "authors_id = '" . (int)$authors_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $authors_desc_array = $_POST['authors_description'];
          $authors_url_array = $_POST['authors_url'];
          $language_id = $languages[$i]['id'];

          $sql_data_array = array('authors_description' => tep_db_prepare_input($authors_desc_array[$language_id]),
                                  'authors_url' => tep_db_prepare_input($authors_url_array[$language_id]));

          if ($action == 'insert') {
            $insert_sql_data = array('authors_id' => $authors_id,
                                     'languages_id' => $language_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform('authors_info', $sql_data_array);
          } elseif ($action == 'save') {
            tep_db_perform('authors_info', $sql_data_array, 'update', "authors_id = '" . (int)$authors_id . "' and languages_id = '" . (int)$language_id . "'");
          }
        }

        tep_redirect(tep_href_link('authors.php', (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'auID=' . $authors_id));
        break;
        
      case 'deleteconfirm':
        $authors_id = tep_db_prepare_input($_GET['auID']);

        tep_db_query("delete from authors where authors_id = '" . (int)$authors_id . "'");
        tep_db_query("delete from authors_info where authors_id = '" . (int)$authors_id . "'");

        if (isset($_POST['delete_articles']) && ($_POST['delete_articles'] == 'on')) {
          $articles_query = tep_db_query("select articles_id from articles where authors_id = '" . (int)$authors_id . "'");
          while ($articles = tep_db_fetch_array($articles_query)) {
            tep_remove_article($articles['articles_id']);
          }
        } else {
          tep_db_query("update articles set authors_id = '' where authors_id = '" . (int)$authors_id . "'");
        }

        tep_redirect(tep_href_link('authors.php', 'page=' . $_GET['page']));
        break;
    }
  }
  //display error message if at least one author doesn't exist
  if (isset($_GET['no_authors']) && $_GET['no_authors'] == 'true')  {
     $messageStack->add(ERROR_NO_AUTHORS_FOUND, 'error');
  }
  require('includes/template_top.php');
 
  switch (ARTICLE_ENABLE_HTML_EDITOR) {
     case "CKEditor":
       echo '<script type="text/javascript" src="ckeditor/ckeditor.js"></script>';
     break;

     case "FCKEditor":
     break;

     case "TinyMCE":
       // START tinyMCE Anywhere
       $storeGet = $_GET['action']; //kludge to work around poor coding used by previous programer
       if ($_GET['action'] = 'new_topic_ACD' || $_GET['action'] = 'new_topic_preview' || $_GET['action'] = 'new_article' || $_GET['action'] = 'article_preview' || $_GET['action'] = 'insert_article') {
           $languages = tep_get_languages(); // Get all languages
           $str = ''; // Build list of textareas to convert
           for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
             $str .= "authors_description[".$languages[$i]['id']."],";

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
.heading-box {background:#343A40;color:#fff; padding:10px 0;}
table.BorderedBoxWhite {border: ridge #ddd 3px; background-color: #eee; }
</style>


<?php
  if (isset($_GET['action']) && $_GET['action'] == 'new') {
?>
  <div class="row">
    <div class="col">
      <h1 class="display-4 mb-2"><?php echo TEXT_HEADING_NEW_AUTHOR; ?></h1>
    </div>
  </div>  
    
    <?php echo tep_draw_form('authors', 'authors.php', '', 'post', 'enctype="multipart/form-data"') . tep_hide_session_id() . tep_draw_hidden_field('action', 'insert'); ?>
   
    <div class="row">
      <div class="col-sm-2"><?php echo TEXT_AUTHORS_NAME; ?></div>
      <div class="col-sm-10"><?php echo tep_draw_input_field('authors_name', '', 'size="20"'); ?></div>
    </div>
    
    <div class="row">
      <div class="col-sm-2"><?php echo TEXT_AUTHORS_IMAGE; ?></div>
      <div class="col-sm-10"><?php echo tep_draw_file_field('authors_image'); ?></div>
    </div>    
    
    <?php
    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
    ?>
      <div class="row">
        <div class="col"><?php echo $languages[$i]['name']; ?></div>
      </div>
      
      <div class="row">
        <div class="col-sm-2"><?php echo TEXT_AUTHORS_DESCRIPTION; ?></div>
        <div class="col-sm-10">
        <?php 
        //$name = ('authors_description[' . $languages[$i]['id'] . ']'
        echo tep_draw_textarea_field('authors_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (isset($authors_description[$languages[$i]['id']]) ? stripslashes($authors_description[$languages[$i]['id']]) : (isset($aInfo->articles_id, $languages[$i]['id']) ? tep_get_author_description($aInfo->articles_id, $languages[$i]['id']) : ''))); 
        ?>
        </div>
      </div>
      
      <div class="row">
        <div class="col-sm-2"><?php echo TEXT_AUTHORS_URL; ?></div>
        <div class="col-sm-10"><?php echo tep_draw_input_field('authors_url[' . $languages[$i]['id'] . ']', '', 'size="30"'); ?></div>
      </div>         
    <?php
    }
    ?>
    
    <div class="row">
      <div class="col"><?php echo 
        tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn-success btn-block') .
        tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('authors.php'));
      ?>
      </div>
 
   </form>    
      
  </div>       
      
      
<?php
  } elseif (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $authors_query = tep_db_query("select authors_id, authors_name, authors_image from authors where authors_id = '" . (int)$_GET['auID'] . "'");
    $authors = tep_db_fetch_array($authors_query);
?>
  <div class="row">
      <h1 class="col display-4 mb-2"><?php echo TEXT_HEADING_EDIT_AUTHOR; ?></h1>
  </div>  
    
    <?php //echo tep_draw_form('authors', 'authors.php', 'page=' . $_GET['page'] . '&auID=' . $authors['authors_id'] . '&action=save', 'post', 'enctype="multipart/form-data"'); ?>
    <?php 
    echo tep_draw_form('authors', 'authors.php', '', 'post', 'enctype="multipart/form-data"') . tep_hide_session_id() . tep_draw_hidden_field('action', 'save'); 
    echo tep_draw_hidden_field('page', $_GET['page']) . 
         tep_draw_hidden_field('&auID', $authors['authors_id']);
    ?>
    
    <div class="row">
      <div class="col-sm-2"><?php echo TEXT_AUTHORS_NAME; ?></div>
      <div class="col-sm-10"><?php echo tep_draw_input_field('authors_name', $authors['authors_name'], 'size="20"'); ?></div>
    </div>
    
    <div class="row mt-2">
      <div class="col-sm-2"><?php echo TEXT_AUTHORS_IMAGE; ?></div>
      <div class="col-sm-3"><?php echo tep_draw_input_field('authors_image_name', $authors['authors_image'], 'disabled size="8"'); ?></div>
      <div class="col-sm-6"><?php echo tep_draw_file_field('authors_image'); ?></div>
    </div>      
    
    <?php
    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) { 
    ?>
      <div class="row">
        <div class="col"><?php echo $languages[$i]['name']; ?></div>
      </div>
      
      <div class="row">
        <div class="col-sm-2"><?php echo TEXT_AUTHORS_DESCRIPTION; ?></div>
        <div class="col-sm-10"><?php echo tep_draw_textarea_field('authors_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (isset($authors['authors_description'][$languages[$i]['id']]) && tep_not_null($authors['authors_description'][$languages[$i]['id']]) ? stripslashes($authors['authors_description'][$languages[$i]['id']]) : tep_get_author_description($authors['authors_id'], $languages[$i]['id']))); ?></div>
      </div>    
     
      <div class="row">
        <div class="col-sm-2"><?php echo TEXT_AUTHORS_URL; ?></div>
        <div class="col-sm-10"><?php echo tep_draw_input_field('authors_url[' . $languages[$i]['id'] . ']', tep_get_author_url($authors['authors_id'], $languages[$i]['id']), 'size="30"'); ?></div>
      </div>         
    <?php
    }
    ?>
    
    <div class="row">
      <div class="col"><?php echo 
        tep_draw_hidden_field('page', $_GET['page']) .
        tep_draw_hidden_field('auID', $authors['authors_id']) .
        tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn-success btn-block') .
        tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('authors.php', 'auID=' . $_GET['auID']));
      ?>
    </div>
    
   </form>    
      
  </div>    
 

<?php } else { ?>


  <div class="row">
    <div class="col">
      <h1 class="display-4 mb-2"><?php echo HEADING_TITLE; ?></h1>
    </div>
  </div>    
  
  <div class="row heading-box">
      <div class="col-11 col-sm-4"><?php echo TABLE_HEADING_AUTHORS; ?></div>
      <div class="col-1 col-sm-4 text-right"><?php echo TABLE_HEADING_ACTION; ?></div>
  </div>    
   
 
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  $authors_query_raw = "select authors_id, authors_name, date_added, last_modified from authors order by authors_name";
  $authors_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $authors_query_raw, $authors_query_numrows);
  $authors_query = tep_db_query($authors_query_raw);
  while ($authors = tep_db_fetch_array($authors_query)) {
    if ((!isset($_GET['auID']) || (isset($_GET['auID']) && ($_GET['auID'] == $authors['authors_id']))) && !isset($auInfo) && (substr($action, 0, 3) != 'new')) {
      $author_articles_query = tep_db_query("select count(*) as articles_count from articles where authors_id = '" . (int)$authors['authors_id'] . "'");
      $author_articles = tep_db_fetch_array($author_articles_query);

     $auInfo_array = array_merge($authors, $author_articles);
      $auInfo = new objectInfo($auInfo_array);
    }

    if (isset($auInfo) && is_object($auInfo) && ($authors['authors_id'] == $auInfo->authors_id)) {
      echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . $authors['authors_id'] . '&action=edit') . '\'">' . "\n";
    } else {
      echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . $authors['authors_id']) . '\'">' . "\n";
    }
?>
                <td class="dataTableContent"><?php echo $authors['authors_name']; ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($auInfo) && is_object($auInfo) && ($authors['authors_id'] == $auInfo->authors_id)) { echo tep_image('images/icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . $authors['authors_id']) . '">' . tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $authors_split->display_count($authors_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_AUTHORS); ?></td>
                    <td class="smallText" align="right"><?php echo $authors_split->display_links($authors_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
<?php
  if (empty($action)) {
?>
              <tr>
                <td align="right" colspan="2" class="smallText"><?php echo 
                
 tep_draw_bootstrap_button(IMAGE_INSERT, 'fas fa-save', tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . ($auInfo->authors_id ?? '') . '&action=new'), 'primary', null, 'btn-success btn-block btn-lg');

                ?></td>
              </tr>
<?php
  }
?>
            </table></td>
<?php
  $heading = array();
  $contents = array();
  $action = ($_GET['action'] ?? '');
  
  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_AUTHOR . '</b>');

      $contents = array('form' => tep_draw_form('authors', 'authors.php', 'page=' . $_GET['page'] . '&auID=' . $auInfo->authors_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $auInfo->authors_name . '</b>');

      if ($auInfo->articles_count > 0) {
        $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_articles') . ' ' . TEXT_DELETE_ARTICLES);
        $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_ARTICLES, $auInfo->articles_count));
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . 
      
      tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . $auInfo->authors_id . '&action=deleteconfirm'), 'primary', null, 'btn btn-danger btn-block') .
      tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('authors.php', 'auID=' . $_GET['auID'])));
    
      break;
    default:
      if (isset($auInfo) && is_object($auInfo)) {
        $heading[] = ['text' => '<strong>' . $auInfo->authors_name . '</strong>'];       
        
        $contents[] = ['text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($auInfo->date_added)];
        if (tep_not_null($auInfo->last_modified)) $contents[] = ['text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($auInfo->last_modified)];
        $contents[] = ['text' => '<br>' . TEXT_ARTICLES . ' ' . $auInfo->articles_count];
        
        $contents[] = ['align' => 'center', 'text' => 
         tep_draw_bootstrap_button(IMAGE_EDIT, 'fas fa-save', tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . $auInfo->authors_id . '&action=edit'), 'primary', null, 'btn btn-success btn-block') .
         tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', tep_href_link('authors.php', 'page=' . $_GET['page'] . '&auID=' . $auInfo->authors_id . '&action=delete'), 'primary', null, 'btn btn-danger btn-block')] ;
        
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
 

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
 
