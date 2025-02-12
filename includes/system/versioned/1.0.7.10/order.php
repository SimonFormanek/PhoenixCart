<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  class order {

    public $info = [];
    public $totals = [];
    public $products = [];
    public $customer = [];
    public $delivery = [];
    public $billing = [];
    public $content_type, $id;

    public function __construct($order_id = '') {
      if (tep_not_null($order_id)) {
        $this->set_id($order_id);
        database_order_builder::build($this);
      } else {
        cart_order_builder::build($this);
      }

      $GLOBALS['OSCOM_Hooks']->call('siteWide', 'constructOrder', $this);
    }

    public function has_id() {
      return isset($this->id);
    }

    public function get_id() {
      return $this->id;
    }

    public function set_id($order_id) {
      $this->id = tep_db_prepare_input(strval($order_id));
    }

  }
