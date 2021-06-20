<?php
/*
  $Id: header_tags_seo.php,v 1.2 2008/08/08
  header_tags_seo Originally Created by: Jack_mcs
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2010 oscommerce-solution.com

  Released under the GNU General Public License
*/
/****************************************************
Module to handle automatic addition of all pseudo 
pages in Header Tags SEO.
****************************************************/

if (strpos($pseudoPage, ".php") === FALSE || strpos($pseudoPage, "?") === FALSE || strpos($pseudoPage, "=") === FALSE) {
    $messageStack->add(sprintf(ERROR_INVALID_PSEUDO_FORMAT, $pseudoPage), 'error');
} else {
    $parts = explode("?", $pseudoPage);
    $baseFiles = (array)GetBaseFiles();
    if (in_array($parts[0], $baseFiles)) { //don't allow pseudo pages for base files
        $messageStack->add(sprintf(ERROR_INVALID_PSEUDO_PAGE, $parts[0]), 'error');
    }

    else if (($result = FileNotUsingHeaderTags($parts[0])) === 'FALSE' || IsTemplate()) {
        $pageTags_query = tep_db_query("select * from headertags where page_name like '" . tep_db_input($pseudoPage) . "' and language_id = '" . (int)$language_id. "'");
        $pageTags = tep_db_fetch_array($pageTags_query);

        if (tep_db_num_rows($pageTags_query) == 0) {
           $filenameInc = DIR_FS_CATALOG. 'includes/header_tags.php';
           $fp = @file($filenameInc);  
 
           if (AddedToHeaderTagsIncludesFilePseudo($pseudoPage, $fp, $languages_id)) {
            
              if (WriteHeaderTagsFile($filenameInc, $fp)) {             
              
                $pageTags_query = tep_db_query("select * from headertags where page_name like '" . tep_db_input($pseudoPage) . "' and language_id = '" . (int)$language_id. "'");
                
                if (tep_db_num_rows($pageTags_query) == 0)               {
                  for ($a=0; $a < count($languages); ++$a) {
                     $pseudo_data_array = array('page_name' => $pseudoPage,
                                             'page_title' => tep_db_input($htsTitle),
                                             'page_description' => tep_db_input($htsDesc),
                                             'page_keywords' => tep_db_input($htsKwords),
                                             'page_logo' => '',
                                             'page_logo_1' => '',
                                             'page_logo_2' => '',
                                             'page_logo_3' => '',
                                             'page_logo_4' => '',
                                             'append_default_title' => 0,
                                             'append_default_description' => 0,
                                             'append_default_keywords' => 0,
                                             'append_default_logo' => 0,
                                             'append_category' =>  0,
                                             'append_manufacturer' =>  0,
                                             'append_product' =>  0,
                                             'append_root' =>  1,
                                             'sortorder_title' =>  0,
                                             'sortorder_description' =>  0,
                                             'sortorder_keywords' =>  0,
                                             'sortorder_logo' =>  0, 
                                             'sortorder_logo_1' =>  0,
                                             'sortorder_logo_2' =>  0,
                                             'sortorder_logo_3' =>  0,
                                             'sortorder_logo_4' =>  0,
                                             'sortorder_category' =>  0,
                                             'sortorder_manufacturer' =>  0,
                                             'sortorder_product' =>  0,
                                             'sortorder_root' =>  1,
                                             'sortorder_root_1' =>  0,
                                             'sortorder_root_2' =>  0,
                                             'sortorder_root_3' =>  0,
                                             'sortorder_root_4' =>  0,
                                             'language_id' => (int)$languages[$a]['id']);
                     tep_db_perform('headertags', $pseudo_data_array);
                  }
                  $newfiles = GetFileList($languages_id);
                }
              }
           } else {
               $pageTags_query = tep_db_query("select * from headertags where page_name like '" . tep_db_input($pseudoPage) . "' and language_id = '" . (int)$language_id. "'");
               if (tep_db_num_rows($pageTags_query) == 0) {
                   for ($a=0; $a < count($languages); ++$a) {
                       $pseudo_data_array = array('page_name' => $pseudoPage,
                                               'page_title' => tep_db_input($htsTitle),
                                               'page_description' => tep_db_input($htsDesc),
                                               'page_keywords' => tep_db_input($htsKwords),
                                               'page_logo' => '',
                                               'page_logo_1' => '',
                                               'page_logo_2' => '',
                                               'page_logo_3' => '',
                                               'page_logo_4' => '',
                                               'append_default_title' => 0,
                                               'append_default_description' => 0,
                                               'append_default_keywords' => 0,
                                               'append_default_logo' => 0,
                                               'append_category' =>  0,
                                               'append_manufacturer' =>  0,
                                               'append_product' =>  0,
                                               'append_root' =>  1,
                                               'sortorder_title' =>  0,
                                               'sortorder_description' =>  0,
                                               'sortorder_keywords' =>  0,
                                               'sortorder_logo' =>  0, 
                                               'sortorder_logo_1' =>  0,
                                               'sortorder_logo_2' =>  0,
                                               'sortorder_logo_3' =>  0,
                                               'sortorder_logo_4' =>  0,
                                               'sortorder_category' =>  0,
                                               'sortorder_manufacturer' =>  0,
                                               'sortorder_product' =>  0,
                                               'sortorder_root' =>  1,
                                               'sortorder_root_1' =>  0,
                                               'sortorder_root_2' =>  0,
                                               'sortorder_root_3' =>  0,
                                               'sortorder_root_4' =>  0,
                                               'language_id' => (int)$languages[$a]['id']);
                       tep_db_perform('headertags', $pseudo_data_array, 'insert');
                   }
               }
           } 
        }
        else {  //this is a duplicate page so update it
            $pageTags_query = tep_db_query("UPDATE headertags SET 
             page_title = '" . tep_db_input($htsTitle) . "',
             page_description = '" . tep_db_input($htsDesc) . "', 
             page_keywords = '" . tep_db_input($htsKwords) . "' 
             WHERE page_name = '" . tep_db_input($pseudoPage) . "' AND 
             language_id = '" . (int)$language_id  . "'");

        }  
    }
    else if ($result != 'TRUE') {
        $messageStack->add(sprintf(ERROR_NOT_USING_HEADER_TAGS, $parts[0]), 'error');
    } 
}
