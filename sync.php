#!/usr/bin/php
<?php
date_default_timezone_set('America/Los_Angeles');
require 'openphoto-php/OpenPhotoOAuth.php';
$params = json_decode(file_get_contents('.secrets.json'), 1);

if(!is_dir($params['target']))
{
  printf("Target directory does not exist (%s)\n", $params['target']);
  die();
}

$client = new OpenPhotoOAuth($params['host'], $params['consumerKey'], $params['consumerSecret'], $params['token'], $params['tokenSecret']);
$client->useSSL(true);
$db = new SQLite3('db.sqlite');

// stats
$photosNew = 0;
$photosSkip = 0;

// photos
$page = 1;
$pageSize = 100;
while(true)
{
  printf("Fetching page %s\n", $page);
  $photosResp = json_decode($client->get('/photos/list.json', array('page' => $page, 'pageSize' => $pageSize)), 1);
  $photos = $photosResp['result'];
  if(empty($photos))
  {
    printf("No more photos (page %s)\n", $page);
    break;
  }

  foreach($photos as $photo)
  {
    $ident = sprintf('photo-%s', $photo['id']);
    // check if this id is already in the db
    $result = $db->querySingle("SELECT `id` FROM `meta` WHERE `id`='{$ident}'");
    if($result !== null)
    {
      printf("Photo already stored %s\n", $photo['id']);
    }
    else
    {
      if(!is_dir($dir = sprintf('%s/%s', $params['target'], date('Ym', $photo['dateTaken']))))
        mkdir($dir);
      
      $nameStored = $photo['filenameOriginal'];
      if(file_exists($pathStored = sprintf('%s/%s', $dir, $nameStored)))
      {
        $nameStored = sprintf('%s-%s%s', substr($nameStored, 0, strrpos($nameStored,'.')), $photo['id'], substr($nameStored, strrpos($nameStored,'.')));
        $pathStored = sprintf('%s/%s', $dir, $nameStored);
      }
      $urlParts = parse_url($photo['pathDownload']);
      $data = $client->get($urlParts['path']);
      $fileSize = floor(strlen($data)/1024);
      if($fileSize != $photo['size'])
      {
        printf("File size did not match for %s (fetched: %s, api: %s)\n", $photo['id'], $fileSize, $photo['size']);
        continue;
      }

      $json = json_encode($photo);
      $stmt = $db->prepare('INSERT INTO `meta`(`id`,`data`,`hash`) VALUES(:id, :data, :hash)');
      $stmt->bindValue(':id', $ident);
      $stmt->bindValue(':data', $json);
      $stmt->bindValue(':hash', sha1($json));
      $result = $stmt->execute();
      if($result)
      {
        $fsStat = file_put_contents($pathStored, $data);
        if($fsStat)
        {
          printf("File successfully stored %s\n", $photo['id']);
        }
        else
        {
          $result = $db->exec("DELETE FROM `meta` WHERE `id`='{$ident}'");
          if($result)
            printf("Failed to store photo, database record deleted %s\n", $photo['id']);
          else
            printf("Failed to store photo, failed database record deleted %s\n", $photo['id']);
        }
      }
    }
  }
  $page++;
}
