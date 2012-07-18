<?php

$string = <<<EOT
{alpha {beta {charlie;}delta;}echo;foxtrot {golf;hotel;}india{juliette{kilo{lima{mike{november;oscar;papa{quebec;}}}romeo;}}}}
EOT;

echo $string;

$heirarchalStorage = array();
$n = 0;
do {
    $string = \preg_replace_callback('#\{[^\{\}]*?\}#', function($block)
    use(&$heirarchalStorage) {
        // do your magic with $heirarchalStorage
        // in here
        dump($block);
        $n++;
        return '#'.$n.'#';
    }, $string);
} while (preg_match('/[{}]/', $string)>0);

echo $string;

function dump($string){
	echo "<pre>";
	print_r($string);
	echo "</pre>";
}



?>