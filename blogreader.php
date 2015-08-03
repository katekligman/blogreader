<?php

include('vendor/autoload.php');

use Goutte\Client;

$audio = $argv[1];
$url = $argv[2];

if (empty($audio) || empty($url)) {
  die("Usage: blogreader.php [audiofile.wav] [url]\r\n");
}

$client = new Client();
$crawler = $client->request('GET', $url);

if (200 !== $client->getResponse()->getStatus()) {
  die("Cannot fetch: $url");
}

$sounds = array();
$items = $crawler->filter('.has-sidebar-cta')->children();
$items->each(function($node, $i) use (&$sounds) {

  if (empty($node->text())) return;

  switch ($node->nodeName()) {
  case 'h3':
    $sounds[] = 'header';
    $sounds[] = $node->text();
    break;
  case 'p':
    $sounds[] = 'paragraph';
    $sounds[] = $node->text();
    break;
  default:
    //echo $node->nodeName() . "\r\n";
  }
});

// Clean up any lingering raw audio files just to be sure
@exec("rm process/*.raw &> /dev/null");

// Generate sound for the segments.
foreach ($sounds as $order => $s) {
  if (empty($s)) continue;

  // Describe text markup.
  $markup = array(
    '.' => ' period.',
    ',' => ' comma,',
    '“' => ' quote ',
    '”' => ' quote '
  );
  $s = str_replace(array_keys($markup), array_values($markup), $s);
  text2audio($s, "process/temp-{$order}.raw");
}

// Process the master output file.
@exec("cat process/*.raw > master.raw");
@exec("sox -q -S -r 16k -e signed -c 1 -b 16 master.raw $audio &>/dev/null");
@unlink("master.raw");

// Clean up raw temp files
@exec("rm process/*.raw &> /dev/null");

// Generate the output.
function text2audio($text, $file) {
  exec("say " . escapeshellarg($text) . " -o temp.aiff");
  exec("sox temp.aiff -q -S -V -r 16k -e signed -c 1 -b 16 $file &>/dev/null");
  @unlink("temp.aiff");
}
