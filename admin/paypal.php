<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  require 'includes/application_top.php';

  if ( mysqli_num_rows($db->query("SHOW TABLES LIKE 'oscom_app_paypal_log'")) != 1 ) {
    $db->query(<<<'EOSQL'
CREATE TABLE oscom_app_paypal_log (
  id int unsigned NOT NULL auto_increment,
  customers_id int NOT NULL,
  module varchar(8) NOT NULL,
  action varchar(255) NOT NULL,
  result tinyint NOT NULL,
  server tinyint NOT NULL,
  request text NOT NULL,
  response text NOT NULL,
  ip_address int unsigned,
  date_added datetime,
  PRIMARY KEY (id),
  KEY idx_oapl_module (module)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

EOSQL
      );
  }

  require DIR_FS_CATALOG . 'includes/apps/paypal/OSCOM_PayPal.php';
  $OSCOM_PayPal = new OSCOM_PayPal();

  $content = 'start.php';
  $action = 'start';
  $subaction = '';

  $OSCOM_PayPal->loadLanguageFile('admin.php');

  if ( isset($_GET['action']) && file_exists(DIR_FS_CATALOG . 'includes/apps/paypal/admin/actions/' . basename($_GET['action']) . '.php') ) {
    $action = basename($_GET['action']);
  }

  $OSCOM_PayPal->loadLanguageFile("admin/$action.php");

  if ( 'start' === $action ) {
    if ( $OSCOM_PayPal->migrate() ) {
      if ( defined('MODULE_ADMIN_DASHBOARD_INSTALLED') ) {
        $admin_dashboard_modules = explode(';', MODULE_ADMIN_DASHBOARD_INSTALLED);

        if ( !in_array('d_paypal_app.php', $admin_dashboard_modules) ) {
          $admin_dashboard_modules[] = 'd_paypal_app.php';

          $db->query("UPDATE configuration SET configuration_value = '" . $db->escape(implode(';', $admin_dashboard_modules)) . "' WHERE configuration_key = 'MODULE_ADMIN_DASHBOARD_INSTALLED'");
          $d_paypal_app = new d_paypal_app();
          $d_paypal_app->install();
        }
      }

      Href::redirect(Guarantor::ensure_global('Admin')->link('paypal.php')->retain_parameters());
    }
  }

  include DIR_FS_CATALOG . "includes/apps/paypal/admin/actions/$action.php";

  if ( isset($_GET['subaction']) && file_exists(DIR_FS_CATALOG . "includes/apps/paypal/admin/actions/$action/" . basename($_GET['subaction']) . '.php') ) {
    $subaction = basename($_GET['subaction']);
  }

  if ( !empty($subaction) ) {
    include DIR_FS_CATALOG . "includes/apps/paypal/admin/actions/$action/$subaction.php";
  }

  include DIR_FS_ADMIN . 'includes/template_top.php';
?>

<script>
var OSCOM = {
  dateNow: new Date(),
  htmlSpecialChars: function(string) {
    if ( string == null ) {
      string = '';
    }

    return $('<span />').text(string).html();
  },
  nl2br: function(string) {
    return string.replace(/\n/g, '<br>');
  },
  APP: {
    PAYPAL: {
      version: '<?= $OSCOM_PayPal->getVersion() ?>',
      versionCheckResult: <?= (defined('OSCOM_APP_PAYPAL_VERSION_CHECK')) ? '"' . OSCOM_APP_PAYPAL_VERSION_CHECK . '"' : 'undefined' ?>,
      action: '<?= $action ?>',
      doOnlineVersionCheck: false,
      canApplyOnlineUpdates: false,
      accountTypes: {
        live: <?= ($OSCOM_PayPal->hasApiCredentials('live') === true) ? 'true' : 'false' ?>,
        sandbox: <?= ($OSCOM_PayPal->hasApiCredentials('sandbox') === true) ? 'true' : 'false' ?>
      },
      versionCheck: function() {
        $.get('<?= Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'checkVersion']) ?>', function (data) {
          var versions = [];

          if ( OSCOM.APP.PAYPAL.canApplyOnlineUpdates == true ) {
            try {
              data = $.parseJSON(data);
            } catch (ex) {
            }

            if ( (typeof data == 'object') && ('rpcStatus' in data) && (data['rpcStatus'] == 1) && ('releases' in data) && (data['releases'].length > 0) ) {
              for ( var i = 0; i < data['releases'].length; i++ ) {
                versions.push(data['releases'][i]['version']);
              }
            }
          } else {
            if ( (typeof data == 'string') && (data.indexOf('rpcStatus') > -1) ) {
              var result = data.split("\n", 2);

              if ( result.length == 2 ) {
                var rpcStatus = result[0].split('=', 2);

                if ( rpcStatus[1] == 1 ) {
                  var release = result[1].split('=', 2);

                  versions.push(release[1]);
                }
              }
            }
          }

          if ( versions.length > 0 ) {
            OSCOM.APP.PAYPAL.versionCheckResult = [ OSCOM.dateNow.getDate(), Math.max.apply(Math, versions) ];

            OSCOM.APP.PAYPAL.versionCheckNotify();
          }
        });
      },
      versionCheckNotify: function() {
        if ( (typeof this.versionCheckResult[0] != 'undefined') && (typeof this.versionCheckResult[1] != 'undefined') ) {
          if ( this.versionCheckResult[1] > this.version ) {
          }
        }
      }
    }
  }
};

if ( typeof OSCOM.APP.PAYPAL.versionCheckResult != 'undefined' ) {
  OSCOM.APP.PAYPAL.versionCheckResult = OSCOM.APP.PAYPAL.versionCheckResult.split('-', 2);
}
</script>

<div class="pp-container">
  <div class="row">
    <div class="col">
      <a href="<?= Guarantor::ensure_global('Admin')->link('paypal.php') ?>"><img src="<?= Guarantor::ensure_global('Admin')->catalog('images/apps/paypal/paypal.png') ?>" /></a>
    </div>
    <div class="col" id="ppAppInfo">
      <ul class="nav justify-content-end">
        <li class="nav-item">
          <p class="navbar-text"><?= $OSCOM_PayPal->getTitle() . ' v' . $OSCOM_PayPal->getVersion() ?></p>
        </li>
        <li class="nav-item">
          <?= '<a class="nav-link" href="' . Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'info']) . '">' . $OSCOM_PayPal->getDef('app_link_info') . '</a>' ?>
        </li>
        <li class="nav-item">
          <?= '<a class="nav-link" href="' . Guarantor::ensure_global('Admin')->link('paypal.php', ['action' => 'privacy']) . '">' . $OSCOM_PayPal->getDef('app_link_privacy') . '</a>' ?>
        </li>
      </ul>
    </div>
  </div>

