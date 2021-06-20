<?php
/*
  $Id: article_manager_db_handler.php, v 1.0 by Jack_mcs

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2019 oscommerce-solution.com

  Released under the GNU General Public License
  
  **********************************************************************
  Run this script to install, update or delete Articles Manager in 
  the database. Use this url to execute the script. The code will 
  determine what to do and provide options.
  
  http://....com/article_manager_db_handler.php
  
  **********************************************************************  
*/

  require('includes/application_top.php');
  
  define('THIS_ADDON', 'Article Manager');
  define('THIS_ADDON_DESCRIPTION', 'Server wide settings for ' . THIS_ADDON);
  define('THIS_FILE', 'article_manager_db_handler.php');
  define('THIS_FORM', 'article_manager_db_handler');  
  
  $do_delete = (isset($_GET['delete']) ? true : false);
  $do_reset  = (isset($_GET['reset'])  ? true : false);
 
  if (isset($_POST['action']) && $_POST['action'] == 'process') {
      if (isset($_POST['delete'])) {
          $do_delete = true;
      } else if (isset($_POST['reset'])) {
          $do_reset = true;          
      } else if (isset($_POST['exit'])) {   
          unlink(THIS_FILE);      
          tep_redirect(tep_href_link('index.php'));          
      } else if (isset($_POST['goto'])) {          //end of run - just go to home page
          tep_redirect(tep_href_link('index.php'));          
      } else if (isset($_POST['goto_delete'])) {   //end of run - just go to home page after deleting this file
          unlink(THIS_FILE);      
          tep_redirect(tep_href_link('index.php'));          
      } else if (isset($_POST['goto_install'])) {   //fall through like a new run
      
      } else {  //catch-all
          unlink(THIS_FILE);      
          tep_redirect(tep_href_link('index.php'));
      }    
  }  
 
  $cfg_group_id = 0;
  $db_query = tep_db_query("select configuration_group_id from configuration_group where configuration_group_title = '" . THIS_ADDON . "'");
  if (tep_db_num_rows($db_query)) {    
      if ($do_delete) {
          $db = tep_db_fetch_array($db_query);          
          tep_db_query("DELETE FROM configuration_group WHERE configuration_group_id = '" . (int)$db['configuration_group_id'] . "'");
          tep_db_query("DELETE FROM configuration WHERE configuration_group_id = '" . (int)$db['configuration_group_id'] . "'");
          tep_db_query("DROP TABLE IF EXISTS topics;");
          tep_db_query("DROP TABLE IF EXISTS topics_description;");
          tep_db_query("DROP TABLE IF EXISTS articles;");
          tep_db_query("DROP TABLE IF EXISTS articles_description;");
          tep_db_query("DROP TABLE IF EXISTS articles_to_topics;");
          tep_db_query("DROP TABLE IF EXISTS articles_blog;");
          tep_db_query("DROP TABLE IF EXISTS authors;");
          tep_db_query("DROP TABLE IF EXISTS authors_info;");
          tep_db_query("DROP TABLE IF EXISTS article_reviews;");
          tep_db_query("DROP TABLE IF EXISTS article_reviews_description;");
          tep_db_query("DROP TABLE IF EXISTS articles_xsell;");           
          
      } else if ($do_reset) {
          $db = tep_db_fetch_array($db_query);
          $cfg_group_id = (int)$db['configuration_group_id'];   
          tep_db_query("delete from configuration where configuration_group_id = '" . (int)$db['configuration_group_id'] . "'");   
          
      } else {
          echo 'Looks like ' . THIS_ADDON . ' is already installed.<br>';
          echo 'Choose one of the following options.<br><br>';
         
          echo tep_draw_form('begin', THIS_FILE, 'post') . tep_hide_session_id() . tep_draw_hidden_field('action', 'process'); ?>
          <div style="padding-bottom:10px"><input type="submit" name="delete" value="Reload This Page and Set Delete Option"></div>
          <div style="padding-bottom:10px"><input type="submit" name="reset" value="Reload This Page and Set Reset Option"></div>
          <div style="padding-bottom:10px"><input type="submit" name="exit" value="Go To Home Page AFTER deleting this file"></div>
          </form> 
          <?php
	         exit();
      }
  } 
  
  $db_sql_array = array(); 
  if ( ! $do_delete ) {  //only new runs and resets
      if ($cfg_group_id == 0) {
          $db_check_query = tep_db_query("select max(configuration_group_id) as id from configuration_group ");
          $max = tep_db_fetch_array($db_check_query);
          $cfg_group_id = $max['id'] + 1;
          tep_db_query("insert into configuration_group (configuration_group_id, configuration_group_title, configuration_group_description, sort_order, visible ) VALUES ('" . $cfg_group_id . "', '" . THIS_ADDON . "', '" . THIS_ADDON_DESCRIPTION . "', '" . (int)$cfg_group_id . "' , '1')");
      } 
    
      /**** BEGIN CONFIGURATION SECTION ****/
      $fields = " configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added, use_function ";
      $fields_short = " configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added ";
      $lines = array();
      $sortID = 1;
      
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Article Image Width', 'ARTICLES_IMAGE_WIDTH', '100', 'Set the width of the image displayed in an article.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Article Image Height', 'ARTICLES_IMAGE_HEIGHT', '100', 'Set the height of the image displayed in an article.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Authors List Style', 'MAX_DISPLAY_AUTHORS_IN_A_LIST', '1', 'Used in Authors box. When the number of authors exceeds this number, a drop-down list will be displayed instead of the default list', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Authors Select Box Size', 'MAX_AUTHORS_LIST', '1', 'Used in Authors box. When this value is 1 the classic drop-down list will be used for the authors box. Otherwise, a list-box with the specified number of rows will be displayed.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";

      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Box ID Cross Reference - Account', 'ARTICLE_MANAGER_XREF_ACCOUNT', '1', 'Relates the Account footer box to a Box ID.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Box ID Cross Reference - Articles', 'ARTICLE_MANAGER_XREF_ARTICLES', '2', 'Relates the Article Manager footer box to a Box ID.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Box ID Cross Reference - Contact Us', 'ARTICLE_MANAGER_XREF_CONTACT_US', '3', 'Relates the Contact Us footer box to a Box ID.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Box ID Cross Reference - InfoBox', 'ARTICLE_MANAGER_XREF_INFOBOX', '4', 'Relates the Information box to a Box ID.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Box ID Cross Reference - Links', 'ARTICLE_MANAGER_XREF_LINKS', '5', 'Relates the Links footer box to a Box ID.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Box ID Cross Reference - Text', 'ARTICLE_MANAGER_XREF_TEXT', '6', 'Relates the Links footer box to a Box ID.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Author in Article Listing', 'DISPLAY_AUTHOR_ARTICLE_LISTING', 'true', 'Display the Author in the Article Listing?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Topic in Article Listing', 'DISPLAY_TOPIC_ARTICLE_LISTING', 'true', 'Display the Topic in the Article Listing?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Abstract in Article Listing', 'DISPLAY_ABSTRACT_ARTICLE_LISTING', 'true', 'Display the Abstract in the Article Listing?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Date Added in Article Listing', 'DISPLAY_DATE_ADDED_ARTICLE_LISTING', 'true', 'Display the Date Added in the Article Listing?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Topic/Author Filter', 'ARTICLE_LIST_FILTER', 'true', 'Do you want to display the Topic/Author Filter?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Authors', 'AUTHOR_BOX_DISPLAY', 'true', 'Display the Author box in the destination column', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles', 'ARTICLE_BOX_DISPLAY', 'true', 'Display the Articles box in the destination column', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";

      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - All Articles Section', 'ARTICLE_BOX_DISPLAY_ALL_ARTICLES_SECTION', 'true', 'Display an All Articles section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - All Articles Sort Order', 'ARTICLE_BOX_DISPLAY_ALL_ARTICLES_SECTION_SORT_ORDER', '2', 'Determines the where the All Articles section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - All Articles Links?', 'ARTICLE_BOX_DISPLAY_All_ARTICLES_LINKS', 'true', 'Display links to individual articles. Requires the Display Box Articles - All Articles Section option to be true. ', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - All Articles Links Limit', 'ARTICLE_BOX_DISPLAY_ALL_ARTICLES_LINKS_LIMIT', '', 'Maximum number of article links to display in the articles infobox. Leave blank for no limit.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - All Blog Section', 'ARTICLE_BOX_DISPLAY_ALL_BLOG_SECTION', 'true', 'Display an All Blog Articles section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - All Blog Sort Order', 'ARTICLE_BOX_DISPLAY_ALL_BLOG_SECTION_SORT_ORDER', '1', 'Determines the where the All Blog Articles section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - All Blog Links?', 'ARTICLE_BOX_DISPLAY_All_BLOG_LINKS', 'true', 'Display links to individual articles. Requires the Display Box Articles - All Blog Articles Section option to be true. ', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - All Blog Links Limit', 'ARTICLE_BOX_DISPLAY_ALL_BLOG_LINKS_LIMIT', '', 'Maximum number of blog article links to display in the articles infobox. Leave blank for no limit.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - All Topics Section', 'ARTICLE_BOX_DISPLAY_TOPICS_SECTION', 'true', 'Display an All Topics section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - All Topics Sort Order', 'ARTICLE_BOX_DISPLAY_TOPICS_SECTION_SORT_ORDER', '3', 'Determines the where the All Topics section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - All Topics Links?', 'ARTICLE_BOX_DISPLAY_TOPICS_LINKS', 'true', 'Display links to individual topics. Requires the Display Box Articles - All Topics Section option to be true. ', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - All Topics Links Limit', 'ARTICLE_BOX_DISPLAY_TOPICS_LINKS_LIMIT', '', 'Maximum number of topics links to display in the articles infobox. Leave blank for no limit.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - New Articles Section', 'ARTICLE_BOX_DISPLAY_NEW_ARTICLES_SECTION', 'true', 'Display a New Articles section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";;
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - New Articles Sort Order', 'ARTICLE_BOX_DISPLAY_NEW_ARTICLES_SECTION_SORT_ORDER', '4', 'Determines the where the New Articles section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - New Articles Links?', 'ARTICLE_BOX_DISPLAY_NEW_ARTICLES_LINKS', 'true', 'Display links to individual articles. Requires the Display Box Articles - New Articles Section option to be true. ', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - New Articles Links Limit', 'ARTICLE_BOX_DISPLAY_NEW_ARTICLES_LINKS_LIMIT', '', 'Maximum number of new article links to display in the articles infobox. Leave blank for no limit.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - RSS Feed Section', 'ARTICLE_BOX_DISPLAY_RSS_FEED_SECTION', 'true', 'Display an RSS Feed section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - RSS Feed Sort Order', 'ARTICLE_BOX_DISPLAY_RSS_FEED_SECTION_SORT_ORDER', '5', 'Determines the where the RSS Feed section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now())";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Search Articles Section', 'ARTICLE_BOX_DISPLAY_SEARCH_ARTICLES_SECTION', 'true', 'Display a Search Box in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - Search Articles Sort Order', 'ARTICLE_BOX_DISPLAY_SEARCH_ARTICLES_SECTION_SORT_ORDER', '8', 'Determines the where the Search Box will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Submit Article Section', 'ARTICLE_BOX_DISPLAY_SUBMIT_ARTICLE_SECTION', 'true', 'Display a Submit Article section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - Submit Article Sort Order', 'ARTICLE_BOX_DISPLAY_SUBMIT_ARTICLE_SECTION_SORT_ORDER', '6', 'Determines the where the Submit Article section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Upcoming Articles Section', 'ARTICLE_BOX_DISPLAY_UPCOMING_SECTION', 'true', 'Display an Upcoming Articles section in the articles box', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Display Box Articles - Upcoming Articles Sort Order', 'ARTICLE_BOX_DISPLAY_UPCOMING_SECTION_SORT_ORDER', '6', 'Determines the where the Upcoming Articles section will be displayed in the infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Separate Articles', 'SEPARATE_ARTICLES', 'false', 'Separate each article in the article infobox with a line.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Separate Blog Articles', 'SEPARATE_BLOG_ARTICLES', 'false', 'Separate each blog article in the article infobox with a line.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Separate New Articles', 'SEPARATE_NEW_ARTICLES', 'false', 'Separate each new article in the article infobox with a line.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Display Box Articles - Separate Topics', 'SEPARATE_TOPICS', 'false', 'Separate each topic in the article infobox with a line.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";

      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Enable Article Reviews', 'ENABLE_ARTICLE_REVIEWS', 'true', 'Enable registered users to review articles?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Enable an HTML Editor', 'ARTICLE_ENABLE_HTML_EDITOR', 'No Editor', 'Use an HTML editor, if selected. !!! Warning !!! The selected editor must be installed for it to work!!!)', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'CKEditor\', \'FCKEditor\', \'TinyMCE\', \'No Editor\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Enable Header Tags SEO', 'ENABLE_HEADER_TAGS_SEO', 'True', 'If Header Tags SEO is not installed, set this to false.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Enable Tell a Friend About Article', 'ENABLE_TELL_A_FRIEND_ARTICLE', 'true', 'Enable Tell a Friend option in the Article Information page?', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Enable Version Checker', 'ARTICLE_ENABLE_VERSION_CHECKER', 'false', 'Enables the version checking code to automatically check if an update is available.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";

      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Location of Prev/Next Navigation Bar', 'ARTICLE_PREV_NEXT_BAR_LOCATION', 'bottom', 'Sets the location of the Previous/Next Navigation Bar<br><br>(top; bottom; both)', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'top\', \'bottom\', \'both\'), ', now(), NULL)";

      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum New Articles Per Page', 'MAX_NEW_ARTICLES_PER_PAGE', '10', 'The maximum number of New Articles to display per page<br>(New Articles page)', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Article Abstract Length', 'MAX_ARTICLE_ABSTRACT_LENGTH', '300', 'Sets the maximum length of the Article Abstract to be displayed<br><br>(No. of characters)', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Articles Per Page', 'MAX_ARTICLES_PER_PAGE', '10', 'The maximum number of Articles to display per page<br>(All Articles and Topic/Author pages)', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Number Articles in Infobox', 'MAX_DISPLAY_ARTICLES_INFOBOX', '6', 'Maximum number of articles to display in the articles infobox.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Number Articles in Navbar (Pro Version Only)', 'MAX_DISPLAY_ARTICLES_NAVBAR', '10', 'Maximum number of articles to display in the Navbar.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Display Upcoming Articles', 'MAX_DISPLAY_UPCOMING_ARTICLES', '5', 'Maximum number of articles to display in the Upcoming Articles module', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Minimum Number Cross-Sell Products', 'MIN_DISPLAY_ARTICLES_XSELL', '1', 'Minimum number of products to display in the articles Cross-Sell listing.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Number Cross-Sell Products', 'MAX_DISPLAY_ARTICLES_XSELL', '6', 'Maximum number of products to display in the articles Cross-Sell listing.', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Maximum Length of Author Name', 'MAX_DISPLAY_AUTHOR_NAME_LEN', '20', 'The maximum length of the author\'s name for display in the Author box', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";

      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Number of Days Display New Articles', 'NEW_ARTICLES_DAYS_DISPLAY', '30', 'The number of days to display New Articles?', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Number of articles to display in the RSS Feed.', 'NEWS_RSS_ARTICLE', '10', 'If you want all of your articles to display in the RSS type in something like 2000.  The default is 10', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
      $lines[] = "INSERT INTO configuration (" . $fields_short . ") VALUES ('Number of characters to display in each RSS article.', 'NEWS_RSS_CHARACTERS', '250', 'If you keep this at 250 it will hide a little bit of each of article from your viewers. They will have to come to your store to finish.  The default is 250', '" . $cfg_group_id . "', '" . ($sortID++). "', now());";
            
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Require Blog Post Approval', 'REQUIRE_ARTICLE_BLOG_POST_APPROVAL', 'true', 'A blog post must be approved before it will display.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";
      $lines[] = "INSERT INTO configuration (" . $fields       . ") VALUES ('Show Article Counts', 'SHOW_ARTICLE_COUNTS', 'true', 'Count recursively how many articles are in each topic.', '" . $cfg_group_id . "', '" . ($sortID++). "', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now(), NULL)";

      $colors = array('a' => 'blue','b' => 'maroon','e' => 'steelblue', 'm' => 'red', 'n' => 'purple');
      $letters = array('a', 'b', 'e', 'm', 'n') ;
      $ctr = 0;      
      $configuration_array = array();
      
      foreach ($lines as &$line) {
          if ($ctr == 0 && ! $do_reset) { //skip the configuration group entry except for resets
              $ctr++;
              continue;
          }
          $posStart = strpos($line, "'") + 1;
          $posStop  = strpos($line, "'", $posStart);
          $word = substr($line, $posStart, $posStop - $posStart);          
          $lgth = strlen($word);
          $char = strtolower($word[0]);
          $color = '';

          foreach ($letters as $letter) {
              if ($letter == $char) {
                  $color = $colors[$char];
                  break;
              }
          }
          $configuration_array[] = array(substr_replace($line, '<font color=' . $color . '>' . $word . '</font>', $posStart, $lgth));
      }

      $db_sql_array = array_merge($db_sql_array, $configuration_array);
      /**** END CONFIGURATION SECTION ****/
      
      /**** BEGIN CREATING THE TABLES ****/               
      $tables = array(
                  array("CREATE TABLE IF NOT EXISTS topics (
                              topics_id int(11) NOT NULL auto_increment,
                              topics_image varchar(64) default NULL,
                              parent_id int(11) NOT NULL default '0',
                              sort_order int(3) default NULL,
                              date_added datetime default NULL,
                              last_modified datetime default NULL,
                              PRIMARY KEY  (topics_id),
                              KEY idx_topics_parent_id (parent_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),

                //  array("INSERT INTO topics (topics_id,topics_image, parent_id,sort_order, date_added, last_modified) VALUES (0,NULL, 0, 0, '2007-08-06 08:52:12', NULL);";

                  array("CREATE TABLE IF NOT EXISTS topics_description (
                              topics_id int(11) NOT NULL default '0',
                              language_id int(11) NOT NULL default '1',
                              topics_name varchar(32) NOT NULL default '',
                              topics_heading_title varchar(64) default NULL,
                              topics_description text,
                              PRIMARY KEY  (topics_id,language_id),
                              KEY idx_topics_name (topics_name)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                 // array("INSERT INTO topics_description VALUES (0, 1, 'Miscellaneous Articles', 'Miscellaneous', 'Articles that do not fall into a specific category.');";
                  
                  array("CREATE TABLE IF NOT EXISTS articles (
                              articles_id int(11) NOT NULL auto_increment,
                              articles_date_added datetime NOT NULL default '0000-00-00 00:00:00',
                              articles_last_modified datetime default NULL,
                              articles_date_available datetime default NULL,
                              articles_status tinyint(1) NOT NULL default '0',
                              articles_is_blog tinyint(1) NOT NULL default 0,
                              articles_sort_order tinyint(5) NOT NULL default '0',
                              authors_id int(11) default NULL,
                              box_id tinyint(4) default '1',
                              PRIMARY KEY  (articles_id),
                              KEY idx_articles_date_added (articles_date_added)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  array("CREATE TABLE IF NOT EXISTS articles_description (
                              articles_id int(11) NOT NULL auto_increment,
                              language_id int(11) NOT NULL default '1',
                              articles_name varchar(64) NOT NULL default '',
                              articles_description text,
                              articles_image varchar(64) NOT NULL default '',
                              articles_url varchar(255) default NULL,
                              articles_viewed int(5) default '0',
                              PRIMARY KEY  (articles_id,language_id),
                              KEY articles_name (articles_name)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  array("CREATE TABLE IF NOT EXISTS articles_to_topics (
                              articles_id int(11) NOT NULL default '0',
                              topics_id int(11) NOT NULL default '0',
                              KEY idx_a2t_articles_id (articles_id),
                              KEY idx_a2t_topics_id (topics_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                            
                  array("CREATE TABLE IF NOT EXISTS articles_blog (
                              unique_id int(11) NOT NULL auto_increment,
                              articles_id int(11) NOT NULL default 0,
                              customers_id int(11) NOT NULL default 0,
                              commenters_name varchar(54) default NULL,
                              commenters_ip int( 64 ) UNSIGNED NOT NULL,
                              comment_date_added datetime NOT NULL default '0000-00-00 00:00:00',
                              comments_status tinyint(1) NOT NULL default '0',
                              comment text NOT NULL default '',
                              language_id int(11) NOT NULL default '1',
                              PRIMARY KEY  (unique_id),
                              KEY idx_articles_id (articles_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  array("CREATE TABLE IF NOT EXISTS authors (
                              authors_id int(11) NOT NULL auto_increment,
                              customers_id int( 11 ) NOT NULL,
                              authors_name varchar(32) NOT NULL default '',
                              authors_image varchar(64) default NULL,
                              date_added datetime default NULL,
                              last_modified datetime default NULL,
                              PRIMARY KEY  (authors_id),
                              KEY IDX_AUTHORS_NAME (authors_name)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  array("CREATE TABLE IF NOT EXISTS authors_info (
                              authors_id int(11) NOT NULL default '0',
                              languages_id int(11) NOT NULL default '0',
                              authors_description text,
                              authors_url varchar(255) NOT NULL default '',
                              url_clicked int(5) NOT NULL default '0',
                              date_last_click datetime default NULL,
                              PRIMARY KEY  (authors_id,languages_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  array("CREATE TABLE IF NOT EXISTS article_reviews (
                              reviews_id int(11) NOT NULL auto_increment,
                              articles_id int(11) NOT NULL default '0',
                              customers_id int(11) default NULL,
                              customers_name varchar(64) NOT NULL default '',
                              reviews_rating int(1) default NULL,
                              date_added datetime default NULL,
                              last_modified datetime default NULL,
                              reviews_read int(5) NOT NULL default '0',
                              approved tinyint(3) unsigned default '0',
                              PRIMARY KEY  (reviews_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  array("CREATE TABLE IF NOT EXISTS article_reviews_description (
                              reviews_id int(11) NOT NULL default '0',
                              languages_id int(11) NOT NULL default '0',
                              reviews_text text NOT NULL,
                              PRIMARY KEY  (reviews_id,languages_id)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"),
                  
                  
                  array("CREATE TABLE IF NOT EXISTS articles_xsell (
                              ID int(10) NOT NULL auto_increment,
                              articles_id int(10) unsigned NOT NULL default '1',
                              xsell_id int(10) unsigned NOT NULL default '1',
                              sort_order int(10) unsigned NOT NULL default '1',
                              PRIMARY KEY  (ID)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;") 
                            );
      $db_sql_array = array_merge($db_sql_array, $tables);
      /**** END CREATING THE TABLES ****/ 
       
      /**** BEGIN UPDATES FOR PREVIOUS VERSIONS ****/        
      if ( $do_reset ) { 
          $updates = array();
          
          if ((tep_db_num_rows(tep_db_query("SHOW COLUMNS FROM articles_blog LIKE 'commenters_ip'"))) == 0) { 
              $updates[] = array("ALTER TABLE `articles_blog` ADD `commenters_ip` INT(64) UNSIGNED NOT NULL AFTER `commenters_name`");
          }

          if ((tep_db_num_rows(tep_db_query("SHOW COLUMNS FROM articles_description LIKE 'articles_head_desc_tag'"))) == 1) { 
              $updates[] = array("ALTER TABLE `articles_description` DROP `articles_head_desc_tag`");
          }
          
          if ((tep_db_num_rows(tep_db_query("SHOW COLUMNS FROM articles LIKE 'box_id'"))) == 0) { 
              $updates[] = array("ALTER TABLE `articles` ADD `box_id` TINYINT(4) DEFAULT '1' AFTER `authors_id`");
          }
          
          $db_sql_array = array_merge($db_sql_array, $updates);
      }      
      /**** END UPDATES FOR PREVIOUS VERSIONS ****/        
  }

      
  // APPLY THE CHANGES
  foreach ($db_sql_array as $sql_array) {
      foreach ($sql_array as $value) {
          if (tep_db_query($value) == false) {
              echo 'An error has occurred while inserting the settings. Aborting!';
              exit();
          }
      }  
  }
?>
<div class="pageHeading"><?php echo THIS_ADDON . ' Database Handler'; ?></div>
<div style="padding:10px 0">
<?php
  if ($do_delete) {
      echo 'Database successfully Removed for ' . THIS_ADDON , '!!!';
  } else if ($do_reset) {
      echo 'Database successfully Updated for ' . THIS_ADDON , '!!!';
  } else {
      echo 'Database successfully Installed for ' . THIS_ADDON , '!!!';
  }
?>
</div>

<?php echo tep_draw_form(THIS_FORM, THIS_FILE, 'post') . tep_hide_session_id() . tep_draw_hidden_field('action', 'process'); ?>

  <?php if ($do_delete) { ?>
  <div style="padding-bottom:10px"><input type="submit" name="goto_install" value="Install Again"></div>
  <?php } ?>
  <div style="padding-bottom:10px"><input type="submit" name="goto_delete" value="Go To Home Page AFTER deleting this file (recommended)"></div>
  <div style="padding-bottom:10px"><input type="submit" name="goto" value="Go To Home Page"></div>
</form> 


