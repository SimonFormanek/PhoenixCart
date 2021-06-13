<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

class hook_shop_siteWide_jQuery {

  public $version = '3.5.1';

  public function listen_injectSiteStart() {
    $jQuery = '<script src="/ext/js/jquery.min.js"></script>' . PHP_EOL;

    return $jQuery;
  }

}
