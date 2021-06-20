<?php
/*
  $Id: articles.php, v1.0 2003/12/04 12:00:00 ra Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2009 http://www.oscommerce-solution.com

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  require_once('includes/functions/articles.php');

function createResizedImages($fName) {

$fSrc = '../images/article_manager_uploads/'.$fName;
if(file_exists($fSrc)) {
  $imagick = new Imagick();
  
  $imagick->readImage($fSrc); 
  $W = $imagick->getImageWidth();
  $H = $imagick->getImageHeight();
  //thumb
  $imagick->thumbnailImage($W, IMG_THUMB_HEIGHT, true); 
  $imagick->writeImage($fSrc.'-thumb.jpeg'); 
  //prev
  $imagick->readImage($fSrc); 
  $imagick->thumbnailImage(IMG_PREVIEW_WIDTH, $H, true); 
  $imagick->writeImage($fSrc.'-prev.jpeg'); 
  //ev.obrizka velkeho:
  if($W > IMG_BIG_WIDTH) {
    $imagick->readImage($fSrc); 
    $imagick->thumbnailImage(IMG_BIG_WIDTH, $H, true); 
    $imagick->writeImage($fSrc); 
  }
  
  unset($imagick);
  
} else return false;

return true;
} //------------------------------------------------------------------------------------
  
  
if(isset($_GET['aID'])) {
  $aiQ = tep_db_query("SELECT * FROM articles_images WHERE articles_id = ".$_GET['aID']);
  $aiCounter = tep_db_num_rows($aiQ);
} else $aiCounter = 0;

$langs = [['id'=>0, 'text'=>'Pro jazyk(y): VŠECHNY']]; 
foreach(tep_get_languages() as $lang) $langs[] = ['id'=>$lang['id'], 'text'=>$lang['name']]; 
  
  
  //goto the authors page since at least one author is required
  $authors_query = tep_db_query("select count(*) as total from authors" );
  $authors = tep_db_fetch_array($authors_query);
  $box_ids_list = GetBoxIDs();
  $tPath_array = (isset($tPath_array) ?? []);
  $current_topic_id = GetCurrentTopic($tPath_array);
  $topics_id = 0;
  
  if ($authors['total'] < 1)
    tep_redirect(tep_href_link('authors.php', 'no_authors=true'));

  /********************** BEGIN VERSION CHECKER *********************/
  if (file_exists('includes/functions/version_checker.php')) {
     require_once('includes/languages/' . $language . '/version_checker.php');
     require_once('includes/functions/version_checker.php');
     $contribPath = 'http://addons.oscommerce.com/info/1709';
     $currentVersion = 'Articles Manager V 1.57_10';
     $contribName = 'Articles Manager V';
     $versionStatus = '';
  }
  /********************** END VERSION CHECKER *********************/

  $action = ($_REQUEST['action'] ?? '');
  
 
     /********************** CHECK THE VERSION ***********************/
  if (isset($_POST['action']) && $_POST['action'] == 'getversion') {
      if (isset($_POST['version_check']) && $_POST['version_check'] == 'on')
          $versionStatus = AnnounceVersion($contribPath, $currentVersion, $contribName);
  }

  else if (tep_not_null($action)) {
    switch ($action) {
      case 'setflag':
          if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
              if (isset($_GET['aID'])) {
                  tep_set_article_status($_GET['aID'], $_GET['flag']);
              }
          }
          tep_redirect(tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&aID=' . $_GET['aID']));
      break;
      
      case 'setflagblog':
          if ( ($_GET['flagblog'] == '0') || ($_GET['flagblog'] == '1') ) {
              if (isset($_GET['aID'])) {
                  tep_set_article_blog_status($_GET['aID'], $_GET['flagblog']);
              }
          }
          tep_redirect(tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&aID=' . $_GET['aID']));
      break;
      
      case 'insert_topic':
      case 'update_topic':
          //$topics_id = ($_REQUEST['tID'] ?? 0); //DEBILE!!!
          $topics_id = (int)$_GET['tID'];
          $sort_order = tep_db_prepare_input($_POST['sort_order']);
          $sql_data_array = ['sort_order' => (int)$sort_order];

          $t = new upload('topics_image');
          $t->set_destination(DIR_FS_CATALOG . 'images/article_manager_uploads/'); 
          
          $t->set_filename($_FILES[$t->file]['name']);
          $t->set_tmp_filename($_FILES[$t->file]['tmp_name']);

          $t->parse();
          $t->save();
          
          if(true /*$t->parse() && $t->save()*/) {
            $topicsImg = tep_db_prepare_input($t->filename);
          }
          if (! isset($topicsImg) || ! tep_not_null($topicsImg)) {
            $db_query = tep_db_query("select topics_image from topics where topics_id = " . (int)$topics_id);
          if (tep_db_num_rows($db_query) > 0) {
            $db = tep_db_fetch_array($db_query);
            $topicsImg = $db['topics_image'];
          }
          }
          
          
          if ($action == 'insert_topic') {
              $insert_sql_data = ['parent_id' => $current_topic_id, 'date_added' => 'now()', 'topics_image' => $topicsImg];
              $sql_data_array = array_merge($sql_data_array, $insert_sql_data);              
              tep_db_perform('topics', $sql_data_array);
              $topics_id = tep_db_insert_id();
          } elseif ($action == 'update_topic') {
              $update_sql_data = ['last_modified' => 'now()', 'topics_image' => $topicsImg];
              $sql_data_array = array_merge($sql_data_array, $update_sql_data);
              tep_db_perform('topics', $sql_data_array, 'update', "topics_id = '" . (int)$topics_id . "'");
          }

          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
              $language_id = $languages[$i]['id'];

              $sql_data_array = ['topics_name' => tep_db_prepare_input($_POST['topics_name'][$language_id]),
                                 'topics_heading_title' => tep_db_prepare_input($_POST['topics_heading_title'][$language_id]),
                                 'topics_description' => tep_db_prepare_input($_POST['topics_description'][$language_id])];

              if ($action == 'insert_topic') {
                  $insert_sql_data = ['topics_id' => $topics_id, 'language_id' => $languages[$i]['id']];
                  $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
                  tep_db_perform('topics_description', $sql_data_array);
              } elseif ($action == 'update_topic') {
                  tep_db_perform('topics_description', $sql_data_array, 'update', "topics_id = '" . (int)$topics_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
              }

              /***************** ADD AS PSEDUO PAGE FOR HEADER TAGS SEO ****************/
              if (ENABLE_HEADER_TAGS_SEO == 'True') {
                  $pseudoPage = sprintf("articles.php?tPath=%d", $topics_id);
                  
                  $hstTitle = ($_POST['articles_hts_title'][$language_id] ?? $_POST['topics_heading_title']);
                  $hstDesc = ($_POST['articles_hts_desc'][$language_id] ?? $_POST['topics_description']);
                  $hstKwords = ($_POST['articles_hts_kwords'][$language_id] ?? $_POST['topics_heading_title']);
                  
                  $htsTitle = tep_db_prepare_input($htsTitle);
                  $htsDesc = tep_db_prepare_input($htsDesc);
                  $htsKwords = tep_db_prepare_input($htsKwords);
                  
                  require_once('includes/functions/header_tags.php');
                  require('includes/modules/pseudo_handler.php');
              }
          }

          tep_redirect(tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&tID=' . $topics_id));
      break;
      
      case 'delete_topic_confirm':
      
          if (isset($_POST['topics_id'])) {
              $topics_id = tep_db_prepare_input($_POST['topics_id']);
              $topics = tep_get_topic_tree($topics_id, '', '0', '', true);
              $articles = [];
              $articles_delete = [];

              for ($i=0, $n=sizeof($topics); $i<$n; $i++) {
                  $article_ids_query = tep_db_query("select articles_id from articles_to_topics where topics_id = '" . (int)$topics[$i]['id'] . "'");

                  while ($article_ids = tep_db_fetch_array($article_ids_query)) {
                      $articles[$article_ids['articles_id']]['topics'][] = $topics[$i]['id'];
                  }
              }

              foreach ($articles as $key => $value) { 
                  $topic_ids = '';

                  for ($i=0, $n=sizeof($value['topics']); $i<$n; $i++) {
                      $topic_ids .= "'" . (int)$value['topics'][$i] . "', ";
                  }
                  $topic_ids = substr($topic_ids, 0, -2);

                  $check_query = tep_db_query("select count(*) as total from articles_to_topics where articles_id = '" . (int)$key . "' and topics_id not in (" . $topic_ids . ")");
                  $check = tep_db_fetch_array($check_query);
                  if ($check['total'] < '1') {
                      $articles_delete[$key] = $key;
                  }
              }

              // removing topics can be a lengthy process
              tep_set_time_limit(0);
              for ($i=0, $n=sizeof($topics); $i<$n; $i++) {
                  tep_remove_topic($topics[$i]['id']);
              }

              foreach ($articles_delete as $key) { 
                  tep_remove_article($key);
              }
          }

          tep_redirect(tep_href_link('articles.php', 'tPath=' . $_GET['tPath']));
      break;
      
      case 'delete_article_confirm':
          if (isset($_POST['articles_id']) && isset($_POST['article_topics']) && is_array($_POST['article_topics'])) {
              $article_id = tep_db_prepare_input($_POST['articles_id']);
              $article_topics = $_POST['article_topics'];

              for ($i=0, $n=sizeof($article_topics); $i<$n; $i++) {
                  tep_db_query("delete from articles_to_topics where articles_id = '" . (int)$article_id . "' and topics_id = '" . (int)$article_topics[$i] . "'");
              }

              $article_topics_query = tep_db_query("select count(*) as total from articles_to_topics where articles_id = '" . (int)$article_id . "'");
              $article_topics = tep_db_fetch_array($article_topics_query);

              if ($article_topics['total'] == '0') {
                  tep_remove_article($article_id);
                  tep_db_query("DELETE FROM articles_images WHERE articles_id = ".$article_id);
              }
          }

          tep_redirect(tep_href_link('articles.php', 'tPath=' . $_GET['tPath']));
      break;
      
      case 'move_topic_confirm':
        if (isset($_POST['topics_id']) && ($_POST['topics_id'] != $_POST['move_to_topic_id'])) {
          $topics_id = tep_db_prepare_input($_POST['topics_id']);
          $new_parent_id = tep_db_prepare_input($_POST['move_to_topic_id']);

          $path = explode('_', tep_get_generated_topic_path_ids($new_parent_id));

          if (in_array($topics_id, $path)) {
            $messageStack->add_session(ERROR_CANNOT_MOVE_TOPIC_TO_PARENT, 'error');

            tep_redirect(tep_href_link('articles.php', 'tPath=' . $tPath . '&tID=' . $topics_id));
          } else {
            tep_db_query("update topics set parent_id = '" . (int)$new_parent_id . "', last_modified = now() where topics_id = '" . (int)$topics_id . "'");

            tep_redirect(tep_href_link('articles.php', 'tPath=' . $new_parent_id . '&tID=' . $topics_id));
          }
        }

        break;
      case 'move_article_confirm':
        $articles_id = tep_db_prepare_input($_POST['articles_id']);
        $new_parent_id = tep_db_prepare_input($_POST['move_to_topic_id']);
 
        $duplicate_check_query = tep_db_query("select count(*) as total from articles_to_topics where articles_id = '" . (int)$articles_id . "' and topics_id = '" . (int)$new_parent_id . "'");
        $duplicate_check = tep_db_fetch_array($duplicate_check_query);
        if ($duplicate_check['total'] < 1) tep_db_query("update articles_to_topics set topics_id = '" . (int)$new_parent_id . "' where articles_id = '" . (int)$articles_id . "' and topics_id = '" . (int)$_GET['tPath'] . "'");

        tep_redirect(tep_href_link('articles.php', 'tPath=' . $new_parent_id . '&aID=' . $articles_id));
        break;
        
      case 'insert_article':
      case 'update_article':

          $error = false;      
          $languages = tep_get_languages();
          for ($i = 0, $n=sizeof($languages); $i<$n; $i++) {
              $language_id = $languages[$i]['id'];
              /*
              if (ISSET($_POST['articles_name'][$language_id]) && ! tep_not_null($_POST['articles_name'][$language_id])) {
                  $messageStack->add(ERROR_INVLAID_NAME, 'error');
                  $error = true;
                  break;
              }
              */
          }
          //DirtyHack: $_POST['box_id'] set to 2=articles
          $_POST['box_id'] =2;
          if (isset($_POST['box_id']) && $_POST['box_id'] < 1) {
              $messageStack->add(ERROR_INVLAID_BOX_ID, 'error');
              $error = true;
          } 
          
          if ($error) break;
        
          if (isset($_POST['edit_x']) || isset($_POST['edit_y'])) {
              $action = 'new_article';
          } else {
              if (isset($_GET['aID'])) {
                  $articles_id = tep_db_prepare_input($_GET['aID']);
              } 
    
              $articles_date_available = date('Y-m-d');
              if (isset($_POST['articles_date_available'])) {
                  $articles_date_available = tep_db_prepare_input($_POST['articles_date_available']);
                  if ($articles_date_available < date("Y-m-d", strtotime( '-1 days' ) )) {  
                      $articles_date_available = date("Y-m-d", strtotime( '-1 days' ) );
                  }
              }

              $sql_data_array = ['articles_date_available' => $articles_date_available,
                                 'articles_status' => tep_db_prepare_input($_POST['articles_status']),
                                 'articles_is_blog' => tep_db_prepare_input($_POST['articles_is_blog']),
                                 'authors_id' => tep_db_prepare_input($_POST['authors_id']),
                                 'articles_sort_order' => tep_db_prepare_input($_POST['sort_order']),
                                 'box_id' => '2'];
//orig                                 'box_id' => tep_db_prepare_input($_POST['box_id'])];


              if ($action == 'insert_article') {
                  // If expected article then articles_date _added becomes articles_date_available
                  if (isset($_POST['articles_date_available']) && tep_not_null($_POST['articles_date_available'])) {
                      $insert_sql_data = ['articles_date_added' => tep_db_prepare_input($_POST['articles_date_available'])];
                  } else {
                      $insert_sql_data = ['articles_date_added' => 'now()'];
                  }
                  
                  $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
                  tep_db_perform('articles', $sql_data_array);
                  $articles_id = tep_db_insert_id();
                  tep_db_query("insert into articles_to_topics (articles_id, topics_id) values ('" . (int)$articles_id . "', '" . (int)$current_topic_id . "')");
              } elseif ($action == 'update_article') {
                  $update_sql_data = ['articles_last_modified' => 'now()'];
                  // If expected article then articles_date _added becomes articles_date_available
                  if (isset($_POST['articles_date_available']) && tep_not_null($_POST['articles_date_available'])) {
                    $update_sql_data = ['articles_date_added' => tep_db_prepare_input($_POST['articles_date_available'])];
                  }

                  $sql_data_array = array_merge($sql_data_array, $update_sql_data);

                  tep_db_perform('articles', $sql_data_array, 'update', "articles_id = '" . (int)$articles_id . "'");
              }
 
              for ($i = 0, $n = sizeof($languages); $i < $n; ++$i) {
                  $language_id = $languages[$i]['id'];

                  $t = new upload('articles_image_' . $language_id);
                  $t->set_destination(DIR_FS_CATALOG . 'images/article_manager_uploads/');

                  $t->set_filename($_FILES[$t->file]['name']);
                  $t->set_tmp_filename($_FILES[$t->file]['tmp_name']);

                  $t->parse();
                  $t->save();
 
                  if(true /*$t->parse() && $t->save()*/) {
                      $articlesImg = tep_db_prepare_input($t->filename);
                  }
                  if (! isset($articlesImg) || ! tep_not_null($articlesImg)) {
                      $db_query = tep_db_query("select articles_image from articles_description where articles_id = " . (int)$articles_id . " and language_id = " . (int)$language_id);
                      if (tep_db_num_rows($db_query) > 0) {
                          $db = tep_db_fetch_array($db_query);
                          $articlesImg = $db['articles_image'];
                      }
                  }

                  $sql_data_array = ['articles_name' => tep_db_prepare_input($_POST['articles_name'][$language_id]),
                                          'articles_description' => tep_db_prepare_input($_POST['articles_description'][$language_id]),
                                          'articles_image' => (isset($articlesImg) ? tep_db_prepare_input($articlesImg) : ''),
                                          'articles_url' => tep_db_prepare_input($_POST['articles_url'][$language_id])];

                  if ($action == 'insert_article') {
                      $insert_sql_data = ['articles_id' => $articles_id, 'language_id' => (int)$language_id];
                      $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
                      tep_db_perform('articles_description', $sql_data_array);
                  } elseif ($action == 'update_article') {
                  
                      $dlQ = tep_db_query("SELECT * FROM articles_description WHERE articles_id=".(int)$articles_id." AND language_id=".(int)$language_id);
                      
                      if(tep_db_num_rows($dlQ) > 0) tep_db_perform('articles_description', $sql_data_array, 'update', "articles_id = '" . (int)$articles_id . "' AND language_id = '" . (int)$language_id . "'");
                      else { 
                        $insert_sql_data = ['articles_id' => $articles_id, 'language_id' => (int)$language_id];
                        $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
                        tep_db_perform('articles_description', $sql_data_array);
                      }
                  }


                  
        $ai_sort_order = 0;
        $aiArray = [0];

        foreach ($_FILES as $key => $value) {
// Update existing large product images
          if (preg_match('/^articles_image_large_([0-9]+)$/', $key, $matches)) {
            $ai_sort_order++;

            $sql_data_array = ['htmlcontent' => tep_db_prepare_input($_POST['articles_image_htmlcontent_' . $matches[1]]), 
            'language_id' => tep_db_prepare_input($_POST['language_id_' . $matches[1]]), 
            'sort_order' => $ai_sort_order];

            $t = new upload($key);
            $t->set_destination(DIR_FS_CATALOG . 'images/article_manager_uploads/'); // DIR_FS_CATALOG_IMAGES
            if ($t->parse() && $t->save()) {
              $sql_data_array['image'] = tep_db_prepare_input($t->filename);
              createResizedImages($t->filename);
            }

            tep_db_perform('articles_images', $sql_data_array, 'update', "articles_id = '" . (int)$articles_id . "' and id = '" . (int)$matches[1] . "'");

            $aiArray[] = (int)$matches[1];
            
          } elseif (preg_match('/^articles_image_large_new_([0-9]+)$/', $key, $matches)) {
// Insert new large product images

            $sql_data_array = ['articles_id' => (int)$articles_id, 
            'htmlcontent' => tep_db_prepare_input($_POST['articles_image_htmlcontent_new_' . $matches[1]]),
            'language_id' => tep_db_prepare_input($_POST['language_id_new_' . $matches[1]]), 
            ];

            $t = new upload($key);
            $t->set_destination(DIR_FS_CATALOG . 'images/article_manager_uploads/');
            
            $t->set_filename($value['name']);
            $t->set_tmp_filename($value['tmp_name']);
            
            $t->parse();
            $t->save();
            
            if(true /*($dP = $t->parse()) && ($dS = $t->save())*/)  {
              $ai_sort_order++;

              $sql_data_array['image'] = tep_db_prepare_input($t->filename);
              $sql_data_array['sort_order'] = $ai_sort_order;

              tep_db_perform('articles_images', $sql_data_array);

              $aiArray[] = tep_db_insert_id();
              
              createResizedImages($t->filename);
            } 
          }
        }


        
        $article_images_query = tep_db_query("select image, language_id from articles_images where articles_id = '" . (int)$articles_id . "' and id not in (" . implode(',', $aiArray) . ")");
        if (tep_db_num_rows($article_images_query)) {
          while ($article_images = tep_db_fetch_array($article_images_query)) {
            $duplicate_image_query = tep_db_query("select count(*) as total from articles_images where image = '" . tep_db_input($article_images['image']) . "' AND language_id=".(int)$article_images['language_id']);
            $duplicate_image = tep_db_fetch_array($duplicate_image_query);

            if ($duplicate_image['total'] < 2) {
              if (file_exists('images/article_manager_uploads/' . $article_images['image'])) {
                @unlink('images/article_manager_uploads/' . $article_images['image']);
              }
            }
          }

          tep_db_query("delete from articles_images where articles_id = '" . (int)$articles_id . "' and id not in (" . implode(',', $aiArray) . ")");
        }
                  
                  
                  
                  /***************** ADD AS PSEDUO PAGE FOR HEADER TAGS SEO ****************/
                  
                  if (ENABLE_HEADER_TAGS_SEO == 'True') {
                      $fileName = ($_POST['articles_is_blog'] == 1 ? 'article_blog.php' : 'article_info.php');
                      $pseudoPage = sprintf($fileName . "?articles_id=%d", $articles_id);
                      $htsTitle = tep_db_prepare_input($_POST['articles_hts_title'][$language_id]);
                      $htsDesc = tep_db_prepare_input($_POST['articles_hts_desc'][$language_id]);
                      $htsKwords = tep_db_prepare_input($_POST['articles_hts_kwords'][$language_id]);
            
                      require_once('includes/functions/header_tags.php');
                      require('includes/modules/pseudo_handler.php');
                  }
              }   
 
              tep_redirect(tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . 
              (isset($_GET['aID']) ? ('&aID=' . $_GET['aID']) : (($articles_id > 0) ? ('&aID=' . $articles_id) : '')) ));
          }
          break;
        
      case 'copy_to_confirm':
        if (isset($_POST['articles_id']) && isset($_POST['topics_id'])) {
          $articles_id = tep_db_prepare_input($_POST['articles_id']);
          $topics_id = tep_db_prepare_input($_POST['topics_id']);

          if ($_POST['copy_as'] == 'link') {
            if ($topics_id != $current_topic_id) {
              $check_query = tep_db_query("select count(*) as total from articles_to_topics where articles_id = '" . (int)$articles_id . "' and topics_id = '" . (int)$topics_id . "'");
              $check = tep_db_fetch_array($check_query);
              if ($check['total'] < '1') {
                tep_db_query("insert into articles_to_topics (articles_id, topics_id) values ('" . (int)$articles_id . "', '" . (int)$topics_id . "')");
              }
            } else {
              $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_TOPIC, 'error');
            }
          } elseif ($_POST['copy_as'] == 'duplicate') {
            $article_query = tep_db_query("select articles_date_available, authors_id from articles where articles_id = '" . (int)$articles_id . "'");
            $article = tep_db_fetch_array($article_query);
            $sort_order = tep_db_prepare_input($_POST['sort_order']);

           // tep_db_query("insert into articles (articles_date_added, articles_date_available, articles_status, articles_is_blog, articles_sort_order, authors_id, box_id) values (now(), '" . tep_db_input($article['articles_date_available']) . "', '0', '" . (int)$sort_order . "', '" . (int)$article['authors_id'] . "', '" . (int)$box_id . "')");
            
            tep_db_query("insert into articles (articles_date_added, articles_date_available, articles_status, articles_is_blog, articles_sort_order, authors_id, box_id) values (now(), " . (isset($article['articles_date_available']) ? "'" . tep_db_input($article['articles_date_available']) . "'" : 'DEFAULT') . ",'0','" . tep_db_input($article['articles_is_blog']) . "', '" .(int)$sort_order . "', '" . (int)$article['authors_id'] . "', '" . (int)$box_id . "')");            
            
            $dup_articles_id = tep_db_insert_id();

            $description_query = tep_db_query("select language_id, articles_name, articles_description, articles_image, articles_url from articles_description where articles_id = '" . (int)$articles_id . "'");
            while ($description = tep_db_fetch_array($description_query)) {
              tep_db_query("insert into articles_description (articles_id, language_id, articles_name, articles_description, articles_image, articles_url, articles_viewed) values ('" . (int)$dup_articles_id . "', '" . (int)$description['language_id'] . "', '" . tep_db_input($description['articles_name']) . "', '" . tep_db_input($description['articles_description']) . "', '" . tep_db_input($description['articles_image']) . "', '" . tep_db_input($description['articles_url']) . "', '0')");
            }

            tep_db_query("insert into articles_to_topics (articles_id, topics_id) values ('" . (int)$dup_articles_id . "', '" . (int)$topics_id . "')");
            $articles_id = $dup_articles_id;
          }
       }

        tep_redirect(tep_href_link('articles.php', 'tPath=' . $topics_id . '&aID=' . $articles_id));
        break;
    }
  }

