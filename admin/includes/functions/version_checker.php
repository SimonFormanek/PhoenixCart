<?php
/*
  $Id: version checker, v 1.4 
  Originally Created by: Jack_mcs - http://www.oscommerce-solution.com
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce
  Portions Copyright 2009 oscommerce-solution.com

  Released under the GNU General Public License
*/

function AnnounceVersion($contribPath, $currentVersion, $contribName) {
  $lines = array();
  $lines = GetFileArray($contribPath);

  if (tep_not_null($lines)) {
    $result = CheckContributionVersion($lines, $currentVersion, $contribName);
    switch ($result)     {
      case 0: return sprintf(TEXT_VERSION_LATEST, $currentVersion); break;
      case 'ERROR_NO_MATCH_FOUND': return TEXT_VERSION_NO_MATCH_FOUND; break;     
      default: if ($result > 0) {
       $plural = ($result > 1) ? sprintf(TEXT_ARE, $result) : sprintf(TEXT_IS, $result);
       return '<a style="font-weight: bold; color: red;" target="_blank" href="' . $contribPath . '">' . sprintf(TEXT_VERSION_FOUND_UPDATE, $plural) . '</a>';
       break;
      }
    }
  }
  return '';
}

function CheckContributionVersion($lines, $currentVersion, $contribName) {
 
    foreach ($lines as $line) {
        if (strpos($line, 'Information Pages SEO V') !== FALSE) {
            preg_match('/title":"(.*)"/', $line, $matches, PREG_OFFSET_CAPTURE);

            if (is_array($matches[1])) {
                $parts = explode(',', $matches[1][0]);
                $ver = str_replace('"', '', $parts[0]);
                 
                $name_lgth = strlen($contribName);
                $v1 = (float)substr($ver, $name_lgth);           
                $v2 = (float)substr($currentVersion, $name_lgth);   
                if ($v2 <= $v1) return ((($v1 - $v2)*10)); 
            }
        }
    }

    return 'ERROR_NO_MATCH_FOUND';
}

function FindOccurences($string, $find) {
    $lastPos = 0;
    $positions = array();

    while (($lastPos = strpos($string, $find, $lastPos)) !== false) { 
        $positions[] = $lastPos;
        $lastPos = $lastPos + strlen($find);
    }
    return $positions;
}

if (! function_exists('GetFileArray')) {
  function GetFileArray($path) {
    $lines = array();

    if (function_exists('curl_init')) {
       $ch = curl_init();
       $timeout = 5; // set to zero for no timeout
       curl_setopt ($ch, CURLOPT_URL, $path);
       curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
              
       //curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
       //curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
       
       $file_contents = curl_exec($ch);
       curl_close($ch);
       $lines = explode("\n", $file_contents);

    } else {
       $lines = @file($path); 
    }

    return $lines;  
  }
}