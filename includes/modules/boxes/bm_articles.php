<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  class bm_articles {
    var $code = 'bm_articles';
    var $group = 'boxes';
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->title = MODULE_BOXES_ARTICLES_TITLE;
      $this->description = MODULE_BOXES_ARTICLES_DESCRIPTION;

      if ( defined('MODULE_BOXES_ARTICLES_STATUS') ) {
        $this->sort_order = MODULE_BOXES_ARTICLES_SORT_ORDER;
        $this->enabled = (MODULE_BOXES_ARTICLES_STATUS == 'True');

        $this->group = ((MODULE_BOXES_ARTICLES_CONTENT_PLACEMENT == 'Left Column') ? 'boxes_column_left' : 'boxes_column_right');
      }
    }

    function execute() {
      global $oscTemplate, $languages_id, $request_type;

        $topicCtr = 0;
        $topics_string = '';

        $articlesArray = [];
        $tree = [];

        function GetBoldTags($page, $id = 0) {
           $boldTags = ['start' => '', 'stop' => ''];
           
           if (basename($_SERVER['PHP_SELF']) === $page) {
               if ($id == 0) {
                   $boldTags['start'] = '<strong>';
                   $boldTags['stop'] = '</strong>';
               }
               else if ((int)$_GET['articles_id'] == $id)  {
                   $boldTags['start'] = '<strong>';
                   $boldTags['stop'] = '</strong>';
               }
           }    
           return $boldTags;    
        }

        function SortBySetting($a, $b) {
            return strnatcasecmp($a["sort_order"], $b["sort_order"]);
        }

        /********************* BUILD ALL ARTICLES ********************/
        if (ARTICLE_BOX_DISPLAY_ALL_ARTICLES_SECTION == 'true') {
           $articles_all_count = '';
           if (SHOW_ARTICLE_COUNTS == 'true') {
               $articles_new_query = tep_db_query("SELECT a.articles_id, ad.articles_name from articles a left join articles_description ad on a.articles_id = ad.articles_id where (a.articles_date_available IS NULL or to_days(a.articles_date_available) <= to_days(now())) and a.articles_status = '1' and a.articles_is_blog = 0 and ad.language_id = '" . (int)$languages_id . "' order by a.articles_sort_order, a.articles_date_added desc, ad.articles_name");
               $articles_all_count = (tep_db_num_rows($articles_new_query) ?: '');
           }
 
           if ($articles_all_count) {
               $articlesArray['all_articles']['sort_order'] = ARTICLE_BOX_DISPLAY_ALL_ARTICLES_SECTION_SORT_ORDER;
               $boldTags = [];
               $boldTags = GetBoldTags('articles.php');

               $articlesArray['all_articles']['string'] = '<span class="articleLinkHdr">';
               $articlesArray['all_articles']['string'] .= '<a href="' . tep_href_link('articles.php', '', $request_type) . '">' . $boldTags['start'] . BOX_ALL_ARTICLES . $boldTags['stop'] . '</a>';
               $articlesArray['all_articles']['string'] .= '&nbsp;' . $articles_all_count . '</span><br />';

               if (ARTICLE_BOX_DISPLAY_All_ARTICLES_LINKS == 'true') {
                  $ctr = 0;
                   while ($all = tep_db_fetch_array($articles_new_query)) {
                     if (! tep_not_null(ARTICLE_BOX_DISPLAY_ALL_ARTICLES_LINKS_LIMIT) || (tep_not_null(ARTICLE_BOX_DISPLAY_ALL_ARTICLES_LINKS_LIMIT) && $ctr < ARTICLE_BOX_DISPLAY_ALL_ARTICLES_LINKS_LIMIT)) {
                         $boldTags = GetBoldTags('article_info.php', $all['articles_id']);
                         $articlesArray['all_articles']['string'] .= '<span class="articleLinkMarker">-&nbsp;<a href="' . tep_href_link('article_info.php', 'articles_id='.$all['articles_id'], $request_type) . '">' . $boldTags['start'] . $all['articles_name'] . $boldTags['stop'] . '</a></span>' . (SEPARATE_ARTICLES == 'true' ? '<hr class="separatorArticle">' : '<br />');
                     } else {
                         $articlesArray['all_articles']['string'] .= '<span class="articleLinkMarker">-&nbsp;<a href="' . tep_href_link('articles.php', '', $request_type) . '">' . $boldTags['start'] . '<div style="float:right; color:red;">...more</div>' . $boldTags['stop'] . '</a></span><br />';
                         break;
                     }
                     $ctr++;
                  }
               }
           }
        }

        /********************* BUILD NEW ARTICLES ********************/
        if (ARTICLE_BOX_DISPLAY_NEW_ARTICLES_SECTION == 'true') {
           $articles_new_count = '';
           if (SHOW_ARTICLE_COUNTS == 'true') {
               $articles_new_query = tep_db_query("SELECT a.articles_id, ad.articles_name from articles a, articles_to_topics a2t, topics_description td, authors au, articles_description ad where a.authors_id = au.authors_id and a2t.topics_id = td.topics_id and (a.articles_date_available IS NULL or to_days(a.articles_date_available) <= to_days(now())) and a.articles_id = a2t.articles_id and a.articles_status = '1' and a.articles_is_blog = 0 and a.articles_id = ad.articles_id and ad.language_id = '" . (int)$languages_id . "' and td.language_id = '" . (int)$languages_id . "' and a.articles_date_added > SUBDATE(now( ), INTERVAL '" . NEW_ARTICLES_DAYS_DISPLAY . "' DAY)");
               $articles_new_count = (tep_db_num_rows($articles_new_query) ?: '');
           }
           
           if ($articles_new_count) {
               $articlesArray['new_articles']['sort_order'] = ARTICLE_BOX_DISPLAY_NEW_ARTICLES_SECTION_SORT_ORDER;
               $boldTags = GetBoldTags('articles_new.php');
               $articlesArray['new_articles']['string'] = '<span class="articleLinkHdr">';
               $articlesArray['new_articles']['string'] .= '<a href="' . tep_href_link('articles_new.php', '', $request_type) . '">' . $boldTags['start'] . BOX_NEW_ARTICLES . $boldTags['stop'] . '</a>';
               $articlesArray['new_articles']['string'] .= '&nbsp;' . $articles_new_count . '</span><br />';

               if (ARTICLE_BOX_DISPLAY_NEW_ARTICLES_LINKS == 'true') {
                  $ctr = 0;
                  while($new = tep_db_fetch_array($articles_new_query)) {
                     if (! tep_not_null(ARTICLE_BOX_DISPLAY_NEW_ARTICLES_LINKS_LIMIT) || (tep_not_null(ARTICLE_BOX_DISPLAY_NEW_ARTICLES_LINKS_LIMIT) && $ctr < ARTICLE_BOX_DISPLAY_NEW_ARTICLES_LINKS_LIMIT)) {
                         $boldTags = GetBoldTags('article_info.php', $new['articles_id']);
                         $articlesArray['new_articles']['string'] .= '<span class="articleLinkMarker">-&nbsp;<a href="' . tep_href_link('article_info.php', 'articles_id='.$new['articles_id'], $request_type) . '">' . $boldTags['start'] . $new['articles_name'] . $boldTags['stop'] . '</a></span>' . (SEPARATE_NEW_ARTICLES == 'true' ? '<hr class="separatorArticle">' : '<br />');
                     } else {
                         $articlesArray['new_articles']['string'] .= '<span class="articleLinkHdr">-&nbsp;<a href="' . tep_href_link('articles_new.php', '', $request_type) . '">' . $boldTags['start'] . '<div style="float:right; color:red;">...more</div>' . $boldTags['stop'] . '</a></span><br />';
                         break;
                     }
                     $ctr++;
                  }
               }
           }
        }

        /********************* BUILD TOPICS ********************/
        if (ARTICLE_BOX_DISPLAY_TOPICS_SECTION == 'true') {
          $articlesArray['all_topics']['sort_order'] = ARTICLE_BOX_DISPLAY_TOPICS_SECTION_SORT_ORDER;
          $boldTags = GetBoldTags('article-topics.php');
          $articlesArray['all_topics']['string'] = '<span class="articleLinkHdr"><a href="' . tep_href_link('article-topics.php', '', $request_type) . '">' . $boldTags['start'] . BOX_ARTICLE_TOPICS . $boldTags['stop'] . '</a></span><br />';
          if (ARTICLE_BOX_DISPLAY_TOPICS_LINKS == 'true') {
              $topics = tep_get_topics_tree();
              foreach ($topics as $topic) {
                  $spacer = '-&nbsp;';
                  if (($pos = strpos($topic['text'], '&nbsp;&nbsp;&nbsp;')) !== FALSE) {
                      $spacer = '&nbsp;&nbsp;&nbsp;';
                      $topic['text'] = substr($topic['text'], strlen('&nbsp;&nbsp;&nbsp;'));
                  }
                  $articlesArray['all_topics']['string'] .= '<span class="articleLinkMarker">' . $spacer . '<a href="' . tep_href_link('articles.php', 'tPath='.$topic['id'], $request_type) . '">' . $boldTags['start'] . $topic['text'] . $boldTags['stop'] . '</a></span>' . (SEPARATE_TOPICS == 'true' ? '<hr class="separatorTopics">' : '<br />');
              } 
          }   
        }

        /********************* BUILD RSS LINK ********************/
        if (ARTICLE_BOX_DISPLAY_RSS_FEED_SECTION == 'true') {
          $articlesArray['rss_feed']['sort_order'] = ARTICLE_BOX_DISPLAY_RSS_FEED_SECTION_SORT_ORDER;
          $articlesArray['rss_feed']['string'] = '<span class="articleLinkHdr"><a href="' . tep_href_link('article_rss.php', '', $request_type) . '" target="_blank">' . BOX_RSS_ARTICLES . '</a></span><br />';
        }


        /********************* BUILD SUBMIT LINK ********************/
        if (ARTICLE_BOX_DISPLAY_SUBMIT_ARTICLE_SECTION == 'true') {
          $articlesArray['submit_article']['sort_order'] = ARTICLE_BOX_DISPLAY_SUBMIT_ARTICLE_SECTION_SORT_ORDER;
          $boldTags = GetBoldTags('article-submit.php');
          $articlesArray['submit_article']['string'] = '<span class="articleLinkHdr"><a href="' . tep_href_link('article-submit.php', '', $request_type) . '">' . $boldTags['start'] . BOX_ARTICLE_SUBMIT . $boldTags['stop'] . '</a></span><br />';
        }

        /********************* BUILD UPCOMING ARTICLES LINK ********************/
        if (ARTICLE_BOX_DISPLAY_UPCOMING_SECTION == 'true') {
          $upcoming_query = tep_db_query("select a.articles_date_added, a.articles_date_available as date_expected, ad.articles_name from articles a left join articles_description ad on a.articles_id = ad.articles_id where to_days(a.articles_date_available) > to_days(now()) and a.articles_status = '1' and ad.language_id = '" . (int)$languages_id . "' order by date_expected limit " . MAX_DISPLAY_UPCOMING_ARTICLES);
          if (tep_db_num_rows($upcoming_query) > 0) {
            $articlesArray['upcoming']['sort_order'] = ARTICLE_BOX_DISPLAY_UPCOMING_SECTION_SORT_ORDER;
            $boldTags = array();
            $boldTags = GetBoldTags('articles_upcoming.php');
               
            $articlesArray['upcoming']['string'] = '<span class="articleLinkHdr">';
            $articlesArray['upcoming']['string'] .= '<a href="' . tep_href_link('articles.php', 'showblogarticles=true', $request_type) . '">' . $boldTags['start'] . BOX_UPCOMING_ARTICLES . $boldTags['stop'] . '</a>';
            $articlesArray['upcoming']['string'] .= '&nbsp;' . tep_db_num_rows($upcoming_query) . '</span><br />';

            while ($upcoming = tep_db_fetch_array($upcoming_query)) {
                $dateParts = explode(" ", $upcoming['date_expected']);
                $articlesArray['upcoming']['string'] .= '<span class="articleLinkMarker">-&nbsp;' . $boldTags['start'] . $upcoming['articles_name'] . '<br />&nbsp;&nbsp; '. $dateParts['0'] . $boldTags['stop'] . '</span>' . (defined('SEPARATE_UPCOMING_ARTICLES') && SEPARATE_UPCOMING_ARTICLES == 'True' ? '<hr class="separatorUpcomingArticle">' : '<br />');
            }
          } 
        }


        /********************* ADD A SEARCH FUNCTION ********************/
        if (ARTICLE_BOX_DISPLAY_SEARCH_ARTICLES_SECTION == 'true') {
            $articlesArray['search']['sort_order'] = ARTICLE_BOX_DISPLAY_SEARCH_ARTICLES_SECTION_SORT_ORDER;
             $articlesArray['search']['string'] = '<div class="articleSearch">' .  
                                                  tep_draw_form('article_search', tep_href_link('article_manager_search_result.php', '', $request_type, false), 'get') .
                                                  '<input type="text" name="article_keywords" value="' . TEXT_ARTICLE_SEARCH_STRING . '" onFocus="form.article_keywords.value=\'\';" style="width: 90%" maxlength="35" ><br />'.
                                                  tep_hide_session_id() . tep_draw_button(IMAGE_BUTTON_SEARCH, 'fa fa-user', null, 'primary', null, 'btn-success btn-sm') .
                                                  '</form></div>';
        }

        /********************* ADD BLOG ARTICLES ********************/
        if (ARTICLE_BOX_DISPLAY_ALL_BLOG_SECTION == 'true') {
           $articles_all_count = '';
           if (SHOW_ARTICLE_COUNTS == 'true') {
               $articles_new_query = tep_db_query("SELECT a.articles_id, ad.articles_name from articles a left join articles_description ad on a.articles_id = ad.articles_id where (a.articles_date_available IS NULL or to_days(a.articles_date_available) <= to_days(now())) and a.articles_status = '1' and a.articles_is_blog = 1 and ad.language_id = '" . (int)$languages_id . "' order by a.articles_sort_order, a.articles_date_added desc, ad.articles_name");
               $articles_all_count = (tep_db_num_rows($articles_new_query) ?: '');
           }

           if ($articles_all_count) {
               $articlesArray['blog']['sort_order'] = ARTICLE_BOX_DISPLAY_ALL_BLOG_SECTION_SORT_ORDER;
               $boldTags = array();
               $boldTags = GetBoldTags('article_blog.php');

               $articlesArray['blog']['string'] = '<span class="articleLinkHdr">';
               $articlesArray['blog']['string'] .= '<a href="' . tep_href_link('articles.php', 'showblogarticles=true', $request_type) . '">' . $boldTags['start'] . BOX_ALL_BLOG_ARTICLES . $boldTags['stop'] . '</a>';
               $articlesArray['blog']['string'] .= '&nbsp;' . $articles_all_count . '</span><br />';

               if (ARTICLE_BOX_DISPLAY_All_BLOG_LINKS == 'true') {
                  $ctr = 0;
                  while($all = tep_db_fetch_array($articles_new_query)) {
                     if (! tep_not_null(ARTICLE_BOX_DISPLAY_ALL_BLOG_LINKS_LIMIT) || (tep_not_null(ARTICLE_BOX_DISPLAY_ALL_BLOG_LINKS_LIMIT) && $ctr < ARTICLE_BOX_DISPLAY_ALL_BLOG_LINKS_LIMIT)) {
                         $boldTags = GetBoldTags('article_blog.php', $all['articles_id']);
                         $articlesArray['blog']['string'] .= '<span class="articleLinkMarker">-&nbsp;<a href="' . tep_href_link('article_blog.php', 'articles_id='.$all['articles_id'], $request_type) . '">' . $boldTags['start'] . $all['articles_name'] . $boldTags['stop'] . '</a></span>' . (SEPARATE_BLOG_ARTICLES == 'true' ? '<hr class="separatorBlogArticle">' : '<br />');
                     } else {
                         $articlesArray['blog']['string'] .= '<span class="articleLinkMarker">-&nbsp;<a href="' . tep_href_link('articles.php', '', $request_type) . '">' . $boldTags['start'] . '<div style="float:right; color:red;">...more</div>' . $boldTags['stop'] . '</a></span><br />';
                         break;
                     }
                     $ctr++;
                  }
               }
           }
        }


        /********************* SORT THE DISPLAY  ********************/
        usort($articlesArray, "SortBySetting");

        $content = '';
        foreach ($articlesArray as $line) {
            $content .= $line['string'];
        }
        /********************* DISPLAY IT ALL ********************/

      $tpl_data = ['group' => $this->group, 'file' => __FILE__];
      include 'includes/modules/block_template.php';

    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_BOXES_ARTICLES_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Article Manager Authors Infobox', 'MODULE_BOXES_ARTICLES_STATUS', 'True', 'Do you want to add the module to your shop?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Placement', 'MODULE_BOXES_ARTICLES_CONTENT_PLACEMENT', 'Left Column', 'Should the module be loaded in the left or right column?', '6', '1', 'tep_cfg_select_option(array(\'Left Column\', \'Right Column\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_BOXES_ARTICLES_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_BOXES_ARTICLES_STATUS', 'MODULE_BOXES_ARTICLES_CONTENT_PLACEMENT', 'MODULE_BOXES_ARTICLES_SORT_ORDER');
    }
  }
?>