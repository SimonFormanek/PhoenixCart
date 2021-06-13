<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>OSCOM, Starting Your Online Business with CE Phoenix</title>
    <meta name="robots" content="noindex,nofollow" />
    <link rel="icon" type="image/png" href="images/icon_phoenix.png" />
    <link rel="stylesheet" href="/ext/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/ext/fonts/font-awesome/5.15.1/css/all.min.css" />
    <link rel="stylesheet" href="templates/main_page/stylesheet.css" />
    <script src="/ext/js/jquery.min.js" ></script>
  </head>

  <body>
    <div class="container">
      <div class="row">
        <div id="storeLogo" class="col-sm-6">
          <a href="index.php"><img src="images/phoenix.png" title="CE Phoenix" style="margin: 10px 10px 0 10px;" /></a>
        </div>

        <div id="headerShortcuts" class="col-sm-6">
          <ul class="nav justify-content-end">
            <li class="nav-item"><a class="nav-link active" href="https://phoenixcart.org/" target="_blank" rel="noreferrer">Website</a></li>
            <li class="nav-item"><a class="nav-link" href="https://phoenixcart.org/forum/" target="_blank" rel="noreferrer">Support</a></li>
          </ul>
        </div>
      </div>

      <hr>

      <?php require 'templates/pages/' . $page_contents; ?>

      <footer class="card bg-light mb-3 card-body text-center">CE Phoenix &copy; 2000-<?= date('Y') ?></footer>
    </div>

    <script src="/ext/js/popper.min.js"></script>
    <script src="/ext/js/bootstrap.min.js" ></script>
  </body>
</html>
