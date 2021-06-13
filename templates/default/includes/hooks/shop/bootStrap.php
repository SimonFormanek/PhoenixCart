<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

class hook_shop_siteWide_bootStrap {

  public $version = '4.6.0';

  public $sitestart = null;
  public $siteend = null;

  function listen_injectSiteStart() {
    if (BOOTSTRAP_ENABLED == 'True') {
      $this->sitestart .= '<link rel="stylesheet" href="/ext/css/bootstrap.min.css"  />' . PHP_EOL;
    }
    return $this->siteend;
  }

  function listen_injectSiteEnd() {
    if (BOOTSTRAP_ENABLED == 'True') {
      $this->siteend .= '<script src="/ext/js/popper.min.js"></script>' . PHP_EOL;
      $this->siteend .= '<script src="/ext/js/bootstrap.min.js"></script>' . PHP_EOL;
    }
    return $this->siteend;
  }
