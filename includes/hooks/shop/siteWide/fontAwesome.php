<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

class hook_shop_siteWide_fontAwesome {
  var $version = '5.15.1';

  var $sitestart = null;

  function listen_injectSiteStart() {
    $this->sitestart .= '<link rel="stylesheet" href="/ext/fonts/font-awesome/5.15.1/css/all.min.css" />' . PHP_EOL;

    return $this->sitestart;
  }
  
}
