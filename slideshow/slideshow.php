<?php

function slideShowImages() {

  // Get path to Slideshow plugin and path to where plugin is included from
  $cwd = explode('/', getcwd());  // Current working directory
  $dir = explode('/', __DIR__);   // Plugin directory

  // Count the array to get correct directories no matter where plugin is included from
  $cwdCount = count($cwd);
  $dirCount = count($dir);

  // New directory path
  $newDir = null;

  // Check if plugin is within children or parent directories
  switch (true) 
  {
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

  // Scan only for images, remove "." and ".." from scandir array
  $slideshowImg = array_diff(scandir($newDir . '/images'), array('.', '..'));

  // Prepare the output HTML
  $html = '<div id="slideshow">';

  // Include every image (alter "alt"-tag if you wish)
  foreach($slideshowImg as $img) {
    $html .= '<img src="' . $newDir . '/images/' . $img . '" alt="">';
  }

  $html .= '</div>';

  // Include the "js_slideshow.js" file to start sliding through images
  $html .= '<script type="text/javascript" src="' . $newDir . '/js_slideshow.js"></script>';

  // Output the HTML
  echo $html;
}