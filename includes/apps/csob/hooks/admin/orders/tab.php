<?php

/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
 */

class csob_hook_admin_orders_tab {

    function __construct() {
        global $PUREHTML_CSOB;

        if (!isset($PUREHTML_CSOB) || !($PUREHTML_CSOB instanceof PUREHTML_CSOB)) {
            require_once DIR_FS_CATALOG . 'includes/apps/csob/PUREHTML_CSOB.php';
            $PUREHTML_CSOB = new PUREHTML_CSOB();
        }

        $this->_app = $PUREHTML_CSOB;

        $this->_app->loadLanguageFile('hooks/admin/orders/tab.php');
    }

    function execute() {
        if (!defined('MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID')) {
            return;
        }

        global $oID, $base_url;

        $output = 'csob_hook_admin_orders_tab';

        $status = [];

//        $ppstatus_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $oID . "' and orders_status_id = '" . (int) MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like 'Transaction ID:%' order by date_added desc limit 1");
        $ppstatus_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $oID . "' order by date_added desc limit 1");
        if (tep_db_num_rows($ppstatus_query)) {
            $ppstatus = tep_db_fetch_array($ppstatus_query);

            foreach (explode("\n", $ppstatus['comments']) as $s) {
                if (!empty($s) && (strpos($s, ':') !== false)) {
                    $entry = explode(':', $s, 2);

                    $status[trim($entry[0])] = trim($entry[1]);
                }
            }

            $status['Transaction ID'] = 122;
            $status['Pending Reason'] = 'authorization';

            if (isset($status['Transaction ID'])) {
                $order_query = tep_db_query("select o.orders_id, o.payment_method, o.currency, o.currency_value, ot.value as total from orders o, orders_total ot where o.orders_id = '" . (int) $oID . "' and o.orders_id = ot.orders_id and ot.class = 'ot_total'");
                $order = tep_db_fetch_array($order_query);

                $pp_server = (strpos(strtolower($order['payment_method']), 'sandbox') !== false) ? 'sandbox' : 'live';

                $info_button = $this->_app->drawButton($this->_app->getDef('button_details'), tep_href_link('orders.php', 'page=' . (int) ($_GET['page'] ?? 1) . '&oID=' . $oID . '&action=edit&tabaction=getTransactionDetails'), 'primary', null, true);
                $capture_button = $this->getCaptureButton($status, $order);
                $void_button = $this->getVoidButton($status, $order);
                $refund_button = $this->getRefundButton($status, $order);
//                $csob_button = $this->_app->drawButton($this->_app->getDef('button_view_at_csob'), 'https://www.' . ($pp_server == 'sandbox' ? 'sandbox.' : '') . 'csob.com/cgi-bin/webscr?cmd=_view-a-trans&id=' . $status['Transaction ID'], 'info', 'target="_blank" rel="noreferrer"', true);

                $tab_title = addslashes($this->_app->getDef('tab_title'));
                $tab_link = '#section_csob_content';

                $output = <<<EOD
<script>
$(function() {
  $('#orderTabs ul').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="{$tab_link}" role="tab">{$tab_title}</a></li>');
});
</script>

<div class="tab-pane fade" id="section_csob_content" role="tabpanel">
  {$info_button} {$capture_button} {$void_button} {$refund_button}
</div>
EOD;
            }
        }

        $client = new \PureOSC\Payment(new \PureOSC\Order($oID));

        $output .= \PureOSC\Payment::paymentStatusMeaning($client->requestStatus()) ;

