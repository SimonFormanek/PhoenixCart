<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License
*/

  class cm_i_article_manager {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_INDEX_ARTICLE_MANAGER_TITLE;
      $this->description = MODULE_CONTENT_INDEX_ARTICLE_MANAGER_DESCRIPTION;
      $this->description .= '<div class="secWarning">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_CONTENT_INDEX_ARTICLE_MANAGER_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_INDEX_ARTICLE_MANAGER_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $category_depth;
      
      $content_width = (int)MODULE_CONTENT_INDEX_ARTICLE_MANAGER_CONTENT_WIDTH;
      
      if ($category_depth == 'top') {
          $data = '';
          $db_query = tep_db_query("SELECT a.articles_id, a.articles_is_blog, ad.articles_description, ad.articles_name, ad.articles_image from articles a left join articles_description ad on a.articles_id = ad.articles_id where (a.articles_date_available IS NULL or to_days(a.articles_date_available) <= to_days(now())) and a.articles_status = '1' and ad.language_id = '" . (int)$_SESSION['languages_id'] . "' order by a.articles_sort_order, a.articles_date_added desc, ad.articles_name limit " . MODULE_CONTENT_INDEX_ARTICLE_MANAGER_MAX_LINKS);

          if (tep_db_num_rows($db_query)) {
              $data .= '<div class="articles-index-container">';

              while ($db = tep_db_fetch_array($db_query)) {
                  $path = 'images/article_manager_uploads/' . $db['articles_image'];
                  $page = ($db['articles_is_blog'] ? 'article_blog.php' : 'article_info.php' );
                  
                  $data .= '<div class="articles-index-child">';
                  if (tep_not_null($db['articles_image']) && file_exists($path)) {
                     $data .= '<div class="article-link"><a href="' . tep_href_link($page, 'articles_id=' . $db['articles_id']) . '">' . tep_image($path, addslashes($db['articles_name']), SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a></div>' ;
                  }   
                  $data .= '<div class="articles-index-title article-link"><a href="' . tep_href_link($page, 'articles_id=' . $db['articles_id']) . '">' . addslashes($db['articles_name']) . '</a></div>';
                  
                  if (MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SHOW_DESCRIPTION > 0) {
                      $data .= '<div>' . TruncateHTML($db['articles_description'], MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SHOW_DESCRIPTION) .'</div>';
                  }
                  $data .= '</div>';  
                  
                   	
              }
              $data .= '</div>';  
              
              $tpl_data = [ 'group' => $this->group, 'file' => __FILE__ ];
              include 'includes/modules/content/cm_template.php';
          }           
      }   
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_INDEX_ARTICLE_MANAGER_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable ARTICLE_MANAGER Module', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_STATUS', 'True', 'Should this module be shown on the home page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_CONTENT_WIDTH', '12', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Maximum Links', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_MAX_LINKS', '12', 'The maximum number of Article links to display.', '6', '2', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Show Description', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SHOW_DESCRIPTION', '100', 'Enter the number of characters of the description to display. Leave blank to not show the description.', '6', '3', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '5', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_INDEX_ARTICLE_MANAGER_STATUS', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_CONTENT_WIDTH', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_MAX_LINKS', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SHOW_DESCRIPTION', 'MODULE_CONTENT_INDEX_ARTICLE_MANAGER_SORT_ORDER');
    }
  }
  