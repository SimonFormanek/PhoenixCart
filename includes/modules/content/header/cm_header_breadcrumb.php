<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  class cm_header_breadcrumb extends abstract_executable_module {

    const CONFIG_KEY_BASE = 'MODULE_CONTENT_HEADER_BREADCRUMB_';

    public function __construct() {
      parent::__construct(__FILE__);
    }

    public function execute() {
      global $breadcrumb, $cPath_array, $OSCOM_category, $brand;

      // add the products model to the breadcrumb trail
      if (isset($_GET['products_id'])) {
        $model_sql = "SELECT COALESCE(";
        if ( 'True' === $this->base_constant('PRODUCT_SEO_OVERRIDE') ) {
          $model_sql .= "NULLIF(pd.products_seo_title, ''), ";
        }
        $model_sql .= <<<'EOSQL'
NULLIF(p.products_model, ''), pd.products_name) AS products_model
 FROM products p INNER JOIN products_description pd ON p.products_id = pd.products_id
 WHERE p.products_id = %d AND pd.language_id = %d
EOSQL;

        $model_query = tep_db_query(sprintf($model_sql, (int)$_GET['products_id'], (int)$_SESSION['languages_id']));
        if ($model = tep_db_fetch_array($model_query)) {
          $breadcrumb->prepend($model['products_model'], tep_href_link('product_info.php', 'products_id=' . (int)$_GET['products_id']));
        }
      }

      // add category names or the manufacturer name to the breadcrumb trail
      if (isset($cPath_array)) {
        foreach (array_reverse($cPath_array) as $k => $v) {
          if ( ( 'True' !== $this->base_constant('CATEGORY_SEO_OVERRIDE') ) || !tep_not_null($breadcrumb_category = $OSCOM_category->getData($v, 'seo_title')) ) {
            $breadcrumb_category = $OSCOM_category->getData($v, 'name');
          }

          $breadcrumb->prepend($breadcrumb_category, tep_href_link('index.php', 'cPath=' . implode('_', array_slice($cPath_array, 0, ($k+1)))));
        }
      } elseif (isset($_GET['manufacturers_id'])) {
        if ( ( 'True' !== $this->base_constant('MANUFACTURER_SEO_OVERRIDE') ) || !tep_not_null($breadcrumb_brand = $brand->getData('manufacturers_seo_title'))) {
          $breadcrumb_brand = $brand->getData('manufacturers_name');
        }

        $breadcrumb->prepend($breadcrumb_brand, tep_href_link('index.php', 'manufacturers_id=' . (int)$_GET['manufacturers_id']));
      }

      foreach (array_reverse(MODULE_CONTENT_HEADER_BREADCRUMB_TITLES) as $text => $page) {
        if (is_string($page) && (strlen($page) > 0)) {
          $link = tep_href_link($page);
        } else {
          $link = HTTP_SERVER;
        }

        $breadcrumb->prepend($text, $link);
      }

      $content_width = (int)MODULE_CONTENT_HEADER_BREADCRUMB_CONTENT_WIDTH;

      $tpl_data = [ 'group' => $this->group, 'file' => __FILE__ ];
      include 'includes/modules/content/cm_template.php';
    }

    protected function get_parameters() {
      return [
        $this->config_key_base . 'STATUS' => [
          'title' => 'Enable Header Breadcrumb Module',
          'value' => 'True',
          'desc' => 'Do you want to enable the Breadcrumb content module?',
          'set_func' => "tep_cfg_select_option(['True', 'False'], ",
        ],
        $this->config_key_base . 'CONTENT_WIDTH' => [
          'title' => 'Content Width',
          'value' => '12',
          'desc' => 'What width container should the content be shown in?',
          'set_func' => "tep_cfg_select_option(['12', '11', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1'], ",
        ],
        $this->config_key_base . 'SORT_ORDER' => [
          'title' => 'Sort Order',
          'value' => '0',
          'desc' => 'Sort order of display. Lowest is displayed first.',
        ],
        $this->config_key_base . 'PRODUCT_SEO_OVERRIDE' => [
          'title' => 'Product SEO Override?',
          'value' => 'True',
          'desc' => 'Do you want to allow product names in the breadcrumb to be over-ridden by your SEO Titles (if set)?',
          'set_func' => "tep_cfg_select_option(['True', 'False'], ",
        ],
        $this->config_key_base . 'MANUFACTURER_SEO_OVERRIDE' => [
          'title' => 'Manufacturer SEO Override?',
          'value' => 'True',
          'desc' => 'Do you want to allow manufacturer names in the breadcrumb to be over-ridden by your SEO Titles (if set)?',
          'set_func' => "tep_cfg_select_option(['True', 'False'], ",
        ],
        $this->config_key_base . 'CATEGORY_SEO_OVERRIDE' => [
          'title' => 'SEO Breadcrumb Override?',
          'value' => 'True',
          'desc' => 'Do you want to allow category names in the breadcrumb to be over-ridden by your SEO Titles (if set)?',
          'set_func' => "tep_cfg_select_option(['True', 'False'], ",
        ],
      ];
    }

  }

