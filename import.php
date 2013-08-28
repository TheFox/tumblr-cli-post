<?php

$DEBUG = 0;

if(PHP_SAPI != 'cli') die('ERROR: You must run this script under shell.');

chdir(dirname(__FILE__));

date_default_timezone_set('Europe/Vienna');
declare(ticks = 1);
require 'vendor/autoload.php';

use \Symfony\Component\Yaml\Yaml;

$exit = 0;
if(function_exists('pcntl_signal')){
	function signalHandler($signo){ global $exit; $exit++; if($exit >= 2) exit(); }
	pcntl_signal(SIGTERM, 'signalHandler');
	pcntl_signal(SIGINT, 'signalHandler');
}

#$argPostsNoClear = false;
$argPostFile = 'post.txt';
$argTags = '';
$argc = count($argv);
for($argn = 1; $argn < $argc; $argn++){
	$arg = $argv[$argn];
	#if($arg == '--no-clear'){ $argPostsNoClear = true; }
	if($arg == '-f'){
		$argn++;
		$argPostFile = $argv[$argn];
	}
	elseif($arg == '-t'){
		$argn++;
		$argTags = $argv[$argn];
	}
}

if($argc <= 1){
	print "Usage: php import.php [-f FILE]\n";
	print "\n";
	print "FILE - is the path to the file with the post content.\n";
	exit(3);
}

if(!file_exists($argPostFile)){
	print "ERROR: file '".$argPostFile."' doesn't exists.\n";
	exit(1);
}

#exit(1);


$paramtersFilePath = 'parameters.yml';
if(!file_exists($paramtersFilePath)){
	die('ERROR: File "'.$paramtersFilePath.'" not found.'."\n");
}

$paramters = Yaml::parse($paramtersFilePath);

if(
	!isset($paramters)
	|| !isset($paramters['tumblr'])
	|| !isset($paramters['tumblr']['consumer_key'])
	|| !isset($paramters['tumblr']['consumer_secret'])
	|| !isset($paramters['tumblr']['token'])
	|| !isset($paramters['tumblr']['token_secret'])
){
	print "ERROR: parameters invalid.\n";
	var_export($paramters); print "\n";
	exit(1);
}


$client = new Tumblr\API\Client($paramters['tumblr']['consumer_key'], $paramters['tumblr']['consumer_secret'], $paramters['tumblr']['token'], $paramters['tumblr']['token_secret']);

if(!isset($paramters['tumblr']['blog'])){
	print "You havn't set up a blog name.\nAvailable names are:\n";
	foreach($client->getUserInfo()->user->blogs as $blog){
		print "\t".$blog->name."\n";
	}
	exit(1);
}


$options = array(
	'type' => 'text',
	'title' => 'title',
	'tags' => $argTags,
	'body' => file_get_contents($argPostFile),
	'format' => 'markdown',
	#'state' => 'queue',
);

print "post ".strlen($options['body'])." bytes ... ";

try{
	$res = false;
	if(!$DEBUG){
		$res = $client->createPost($paramters['tumblr']['blog'], $options);
	}
	print 'done: ';
	if($res){
		print $res->id;
	}
	else{
		print 'failed';
	}
}
catch(Exception $e){
	print 'ERROR: '.$e->getMessage();
}
print "\n";

