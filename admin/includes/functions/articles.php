<?php
/*
  $Id: articles.php, v1.5.1 2003/12/04 12:00:00 ra Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2009 http://www.oscommerce-solution.com

  Released under the GNU General Public License
*/
/*****************************************************************
Return an array of details about articles in a topic
*****************************************************************/
function GetChildArticles($topics_id, &$articles_array) {    
    $articles_query = tep_db_query("select a.articles_is_blog, a.articles_status from articles a left join articles_to_topics p2c on a.articles_id = p2c.articles_id where p2c.topics_id = '" . (int)$topics_id . "'");

    while ($articles = tep_db_fetch_array($articles_query)) {

        if ($articles['articles_is_blog']) {
            $articles_array['articles_ttl_blog_yes'] += 1;  
            if ($articles['articles_status']) {
                $articles_array['articles_active_blog_yes'] += 1;  
            }
        } else {
            $articles_array['articles_ttl_blog_no'] += 1;  
            if ($articles['articles_status']) {
                $articles_array['articles_active_blog_no'] += 1;  
            }
        } 
        $articles_array['articles_ttl_cnt'] += 1;

        $childs_query = tep_db_query("select topics_id from topics where parent_id = '" . (int)$topics_id . "'");
        if (tep_db_num_rows($childs_query)) {
            while ($childs = tep_db_fetch_array($childs_query)) {
                $articles_array = GetChildArticles($childs['topics_id'], $articles_array);
            }
        }
    }
        
    return $articles_array;
}

/*****************************************************************
Count how many subtopics exist in a topic
*****************************************************************/
function GetChildTopicCount($topics_id) {
    $topics_count = 0;

    $topics_query = tep_db_query("select topics_id from topics where parent_id = '" . (int)$topics_id . "'");
    while ($topics = tep_db_fetch_array($topics_query)) {
        $topics_count++;
        $topics_count += GetChildTopicCount($topics['topics_id']);
    }

    return $topics_count;
}

function GetHeaderTagsTitle($aID, $langID) {
    $pageName = 'articles_id=' . $aID;
    $pageTags_query = tep_db_query("select page_title from headertags where page_name like '%" . tep_db_input($pageName) . "%' and language_id = '" . (int)$langID . "'");
    $pageTags = tep_db_fetch_array($pageTags_query);
    return $pageTags['page_title'];
}
function GetHeaderTagsDescription($aID, $langID) {
    $pageName = 'articles_id=' . $aID;
    $pageTags_query = tep_db_query("select page_description from headertags where page_name like '%" . tep_db_input($pageName) . "%' and language_id = '" . (int)$langID . "'");
    $pageTags = tep_db_fetch_array($pageTags_query);
    return $pageTags['page_description'];
}
function GetHeaderTagsKeywords($aID, $langID) {
    $pageName = 'articles_id=' . $aID;
    $pageTags_query = tep_db_query("select page_keywords from headertags where page_name like '%" . tep_db_input($pageName) . "%' and language_id = '" . (int)$langID . "'");
    $pageTags = tep_db_fetch_array($pageTags_query);
    return $pageTags['page_keywords'];
}

// Parse and secure the tPath parameter values
function tep_parse_topic_path($tPath) {
    // make sure the topic IDs are integers
    $tPath_array = array_map('StringToInt', explode('_', $tPath));

    // make sure no duplicate topic IDs exist which could lock the server in a loop
    $tmp_array = array();
    $n = sizeof($tPath_array);
    for ($i=0; $i<$n; $i++) {
        if (!in_array($tPath_array[$i], $tmp_array)) {
            $tmp_array[] = $tPath_array[$i];
        }
    }

    return $tmp_array;
}
  
