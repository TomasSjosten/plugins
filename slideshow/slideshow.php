<?php

function slideShowImages() {


  // Get path to Slideshow plugin and path to where plugin is included from
  $cwd = explode('/', getcwd());
  $dir = explode('/', __DIR__);

  $cwdCount = count($cwd);
  $dirCount = count($dir);

  $newDir = null;

  switch (true) {
  case $dirCount > $cwdCount:
  $difference = $dirCount - $cwdCount;
  $removeThisMany = $dirCount - $difference;
  $newDir = implode('/', array_splice($dir, $removeThisMany));
  break;
  case $dirCount <= $cwdCount:
  $dirCount = $dirCount - $cwdCount;
  for ($i = 0; $i <= $dirCount; $i++) {
  $newDir .= '../';
  }
  $newDir .= 'slideshow';
  break;
  }

  $slideshowImg = array_diff(scandir($newDir . '/images'), array('.', '..'));

  $html = '<div id="slideshow">';
  foreach($slideshowImg as $img) {
  $html .= '<img src="' . $newDir . '/images/' . $img . '" alt="">';
  }
  $html .= '</div>';
  $html .= '
    <script type="text/javascript" src="' . $newDir . '/js_slideshow.js"></script>';
  echo $html;
}