<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

function dump($arr){
?>
	<pre>
<?
	print_r($arr);
?>
	</pre>
<?
}



$filename = 'index.wtml';
$templateHandle = fopen($filename, 'r');
$template = fread($templateHandle, filesize($filename));


/*Matches*/
/*
{%.*?%}					//Twig block
{{.*?}}					//Twig var
\/\/.*[^\n				//short comment
<!--.*?-->				//html comment (rough)
\/\*([^*]|[\r\n])*\*\/	//long comment or

\..*?(?=[\.#\[\{\>\ ])	//class
#.*?(?=[\.#\[\{\>\ ])	//id
\[.*?\]					//attr
\(.*?\)					//text


Strip Order:

long_comment > short_comment > html_comment > twig_block > twig_var > text > attr > id > class > tag > brace
*/
$twigBlock = "{%.*?%}";
$htmlComment = "<!--.*?-->";
$nestingGrammar = "[>{}]";

$regexes = array (
	'twig_block'=>"{%.*?%}",
	'html_comment'=>"<!--.*?-->",
	'text'	=>	"\([\"\'].*?[\'\"]\)",
	'attr'	=>	"\[.*?\]",
	'id'	=>	"#.*?(?=[\.#\[\{\>\ ])",
	'class'	=>	"\..*?(?=[\.#\[\{\>\ ])",
	'tag'	=>	"[a-z0-9]+"
);

$selectorRegex = '[a-zA-Z0-9]+((\.-?[_a-zA-Z]+[_a-zA-Z0-9-]*)*(\#-?[_a-zA-Z]+[_a-zA-Z0-9-]*)*(\[.*?\])*(\(".*?"\))*)*';

$template = preg_replace("/\/\/.*?(?=\n)|\/\*([^*]|[\r\n])*\*\//", '', $template); //remove all template comments, and uneccessary code (;)

echo "<h1>Comments stripped</h1>";
echo nl2br($template);



?>
	<h2>Split</h2>
<?

$finalRegex = "/($twigBlock)|($htmlComment)|($selectorRegex)|($nestingGrammar)/s";

echo "final regex: ".htmlspecialchars($finalRegex);

preg_match_all($finalRegex, $template, $matches);

/* $matches = preg_split("/($selectorRegex)/s", $template, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY); */

dump($matches);

/*

preg_match_all("/[^\n\t ].*[^;\{]/", $template, $lineMatches); //match each line

$lines = array();

foreach($lineMatches[0] as $lineNum => &$line){ //foreach line


	$lineComponents = array('_line'=>$line);
	foreach($regexes as $name=>$regex){
		
		$line = preg_replace_callback("/$regex/", function($match) use($name, &$lineComponents, &$test){
			$lineComponents[$name][] = $match[0];
			return '';
		}, $line);
	}
	$lineComponents['~end_line'] = $line;
	
	$lines[] = $lineComponents;

}

dump($lines);
*/







?>