// check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG . 'images')) {
      if (!is_writeable(DIR_FS_CATALOG . 'images')) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else {
      $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
  }
  require('includes/template_top.php');  

?>



  
          <script>
//          $('#aiList').sortable({ containment: 'parent' });

          var aiSize = <?php echo $aiCounter; ?>;

          function addNewAiForm() {
            aiSize++;
            
            $('#aiList').append('<div class="row mb-2" id="aiId' + aiSize + '"><div class="col"><div class="custom-file mb-2"><input type="file" class="form-control-input" id="aImg' + aiSize + '" name="articles_image_large_new_' + aiSize + '"><label class="custom-file-label" for="aImg' + aiSize + '">&nbsp;</label></div></div><div class="col"><?php echo tep_draw_pull_down_menu('language_id_new_', $langs, 0, 'id="hovno"'); ?><textarea name="articles_image_htmlcontent_new_' + aiSize + '" wrap="soft" class="form-control" cols="70" rows="3"></textarea><small class="form-text text-muted"><?php echo TEXT_ARTICLES_LARGE_IMAGE_HTML_CONTENT; ?></small></div><div class="col-1"><i class="fas fa-arrows-alt-v mr-2"></i><a class="aiDel"  data-ai-id="' + aiSize + '"><i class="fas fa-trash text-danger"></i></a></div></div>');
            
            $("#hovno").attr('name', 'language_id_new_'+aiSize);
            $("#hovno").attr('id', 'prdel');

          }