/*****************************************************************
Return an array of box ID's based on the configuration settings
*****************************************************************/
function GetBoxIDs() {
    $ids = array();
    $db_query = tep_db_query("select configuration_key, configuration_value from configuration where configuration_key like 'ARTICLE_MANAGER_XREF_%' order by configuration_value");
    while ($db = tep_db_fetch_array($db_query)) {
        switch ($db['configuration_key']) {
            case "ARTICLE_MANAGER_XREF_ACCOUNT": $ids[] = array('id' => $db['configuration_value'], 'text' => TEXT_BOX_ID_XREF_ACCOUNT); break;
            case "ARTICLE_MANAGER_XREF_ARTICLES": $ids[] = array('id' => $db['configuration_value'], 'text' => TEXT_BOX_ID_XREF_ARTICLES); break;
            case "ARTICLE_MANAGER_XREF_CONTACT_US": $ids[] = array('id' => $db['configuration_value'], 'text' => TEXT_BOX_ID_XREF_CONTACT_US); break;
            case "ARTICLE_MANAGER_XREF_INFOBOX": $ids[] = array('id' => $db['configuration_value'], 'text' => TEXT_BOX_ID_XREF_INFOBOX); break;
            case "ARTICLE_MANAGER_XREF_LINKS": $ids[] = array('id' => $db['configuration_value'], 'text' => TEXT_BOX_ID_XREF_LINKS); break;
            case "ARTICLE_MANAGER_XREF_TEXT": $ids[] = array('id' => $db['configuration_value'], 'text' => TEXT_BOX_ID_XREF_TEXT); break;
        }
    }
    $ids[] = array('id' => tep_db_num_rows($db_query) + 1, 'text' => TEXT_BOX_ID_XREF_OTHER); 

    return $ids;
} 

function GetCurrentTopic(&$tPath_array) {
    
    $tPath = ($_GET['tPath'] ?? '');
    
    if (tep_not_null($tPath)) {
        $tPath_array = tep_parse_topic_path($tPath);
        $tPath = implode('_', $tPath_array);
        return $tPath_array[(sizeof($tPath_array)-1)];
    }
    return 0;
}

/************************************************************
Callback to ensure all parts of an array are integers
************************************************************/  
function StringToInt($string) {
  return (int)$string;
}

function tep_get_topic_name($topic_id, $language_id) {
  $topic_query = tep_db_query("select topics_name from topics_description where topics_id = '" . (int)$topic_id . "' and language_id = '" . (int)$language_id . "'");
  $topic = tep_db_fetch_array($topic_query);

  return $topic['topics_name'];
}

function tep_get_topic_tree($parent_id = '0', $spacing = '', $exclude = '', $topic_tree_array = '', $include_itself = false) {
  global $languages_id;

  if (!is_array($topic_tree_array)) $topic_tree_array = array();
  if ( (sizeof($topic_tree_array) < 1) && ($exclude != '0') ) $topic_tree_array[] = array('id' => '0', 'text' => TEXT_TOP);

  if ($include_itself) {
    $topic_query = tep_db_query("select cd.topics_name from topics_description cd where cd.language_id = '" . (int)$languages_id . "' and cd.topics_id = '" . (int)$parent_id . "'");
    $topic = tep_db_fetch_array($topic_query);
    $topic_tree_array[] = array('id' => $parent_id, 'text' => $topic['topics_name']);
  }

  $topics_query = tep_db_query("select c.topics_id, cd.topics_name, c.parent_id from topics c, topics_description cd where c.topics_id = cd.topics_id and cd.language_id = '" . (int)$languages_id . "' and c.parent_id = '" . (int)$parent_id . "' order by c.sort_order, cd.topics_name");
  while ($topics = tep_db_fetch_array($topics_query)) {
    if ($exclude != $topics['topics_id']) $topic_tree_array[] = array('id' => $topics['topics_id'], 'text' => $spacing . $topics['topics_name']);
    $topic_tree_array = tep_get_topic_tree($topics['topics_id'], $spacing . '&nbsp;&nbsp;&nbsp;', $exclude, $topic_tree_array);
  }

  return $topic_tree_array;
}

