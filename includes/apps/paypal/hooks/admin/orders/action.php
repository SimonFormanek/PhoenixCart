<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  if ( !class_exists('OSCOM_PayPal') ) {
    include DIR_FS_CATALOG . 'includes/apps/paypal/OSCOM_PayPal.php';
  }

  class paypal_hook_admin_orders_action {

    function __construct() {
      global $OSCOM_PayPal;

      if ( !(($OSCOM_PayPal ?? null) instanceof OSCOM_PayPal) ) {
        $OSCOM_PayPal = new OSCOM_PayPal();
      }

      $this->_app = $OSCOM_PayPal;

      $this->_app->loadLanguageFile('hooks/admin/orders/action.php');
    }

    function execute() {
      if ( isset($_GET['tabaction']) ) {
        $ppstatus_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int)$_GET['oID'] . "' and orders_status_id = '" . (int)MODULE_PAYMENT_CSOB_PROCESSING_ORDER_STATUS_ID . "' and comments like '%Transaction ID:%' order by date_added limit 1");
        if ( tep_db_num_rows($ppstatus_query) ) {
          $ppstatus = tep_db_fetch_array($ppstatus_query);

          $pp = [];

          foreach ( explode("\n", $ppstatus['comments']) as $s ) {
            if ( !empty($s) && (strpos($s, ':') !== false) ) {
              $entry = explode(':', $s, 2);

              $pp[trim($entry[0])] = trim($entry[1]);
            }
          }

          if ( isset($pp['Transaction ID']) ) {
            $o_query = tep_db_query("select o.orders_id, o.payment_method, o.currency, o.currency_value, ot.value as total from orders o, orders_total ot where o.orders_id = '" . (int)$_GET['oID'] . "' and o.orders_id = ot.orders_id and ot.class = 'ot_total'");
            $o = tep_db_fetch_array($o_query);

            switch ( $_GET['tabaction'] ) {
              case 'getTransactionDetails':
                $this->getTransactionDetails($pp, $o);
                break;

              case 'doCapture':
                $this->doCapture($pp, $o);
                break;

              case 'doVoid':
                $this->doVoid($pp, $o);
                break;

              case 'refundTransaction':
                $this->refundTransaction($pp, $o);
                break;
            }

            tep_redirect(tep_href_link('orders.php', 'page=' . $_GET['page'] . '&oID=' . $_GET['oID'] . '&action=edit#section_status_history_content'));
          }
        }
      }
    }

    function getTransactionDetails($comments, $order) {
      global $messageStack;

      $result = null;

      if ( !isset($comments['Gateway']) ) {
        $response = $this->_app->getApiResult('APP', 'GetTransactionDetails', ['TRANSACTIONID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $result = 'Transaction ID: ' . htmlspecialchars($response['TRANSACTIONID']) . "\n" .
                    'Payer Status: ' . htmlspecialchars($response['PAYERSTATUS']) . "\n" .
                    'Address Status: ' . htmlspecialchars($response['ADDRESSSTATUS']) . "\n" .
                    'Payment Status: ' . htmlspecialchars($response['PAYMENTSTATUS']) . "\n" .
                    'Payment Type: ' . htmlspecialchars($response['PAYMENTTYPE']) . "\n" .
                    'Pending Reason: ' . htmlspecialchars($response['PENDINGREASON']);
        }
      } elseif ( $comments['Gateway'] == 'Payflow' ) {
        $response = $this->_app->getApiResult('APP', 'PayflowInquiry', ['ORIGID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
          $result = 'Transaction ID: ' . htmlspecialchars($response['ORIGPNREF']) . "\n" .
                    'Gateway: Payflow' . "\n";

          $pending_reason = $response['TRANSSTATE'];
          $payment_status = null;

          switch ( $response['TRANSSTATE'] ) {
            case '3':
              $pending_reason = 'authorization';
              $payment_status = 'Pending';
              break;

            case '4':
              $pending_reason = 'other';
              $payment_status = 'In-Progress';
              break;

            case '6':
              $pending_reason = 'scheduled';
              $payment_status = 'Pending';
              break;

            case '8':
            case '9':
              $pending_reason = 'None';
              $payment_status = 'Completed';
              break;
          }

          if ( isset($payment_status) ) {
            $result .= 'Payment Status: ' . htmlspecialchars($payment_status) . "\n";
          }

          $result .= 'Pending Reason: ' . htmlspecialchars($pending_reason) . "\n";

          switch ( $response['AVSADDR'] ) {
            case 'Y':
              $result .= 'AVS Address: Match' . "\n";
              break;

            case 'N':
              $result .= 'AVS Address: No Match' . "\n";
              break;
          }

          switch ( $response['AVSZIP'] ) {
            case 'Y':
              $result .= 'AVS ZIP: Match' . "\n";
              break;

            case 'N':
              $result .= 'AVS ZIP: No Match' . "\n";
              break;
          }

          switch ( $response['IAVS'] ) {
            case 'Y':
              $result .= 'IAVS: International' . "\n";
              break;

            case 'N':
              $result .= 'IAVS: USA' . "\n";
              break;
          }

          switch ( $response['CVV2MATCH'] ) {
            case 'Y':
              $result .= 'CVV2: Match' . "\n";
              break;

            case 'N':
              $result .= 'CVV2: No Match' . "\n";
              break;
          }
        }
      }

      if ( !empty($result) ) {
        $sql_data_array = [
          'orders_id' => (int)$order['orders_id'],
          'orders_status_id' => OSCOM_APP_PAYPAL_TRANSACTIONS_ORDER_STATUS_ID,
          'date_added' => 'NOW()',
          'customer_notified' => '0',
          'comments' => $result,
        ];

        tep_db_perform('orders_status_history', $sql_data_array);

        $messageStack->add_session($this->_app->getDef('ms_success_getTransactionDetails'), 'success');
      } else {
        $messageStack->add_session($this->_app->getDef('ms_error_getTransactionDetails'), 'error');
      }
    }

    function doCapture($comments, $order) {
      global $messageStack;

      $pass = false;

      $capture_total = $capture_value = $this->_app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);
      $capture_final = true;

      if ( $this->_app->formatCurrencyRaw($_POST['ppCaptureAmount'], $order['currency'], 1) < $capture_value ) {
        $capture_value = $this->_app->formatCurrencyRaw($_POST['ppCaptureAmount'], $order['currency'], 1);
        $capture_final = (isset($_POST['ppCatureComplete']) && ($_POST['ppCatureComplete'] === 'true'));
      }

      if ( !isset($comments['Gateway']) ) {
        $params = [
          'AUTHORIZATIONID' => $comments['Transaction ID'],
          'AMT' => $capture_value,
          'CURRENCYCODE' => $order['currency'],
          'COMPLETETYPE' => ($capture_final === true) ? 'Complete' : 'NotComplete',
        ];

        $response = $this->_app->getApiResult('APP', 'DoCapture', $params, (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $transaction_id = $response['TRANSACTIONID'];

          $pass = true;
        }
      } elseif ( $comments['Gateway'] == 'Payflow' ) {
        $params = [
          'ORIGID' => $comments['Transaction ID'],
          'AMT' => $capture_value,
          'CAPTURECOMPLETE' => ($capture_final === true) ? 'Y' : 'N',
        ];

        $response = $this->_app->getApiResult('APP', 'PayflowCapture', $params, (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
          $transaction_id = $response['PNREF'];

          $pass = true;
        }
      }

      if ( $pass === true ) {
        $result = 'PayPal App: Capture (' . $capture_value . ')' . "\n";

        if ( ($capture_value < $capture_total) && ($capture_final === true) ) {
          $result .= 'PayPal App: Void (' . $this->_app->formatCurrencyRaw($capture_total - $capture_value, $order['currency'], 1) . ')' . "\n";
        }

        $result .= 'Transaction ID: ' . htmlspecialchars($transaction_id);

        $sql_data_array = [
          'orders_id' => (int)$order['orders_id'],
          'orders_status_id' => OSCOM_APP_PAYPAL_TRANSACTIONS_ORDER_STATUS_ID,
          'date_added' => 'NOW()',
          'customer_notified' => '0',
          'comments' => $result,
        ];

        tep_db_perform('orders_status_history', $sql_data_array);

        $messageStack->add_session($this->_app->getDef('ms_success_doCapture'), 'success');
      } else {
        $messageStack->add_session($this->_app->getDef('ms_error_doCapture'), 'error');
      }
    }

    function doVoid($comments, $order) {
      global $messageStack;

      $pass = false;

      if ( !isset($comments['Gateway']) ) {
        $response = $this->_app->getApiResult('APP', 'DoVoid', ['AUTHORIZATIONID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
          $pass = true;
        }
      } elseif ( $comments['Gateway'] == 'Payflow' ) {
        $response = $this->_app->getApiResult('APP', 'PayflowVoid', ['ORIGID' => $comments['Transaction ID']], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

        if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
          $pass = true;
        }
      }

      if ( $pass === true ) {
        $capture_total = $this->_app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);

        $c_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int)$order['orders_id'] . "' and orders_status_id = '" . (int)OSCOM_APP_PAYPAL_TRANSACTIONS_ORDER_STATUS_ID . "' and comments like 'PayPal App: Capture (%'");
        while ( $c = tep_db_fetch_array($c_query) ) {
          if ( preg_match('/^PayPal App\: Capture \(([0-9\.]+)\)\n/', $c['comments'], $c_matches) ) {
            $capture_total -= $this->_app->formatCurrencyRaw($c_matches[1], $order['currency'], 1);
          }
        }

        $result = 'PayPal App: Void (' . $capture_total . ')';

        $sql_data_array = [
          'orders_id' => (int)$order['orders_id'],
          'orders_status_id' => OSCOM_APP_PAYPAL_TRANSACTIONS_ORDER_STATUS_ID,
          'date_added' => 'NOW()',
          'customer_notified' => '0',
          'comments' => $result,
        ];

        tep_db_perform('orders_status_history', $sql_data_array);

        $messageStack->add_session($this->_app->getDef('ms_success_doVoid'), 'success');
      } else {
        $messageStack->add_session($this->_app->getDef('ms_error_doVoid'), 'error');
      }
    }

    function refundTransaction($comments, $order) {
      global $messageStack;

      if ( isset($_POST['ppRefund']) ) {
        $tids = [];

        $ppr_query = tep_db_query("select comments from orders_status_history where orders_id = '" . (int)$order['orders_id'] . "' and orders_status_id = '" . (int)OSCOM_APP_PAYPAL_TRANSACTIONS_ORDER_STATUS_ID . "' and comments like 'PayPal App: %' order by date_added desc");
        if ( tep_db_num_rows($ppr_query) ) {
          while ( $ppr = tep_db_fetch_array($ppr_query) ) {
            if ( strpos($ppr['comments'], 'PayPal App: Refund') !== false ) {
              preg_match('/Parent ID\: ([A-Za-z0-9]+)$/', $ppr['comments'], $ppr_matches);

              $tids[$ppr_matches[1]]['Refund'] = true;
            } elseif ( strpos($ppr['comments'], 'PayPal App: Capture') !== false ) {
              preg_match('/^PayPal App\: Capture \(([0-9\.]+)\).*Transaction ID\: ([A-Za-z0-9]+)/s', $ppr['comments'], $ppr_matches);

              $tids[$ppr_matches[2]]['Amount'] = $ppr_matches[1];
            }
          }
        } elseif ( $comments['Payment Status'] == 'Completed' ) {
          $tids[$comments['Transaction ID']]['Amount'] = $this->_app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);
        }

        $rids = [];

        foreach ( $_POST['ppRefund'] as $id ) {
          if ( isset($tids[$id]) && !isset($tids[$id]['Refund']) ) {
            $rids[] = $id;
          }
        }

        foreach ( $rids as $id ) {
          $pass = false;

          if ( !isset($comments['Gateway']) ) {
            $response = $this->_app->getApiResult('APP', 'RefundTransaction', ['TRANSACTIONID' => $id], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

            if ( in_array($response['ACK'], ['Success', 'SuccessWithWarning']) ) {
              $transaction_id = $response['REFUNDTRANSACTIONID'];

              $pass = true;
            }
          } elseif ( $comments['Gateway'] == 'Payflow' ) {
            $response = $this->_app->getApiResult('APP', 'PayflowRefund', ['ORIGID' => $id], (strpos($order['payment_method'], 'Sandbox') === false) ? 'live' : 'sandbox');

            if ( isset($response['RESULT']) && ($response['RESULT'] == '0') ) {
              $transaction_id = $response['PNREF'];

              $pass = true;
            }
          }

          if ( $pass === true ) {
            $result = 'PayPal App: Refund (' . $tids[$id]['Amount'] . ')' . "\n" .
                      'Transaction ID: ' . htmlspecialchars($transaction_id) . "\n" .
                      'Parent ID: ' . htmlspecialchars($id);

            $sql_data_array = [
              'orders_id' => (int)$order['orders_id'],
              'orders_status_id' => OSCOM_APP_PAYPAL_TRANSACTIONS_ORDER_STATUS_ID,
              'date_added' => 'NOW()',
              'customer_notified' => '0',
              'comments' => $result,
            ];

            tep_db_perform('orders_status_history', $sql_data_array);

            $messageStack->add_session($this->_app->getDef('ms_success_refundTransaction', ['refund_amount' => $tids[$id]['Amount']]), 'success');
          } else {
            $messageStack->add_session($this->_app->getDef('ms_error_refundTransaction', ['refund_amount' => $tids[$id]['Amount']]), 'error');
          }
        }
      }
    }
  }
