<?php
/*
  $Id$ version 1.0 for Phoenix

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  chdir('../../');

  require('includes/application_top.php');
  include('includes/languages/' . $language . '/modules/store/st_store_search.php');

  if (isset($_POST['query'])) {
    $query = tep_db_prepare_input($_POST['query']);
  } else {
    //nothing to do
    $query = "";
    exit();
  }

//  if (strlen($query) < 3) { exit(); }

  //here we can replace certain phrases that people may search for that are wrong, i have left my examples below.
  //for example i have people add food or foods onto the end of search phrases, but food is rarely used in product names.
  //or for if people add spaces where there shouldnt be or remove spaces when there should be

  if ($language == 'english') {
    $query = str_replace('/', '', $query); // avoid highlight crash
    $query = str_replace(' food', '', $query);
    $query = str_replace(' foods', '', $query);
  }

  if ($language == 'french') {
    $query = str_replace('/', '', $query); // avoid highlight crash
  }

  //Explode This Query
  $query_exploded = array();
  $query_exploded = explode(' ', $query);
  $query_exploded = array_unique($query_exploded);


  //if a characters are only "b" or "B" do nothing!
  if (($key = array_search("b", $query_exploded)) !== false) { unset($query_exploded[$key]); }
  if (($key = array_search("B", $query_exploded)) !== false) { unset($query_exploded[$key]); }

  //for highlight rule
  arsort($query_exploded);
  $query_exploded_new = '';

  foreach ($query_exploded as $highlight) {
    //<b> is not search engine sensitive
    $query_exploded_new .= '<b>' . $highlight . '</b>' . PHP_EOL;
  }
  $query_exploded_new = substr($query_exploded_new, 0, -6);

  $query_exploded_highlight = explode(PHP_EOL, $query_exploded_new);


  //Generate Like Statement for Each Word To Find Categories, Second Level, That Match
  $like_statement_category = '';

  foreach ($query_exploded as $category) {
    //Prevent SQL Injection Attempts
    $category = str_replace(array("'", ";", "*", "(", ")"), '', $category);

	// categories_name or categories_htc_title_tag_alt search
	$like_statement_category .= "(cd." . STORE_STORE_SEARCH_CATEGORY_NAME_FIELD . " LIKE '%" . tep_db_input($category) . "%') AND ";
  }

  //Remove The Last AND
  $like_statement_category = substr($like_statement_category, 0, -4);

  //Select categories, that are second level, and that match our query
  $sqlquery = tep_db_query("SELECT distinct(c.categories_id), cd." . STORE_STORE_SEARCH_CATEGORY_NAME_FIELD . ", c.parent_id FROM categories_description cd, categories c WHERE cd.categories_id = c.categories_id AND " . $like_statement_category . " AND cd.language_id = '" . (int)$languages_id . "'");

  if (tep_db_num_rows($sqlquery) && STORE_STORE_SEARCH_MAX_CATEGORY > 0) {
    $c = 0;
    while ($row = tep_db_fetch_array($sqlquery)) {
      $c++;
      if ($c > STORE_STORE_SEARCH_MAX_CATEGORY) {
		$array[] = array('icon'  => '<span style="float:left; margin-right:10px;"><i class="fa fa-exclamation-triangle fa-2x"></i></span>',
                         'title' => sprintf(STORE_STORE_SEARCH_MORE_CATEGORY, STORE_STORE_SEARCH_MAX_CATEGORY, tep_db_num_rows($sqlquery)),
                         'href'  => null,
                         'price' => null);
        break;
      } else {
		$url_title = ucwords(strtolower($row[STORE_STORE_SEARCH_CATEGORY_NAME_FIELD]));

		//highlight
		$url_title = str_ireplace($query_exploded, $query_exploded_highlight, $url_title);

//		$array[] = array('icon'  => "sitemap",
		$array[] = array('icon' => '<span style="float:left; margin-right:10px;"><i class="fa fa-sitemap fa-2x"></i></span>',
						 'title' => $url_title,
						 'href'  => tep_href_link('index.php', 'cPath=' . $row['categories_id'], $request_type),
						 'price' => null);
	  }
    }
  }
  //We Have All Suggested Categories


  //Find Suggested Products
  $like_statement_product = '';

  foreach ($query_exploded as $product) {
    //Prevent SQL Injection Attempts
    $product = str_replace(array("'", ";", "*", "(", ")"), '', $product);
	
	//Set Keywords Search Field
	//Add products_gtin + manufacturers_name
	$like_statement_product .= "(pd.products_name LIKE '%" . tep_db_input($product) . "%' 
	" . (STORE_STORE_SEARCH_PRODUCT_MODEL == 'True' ? "OR p.products_model LIKE '%" . tep_db_input($product) . "%'" : "") . " 
	" . (STORE_STORE_SEARCH_PRODUCT_GTIN == 'True' ? "OR p.products_gtin LIKE '%" . tep_db_input($product) . "%'" : "") . " 
	" . (STORE_STORE_SEARCH_PRODUCT_MANUFACTURER == 'True' ? "OR m.manufacturers_name LIKE '%" . tep_db_input($product) . "%'" : "") . " 
	" . (STORE_STORE_SEARCH_PRODUCT_KEYWORDS_FIELD != 'None' ? "OR pd." . STORE_STORE_SEARCH_PRODUCT_KEYWORDS_FIELD . " LIKE '%" . tep_db_input($product) . "%'" : "") . "
	) AND ";
  }

  //Remove the Last And
  $like_statement_product = substr($like_statement_product, 0, -4);

  //Add products p left join manufacturers m on p.manufacturers_id = m.manufacturers_id
  //Add p.products_image + p.products_quantity
  $sqlquery = tep_db_query("SELECT distinct(p.products_id), pd.products_name, p.products_image, p.products_quantity, p.products_price, p.products_tax_class_id FROM products_description pd, products p " . (STORE_STORE_SEARCH_PRODUCT_MANUFACTURER == 'True' ? "left join manufacturers m on p.manufacturers_id = m.manufacturers_id" : "") . " WHERE " . $like_statement_product . " AND pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_status = 1");

  $p = 0; //Set Row
  if (tep_db_num_rows($sqlquery) && STORE_STORE_SEARCH_MAX_PRODUCT > 0) {
    while ($row = tep_db_fetch_array($sqlquery)) {
      $p++;
      $url_title = str_replace('’', '', $row['products_name']);

      //highlight
      $url_title = str_ireplace($query_exploded, $query_exploded_highlight, $url_title);

      if ($p > STORE_STORE_SEARCH_MAX_PRODUCT) {
//        $array[] = array('icon'  => "cart-plus",
        $array[] = array('icon'  => '<span style="float:left; margin-right:10px;"><i class="fa fa-plus-circle fa-2x"></i></span>',
                         'title' => sprintf(STORE_STORE_SEARCH_MORE_PRODUCT, STORE_STORE_SEARCH_MAX_PRODUCT, tep_db_num_rows($sqlquery)),
                         'href'  => tep_href_link('advanced_search_result.php', 'keywords=' . urlencode(str_replace(' ', ' ', $query)), $request_type) . '&search_in_description=' . (STORE_STORE_SEARCH_FUNCTIONS == 'Descriptions' ? 1 : 0),
                         'price' => null);
        break;
      } else {
        if ($new_price = tep_get_products_special_price($row['products_id'])) {
          $price = '<s>' . $currencies->display_price($row['products_price'], tep_get_tax_rate($row['products_tax_class_id'])) . '</s> <span class="productSpecialPriceStoreSearch">' . $currencies->display_price($new_price, tep_get_tax_rate($row['products_tax_class_id'])) . '</span>';
        } else {
          $price = $currencies->display_price($row['products_price'], tep_get_tax_rate($row['products_tax_class_id']));
        }

		// image or icon for products
		if (STORE_STORE_SEARCH_IMAGE_OR_ICON == 'Image') {

		  if ($row['products_image'] != '') {
			$product_image = $row['products_image'];
		  } else {
			$product_image = 'picture_o_trans.png';
		  }

		  $image_product = '<span class="d-block d-sm-none" style="float:left; margin-right:10px;"><img src="images/' . $product_image . '" width="' . STORE_STORE_SEARCH_IMAGE_WIDTH_XS . '" height="auto"></span>';
		  $image_product .= '<span class="d-none d-sm-block d-md-none" style="float:left; margin-right:10px;"><img src="images/' . $product_image . '" width="' . STORE_STORE_SEARCH_IMAGE_WIDTH_SM . '" height="auto"></span>';
		  $image_product .= '<span class="d-none d-md-block d-lg-none" style="float:left; margin-right:10px;"><img src="images/' . $product_image . '" width="' . STORE_STORE_SEARCH_IMAGE_WIDTH_MD . '" height="auto"></span>';
		  $image_product .= '<span class="d-none d-lg-block d-xl-none" style="float:left; margin-right:10px;"><img src="images/' . $product_image . '" width="' . STORE_STORE_SEARCH_IMAGE_WIDTH_LG . '" height="auto"></span>';
		  $image_product .= '<span class="d-none d-xl-block" style="float:left; margin-right:10px;"><img src="images/' . $product_image . '" width="' . STORE_STORE_SEARCH_IMAGE_WIDTH_XL . '" height="auto"></span>';

		} else {
		  $image_product = '<span style="float:left; margin-right:10px;"><i class="fa fa-cart-plus fa-2x"></i></span>';
		}

//        $array[] = array('icon'  => "cart-plus",
        $array[] = array('icon'  => $image_product,
                         'title' => $url_title,
                         'href'  => tep_href_link('product_info.php', 'products_id=' . $row['products_id'], $request_type),
                         'price' => $price);
      }
    }
  } else {
//    $array[] = array('icon'  => "wrench",
    $array[] = array('icon'  => '<span style="float:left; margin-right:10px;"><i class="fa fa-exclamation-triangle fa-2x"></i></span>',
                     'title' => STORE_STORE_SEARCH_PRODUCT_NOT_FOUND,
                     'href'  => tep_href_link('advanced_search.php', 'keywords=' . urlencode(str_replace(' ', ' ', $query)), $request_type),
                     'price' => null);
  }
  //We Have All Suggested Products

/*
  //Start content searches in files
  if (tep_not_null(STORE_STORE_SEARCH_PAGES)) {
    $content_files = array();

    foreach (explode(';', STORE_STORE_SEARCH_PAGES) as $page) {
      $page = trim($page);

      if (!empty($page)) {
        $content_files[] = $page;
      }
    }

    foreach ($content_files as $file_name) {
      $file = 'includes/languages/' . $language . '/' . $file_name;
      $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

      if ($lines !== false) {
        $f = 0;
        foreach ($lines as $line) {
          $f++;
          // skip header
          if ($f > STORE_STORE_SEARCH_MAX_PAGE) { //empty rows shifted in @file!
            // Check if the line contains the string we're looking for, and add if it does
            foreach ($query_exploded as $q) {
              if (strpos(strtolower($line), strtolower($q)) !== false) {
				
				//you can add any page that contains useful information
				$row_file = array(	'conditions.php',
									'shipping.php',
									'privacy.php');

				//don't forget to add the translation for any page registred above and into language file(s)
				$rendition = array(	STORE_STORE_SEARCH_PAGE_CONDITIONS,
									STORE_STORE_SEARCH_PAGE_SHIPPING,
									STORE_STORE_SEARCH_PAGE_PRIVACY);

				$page_title = str_replace($row_file, $rendition, $file_name);

//                $array[] = array('icon'  => "file",
                $array[] = array('icon'  => '<span style="float:left; margin-right:10px;"><i class="fa fa-file fa-2x"></i></span>',
//                                 'title' => sprintf(STORE_STORE_SEARCH_PAGE, substr(basename($file), 0, -4)),
                                 'title' => sprintf(STORE_STORE_SEARCH_PAGE, $page_title),
                                 'href'  => tep_href_link($file_name, null, $request_type),
                                 'price' => null);
                break 2;
              }
            }
          }
        }
      }
    }
  }
*/

  // build json
  echo json_encode($array);
?>