        return $output;
    }

    function getCaptureButton($status, $order) {
        $output = '';

        if (($status['Pending Reason'] == 'authorization') || ($status['Payment Status'] == 'In-Progress')) {
            $v_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $order['orders_id'] . "' and orders_status_id = '" . (int) MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like '%CSOB App: Void (%' limit 1");

            if (!tep_db_num_rows($v_query)) {
                $capture_total = $this->_app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);

                $c_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $order['orders_id'] . "' and orders_status_id = '" . (int) MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like 'CSOB App: Capture (%'");
                while ($c = tep_db_fetch_array($c_query)) {
                    if (preg_match('/^CSOB App\: Capture \(([0-9\.]+)\)\n/', $c['comments'], $c_matches)) {
                        $capture_total -= $this->_app->formatCurrencyRaw($c_matches[1], $order['currency'], 1);
                    }
                }

                if ($capture_total > 0) {
                    $output .= $this->_app->drawButton($this->_app->getDef('button_dialog_capture'), '#', 'success', 'data-button="csobButtonDoCapture"', true);

                    $dialog_title = htmlspecialchars($this->_app->getDef('dialog_capture_title'));
                    $dialog_body = $this->_app->getDef('dialog_capture_body');
                    $field_amount_title = $this->_app->getDef('dialog_capture_amount_field_title');
                    $field_last_capture_title = $this->_app->getDef('dialog_capture_last_capture_field_title', ['currency' => $order['currency']]);
                    $capture_link = tep_href_link('orders.php', 'page=' . (int) ($_GET['page'] ?? 1) . '&oID=' . $order['orders_id'] . '&action=edit&tabaction=doCapture');
                    $capture_currency = $order['currency'];
                    $dialog_button_capture = addslashes($this->_app->getDef('dialog_capture_button_capture'));
                    $dialog_button_cancel = addslashes($this->_app->getDef('dialog_capture_button_cancel'));

                    $output .= <<<EOD
<div id="csob-dialog-capture" title="{$dialog_title}">
  <form id="ppCaptureForm" action="{$capture_link}" method="post">
    <p>{$dialog_body}</p>

    <p>
      <label for="ppCaptureAmount"><strong>{$field_amount_title}</strong></label>
      <input type="text" name="ppCaptureAmount" value="{$capture_total}" id="ppCaptureAmount" style="text-align: right;" />
      {$capture_currency}
    </p>

    <p id="ppPartialCaptureInfo" style="display: none;"><input type="checkbox" name="ppCatureComplete" value="true" id="ppCaptureComplete" /> <label for="ppCaptureComplete">{$field_last_capture_title}</label></p>
  </form>
</div>

<script>
$(function() {
  $('#csob-dialog-capture').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    buttons: {
      "{$dialog_button_capture}": function() {
        $('#ppCaptureForm').submit();
      },
      "{$dialog_button_cancel}": function() {
        $(this).dialog('close');
      }
    }
  });

  $('a[data-button="csobButtonDoCapture"]').click(function(e) {
    e.preventDefault();

    $('#csob-dialog-capture').dialog('open');
  });

  (function() {
    var ppCaptureTotal = {$capture_total};

    $('#ppCaptureAmount').keyup(function() {
      if (this.value != this.value.replace(/[^0-9\.]/g, '')) {
        this.value = this.value.replace(/[^0-9\.]/g, '');
      }

      if ( this.value < ppCaptureTotal ) {
        $('#ppCaptureVoidedValue').text((ppCaptureTotal - this.value).toFixed(2));
        $('#ppPartialCaptureInfo').show();
      } else {
        $('#ppPartialCaptureInfo').hide();
      }
    });
  })();
});
</script>
EOD;
                }
            }
        }

        return $output;
    }

    function getVoidButton($status, $order) {
        $output = '';

        if ($status['Pending Reason'] == 'authorization') {
            $v_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $order['orders_id'] . "' and orders_status_id = '" . (int) MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like '%CSOB App: Void (%' limit 1");

            if (!tep_db_num_rows($v_query)) {
                $capture_total = $this->_app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);

                $c_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $order['orders_id'] . "' and orders_status_id = '" . (int) MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like 'CSOB App: Capture (%'");
                while ($c = tep_db_fetch_array($c_query)) {
                    if (preg_match('/^CSOB App\: Capture \(([0-9\.]+)\)\n/', $c['comments'], $c_matches)) {
                        $capture_total -= $this->_app->formatCurrencyRaw($c_matches[1], $order['currency'], 1);
                    }
                }

                if ($capture_total > 0) {
                    $output .= $this->_app->drawButton($this->_app->getDef('button_dialog_void'), '#', 'warning', 'data-button="csobButtonDoVoid"', true);

                    $dialog_title = htmlspecialchars($this->_app->getDef('dialog_void_title'));
                    $dialog_body = $this->_app->getDef('dialog_void_body');
                    $void_link = tep_href_link('orders.php', 'page=' . (int) ($_GET['page'] ?? 1) . '&oID=' . $order['orders_id'] . '&action=edit&tabaction=doVoid');
                    $dialog_button_void = addslashes($this->_app->getDef('dialog_void_button_void'));
                    $dialog_button_cancel = addslashes($this->_app->getDef('dialog_void_button_cancel'));

                    $output .= <<<EOD
<div id="csob-dialog-void" title="{$dialog_title}">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>{$dialog_body}</p>
</div>

<script>
$(function() {
  $('#csob-dialog-void').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    buttons: {
      "{$dialog_button_void}": function() {
        window.location = '{$void_link}';
      },
      "{$dialog_button_cancel}": function() {
        $(this).dialog('close');
      }
    }
  });

  $('a[data-button="csobButtonDoVoid"]').click(function(e) {
    e.preventDefault();

    $('#csob-dialog-void').dialog('open');
  });
});
</script>
EOD;
                }
            }
        }

        return $output;
    }

    function getRefundButton($status, $order) {
        $output = '';

        $status['Payment Status'] = 'Completed';

        $tids = [];

        $ppr_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int) $_GET['oID'] . "' and orders_status_id = '" . (int) MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like 'CSOB App: %' order by date_added desc");
        if (tep_db_num_rows($ppr_query)) {
            while ($ppr = tep_db_fetch_array($ppr_query)) {
                if (strpos($ppr['comments'], 'CSOB App: Refund') !== false) {
                    preg_match('/Parent ID\: ([A-Za-z0-9]+)$/', $ppr['comments'], $ppr_matches);

                    $tids[$ppr_matches[1]]['Refund'] = true;
                } elseif (strpos($ppr['comments'], 'CSOB App: Capture') !== false) {
                    preg_match('/^CSOB App\: Capture \(([0-9\.]+)\).*Transaction ID\: ([A-Za-z0-9]+)/s', $ppr['comments'], $ppr_matches);

                    $tids[$ppr_matches[2]]['Amount'] = $ppr_matches[1];
                }
            }
        } elseif ($status['Payment Status'] == 'Completed') {
            $tids[$status['Transaction ID']]['Amount'] = $this->_app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);
        }

        $can_refund = false;

        foreach ($tids as $value) {
            if (!isset($value['Refund'])) {
                $can_refund = true;
                break;
            }
        }

        if ($can_refund === true) {
            $output .= $this->_app->drawButton($this->_app->getDef('button_dialog_refund'), '#', 'error', 'data-button="csobButtonRefundTransaction"', true);

            $dialog_title = htmlspecialchars($this->_app->getDef('dialog_refund_title'));
            $dialog_body = $this->_app->getDef('dialog_refund_body');
            $refund_link = tep_href_link('orders.php', 'page=' . (int) ($_GET['page'] ?? 1) . '&oID=' . (int) $_GET['oID'] . '&action=edit&tabaction=refundTransaction');
            $dialog_button_refund = addslashes($this->_app->getDef('dialog_refund_button_refund'));
            $dialog_button_cancel = addslashes($this->_app->getDef('dialog_refund_button_cancel'));

            $refund_fields = '';

            $counter = 0;

            foreach ($tids as $key => $value) {
                $refund_fields .= '<p><input type="checkbox" name="ppRefund[]" value="' . $key . '" id="ppRefundPartial' . $counter . '"' . (isset($value['Refund']) ? ' disabled="disabled"' : '') . ' /> <label for="ppRefundPartial' . $counter . '"' . (isset($value['Refund']) ? ' style="text-decoration: line-through;"' : '') . '>' . $this->_app->getDef('dialog_refund_payment_title', ['amount' => $value['Amount']]) . '</label></p>';

                $counter++;
            }

            $output .= <<<EOD
<div id="csob-dialog-refund" title="{$dialog_title}">
  <form id="ppRefundForm" action="{$refund_link}" method="post">
    <p>{$dialog_body}</p>

    {$refund_fields}
  </form>
</div>

<script>
$(function() {
  $('#csob-dialog-refund').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    buttons: {
      "{$dialog_button_refund}": function() {
        $('#ppRefundForm').submit();
      },
      "{$dialog_button_cancel}": function() {
        $(this).dialog('close');
      }
    }
  });

  $('a[data-button="csobButtonRefundTransaction"]').click(function(e) {
    e.preventDefault();

    $('#csob-dialog-refund').dialog('open');
  });
});
</script>
EOD;
        }

        return $output;
    }

}

?>