/*
          $('a.aiDel').click(function(e){
            var p = $(this).data('ai-id');
            $('#aiId' + p).effect('blind').remove();

            e.preventDefault();
          });
*/          
          </script>

  
 <script>
$(document).on('change', '#cImg, [id^=aImg]', function (event) { $(this).next('.custom-file-label').html(event.target.files[0].name); });
</script>
 
  
<?php  
  
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

.icon-article-closed {background:white;color:lightgray;}
.icon-article-open {background:yellow;color:green;}
.icon-topic-closed {background:white;color:lightgray;}
.icon-topic-open {background:yellow;color:green;}

table.BorderedBox {width:100%;height:280px;border:3px ridge #ddd; background-color:#eee;}
table.BorderedBoxWhite {border: ridge #ddd 3px; background-color: #eee; }
table.BorderedBoxLight {border: ridge #ddd 3px; background-color: #eee; }
.heading-box {background:#343A40;color:#fff; padding:10px 0;}
tr.Header { background-color: #eee; }
td.HTC_Head {color:#333; font-size:30px;}
.floaters {width:100%;position:fixed;top:80px;left:900px;z-index:100;}
.row-left {width:80px; font-weight:bold}
.row-left-seo {width:120px; font-weight:bold;}
.row-right {width:500px !important;white-space:nowrap; important}
.row-right input[type=text]{width:500px !important}
   
textarea{
 border:1px solid #999999;
 width:100%;
 margin:5px 0;
 padding:3px;
}
.textareacontainer{
 padding-right: 8px; /* 1 + 3 + 3 + 1 */
}
</style>

 <?php $version = '<span style="color:red;font-size:18px;">' . (function_exists('ShowCartInline') ? 'pro version' : 'free version') . '</span>'; ?>

 <div class="row">
    <div class="col mb-12">
      <h1 class="display-4"><?php echo $currentVersion . ' ' . $version; ?></h1>
   
    </div>
    <div class="col col-sm-3 text-right align-self-left">
      <div class="row"><?php echo HEADING_TITLE_AUTHOR_AM; ?></div>
      
        <?php  
        $name = '';
        if (function_exists('AnnounceVersion')) {
            $idParts = explode(' ', $currentVersion);
            foreach ($idParts as $part) {
               if ($part !== 'V') {
                 $name .= $part;
               } else {
                  break;
               }               
            }
            $id = $idParts[count($idParts)-1];
            if (tep_not_null($versionStatus)) { 
               echo '<div class=" text-right align-self-left"><div class="monitor-display" align="right" style="font-weight: bold; color: red;">' . $versionStatus . '</div>';
            } else {
               echo tep_draw_form('version_check', 'view_counter.php', tep_get_all_get_params(), 'post') . tep_draw_hidden_field('action', 'getversion'); 
            ?>
             
             <div class="row">
               <div class="text-right align-self-left">
                 <div class="monitor-display" align="left" style="font-weight: bold; color: red;"><INPUT TYPE="radio" NAME="version_check" onClick="this.form.submit();"><?php echo TEXT_VERSION_CHECK_UPDATES; ?></div>
                 <div class="monitor-display" align="left" style="font-weight: bold; color: red;"><INPUT TYPE="radio" NAME="version_check_unreleased" onClick="window.open('http://www.oscommerce-solution.com/check_unreleased_updates.php?id=<?php echo $id; ?>&name=<?php echo $name; ?>')"><?php echo TEXT_VERSION_CHECK_UPDATES_UNRELEASED; ?></div>
               </div>   
             </div>
             <!--</div>-->
            </form>
            <?php 
            } 
        } else { ?>
           <div class="row monitor-display" align="right" style="font-weight: bold; color: red;"><?php echo TEXT_MISSING_VERSION_CHECKER; ?></div>
        <?php 
        } 
        ?>    
    </div>   

    <div class="row align-items-center"> 
      <div class="col-6 text-left align-self-left d-inline-block"><?php echo HEADING_TITLE_SUPPORT_THREAD_AM; ?></div>     
        <?php
        echo '<form class="form-inline" name="search" action="articles.php" method="get">';
        echo '<label for="search" class="mr-2">' . HEADING_TITLE_SEARCH . '</label>';
        echo  tep_draw_input_field('search', '', 'style="width:300px;"', false) . '';
        echo '</form>';
        
        echo '<form class="form-inline" name="goto" action="articles.php" method="get">';
        echo '<label for="search" class="ml-4 mr-2">' . HEADING_TITLE_GOTO . '</label>';
        echo  tep_draw_pull_down_menu('tPath', tep_get_topic_tree(), $current_topic_id, 'onChange="this.form.submit();"') ;
        echo '</form>';
        ?>  
    </div> 
  </div>
     
     
 <?php
   //----- new_topic / edit_topic  -----
  if (isset($_GET['action']) && ($_GET['action'] == 'new_topic' || $_GET['action'] == 'edit_topic')) {
    if ( isset($_GET['tID']) && (! tep_not_null($_POST)) ) {
        $topics_query = tep_db_query("select t.topics_id, td.topics_name, td.topics_heading_title, td.topics_description, t.topics_image, t.parent_id, t.sort_order, t.date_added, t.last_modified from topics t, topics_description td where t.topics_id = '" . tep_db_input($_GET['tID']) . "' and t.topics_id = td.topics_id and td.language_id = '" . (int)$languages_id . "' order by t.sort_order, td.topics_name");
        $topic = tep_db_fetch_array($topics_query);
        $tInfo = new objectInfo($topic);
    } elseif (tep_not_null($_POST)) {
        $tInfo = new objectInfo($_POST);
        $topics_name = $_POST['topics_name'];
        $topics_heading_title = $_POST['topics_heading_title'];
        $topics_description = $_POST['topics_description'];
        $topics_url = $_POST['topics_url']; 
        $topics_image = $_POST['topics_image'];
    } else {
        $tInfo = new objectInfo(array());
    }

    $languages = tep_get_languages();

    $text_new_or_edit = ($_GET['action']=='new_topic') ? TEXT_INFO_HEADING_NEW_TOPIC : TEXT_INFO_HEADING_EDIT_TOPIC;
?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
  <tr>
     <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo sprintf($text_new_or_edit, tep_output_generated_topic_path($current_topic_id)); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>

      <tr><td>
       <?php 
       $action_type = (isset($_GET['tID']) ? 'update_topic' : 'insert_topic');
       echo tep_draw_form('new_topic', 'articles.php', 'tPath=' . $_GET['tPath'] . '&tID=' . $_GET['tID'] . '&action=' . $action_type, 'post', 'enctype="multipart/form-data"'); 
       ?>
        <!--<td>--><table border="0" cellspacing="0" cellpadding="2">
        <?php
        for ($i=0; $i<sizeof($languages); $i++) {
          if (sizeof($languages) > 1) {
              echo '<tr><td>' . $languages[$i]['name'] . '</td></tr>';
          } 
        ?>
          <tr>
            <td class="smallText"><?php if (true || $i == 0) echo TEXT_EDIT_TOPICS_NAME; ?></td>
            <td class="smallText"><?php echo tep_draw_input_field('topics_name[' . $languages[$i]['id'] . ']', (isset($topics_name[$languages[$i]['id']]) ? stripslashes($topics_name[$languages[$i]['id']]) : (isset($tInfo->topics_id) ? tep_get_topic_name($tInfo->topics_id, $languages[$i]['id']) : ''))); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="smallText"><?php if (true || $i == 0) echo TEXT_EDIT_TOPICS_HEADING_TITLE; ?></td>
            <td class="smallText"><?php echo tep_draw_input_field('topics_heading_title[' . $languages[$i]['id'] . ']', (isset($topics_name[$languages[$i]['id']]) ? stripslashes($topics_name[$languages[$i]['id']]) : (isset($tInfo->topics_id) ? tep_get_topic_heading_title($tInfo->topics_id, $languages[$i]['id']) : ''))); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="smallText" valign="top"><?php if (true || $i == 0) echo TEXT_EDIT_TOPICS_DESCRIPTION; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="smallText">
                  <div class="textareacontainer">
                  <?php
                  
                    if (ARTICLE_ENABLE_HTML_EDITOR == 'No Editor') {
                        echo tep_draw_textarea_field('topics_description[' . $languages[$i]['id'] . ']', 'soft', '700', '10', (isset($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : (isset($tInfo->topics_id) ? tep_get_topic_description($tInfo->topics_id, $languages[$i]['id']) : '')),'class="ckeditor"');
                    } else {
                        if (ARTICLE_ENABLE_HTML_EDITOR == 'FCKEditor') {
                            echo tep_draw_fckeditor('topics_description[' . $languages[$i]['id'] . ']','700','10',(isset($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : tep_get_topic_description($tInfo->topics_id, $languages[$i]['id'])));
                        } else if (ARTICLE_ENABLE_HTML_EDITOR == 'CKEditor') {
                            echo tep_draw_textarea_ckeditor('topics_description[' . $languages[$i]['id'] . ']', 'soft', '700', '10', (($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : (isset($tInfo->topics_id) ? tep_get_topic_description($tInfo->topics_id, $languages[$i]['id']) : '')), 'id = "topics_description[' . $languages[$i]['id'] . ']" class="ckeditor"');
                        } else {
                            echo tep_draw_textarea_field('topics_description[' . $languages[$i]['id'] . ']', 'soft', '700', '10', (($topics_description[$languages[$i]['id']]) ? stripslashes($topics_description[$languages[$i]['id']]) : tep_get_topic_description($tInfo->topics_id, $languages[$i]['id'])));
                        }
                    }
                    
                    
                    
                    
                  ?>
                  </div>
                </td>
              </tr>
            </table></td>
          </tr>
        <?php
            }
        ?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="smallText"><?php echo TEXT_EDIT_SORT_ORDER; ?></td>
            <td class="smallText"><?php echo tep_draw_input_field('sort_order', ($tInfo->sort_order ?? 0), 'size="2"'); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          
          <tr>
            <td class="smallText"><?php echo TEXT_EDIT_TOPICS_IMAGE; ?></td>
            <td class="smallText"><?php echo tep_draw_input_field('topics_image_name', ($tInfo->topics_image ?? ''), 'disabled size="5"') . '&nbsp;&nbsp;' . tep_draw_file_field('topics_image'); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          
          
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="smallText" align="right"><?php echo tep_draw_hidden_field('topics_date_added', (isset($tInfo->date_added) ? $tInfo->date_added : date('Y-m-d'))) . tep_draw_hidden_field('parent_id', ($tInfo->parent_id ?? 0)) . tep_draw_button(IMAGE_AM_SAVE, 'disk', null, 'primary') . '&nbsp;&nbsp;' . 
         tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&tID=' . ($_GET['tID'] ?? $_GET['tPath']))); ?></td>
      </form></tr>
<?php
  } elseif ($action == 'new_article') {
 
    $parameters = array('articles_name' => '',
                       'articles_description' => '',
                       'articles_image' => '',
                       'articles_url' => '',
                       'articles_id' => '',
                       'articles_date_added' => '',
                       'articles_last_modified' => '',
                       'articles_date_available' => '',
                       'articles_status' => '',
                       'articles_is_blog' => '',
                       'articles_sort_order' => 0,
                       'box_id' => 2,
                       'authors_id' => '', 
                       'articles_larger_images' => []
                       );

    $aInfo = new objectInfo($parameters);

    if (isset($_GET['aID']) && empty($_POST)) {
      $article_query = tep_db_query("select ad.articles_name, ad.articles_description, ad.articles_image, ad.articles_url, a.articles_id, a.articles_date_added, a.articles_last_modified, date_format(a.articles_date_available, '%Y-%m-%d') as articles_date_available, a.articles_status, a.articles_is_blog, a.articles_sort_order, a.authors_id, a.box_id from articles a, articles_description ad where a.articles_id = '" . (int)$_GET['aID'] . "' and a.articles_id = ad.articles_id and ad.language_id = '" . (int)$languages_id . "'");
      $article = tep_db_fetch_array($article_query);
         
      $imgs = [];
      $aiQ = tep_db_query("SELECT * FROM articles_images WHERE articles_id = " . (int)$_GET['aID'] . " ORDER BY language_id");
      while($aiA = tep_db_fetch_array($aiQ)) $imgs[] = $aiA;
      $article['articles_larger_images'] = $imgs;
      
      $aInfo->objectInfo($article);
      
    } elseif (tep_not_null($_POST)) {
      $aInfo->objectInfo($_POST);
      $articles_name = $_POST['articles_name'];
      $articles_description = $_POST['articles_description'];

      $articles_image = '';

      $articles_url = $_POST['articles_url'];
      $articles_sort_order = $_POST['sort_order'];
      $box_id = 2;
//orig      $box_id = $_POST['box_id'];
    }

    $authors_array = array(array('id' => '', 'text' => TEXT_NONE));
    $authors_query = tep_db_query("select authors_id, authors_name from authors order by authors_name");
    while ($authors = tep_db_fetch_array($authors_query)) {
      $authors_array[] = ['id' => $authors['authors_id'], 'text' => $authors['authors_name']];
    }

    $languages = tep_get_languages();

    if (!isset($aInfo->articles_status)) $aInfo->articles_status = '1';
    switch ($aInfo->articles_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }
    if (!isset($aInfo->articles_is_blog)) $aInfo->articles_is_blog = '1';
    switch ($aInfo->articles_is_blog) {
      case '0': $blog_in_status = false; $blog_out_status = true; break;
      case '1':
      default: $blog_in_status = true; $blog_out_status = false;
    }
    
    $action_type = (isset($_GET['aID']) ? 'update_article' : 'insert_article');

    echo tep_draw_form('new_article', 'articles.php', 'tPath=' . $current_topic_id . (isset($_GET['aID']) ? '&aID=' . $_GET['aID'] : '') . '&action=' . $action_type, 'post', 'enctype="multipart/form-data" id="form_new_article"'); 
    

    ?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
    
    <tr><td>
      <div class="floaters">
       <?php
        if (isset($_GET['aID'])) {
           echo tep_draw_button(IMAGE_SAVE, 'fas fa-save');
        } else {
           echo tep_draw_button(IMAGE_INSERT, 'fas fa-bug');
        }     

        echo tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $_GET['tPath']));
       ?>
      </div>       
     </td>
    </tr>       
      <tr>
        <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo (isset($_GET['aID']) ? sprintf(TEXT_EDIT_ARTICLE, tep_output_generated_topic_path($current_topic_id)) : sprintf(TEXT_NEW_ARTICLE, tep_output_generated_topic_path($current_topic_id))); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '100%', 15); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td valign="top" width="50%"><table border="0" cellspacing="0" cellpadding="2" class="BorderedBox">
          <tr>
            <td class="smallText row-left"><?php echo TEXT_ARTICLES_STATUS; ?></td>
            <td class="smallText row-right"><?php echo tep_draw_radio_field('articles_status', '0', $out_status) . '&nbsp;' . TEXT_ARTICLE_NOT_AVAILABLE . '&nbsp;' . tep_draw_radio_field('articles_status', '1', $in_status) . '&nbsp;' . TEXT_ARTICLE_AVAILABLE; ?></td>
          </tr>
          <tr>
            <td class="smallText row-left"><?php echo TEXT_ARTICLES_BLOG_STATUS; ?></td>
            <td class="smallText row-right" align=left>
             <INPUT TYPE="radio" NAME="articles_is_blog" VALUE="0" <?php echo ($aInfo->articles_is_blog ? '' : 'checked="checked"'); ?> ><?php echo TEXT_ARTICLE_BLOG_NO; ?>
             <INPUT TYPE="radio" NAME="articles_is_blog" VALUE="1" <?php echo ($aInfo->articles_is_blog ? 'checked="checked"' : ''); ?> ><?php echo TEXT_ARTICLE_BLOG_YES; ?>
            </td>
          </tr>
 
          <tr>
            <td class="smallText row-left"><?php echo TEXT_ARTICLES_DATE_AVAILABLE; ?><br><small>(YYYY-MM-DD)</small></td>
            <td class="main"><?php echo tep_draw_input_field('articles_date_available', $aInfo->articles_date_available, 'id="articles_date_available"'); ?></td>
          </tr> 
 
          <tr>
            <td class="smallText row-left"><?php echo TEXT_EDIT_BOX_ID; ?></td>
            <td class="smallText row-right">
             <?php 
             $chosen = 2;
//             $chosen = $aInfo->box_id;
             $idcnt = count($box_ids_list);
             $lastID = $box_ids_list[($idcnt - 1)]['id'];
             $other_value = 2;
//             $other_value = '';
             
             if ($aInfo->box_id > $lastID) {
                 $other_value = $aInfo->box_id;  //remember the set value for the other selection           
                 $chosen =2; 
//                 $chosen = $idcnt; 
             }
             
             $color = ($aInfo->box_id < $lastID ? '#ddd' : '#fff');
             
/*             
             echo tep_draw_input_field('box_id', $aInfo->box_id, 'id="box-id" size="4" readonly style="background:' . $color . '"') . '&nbsp;'; 
             echo tep_draw_pull_down_menu('box_id_list', $box_ids_list, $chosen, 'id="box-id-list" onChange="ChangeSelectedBoxID(\'' . $idcnt . '\', \'' . $other_value . '\');"'); 
*/             
             
             echo tep_draw_hidden_field('box_id', $aInfo->box_id) . '&nbsp;'; 
             
             //echo tep_draw_pull_down_menu('box_id_list', $box_ids_list, $chosen, 'id="box-id-list" onChange="ChangeSelectedBoxID(\'' . $idcnt . '\', \'' . $other_value . '\');"'); 
             echo tep_draw_hidden_field('box_id_list', $chosen); 

             ?>
            </td>
          </tr> 
                    
          <tr>
            <td class="smallText row-left"><?php echo TEXT_EDIT_SORT_ORDER; ?></td>
            <td class="smallText row-right"><?php echo tep_draw_input_field('sort_order', $aInfo->articles_sort_order, 'size="4"'); ?></td>
          </tr> 
        
          <tr>
            <td class="smallText row-left"><?php echo TEXT_ARTICLES_AUTHOR; ?></td>
            <td class="smallText row-right"><?php echo tep_draw_pull_down_menu('authors_id', $authors_array, $aInfo->authors_id); ?></td>
          </tr>
 
           <?php
           for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
             if ($n > 1) {
           ?>        
              <tr><td width="100%"><strong><?php echo $languages[$i]['name']; ?></strong></td></tr>
           <?php } ?>       
              <tr>
                <td class="smallText row-left"><?php echo TEXT_ARTICLES_NAME; ?></td>
                <td class="smallText row-right"><?php echo tep_draw_input_field('articles_name[' . $languages[$i]['id'] . ']', (isset($articles_name[$languages[$i]['id']]) ? $articles_name[$languages[$i]['id']] : tep_get_articles_name($aInfo->articles_id, $languages[$i]['id'])), 'size="35"'); ?></td>
              </tr>  
              <tr>
                <td class="smallText row-left"><?php echo TEXT_ARTICLES_IMAGE; ?></td>
                <td class="smallText row-right"><?php echo (isset($_GET['aID']) ? tep_draw_input_field('articles_image_name', tep_get_articles_image($aInfo->articles_id, $languages[$i]['id']), 'disabled size="5"') . '&nbsp;&nbsp;' : '') . tep_draw_file_field('articles_image_' . $languages[$i]['id']); ?></td>
              </tr>
           <?php
               }
           ?>  
          </table></td>
          
<script>
function ChangeSelectedBoxID(idcnt, other_selected) {
  var selector = document.getElementById('box-id-list');
  var value = selector[selector.selectedIndex].value;
  $("#box-id").val(value);  
  
  if (value && value < idcnt) {
    $("#box-id").attr("readonly", true); 
    $("#box-id").css("background-color", "#ddd");
  } else {
    $("#box-id").attr("readonly", false); 
    $("#box-id").css("background-color", "white");
    if (other_selected) $("#box-id").val(other_selected); //override the default other value
 }
} 
$('#articles_date_available').datepicker({
  dateFormat: 'yy-mm-dd',
  minDate: -1
});
</script>          
<!--          
          <td valign="top" width="50%"><table border="0" cellspacing="0" cellpadding="2" class="BorderedBox">
          <?php
          $hts_status = (ENABLE_HEADER_TAGS_SEO != 'True' ? 'disabled="disabled" ' : '');
          $hts_show = (ENABLE_HEADER_TAGS_SEO != 'True' ? false : true);
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          ?>
            <tr>
              <td class="smallText row-left-seo"><?php echo TEXT_ARTICLES_HTS_TITLE; ?></td>
              <td class="smallText"><?php echo tep_draw_input_field('articles_hts_title[' . $languages[$i]['id'] . ']', ($hts_show ? GetHeaderTagsTitle($aInfo->articles_id, $languages[$i]['id']) : ''), $hts_status . ' size="35"'); ?></td>
            </tr>
            <tr>
              <td class="smallText row-left-seo"><?php echo TEXT_ARTICLES_HTS_DESC; ?></td>
              <td class="smallText"><?php echo tep_draw_input_field('articles_hts_desc[' . $languages[$i]['id'] . ']', ($hts_show ? GetHeaderTagsDescription($aInfo->articles_id, $languages[$i]['id']) : ''), $hts_status . ' size="35"'); ?></td>
            </tr>
            <tr>
              <td class="smallText row-left-seo"><?php echo TEXT_ARTICLES_HTS_KWORDS; ?></td>
              <td class="smallText"><?php echo tep_draw_input_field('articles_hts_kwords[' . $languages[$i]['id'] . ']', ($hts_show ? GetHeaderTagsKeywords($aInfo->articles_id, $languages[$i]['id']) : ''), $hts_status . ' size="35"'); ?></td>
            </tr>          
         <?php
          }
         ?>
          </table>          
         </td>
-->         
         
        <tr>
          
          <td colspan="2" width="100%"><table border="0" cellspacing="0" cellpadding="2" class="BorderedBox">
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) { //continue;
?>
          <tr>
            <td class="smallText" valign="top"><?php if (TRUE || $i == 0) echo TEXT_ARTICLES_DESCRIPTION . '<br>(' . strtolower($languages[$i]['name']) . ')'; //KTEREJ CURAAAK?!!! PROC JEN JEDNOU??!!  ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="smallText" width="100%">
                  <div class="textareacontainer">
                  <?php
                    if (ARTICLE_ENABLE_HTML_EDITOR == 'No Editor')
                      echo tep_draw_textarea_field('articles_description[' . $languages[$i]['id'] . ']', 'soft', '1000', '10', (isset($articles_description[$languages[$i]['id']]) ? stripslashes($articles_description[$languages[$i]['id']]) : tep_get_articles_description($aInfo->articles_id, $languages[$i]['id'])), 'class="ckeditor"');
                    else
                    {
                      if (ARTICLE_ENABLE_HTML_EDITOR == 'FCKEditor') {
                          echo tep_draw_fckeditor('articles_description[' . $languages[$i]['id'] . ']','1000', '10',(isset($articles_description[$languages[$i]['id']]) ? stripslashes($articles_description[$languages[$i]['id']]) : tep_get_articles_description($aInfo->articles_id, $languages[$i]['id'])));
                      } else if (ARTICLE_ENABLE_HTML_EDITOR == 'CKEditor') {
                          echo tep_draw_textarea_field('articles_description[' . $languages[$i]['id'] . ']', 'soft', '1000', '10', (isset($articles_description[$languages[$i]['id']]) ? stripslashes($articles_description[$languages[$i]['id']]) : tep_get_articles_description($aInfo->articles_id, $languages[$i]['id'])), 'id = "articles_description[' . $languages[$i]['id'] . ']" class="ckeditor"');
                      } else {
                          echo tep_draw_textarea_field('articles_description[' . $languages[$i]['id'] . ']', 'soft', '1000', '10', (isset($articles_description[$languages[$i]['id']]) ? stripslashes($articles_description[$languages[$i]['id']]) : tep_get_articles_description($aInfo->articles_id, $languages[$i]['id'])));
                      }
                    }
                  ?>
                  </div>
                </td>                
              </tr>
            </table></td>
          </tr>
          <tr>
            <td class="smallText"><?php if (true || $i == 0) echo TEXT_ARTICLES_URL; ?></td>
            <td class="smallText"><?php echo tep_draw_input_field('articles_url[' . $languages[$i]['id'] . ']', (isset($articles_url[$languages[$i]['id']]) ? $articles_url[$languages[$i]['id']] : tep_get_articles_url($aInfo->articles_id, $languages[$i]['id'])), 'size="35"'); ?></td>
          </tr>
<?php
    }
?>
        </table></td>
      </tr>
      
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
      
<tr><td colspan="100">   

          <div class="form-group row">
            <div class="col-sm-3 text-left text-sm-right">
              <?php echo TEXT_ARTICLES_OTHER_IMAGES; ?>
              <br><a class="btn btn-info btn-sm text-white mt-2" role="button" id="add_image" onclick="addNewAiForm();return false;"><?php echo TEXT_ARTICLES_ADD_LARGE_IMAGE; ?></a>
            </div>
            <div class="col-sm-9" id="aiList">
              <?php
              $ai_counter = 0; //href="#" 

              //echo var_export($langs, true);
              foreach ($aInfo->articles_larger_images as $ai) {
                $ai_counter++;
                echo '<div class="row mb-2" id="aiId' . $ai_counter . '">';
                  echo '<div class="col">';
                    echo '<div class="custom-file mb-2">';
                      echo tep_draw_input_field('articles_image_large_' . $ai['id'], '', 'id="aImg' . $ai_counter . '"', 'file', null, 'class="form-control-input"');
                      echo '<label class="custom-file-label" for="aImg' . $ai_counter . '">' . $ai['image'] . '</label>';
                    echo '</div>';
                    echo tep_image('../images/article_manager_uploads/'.$ai['image'], $ai['image'], 150, 150);
                  echo '</div>';
                  echo '<div class="col">';
                    echo tep_draw_pull_down_menu('language_id_'.$ai['id'], $langs, $ai['language_id']);
                    echo tep_draw_textarea_field('articles_image_htmlcontent_' . $ai['id'], 'soft', '70', '3', $ai['htmlcontent']);
                    echo '<small class="form-text text-muted">' . TEXT_ARTICLES_LARGE_IMAGE_HTML_CONTENT . '</small>';
                  echo '</div>';
                   echo '<div class="col-1">';
                     echo '<i class="fas fa-arrows-alt-v mr-2"></i>';
                     echo '<a href="#" class="aiDel" data-ai-id="' . $ai_counter . '"><i class="fas fa-trash text-danger"></i></a>';
                  echo '</div>';
                  
                  //echo '<div class="col">';
                    
                  //echo '</div>';
                  
                echo '</div>';
              }
              ?>
            </div>
          </div>


</td>   
</tr>


      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
      
        <td class="smallText" align="right"><?php echo 
          tep_draw_hidden_field('articles_date_added', (tep_not_null($aInfo->articles_date_added) ? $aInfo->articles_date_added : date('Y-m-d'))) . 
          tep_draw_button(IMAGE_SAVE, 'fas fa-save') . '&nbsp;&nbsp;' .
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] .'&aID=' . ($_GET['aID'] ?? 1)));
        ?>
       </td>
      </tr>
      
    </table>  
    </form> 
<?php  
  } else {
?>

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2" class="">
              <tr class="dataTableHeadingRow heading-box">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_TOPICS_ARTICLES; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_BLOG_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_ARTICLE_ID; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_SORT_ORDER; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $topics_count = 0;
    $rows = 0;
    
    if (isset($_GET['search'])) {
        $search = tep_db_prepare_input($_GET['search']);
        $topics_query = tep_db_query("select t.topics_id, td.topics_name, t.parent_id, t.sort_order, t.date_added, t.last_modified from topics t, topics_description td where t.topics_id = td.topics_id and td.language_id = '" . (int)$languages_id . "' and td.topics_name like '%" . tep_db_input($search) . "%' order by t.sort_order, td.topics_name");
    } else {
        $topics_query = tep_db_query("select t.topics_id, td.topics_name, t.parent_id, t.sort_order, t.date_added, t.last_modified from topics t, topics_description td where t.parent_id = '" . (int)$current_topic_id . "' and t.topics_id = td.topics_id and td.language_id = '" . (int)$languages_id . "' order by t.sort_order, td.topics_name");
    }
    while ($topics = tep_db_fetch_array($topics_query)) {
      $topics_count++;
      $rows++;
      
      // Get parent_id for subtopics if search
      if (isset($_GET['search'])) $tPath = $topics['parent_id'];

      if ((!isset($_GET['tID']) && !isset($_GET['aID']) || (isset($_GET['tID']) && ($_GET['tID'] == $topics['topics_id']))) && !isset($tInfo) && (substr($action, 0, 3) != 'new')) {
        $articles_array = ['articles_ttl_cnt' => 0, 'articles_ttl_blog_no' => 0, 'articles_ttl_blog_yes' => 0, 'articles_active_blog_no' => 0, 'articles_active_blog_yes' => 0];
        $topic_articles = GetChildArticles($topics['topics_id'], $articles_array);
        $topic_childs = ['childs_count' => GetChildTopicCount($topics['topics_id'])];

        $tInfo_array = array_merge($topics, $topic_childs, $topic_articles);
        $tInfo = new objectInfo($tInfo_array);
      }

      $show_icon = '<i class="far fa-folder icon-topic-closed"></i>';
      if (isset($tInfo) && is_object($tInfo) && ($topics['topics_id'] == $tInfo->topics_id) ) {
      
//          echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('articles.php', tep_get_topic_path($topics['topics_id'])) . '\'">' . "\n";
          
          echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('articles.php', 
          //'tPath=' . ($_GET['tPath'] ?? '0') . '&tID=' . $topics['topics_id']
          (isset($_GET['search']) ? 'search='.$_GET['search'] :  'tPath=' . ($_GET['tPath'] ?? '0')) . '&tID=' . $topics['topics_id']
          ) . '\'">' . "\n";
          
          
          $show_icon = '<i class="far fa-folder-open icon-topic-open"></i>';
          
      } else {
          echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('articles.php', 
          //'tPath=' . ($_GET['tPath'] ?? '0') . '&tID=' . $topics['topics_id']
          (isset($_GET['search']) ? 'search='.$_GET['search'] :  'tPath=' . ($_GET['tPath'] ?? '0')) . '&tID=' . $topics['topics_id']          
          ) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('articles.php', tep_get_topic_path($topics['topics_id'])) . '">' . $show_icon . '</a>&nbsp;<b>' . $topics['topics_name'] . '</b>'; ?></td>
                <td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="center"><?php echo $topics['sort_order']; ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($tInfo) && is_object($tInfo) && ($topics['topics_id'] == $tInfo->topics_id) ) { echo tep_image('images/icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('articles.php', 
                (isset($_GET['search']) ? 'search='.$_GET['search'] : 'tPath=' . (($tPath > 0) ? $tPath : '0')).'&tID='.$topics['topics_id']
                ) . '">' . tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }

    $articles_count = 0;
    if (isset($_GET['search'])) {
      $articles_query = tep_db_query("select a.articles_id, ad.articles_name, ad.articles_image, a.articles_date_added, a.articles_last_modified, a.articles_date_available, a.articles_status, a.articles_is_blog, a.articles_sort_order, a2t.topics_id from articles a, articles_description ad, articles_to_topics a2t where a.articles_id = ad.articles_id and ad.language_id = '" . (int)$languages_id . "' and a.articles_id = a2t.articles_id and ad.articles_name like '%" . tep_db_input($search) . "%' order by ad.articles_name");
    } else {
      $articles_query = tep_db_query("select a.articles_id, ad.articles_name, ad.articles_image, a.articles_date_added, a.articles_last_modified, a.articles_date_available, a.articles_status, a.articles_is_blog, a.articles_sort_order, a.box_id from articles a left join articles_description ad on a.articles_id = ad.articles_id left join articles_to_topics a2t on a.articles_id = a2t.articles_id where ad.language_id = '" . (int)$languages_id . "' and a2t.topics_id = '" . (int)$current_topic_id . "' order by ad.articles_name");
    }
    
    while ($articles = tep_db_fetch_array($articles_query)) {
      $articles_count++;
      $rows++;

      // Get topics_id for article if search
      if (isset($_GET['search'])) $tPath = $articles['topics_id'];

      if ( (!isset($_GET['aID']) && !isset($_GET['tID']) || (isset($_GET['aID']) && ($_GET['aID'] == $articles['articles_id']))) && !isset($aInfo) && !isset($tInfo) && (substr($action, 0, 3) != 'new')) {
        // find out the rating average from customer reviews
        $reviews_query = tep_db_query("select (avg(reviews_rating) / 5 * 100) as average_rating from article_reviews where articles_id = '" . (int)$articles['articles_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);
        $aInfo_array = array_merge($articles, $reviews);
        $aInfo = new objectInfo($aInfo_array);
      }
 
      $show_article_icon = '<i class="fas fa-search icon-article-closed">';
      if (isset($aInfo) && is_object($aInfo) && ($articles['articles_id'] == $aInfo->articles_id) ) {
        echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('articles.php', 
        //'tPath=' . $_GET['tPath'] . '&aID=' . $articles['articles_id']
        (isset($_GET['search']) ? 'search='.$_GET['search'].'&' : '') . 
        (isset($_GET['tPath']) ? 'tPath=' . $_GET['tPath'].'&' : '') . 'aID=' . $articles['articles_id']) . '\'">' . "\n";
        //) . '\'">' . "\n";
        $show_article_icon = '<i class="fas fa-search icon-article-open">';
      } else {
        echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('articles.php', 
        //'tPath=' . $current_topic_id . '&aID=' . $articles['articles_id']
        (isset($_GET['search']) ? 'search='.$_GET['search'].'&' : '') . 
        (isset($_GET['tPath']) ? 'tPath=' . $_GET['tPath'].'&' : '') . 'aID=' . $articles['articles_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&aID=' . $articles['articles_id']) . '">' . $show_article_icon . '</i></a>&nbsp;' . $articles['articles_name']; ?></td>
                <td class="dataTableContent" align="center">
<?php
      if ($articles['articles_status'] == '1') {
        echo '<a href="' . tep_href_link('articles.php', 'action=setflag&flag=0&aID=' . $articles['articles_id'] . '&tPath=' . $current_topic_id) . '">';
        echo '<i class="fas fa-circle circle-active" title="' . STATUS_CHANGE . '"></i>';
        echo '</a>';
      } else {
        echo '<a href="' . tep_href_link('articles.php', 'action=setflag&flag=1&aID=' . $articles['articles_id'] . '&tPath=' . $current_topic_id) . '">';
        echo '&nbsp;<i class="fas fa-circle circle-inactive" title="' . STATUS_CHANGE . '"></i>';
        echo '</a>';
      }

?></td>
                <td class="dataTableContent" align="center">
<?php
      if ($articles['articles_is_blog'] == '1') {
        echo '<a href="' . tep_href_link('articles.php', 'action=setflagblog&flagblog=0&aID=' . $articles['articles_id'] . '&tPath=' . $current_topic_id) . '">';
        echo '<i class="fas fa-circle circle-active" title="' . STATUS_CHANGE . '"></i>';
        echo '</a>';
      } else {
        echo '<a href="' . tep_href_link('articles.php', 'action=setflagblog&flagblog=1&aID=' . $articles['articles_id'] . '&tPath=' . $current_topic_id) . '">';
        echo '&nbsp;<i class="fas fa-circle circle-inactive" title="' . STATUS_CHANGE . '"></i>';
        echo '</a>';
      }

?></td>
                <td class="dataTableContent" align="center"><?php echo $articles['articles_id']; ?></td>
                <td class="dataTableContent" align="center"><?php echo $articles['articles_sort_order']; ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($aInfo) && is_object($aInfo) && ($articles['articles_id'] == $aInfo->articles_id)) { echo tep_image('images/icon_arrow_right.gif', ''); } else { echo '<a href="' . 
                tep_href_link('articles.php', 
                (isset($_GET['search']) ? 'search='.$_GET['search'].'&' : '') . 
                (isset($_GET['tPath']) ? 'tPath=' . $_GET['tPath'].'&' : /*$tPath?'tPath='.$tPath.'&' :*/ '') . 'aID=' . $articles['articles_id']) . '">' . 
                tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }

    $tPath_back = '';
    if( (tep_not_null($tPath_array)) && (! isset($_GET['search']) ) ) {
        for ($i=0, $n=sizeof($tPath_array)-1; $i<$n; $i++) {
            $tPath_back .= (empty($tPath_back) ? $tPath_array[$i] : '_' . $tPath_array[$i]);
        }
    }

    $tPath_back = (tep_not_null($tPath_back)) ? 'tPath=' . $tPath_back . '&' : '';
?>
              <tr>
                <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText"><?php echo TEXT_TOPICS . '&nbsp;' . $topics_count . '<br>' . TEXT_ARTICLES . '&nbsp;' . $articles_count; ?></td>
                    <td align="right" class="smallText">
                    <?php 
                    if (tep_not_null($tPath_array)) {
                        echo tep_draw_button(IMAGE_BACK, 'far fa-angle-left', tep_href_link('articles.php', $tPath_back . 'tID=' . $current_topic_id)) . '&nbsp;&nbsp;';
                    } 
                    if (! isset($_GET['search'])) {
                        echo tep_draw_button(IMAGE_NEW_TOPIC, 'far fa-angle-left', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&action=new_topic'))  . '&nbsp;&nbsp;' .
                             tep_draw_button(IMAGE_NEW_ARTICLE, 'far fa-angle-left', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&action=new_article'));
                    }
                    ?>
                    </td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
    $heading = [];
    $contents = [];
    switch ($action) {
      case 'new_topic':
        $heading[] = ['text' => '<strong>' . TEXT_INFO_HEADING_NEW_TOPIC . '</strong>'];

        $contents = ['form' => tep_draw_form('newtopic', 'articles.php', 'action=insert_topic&tPath=' . $tPath, 'post', 'enctype="multipart/form-data"')];
        $contents[] = ['text' => TEXT_NEW_TOPIC_INTRO];

        $topic_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $topic_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('topics_name[' . $languages[$i]['id'] . ']');
        }

        $contents[] = ['text' => '<br>' . TEXT_TOPICS_NAME . $topic_inputs_string];
        $contents[] = ['text' => '<br>' . TEXT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', '', 'size="2"')];
        $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $tPath)) .
          tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn btn-success')
        ];
      break;

      case 'delete_topic':
        $heading[] = ['text' => '<b>' . TEXT_INFO_HEADING_DELETE_TOPIC . '</b>'];

        $contents = ['form' => tep_draw_form('topics', 'articles.php', 'action=delete_topic_confirm&tPath=' . $_GET['tPath']) . tep_draw_hidden_field('topics_id', $tInfo->topics_id)];
        $contents[] = ['text' => TEXT_DELETE_TOPIC_INTRO];
        $contents[] = ['text' => '<br><b>' . $tInfo->topics_name . '</b>'];
        if ($tInfo->childs_count > 0) $contents[] = ['text' => '<br>' . sprintf(TEXT_DELETE_WARNING_CHILDS, $tInfo->childs_count)];
        if ($tInfo->articles_ttl_cnt > 0) $contents[] = ['text' => '<br>' . sprintf(TEXT_DELETE_WARNING_ARTICLES, $tInfo->articles_ttl_cnt)];
        $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', null, 'primary', null, 'btn, btn-danger btn-block') .
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&tID=' . $tInfo->topics_id))];
      break;
        
      case 'move_topic':
        $heading[] = ['text' => '<strong>' . TEXT_INFO_HEADING_MOVE_TOPIC . '</strong>'];

        $contents = ['form' => tep_draw_form('topics', 'articles.php', 'action=move_topic_confirm&tPath=' . $current_topic_id) . tep_draw_hidden_field('topics_id', $tInfo->topics_id)];
        $contents[] = ['text' => sprintf(TEXT_MOVE_TOPICS_INTRO, $tInfo->topics_name)];
        $contents[] = ['text' => '<br>' . sprintf(TEXT_MOVE, $tInfo->topics_name) . '<br>' . tep_draw_pull_down_menu('move_to_topic_id', tep_get_topic_tree(), $current_topic_id)];
        $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_bootstrap_button(IMAGE_MOVE, 'fas fa-people-carry', null, 'primary', null, 'btn btn-success btn-block') .
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $tPath . '&tID=' . $tInfo->topics_id))];
      break;
      
      case 'delete_article':
        $heading[] = ['text' => '<strong>' . TEXT_INFO_HEADING_DELETE_ARTICLE . '</strong>'];

        $contents = ['form' => tep_draw_form('articles', 'articles.php', 'action=delete_article_confirm&tPath=' . $_GET['tPath']) . tep_draw_hidden_field('articles_id', $aInfo->articles_id)];
        $contents[] = ['text' => TEXT_DELETE_ARTICLE_INTRO];
        $contents[] = ['text' => '<br><strong>' . $aInfo->articles_name . '</strong>'];

        $article_topics_string = '';
        $article_topics = tep_generate_topic_path($aInfo->articles_id, 'article');
        for ($i = 0, $n = sizeof($article_topics); $i < $n; $i++) {
          $topic_path = '';
          for ($j = 0, $k = sizeof($article_topics[$i]); $j < $k; $j++) {
            $topic_path .= $article_topics[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
          }
          $topic_path = substr($topic_path, 0, -16);
          $article_topics_string .= tep_draw_checkbox_field('article_topics[]', $article_topics[$i][sizeof($article_topics[$i])-1]['id'], true) . '&nbsp;' . $topic_path . '<br>';
        }
        $article_topics_string = substr($article_topics_string, 0, -4);

        $contents[] = ['text' => '<br>' . $article_topics_string];
        $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&aID=' . $aInfo->articles_id)) .
          tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', null, 'primary', null, 'btn btn-danger') 
        ];
      break;
      
      case 'move_article':
        $heading[] = ['text' => '<b>' . TEXT_INFO_HEADING_MOVE_ARTICLE . '</b>'];

        $contents = ['form' => tep_draw_form('articles', 'articles.php', 'action=move_article_confirm&tPath=' . $current_topic_id) . tep_draw_hidden_field('articles_id', $aInfo->articles_id)];
        $contents[] = ['text' => sprintf(TEXT_MOVE_ARTICLES_INTRO, $aInfo->articles_name)];
        $contents[] = ['text' => '<br>' . TEXT_INFO_CURRENT_TOPICS . '<br><b>' . tep_output_generated_topic_path($aInfo->articles_id, 'article') . '</b>'];
        $contents[] = ['text' => '<br>' . sprintf(TEXT_MOVE, $aInfo->articles_name) . '<br>' . tep_draw_pull_down_menu('move_to_topic_id', tep_get_topic_tree(), $current_topic_id)];
        $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $tPath . '&aID=' . $aInfo->articles_id)) . 
          tep_draw_bootstrap_button(IMAGE_MOVE, 'fas fa-people-carry', null, 'primary', null, 'btn btn-success')];
      break;
      case 'copy_to':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_COPY_TO . '</b>');

        $contents = ['form' => tep_draw_form('copy_to', 'articles.php', 'action=copy_to_confirm&tPath=' . $current_topic_id) . tep_draw_hidden_field('articles_id', $aInfo->articles_id)];
        $contents[] = ['text' => TEXT_INFO_COPY_TO_INTRO];
        $contents[] = ['text' => '<br>' . TEXT_INFO_CURRENT_TOPICS . '<br><b>' . tep_output_generated_topic_path($aInfo->articles_id, 'article') . '</b>'];
        $contents[] = ['text' => '<br>' . TEXT_TOPICS . '<br>' . tep_draw_pull_down_menu('topics_id', tep_get_topic_tree(), $current_topic_id)];
        $contents[] = ['text' => '<br>' . TEXT_HOW_TO_COPY . '<br>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '<br>' . tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE];
        $contents[] = ['align' => 'center', 'text' => '<br>' . 
          tep_draw_bootstrap_button(IMAGE_COPY, 'fas fa-copy', null, 'primary', null, 'btn btn-success btn-block btn-lg') .
          tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles.php', 'tPath=' . $_GET['tPath'] . '&aID=' . $aInfo->articles_id ))] ;
      break;

      default:
        if ($rows > 0) {
          if (isset($tInfo) && is_object($tInfo)) { // topic info box contents
            $heading[] = ['text' => '<strong>' . $tInfo->topics_name . '</strong>'];

            $contents[] = ['text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($tInfo->date_added)];
            if (tep_not_null($tInfo->last_modified)) $contents[] = ['text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($tInfo->last_modified)];
            
            $tmp_str = ''; //build first so these are added to the mouseover block
            if ($tInfo->articles_ttl_cnt) {
                $tmp_str = '<br>&nbsp;&nbsp;' . TEXT_ARTICLES_ACTIVE . ' ' . ($tInfo->articles_active_blog_yes + $tInfo->articles_active_blog_no) . '<br>&nbsp;&nbsp;' .
                  TEXT_ARTICLES_BLOG_NO . ' ' . $tInfo->articles_ttl_blog_no . '<br>&nbsp;&nbsp;' .
                  TEXT_ARTICLES_BLOG_YES . ' ' . $tInfo->articles_ttl_blog_yes;
                
            }   
            
            $contents[] = ['text' => '<br>' . TEXT_SUBTOPICS . ' ' . $tInfo->childs_count . '<br>' . TEXT_ARTICLES . ' ' . $tInfo->articles_ttl_cnt . $tmp_str] ;

            $contents[] = ['align' => 'center', 'text' => 
              tep_draw_bootstrap_button(IMAGE_EDIT, 'fas fa-user-edit', tep_href_link('articles.php', 'tPath=' . $current_topic_id  . '&tID=' . $tInfo->topics_id . '&action=edit_topic'), 'primary', null, 'btn btn-primary btn-block') .
              tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', tep_href_link('articles.php', 'tPath=' . $current_topic_id  . '&tID=' . $tInfo->topics_id . '&action=delete_topic'), 'primary', null, 'btn btn-danger btn-block') .
              tep_draw_bootstrap_button(IMAGE_MOVE, 'fas fa-people-carry', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&tID=' . $tInfo->topics_id . '&action=move_topic'), 'primary', null, 'btn btn-success btn-block')] ;
            
          } elseif (isset($aInfo) && is_object($aInfo)) { // article info box contents
            $heading[] = ['text' => '<b>' . tep_get_articles_name($aInfo->articles_id, $languages_id) . '</b>'];

            $contents[] = ['text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($aInfo->articles_date_added)];
            if (tep_not_null($aInfo->articles_last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($aInfo->articles_last_modified));
            if (date('Y-m-d') < $aInfo->articles_date_available) $contents[] = array('text' => TEXT_DATE_AVAILABLE . ' ' . tep_date_short($aInfo->articles_date_available));
            $contents[] = ['text' => '<br>' . TEXT_ARTICLES_AVERAGE_RATING . ' ' . number_format($aInfo->average_rating, 2) . '%'];

            $contents[] = ['align' => 'center', 'text' => 
              tep_draw_bootstrap_button(IMAGE_EDIT, 'fas fa-user-edit', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&aID=' . $aInfo->articles_id . '&action=new_article'), 'primary', null, 'btn btn-primary btn-block') .
              tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&aID=' . $aInfo->articles_id . '&action=delete_article'), 'primary', null, 'btn, btn-danger btn-block') .
              tep_draw_bootstrap_button(IMAGE_MOVE, 'fas fa-people-carry', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&aID=' . $aInfo->articles_id . '&action=move_article'), 'primary', null, 'btn btn-success btn-block') .
              tep_draw_bootstrap_button(IMAGE_COPY, 'fas fa-copy', tep_href_link('articles.php', 'tPath=' . $current_topic_id . '&aID=' . $aInfo->articles_id . '&action=copy_to'), 'primary', null, 'btn btn-success btn-block')] ;
            
          }
        } else { // create topic/article info
          $heading[] = ['text' => '<strong>' . EMPTY_TOPIC . '</strong>'];

          $contents[] = ['text' => TEXT_NO_CHILD_TOPICS_OR_ARTICLES];
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
    </table>
<?php
  }
?>
    </td>
  </tr>
</table>

<script>

$('#aiList').sortable({ containment: 'parent' });

          $('a.aiDel').click(function(e){
            var p = $(this).data('ai-id');
            $('#aiId' + p).effect('blind').remove();

            e.preventDefault();
          });

</script>
<?php
require('includes/template_bottom.php');
require('includes/application_bottom.php');

/* ODPADISTE
file_put_contents('prdel.log', "\nregexp OK key='".$key."' value='".var_export($value, true)."' matches[1]='".$matches[1]."'\n", FILE_APPEND);

file_put_contents('prdel.log', "\nsql: ".var_export($sql_data_array, true)."\nt='".var_export($t, true)."'\nisupf=".(is_uploaded_file($value['tmp_name'])?'true':'false'), FILE_APPEND);

else file_put_contents('prdel.log', "\n '".var_export($t, true)."' HOVNO!!!\n".var_export($messageStack, true)."\nisupf=".(is_uploaded_file($value['tmp_name'])?'true':'false')."\n", FILE_APPEND);

 file_put_contents('prdel.log', "\nPO t='".var_export($t, true)."'\n", FILE_APPEND);   
 
file_put_contents('prdel.log', "\nPRED t='".var_export($t, true)."'\n", FILE_APPEND);                                   

file_put_contents('prdel.log', "\n-------------------------------------\n".date('j.n.Y H:i:s')."\n".var_export($_FILES, true)."\n", FILE_APPEND); 


 
*/
