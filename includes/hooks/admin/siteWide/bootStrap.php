<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

class hook_admin_siteWide_bootStrap {

  public $version = '4.6.0';

  public $sitestart = null;
  public $siteend = null;

  public function listen_injectSiteStart() {
    //4.6.0
    $this->sitestart = '<link rel="stylesheet" href="/ext/css/bootstrap.min.css" />' . PHP_EOL;

    return $this->sitestart;
  }

  public function listen_injectSiteEnd() {
    $this->siteend = '<script src="/ext/js/popper.min.js" ></script>' . PHP_EOL;
    $this->siteend .= '<script src="/ext/js/bootstrap.min.js"></script>' . PHP_EOL;

    return $this->siteend;
  }

}