function tep_generate_topic_path($id, $from = 'topic', $topics_array = '', $index = 0) {
  global $languages_id;

  if (!is_array($topics_array)) $topics_array = array();

  if ($from == 'article') {
    $topics_query = tep_db_query("select topics_id from articles_to_topics where articles_id = '" . (int)$id . "'");
    while ($topics = tep_db_fetch_array($topics_query)) {
      if ($topics['topics_id'] == '0') {
        $topics_array[$index][] = array('id' => '0', 'text' => TEXT_TOP);
      } else {
        $topic_query = tep_db_query("select cd.topics_name, c.parent_id from topics c, topics_description cd where c.topics_id = '" . (int)$topics['topics_id'] . "' and c.topics_id = cd.topics_id and cd.language_id = '" . (int)$languages_id . "'");
        $topic = tep_db_fetch_array($topic_query);
        $topics_array[$index][] = array('id' => $topics['topics_id'], 'text' => $topic['topics_name']);
        if ( (tep_not_null($topic['parent_id'])) && ($topic['parent_id'] != '0') ) $topics_array = tep_generate_topic_path($topic['parent_id'], 'topic', $topics_array, $index);
        $topics_array[$index] = array_reverse($topics_array[$index]);
      }
      $index++;
    }
  } elseif ($from == 'topic') {
    $topic_query = tep_db_query("select cd.topics_name, c.parent_id from topics c, topics_description cd where c.topics_id = '" . (int)$id . "' and c.topics_id = cd.topics_id and cd.language_id = '" . (int)$languages_id . "'");
    $topic = tep_db_fetch_array($topic_query);
    $topics_array[$index][] = array('id' => $id, 'text' => $topic['topics_name']);
    if ( (tep_not_null($topic['parent_id'])) && ($topic['parent_id'] != '0') ) $topics_array = tep_generate_topic_path($topic['parent_id'], 'topic', $topics_array, $index);
  }

  return $topics_array;
}

