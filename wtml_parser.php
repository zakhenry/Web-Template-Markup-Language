<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

function dump($arr){
?>
	<pre>
<?
	echo htmlspecialchars(print_r($arr, true));
	
?>
	</pre>
<?
}

function printElapsed($clock){
	$elapsed = (microtime(true)-$clock)/1000;
	echo "time elapsed is ".$elapsed."ms <br />";
}

$clock = microtime(true);

$filename = 'index.wtml';
$templateHandle = fopen($filename, 'r');
$template = fread($templateHandle, filesize($filename));

printElapsed($clock);

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

$template = preg_replace("/\/\/.*?(?=\n)|\/\*([^*]|[\r\n])*\*\//", '', $template); //remove all template comments, and uneccessary code (;)

$tag = '[a-zA-Z0-9]+';
$class = '((\.-?[_a-zA-Z]+[_a-zA-Z0-9-]*)*';
$id = '(\#-?[_a-zA-Z]+[_a-zA-Z0-9-]*)*';
$attribute = '(\[.*?\])*';
$content = '(\(\".*?\"\))*)*';

$selectorRegex = "$tag$class$id$attribute$content";

$twigBlock = "{%.*?%}";
$htmlComment = "<!--.*?-->";
$nestingGrammar = "[>{}]";

$finalRegex = "/($twigBlock)|($htmlComment)|($selectorRegex)|($nestingGrammar)/s";

echo "<h1>Comments stripped</h1>";
echo nl2br($template);



?>
	<h2>Split</h2>
<?



echo "final regex: ".htmlspecialchars($finalRegex);

preg_match_all($finalRegex, $template, $matches);

/* $matches = preg_split("/($selectorRegex)/s", $template, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY); */

printElapsed($clock);



$regexBlocks = array(
				1=>'twig_block',
				2=>'html_comment',
				3=>'selector',
				9=>'nesting_grammar'
				);
				
/*
$selectorRegexes = array (
	'text'	=>	"\([\"\'].*?[\'\"]\)",
	'attr'	=>	"\[.*?\]",
	'id'	=>	"#.*?(?=[\.#\[$])",
	'class'	=>	"\..*?(?=[\.#\[$])",
	'tag'	=>	"[a-z0-9]+"
);
*/

$selectorRegexes = array (
	'content'	=>	"/\(\".*?\"\)/s",
	'attr'	=>	"/\[.*?\]/",
	'id'	=>	"/#.*?(?![a-zA-Z0-9_-])/",
	'class'	=>	"/\..*?(?![a-zA-Z0-9_-])/",
	'tag'	=>	"/[a-z0-9]+/"
);

foreach($matches[0] as $key => $match){
	foreach($regexBlocks as $i=>$instruction){
		if (strlen($matches[$i][$key])>0){
			if($i==3){ //selector
				$selectorArray = array(); //initialise and unset
				$line = $match;
				
/* 				$selectorArray['_line'] = $line; */
				foreach($selectorRegexes as $name=>$regex){
					$line = preg_replace_callback("$regex", function($match) use($name, &$selectorArray){
						$selectorArray[$name][] = $match[0];
						return '';
					}, $line);
				}
				
/* 				$selectorArray['~end_line'] = $line; */
				
				$instructions[][$instruction] = $selectorArray;
				
			}else{
				$instructions[][$instruction] = $match;
			}
		}
	}
}

dump($matches);
dump($instructions);

$tagBuffer = array();
$htmlArray = array();

foreach($instructions as $key => $instructionArray){
	
	$instruction = key($instructionArray);
/* 	echo "instruction is: ".$instruction; */
/* 	dump($instructionArray); */

	switch($instruction){
		case 'selector':
		{
			if (count($tagBuffer)>0){
				$closureTag = array_pop($tagBuffer);
				$htmlArray[] = "</".$closureTag.">";
			}
			
			$tagComponents = array();
			
			$tagComponents[] = $instructionArray['selector']['tag'][0];
			if (isset($instructionArray['selector']['id']))		$tagComponents[] = 'id="'.str_replace('#', '', $instructionArray['selector']['id'][0]).'"';
			if (isset($instructionArray['selector']['class']))	$tagComponents[] = 'class="'.str_replace('.', '', implode(' ', $instructionArray['selector']['class'])).'"';
			if (isset($instructionArray['selector']['attr']))	$tagComponents[] = str_replace(array('[',']'), '', implode(' ', $instructionArray['selector']['attr']));
			
			$openingTag = "<".implode(' ', $tagComponents).">";
			$htmlArray[] = $openingTag;
			
			if (isset($instructionArray['selector']['content']))	$htmlArray[] = preg_replace("/^\(\"|\"\)$/", '', $instructionArray['selector']['content'][0]);
			
			$tagBuffer[] = $instructionArray['selector']['tag'][0];
		}
		break;
		case 'twig_block':
		{
			$htmlArray[] = $instructionArray['twig_block'];
			echo "twig block added:";
		}
		break;
		case 'html_comment':
		{
			$htmlArray[] = $instructionArray['html_comment'];
			echo "html comment added:";
		}
		break;
		case 'nesting_grammar':
		{
			
		}
		break;
	}
}

printElapsed($clock);


dump($htmlArray);


echo htmlspecialchars(implode('', $htmlArray));






?>