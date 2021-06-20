<?php
/*
  $Id: articles_xsell.php,v 1.1 2006/03/07 08:42:49 tni001 Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2009 http://www.oscommerce-solution.com

  cross.sale.php created By Isaac Mualem im@imwebdesigning.com

  Modified by Andrew Edmond (osc@aravia.com)
  Sept 16th, 2002

  Further Modified by Rob Anderson 12 Dec 03

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require('includes/template_top.php');
?>
    <div class="col">
      <h1 class="display-4 mb-2"><?php echo HEADING_TITLE; ?></h1>
    </div>

<table border="2" width="100%" cellspacing="0" cellpadding="2">
  <tr>
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<!-- body_text //-->
    <td width="100%" valign="top">
      <!-- Start of cross sale //-->

      <table width="100%" border="0" cellpadding="0"  cellspacing="0">
        <tr><td align=left>
        <?php
    /* general_db_conct($query) function */
    /* calling the function:  list ($test_a, $test_b) = general_db_conct($query); */
    function general_db_conct($query_1) {
      $result_1 = tep_db_query($query_1);
      $num_of_rows = tep_db_num_rows($result_1);
      $a_to_pass = $b_to_pass = $c_to_pass = $d_to_pass = $e_to_pass = $f_to_pass = $g_to_pass = $h_to_pass = $i_to_pass = $j_to_pass = $k_to_pass = $l_to_pass = $m_to_pass = $n_to_pass = $o_to_pass = [];
      for ($i=0; $i < $num_of_rows; ++$i) {
        $fields = mysqli_fetch_row($result_1);
        $a_to_pass[$i]= ($fields[$y=0] ?? '');
        $b_to_pass[$i]= ($fields[++$y] ?? '');
        $c_to_pass[$i]= ($fields[++$y] ?? '');
        $d_to_pass[$i]= ($fields[++$y] ?? '');
        $e_to_pass[$i]= ($fields[++$y] ?? '');
        $f_to_pass[$i]= ($fields[++$y] ?? '');
        $g_to_pass[$i]= ($fields[++$y] ?? '');
        $h_to_pass[$i]= ($fields[++$y] ?? '');
        $i_to_pass[$i]= ($fields[++$y] ?? '');
        $j_to_pass[$i]= ($fields[++$y] ?? '');
        $k_to_pass[$i]= ($fields[++$y] ?? '');
        $l_to_pass[$i]= ($fields[++$y] ?? '');
        $m_to_pass[$i]= ($fields[++$y] ?? '');
        $n_to_pass[$i]= ($fields[++$y] ?? '');
        $o_to_pass[$i]= ($fields[++$y] ?? '');
      }
      return array($a_to_pass,$b_to_pass,$c_to_pass,$d_to_pass,$e_to_pass,$f_to_pass,$g_to_pass,$h_to_pass,$i_to_pass,$j_to_pass,$k_to_pass,$l_to_pass,$m_to_pass,$n_to_pass,$o_to_pass);
    }//end of function

        // first major piece of the program
        // we have no instructions, so just dump a full list of products and their status for cross selling

    if (! isset($_GET['add_related_article_ID']) ) {
        $query = "select a.articles_id, ad.articles_name, ad.articles_description, ad.articles_url from articles a, articles_description ad where ad.articles_id = a.articles_id and ad.language_id = '" . (int)$languages_id . "' order by ad.articles_name";
        list ($articles_id, $articles_name, $articles_description, $articles_url) = general_db_conct($query);
    ?>

      <table border="0" width="100%" cellspacing="1" cellpadding="3" bgcolor="#CCCCCC">
        <tr class="dataTableHeadingRow">
          <td class="dataTableHeadingContent" align="center" nowrap>ID</td>
          <td class="dataTableHeadingContent"><?php echo HEADING_ARTICLE_NAME; ?></td>
          <td class="dataTableHeadingContent" nowrap><?php echo HEADING_CROSS_ASSOCIATION; ?></td>
          <td class="dataTableHeadingContent" colspan="3" align="center" nowrap><?php echo HEADING_CROSS_SELL_ACTIONS; ?></td>
        </tr>
         <?php
         $num_of_articles = sizeof($articles_id);
          for ($i=0; $i < $num_of_articles; ++$i) {
             /* now we will query the DB for existing related items */
             $query = "select pd.products_name, ax.xsell_id from articles_xsell ax, products_description pd where pd.products_id = ax.xsell_id and ax.articles_id ='".$articles_id[$i]."' and pd.language_id = '" . (int)$languages_id . "' order by ax.sort_order";
             list ($Related_items, $xsell_ids) = general_db_conct($query);

             echo "<tr bgcolor='#FFFFFF'>";
             echo "<td class=\"dataTableContent\" valign=\"top\">&nbsp;".$articles_id[$i]."&nbsp;</td>\n";
             echo "<td class=\"dataTableContent\" valign=\"top\">&nbsp;".$articles_name[$i]."&nbsp;</td>\n";
             if ($Related_items) {
               echo "<td  class=\"dataTableContent\"><ol>";
               foreach ($Related_items as $display)
                 echo '<li>'. $display .'&nbsp;';
                 echo"</ol></td>\n";
             }
             else
                 echo "<td class=\"dataTableContent\">--</td>\n";

             echo '<td class="dataTableContent"  valign="top">&nbsp;<a href="' . tep_href_link('articles_xsell.php', 'add_related_article_ID=' . $articles_id[$i], $request_type) . '">Add/Remove</a></td>';

             if (count($Related_items)>1)
             {
               echo '<td class="dataTableContent" valign="top">&nbsp;<a href="' . tep_href_link('articles_xsell.php', 'sort=1&add_related_article_ID=' . $articles_id[$i], $request_type) . '">' . TEXT_SORT . '</a>&nbsp;</td>';
             } else {
               echo "<td class=\"dataTableContent\" valign=top align=center>--</td>";
             }
             echo "</tr>\n";
             unset($Related_items);
          }
          ?>

      </table>
      <?php
      }   // the end of -> if (!$_POST['add_related_article_ID'])


    if (is_array($_POST) && ! isset($_GET['sort'])) {
    
      if (isset($_POST['run_update']) && $_POST['run_update'] == true) {
          if (isset($_POST['xsell_id'])) {
              $ids = ' ( ';
              for ($x = 0; $x < count($_POST['xsell_id']); ++$x) {
                  $ids .= ' xsell_id = ' . (int)$_POST['xsell_id'][$x] . ' or ';
              }
              $ids = substr($ids, 0, -3);
              $ids .= ' ) ';

              $ids = (count($_POST['xsell_id']) ? ' and ' . $ids : ''); //clear if list not present

              $query ="DELETE FROM articles_xsell WHERE articles_id = '".(int)$_POST['add_related_article_ID']."'" . $ids;
              if (!tep_db_query($query)) {
                  exit(TEXT_NO_DELETE);
              }  
          } else {

             // tep_db_query("DELETE FROM articles_xsell WHERE articles_id = '".(int)$_POST['add_related_article_ID']."'" . $ids);           
          } 
      } elseif (isset($_POST['xsell_id'])) {
          $id = $_GET['add_related_article_ID'];
          foreach ($_POST['xsell_id'] as $temp) {
              $query = "INSERT INTO articles_xsell VALUES ('', '" . (int)$id . "', '" . (int)$temp . "', '1')";
              if (! tep_db_query($query)) {
                  exit(TEXT_NO_INSERT);
              }   
          }
      }
      
      ?>
          <tr>
            <td class="main"><?php echo TEXT_DATABASE_UPDATED; ?></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main">
            <?php 
            if (isset($_POST['add_related_article_ID'])) {
               echo sprintf(TEXT_LINK_SORT_PRODUCTS, tep_href_link('articles_xsell.php', '&sort=1&add_related_article_ID=' . (int)$_POST['add_related_article_ID'])); 
            }
            ?>
            </td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo sprintf(TEXT_LINK_MAIN_PAGE, tep_href_link('articles_xsell.php', '', $request_type)); ?></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
    <?php

//    if ($_POST[xsell_id])
    //  echo '<a href="' . tep_href_link('articles_xsell.php', 'sort=1&add_related_article_ID=' . $_POST[add_related_article_ID], $request_type) . '">Click here to sort (top to bottom) the added cross sale</a>' . "\n";
    }

    if (isset($_GET['add_related_article_ID']) && ! tep_not_null($_POST) && ! isset($_GET['sort'])) {
   
        echo tep_draw_form('goto', "articles_xsell.php", '', 'get') . tep_hide_session_id();
        echo '<input type="hidden" name="add_related_article_ID" value="'.(int)$_GET['add_related_article_ID'].'" />';
        echo SELECT_CATEGORY ."&nbsp;:" . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $_GET['add_related_article_ID'], 'onChange="this.form.submit();"');
        echo '</form>';
        
        if (isset($_GET['cPath'])) {
        ?>

            <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#CCCCCC">
               <form action="<?php tep_href_link('articles_xsell.php', '', $request_type); ?>" method="post">
               <?php //echo tep_draw_form('form_details', "articles_xsell.php", '', 'post') . tep_hide_session_id(); ?>
                <tr class="dataTableHeadingRow">
                  <td class="dataTableHeadingContent"><input type="checkbox" id="selectall" /></td>
                  <td class="dataTableHeadingContent" nowrap>ID</td>
                  <td class="dataTableHeadingContent"><?php echo HEADING_PRODUCT_NAME; ?></td>
                </tr>

                <?php

            $query = "select p.products_id, pd.products_name, pd.products_description, pd.products_url from products p inner join products_description pd on p.products_id = pd.products_id inner join products_to_categories p2c on p.products_id = p2c.products_id where p2c.categories_id='".tep_db_input((int)$_GET['cPath'])."' and pd.language_id = '" . (int)$languages_id . "' order by pd.products_name ";

            list ($products_id, $products_name, $products_description, $products_url  ) = general_db_conct($query);
            $num_of_products = sizeof($products_id);
            $query = "select * from articles_xsell where articles_id = '".(int)$_GET['add_related_article_ID']."'";
            list ($ID_PR, $products_id_pr, $xsell_id_pr) = general_db_conct($query);
            $run_update=false; // set to false to insert new entry in the DB
 
            //echo tep_draw_hidden_field("number_of_entries", $num_of_products); 

            for ($i=0; $i < $num_of_products; ++$i) {
            ?>
              <tr bgcolor="#FFFFFF"  onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
                <?php /* this is to see it it is in the DB */
                $checked = '';
                if ($xsell_id_pr) {
                    foreach ($xsell_id_pr as $compare_checked) {
                        if ($products_id[$i]===$compare_checked) {
                            $checked = "checked=checked";
                            $run_update=true;
                            break;
                        }
                    }
                }
                echo tep_draw_hidden_field("delete_$i", $products_id[$i], 'id="delete-product-'.$i . '"'); 
                ?>
                  <td class="dataTableContent"><input size="20" size="20" class="checkbox1" name="xsell_id[]" type="checkbox" <?php echo $checked; ?> value="<?php echo $products_id[$i]; ?>" id="box_<?php echo $i; ?>" onChange="CheckedStatus('<?php echo $i; ?>');"></td>                  
                <?php                
             
                echo '<td class="dataTableContent" align="center">' . $products_id[$i] . '</td>' .
                     '<td class="dataTableContent">' . $products_name[$i] . '</td>';
                echo '</tr>';
            }
            ?>

            <tr>
              <td colspan="3" style="width:100%; background-color:#CCC;">
              <?php

              // list also those products not in current category
              $myquery = "SELECT ax.xsell_id AS nid FROM articles_xsell ax, products_to_categories p2c WHERE ax.articles_id='".(int)$_REQUEST['add_related_article_ID']."' AND ax.xsell_id=p2c.products_id AND categories_id!='".tep_db_input((int)$_GET['cPath'])."'";
              $myids_query = tep_db_query($myquery);
              ?> 
              <div style="display:none"> 
              <?php
                while ($tempid = tep_db_fetch_array($myids_query)) {
                   echo  '<input type="checkbox" name="xsell_id[]" value="'.$tempid['nid'].'" checked>';
                }
              ?>
              </div>
                <input type="hidden" name="run_update" value="<?php echo $run_update; ?>">
                <input type="hidden" name="add_related_article_ID" value="<?php echo (int)$_GET['add_related_article_ID']; ?>">
                <?php  echo  tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-save', null, 'primary', null, 'btn-success btn-block btn-lg') . 
                             tep_draw_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('articles_xsell.php')); ?>
              </td>
            </tr>
           </form>
          </table>
        <?php }
        }
        // sort routines
    if (isset($_GET['sort']) && $_GET['sort'] == 1) {
      //  first lets take care of the DB update.
      $run_once=0;

      if (tep_not_null($_POST)) {
        foreach ($_POST as $key_a => $value_a) {
          //tep_db_connect();
          $query = "UPDATE articles_xsell SET sort_order = '". tep_db_input($value_a) . "' WHERE xsell_id= '" . (int)$key_a . "'";
          if ($value_a != 'Update') {
              if (!tep_db_query($query)) {
                  exit(TEXT_NO_UPDATE);
              } elseif ($run_once==0) { ?>
                  <tr>
                    <td class="main"><?php echo TEXT_DATABASE_UPDATED; ?></td>
                  </tr>
                  <tr>
                    <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                  </tr>
                  <tr>
                    <td class="main"><?php echo sprintf(TEXT_LINK_MAIN_PAGE, tep_href_link('articles_xsell.php', '', $request_type)); ?></td>
                  </tr>
                  <tr>
                    <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                  </tr>
              <?php
              $run_once++;
              }
          }
        }// end of foreach.
      }
    ?>
    <form method="post" action="<?php tep_href_link('articles_xsell.php', 'sort=1&add_related_article_ID=' . $_POST['add_related_article_ID'], $request_type); ?>">
              <table cellpadding="3" cellspacing="1" bgcolor="#CCCCCC" border="0">
                <tr class="dataTableHeadingRow">
                  <td class="dataTableHeadingContent">ID</td>
                  <td class="dataTableHeadingContent"><?php echo HEADING_PRODUCT_NAME; ?></td>
                  <td class="dataTableHeadingContent"><?php echo HEADING_PRODUCT_ORDER; ?></td>
                </tr>
                <?php
                $query = "select * from articles_xsell where articles_id = '".(int)$_GET['add_related_article_ID']."'";
                list ($ID_PR, $products_id_pr, $xsell_id_pr, $order_PR) = general_db_conct($query);
                $ordering_size = sizeof($ID_PR);
                for ($i=0;$i<$ordering_size;++$i) {

                    $query = "select p.products_id, pd.products_name, pd.products_description, pd.products_url from products p, products_description pd where pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = '" . tep_db_input($xsell_id_pr[$i]) . "'";

                    list ($products_id, $products_name, $products_description, $products_url) = general_db_conct($query);

                    ?>
                    <tr class="dataTableContentRow" bgcolor="#FFFFFF">
                      <td class="dataTableContent"><?php echo $products_id[0]; ?></td>
                      <td class="dataTableContent"><?php echo $products_name[0]; ?></td>
                      <td class="dataTableContent" align="center"><select name="<?php echo $products_id[0]; ?>">
                      <?php 
                        for ($y=1;$y<=$ordering_size;++$y) {
                            echo "<option value=\"$y\"";
                            if (!(strcmp($y, "$order_PR[$i]"))) {echo "SELECTED";}
                                echo ">$y</option>";
                            }
                      ?>
                      </select></td>
                    </tr>
                    <?php } // the end of foreach
                    ?>
                <tr>
                  <td>&nbsp;</td>
                  <td bgcolor="#CCCCCC"><?php echo tep_image_submit('button_save.gif', IMAGE_SAVE) . '&nbsp;&nbsp;<a href="' . tep_href_link('articles_xsell.php') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
                  <td>&nbsp;</td>
                </tr>
              </table>
            </form>

            <?php }?>

          </td>
        </tr>
    </table>
    <!-- End of cross sale //-->
    </td>
</tr></table>
<script type="text/javascript">
$(document).ready(function() {
    $('#selectall').click(function(event) {  //on click 
        if(this.checked) { // check select status
            $('.checkbox1').each(function() { //loop through each checkbox
                this.checked = true;  //select all checkboxes with class "checkbox1"               
            });
        }else{
            $('.checkbox1').each(function() { //loop through each checkbox
                this.checked = false; //deselect all checkboxes with class "checkbox1"                       
            });         
        }
    });
});
    
function CheckedStatus(id) {   
 var status = $("#box_"+id).prop("checked"); 
 if (! status) {
   //$("#delete-product-"+id).val('delete');
 } 
}    
</script>
<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
