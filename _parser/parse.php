<?php

require 'simple_html_dom.php';

function list_all_files($dir) { 
   $result = array(); 
   $cdir = scandir($dir); 
   foreach ($cdir as $key => $value) 
   { 
      if (!in_array($value,array(".",".."))) 
      { 
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) 
         { 
            $result = array_merge($result, list_all_files($dir . DIRECTORY_SEPARATOR . $value));
         } 
         else if ($value != 'index.html' && substr($value, 0, 4) != 'list')
         { 
            $result[] = realpath( $dir . DIRECTORY_SEPARATOR . $value ); 
         } 
      } 
   } 
   return $result; 
} 

$files = list_all_files('../Products/');

$IMG_BASH='';

function change_x($matches) {
   $m = str_replace('H', 'H ', $matches[2]);
   $m = str_replace('W', 'W ', $m);
   $m = str_replace('D', 'D ', $m);
   $m = str_replace('X', 'mm X ', $m);
   return $matches[1].$m;
}

function imagename($name) {
   $name = str_replace('/', '_', $name);
   $name = str_replace('&', '_', $name);
   $name = str_replace(' ', '_', $name);
   $name = preg_replace('/_{1,}/', '_', $name);
   return $name;
}

foreach ($files as $key => $value) {
   $file = iconv('GBK', 'UTF-8', file_get_contents($value));
   $file = str_replace('Ⅰ', 'I', $file);
   $file = str_replace('Ⅱ', 'II', $file);
   $file = str_replace('Ⅲ', 'III', $file);
   $html = str_get_html($file);

   $category = trim(str_replace(' ', '-', strtolower($html->find('div.place a', 2)->innertext)));
   switch ($category) {
      case 'depositary-safe':
         $category="depository-safe";
         break;
   }
   $category .= 's';

   $info = preg_replace('/\s{2,}/',"\n", trim($html->find('div.infolist', 0)->plaintext));

   $info = str_replace('：', ': ', $info);
   $info = "|\n" .preg_replace('/^[^\n]/m',"  * $0", $info);

   $info = str_replace('Model Number', 'Model', $info);
   $info = preg_replace_callback('#(Exterior Size: )(.*)$#m', 'change_x', $info);
   $info = str_replace('Approx', 'Approx.', $info);

   preg_match('/Model:(.*)$/m', $info, $matches);
   $model = trim($matches[1]);

   $localimages = array();
   $images = $html->find('#imglist img');
   $imgno=1;
   $lastremotesrc='';
   foreach ($images as $image) {
      $src=trim($image->src);
      if ($src == '') continue;
      $remotesrc = preg_replace('#^.*/uploads/#', 'http://www.qnnsafes.com/uploads/', $src);
      if ($lastremotesrc == $remotesrc) continue;
      $lastremotesrc=$remotesrc;
      $newsrc = strtolower($model.'-'.$imgno.'.'.pathinfo($src, PATHINFO_EXTENSION));
      $newsrc = imagename($newsrc);
      $IMG_BASH .= 'echo "Downloading '.$newsrc.' ..." && curl -L -s "'.$remotesrc.'" -o "'.$newsrc.'" &' . "\n\n";
      array_push($localimages, $newsrc);
      $imgno++;
   }

   $images = '['."\n";
   foreach ($localimages as $li) {
      $images .= '  "/prodimgs/'.$li."\",\n";
   }
   $images .= ']';

   $features = preg_replace('/^[^\n]/m',"  * $0", preg_replace('/\s{2,}/',"\n", trim($html->find('#con_two_1 ul', 0)->plaintext)));

   $features = "|\n" . post_process($features);

   $specs = preg_replace('/^[^\n]/m',"  * $0", preg_replace('/\s{2,}/',"\n", trim($html->find('#con_two_2 ul', 0)->plaintext)));

   $specs = "|\n" . post_process($specs);

   $aimages = $html->find('#con_two_3 img');
   $lastremotesrc='';
   foreach ($aimages as $image) {
      $src=trim($image->src);
      if ($src == '') continue;
      $remotesrc = preg_replace('#^.*/uploads/#', 'http://www.qnnsafes.com/uploads/', $src);
      if ($lastremotesrc == $remotesrc) continue;
      $lastremotesrc=$remotesrc;
      $newsrc = strtolower($model.'-'.$imgno.'.'.pathinfo($src, PATHINFO_EXTENSION));
      $newsrc = imagename($newsrc);
      $IMG_BASH .= 'echo "Downloading '.$newsrc.' ..." && curl -L -s "'.$remotesrc.'" -o "'.$newsrc.'" &' . "\n\n";
      array_push($localimages, $newsrc);
      $image->src="{PRODIMGS}/prodimgs/".$newsrc;
      $imgno++;
   }

   $IMG_BASH .= "wait\n\n";

   $additional = trim(strip_tags($html->find('#con_two_3', 0)->innertext, '<p><a><img><strong><b><i>'));

   $additional = preg_replace('/\s{2,}/',"\n\n", $additional);

   $additional = post_process($additional);

   $additional = preg_replace('/\n{3,}/',"\n\n", $additional);

   $additional = preg_replace('#<strong>(.+?)</strong>#',"**$1**", $additional);

   $path = "products/$category/_posts";
   @mkdir($path, 0755, true);

   $PRODUCT = <<<PRO
---
layout: product-details

title: $model

category: $category

name: $model

images: $images

info: $info

features: $features

specs: $specs

---

$additional

PRO;

   $filename=strtolower($model);
   $filename=imagename($filename);
   file_put_contents("$path/2013-1-1-".$filename.".md", $PRODUCT);
/*
$LIST = <<<LIST
---
layout: product-list
---

LIST;
   file_put_contents("products/$category/index.html", $LIST);
*/
}

file_put_contents("download_images.sh", "#!/bin/bash\n\n".$IMG_BASH);

function post_process($src) {
   $src = str_replace('&rdquo;', ' in. ', $src);
   $src = str_replace('&amp;', '&', $src);
   $src = str_replace('&nbsp;', '', $src);
   return $src;
}
