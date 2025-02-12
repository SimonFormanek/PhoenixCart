<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

// start the timer for the page parse time log
  define('PAGE_PARSE_START_TIME', microtime());

// load server configuration parameters
  include 'includes/configure.php';

// autoload classes in the classes or modules directories
  require 'includes/functions/autoloader.php';
  spl_autoload_register('tep_autoload_catalog');
  require 'vendor/autoload.php';

// include the database functions
  require 'includes/functions/database.php';

// make a connection to the database... now
  $db = new Database() or die('Unable to connect to database server!');

  // hooks
  $hooks = new hooks('shop');
  $OSCOM_Hooks =& $hooks;
  $all_hooks =& $hooks;
  $hooks->register('system');
  foreach ($hooks->generate('startApplication') as $result) {
    if (!isset($result)) {
      continue;
    }

    if (is_string($result)) {
      $result = [ $result ];
    }

    if (is_array($result)) {
      foreach ($result as $path) {
        if (is_string($path ?? null) && file_exists($path)) {
          require $path;
        }
      }
    }
  }