<?php
  if ( $OSCOM_PayPal->hasAlert() ) {
    echo $OSCOM_PayPal->getAlerts();
  }
?>

  <div>
    <?php include DIR_FS_CATALOG . 'includes/apps/paypal/admin/content/' . basename($content) ?>
  </div>
</div>

<script>
$(function() {
  if ( (OSCOM.APP.PAYPAL.action != 'update') && (OSCOM.APP.PAYPAL.action != 'info') ) {
    if ( typeof OSCOM.APP.PAYPAL.versionCheckResult == 'undefined' ) {
      OSCOM.APP.PAYPAL.doOnlineVersionCheck = true;
    } else {
      if ( typeof OSCOM.APP.PAYPAL.versionCheckResult[0] != 'undefined' ) {
        if ( OSCOM.dateNow.getDate() != OSCOM.APP.PAYPAL.versionCheckResult[0] ) {
          OSCOM.APP.PAYPAL.doOnlineVersionCheck = true;
        }
      }
    }

    if ( OSCOM.APP.PAYPAL.doOnlineVersionCheck == true ) {
      OSCOM.APP.PAYPAL.versionCheck();
    } else {
      OSCOM.APP.PAYPAL.versionCheckNotify();
    }
  }
});
</script>

<?php
  include(DIR_FS_ADMIN . 'includes/template_bottom.php');
  require(DIR_FS_ADMIN . 'includes/application_bottom.php');
?>