function tep_output_generated_topic_path($id, $from = 'topic') {
  $calculated_topic_path_string = '';
  $calculated_topic_path = tep_generate_topic_path($id, $from);
  for ($i=0, $n=sizeof($calculated_topic_path); $i<$n; $i++) {
    for ($j=0, $k=sizeof($calculated_topic_path[$i]); $j<$k; $j++) {
      $calculated_topic_path_string .= $calculated_topic_path[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
    }
    $calculated_topic_path_string = substr($calculated_topic_path_string, 0, -16) . '<br>';
  }
  $calculated_topic_path_string = substr($calculated_topic_path_string, 0, -4);

  if (strlen($calculated_topic_path_string) < 1) $calculated_topic_path_string = TEXT_TOP;

  return $calculated_topic_path_string;
}

////
// Generate a path to topics
function tep_get_topic_path($current_topic_id = '') {
  global $tPath_array;

  if (tep_not_null($current_topic_id)) {
    $cp_size = (tep_not_null($tPath_array) ? sizeof($tPath_array) : 0);
    if ($cp_size == 0) {
      $tPath_new = $current_topic_id;
    } else {
      $tPath_new = '';
      $last_topic_query = tep_db_query("select parent_id from topics where topics_id = '" . (int)$tPath_array[($cp_size-1)] . "'");
      $last_topic = tep_db_fetch_array($last_topic_query);

      $current_topic_query = tep_db_query("select parent_id from topics where topics_id = '" . (int)$current_topic_id . "'");
      $current_topic = tep_db_fetch_array($current_topic_query);

      if ($last_topic['parent_id'] == $current_topic['parent_id']) {
        for ($i=0; $i<($cp_size-1); $i++) {
          $tPath_new .= '_' . $tPath_array[$i];
        }
      } else {
        for ($i=0; $i<$cp_size; $i++) {
          $tPath_new .= '_' . $tPath_array[$i];
        }
      }
      $tPath_new .= '_' . $current_topic_id;

      if (substr($tPath_new, 0, 1) == '_') {
        $tPath_new = substr($tPath_new, 1);
      }
    }
  } else {
    $tPath_new = implode('_', $tPath_array);
  }

  return 'tPath=' . $tPath_new;
}

function tep_get_generated_topic_path_ids($id, $from = 'topic') {
  $calculated_topic_path_string = '';
  $calculated_topic_path = tep_generate_topic_path($id, $from);
  for ($i=0, $n=sizeof($calculated_topic_path); $i<$n; $i++) {
    for ($j=0, $k=sizeof($calculated_topic_path[$i]); $j<$k; $j++) {
      $calculated_topic_path_string .= $calculated_topic_path[$i][$j]['id'] . '_';
    }
    $calculated_topic_path_string = substr($calculated_topic_path_string, 0, -1) . '<br>';
  }
  $calculated_topic_path_string = substr($calculated_topic_path_string, 0, -4);

  if (strlen($calculated_topic_path_string) < 1) $calculated_topic_path_string = TEXT_TOP;

  return $calculated_topic_path_string;
}

////
// Return the authors URL in the needed language
// TABLES: authors_info
function tep_get_author_url($author_id, $language_id) {
  $author_query = tep_db_query("select authors_url from authors_info where authors_id = '" . (int)$author_id . "' and languages_id = '" . (int)$language_id . "'");
  $author = tep_db_fetch_array($author_query);

  return $author['authors_url'];
}

////
// Return the authors description in the needed language
// TABLES: authors_info
function tep_get_author_description($author_id, $language_id) {
  $author_query = tep_db_query("select authors_description from authors_info where authors_id = '" . (int)$author_id . "' and languages_id = '" . (int)$language_id . "'");
  $author = tep_db_fetch_array($author_query);

  return $author['authors_description'];
}

function tep_get_authors_image($authors_id, $language_id) {
  $authors_query = tep_db_query("select authors_image from authors_info where authors_id = '" . (int)$authors_id . "' and languages_id = '" . (int)$language_id . "'");
  $authors = tep_db_fetch_array($authors_query);

  return $article['articles_image'];
}

////
// Sets the status of an article
function tep_set_article_status($articles_id, $status) {
  if ($status == '1') {
    return tep_db_query("update articles set articles_status = '1', articles_last_modified = now() where articles_id = '" . (int)$articles_id . "'");
  } elseif ($status == '0') {
    return tep_db_query("update articles set articles_status = '0', articles_last_modified = now() where articles_id = '" . (int)$articles_id . "'");
  } else {
    return -1;
  }
}

////
// Sets the blog status of an article
function tep_set_article_blog_status($articles_id, $status) {
  if ($status == '1') {
    return tep_db_query("update articles set articles_is_blog = '1', articles_last_modified = now() where articles_id = '" . (int)$articles_id . "'");
  } elseif ($status == '0') {
    return tep_db_query("update articles set articles_is_blog = '0', articles_last_modified = now() where articles_id = '" . (int)$articles_id . "'");
  } else {
    return -1;
  }
}

function tep_get_articles_name($article_id, $language_id = 0) {
  global $languages_id;

  if ($language_id == 0) $language_id = $languages_id;
  $article_query = tep_db_query("select articles_name from articles_description where articles_id = '" . (int)$article_id . "' and language_id = '" . (int)$language_id . "'");
  $article = tep_db_fetch_array($article_query);

  return $article['articles_name'];
}

function tep_get_articles_head_title_tag($article_id, $language_id = 0) {
  global $languages_id;

  if ($language_id == 0) $language_id = $languages_id;
  $article_query = tep_db_query("select articles_head_title_tag from articles_description where articles_id = '" . (int)$article_id . "' and language_id = '" . (int)$language_id . "'");
  $article = tep_db_fetch_array($article_query);

  return $article['articles_head_title_tag'];
}

function tep_get_articles_description($article_id, $language_id) {
  $article_query = tep_db_query("select articles_description from articles_description where articles_id = '" . (int)$article_id . "' and language_id = '" . (int)$language_id . "'");
  $article = tep_db_fetch_array($article_query);

  return $article['articles_description'];
}

function tep_get_articles_head_keywords_tag($article_id, $language_id) {
  $article_query = tep_db_query("select articles_head_keywords_tag from articles_description where articles_id = '" . (int)$article_id . "' and language_id = '" . (int)$language_id . "'");
  $article = tep_db_fetch_array($article_query);

  return $article['articles_head_keywords_tag'];
}

function tep_get_articles_image($article_id, $language_id) {
  $article_query = tep_db_query("select articles_image from articles_description where articles_id = '" . (int)$article_id . "' and language_id = '" . (int)$language_id . "'");
  $article = tep_db_fetch_array($article_query);

  return $article['articles_image'];
}

function tep_get_articles_url($article_id, $language_id) {
  $article_query = tep_db_query("select articles_url from articles_description where articles_id = '" . (int)$article_id . "' and language_id = '" . (int)$language_id . "'");
  $article = tep_db_fetch_array($article_query);

  return $article['articles_url'];
}

function tep_remove_topic($topic_id) {
  $topic_image_query = tep_db_query("select topics_image from topics where topics_id = '" . (int)$topic_id . "'");
  $topic_image = tep_db_fetch_array($topic_image_query);

  $duplicate_image_query = tep_db_query("select count(*) as total from topics where topics_image = '" . tep_db_input($topic_image['topics_image']) . "'");
  $duplicate_image = tep_db_fetch_array($duplicate_image_query);

  if ($duplicate_image['total'] < 2) {
    if (file_exists(DIR_FS_CATALOG_IMAGES . $topic_image['topics_image'])) {
      @unlink(DIR_FS_CATALOG_IMAGES . $topic_image['topics_image']);
    }
  }

  tep_db_query("delete from topics where topics_id = '" . (int)$topic_id . "'");
  tep_db_query("delete from topics_description where topics_id = '" . (int)$topic_id . "'");
  tep_db_query("delete from articles_to_topics where topics_id = '" . (int)$topic_id . "'");

}

function tep_remove_article($article_id) {
  $languages = tep_get_languages();

  for ($i = 0; $i < count($languages); ++$i) {
      $articleImage = tep_get_articles_image($article_id, $languages[$i]['id']);

      if (tep_not_null($articleImage)) {
          if (file_exists(DIR_FS_CATALOG_IMAGES .'article_manager_uploads/' . $articleImage)) {
              @unlink(DIR_FS_CATALOG_IMAGES .'article_manager_uploads/' . $articleImage);
          }
      }
  }

  tep_db_query("delete from articles where articles_id = '" . (int)$article_id . "'");
  tep_db_query("delete from articles_to_topics where articles_id = '" . (int)$article_id . "'");
  tep_db_query("delete from articles_description where articles_id = '" . (int)$article_id . "'");

  $article_reviews_query = tep_db_query("select reviews_id from article_reviews where articles_id = '" . (int)$article_id . "'");
  while ($article_reviews = tep_db_fetch_array($article_reviews_query)) {
    tep_db_query("delete from article_reviews_description where reviews_id = '" . (int)$article_reviews['reviews_id'] . "'");
  }
  tep_db_query("delete from article_reviews where articles_id = '" . (int)$article_id . "'");

}

// Topics Description contribution
function tep_get_topic_heading_title($topic_id, $language_id) {
  $topic_query = tep_db_query("select topics_heading_title from topics_description where topics_id = '" . $topic_id . "' and language_id = '" . $language_id . "'");
  $topic = tep_db_fetch_array($topic_query);
  return $topic['topics_heading_title'];
}

function tep_get_topic_description($topic_id, $language_id) {
  $topic_query = tep_db_query("select topics_description from topics_description where topics_id = '" . $topic_id . "' and language_id = '" . $language_id . "'");
  $topic = tep_db_fetch_array($topic_query);
  return $topic['topics_description'];
}
