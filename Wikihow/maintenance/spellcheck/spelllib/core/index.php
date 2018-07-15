<?php
header('Content-type: text/html; charset=ISO-8859-1');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

//  Freeing up memory where available improoves performance on huge dictionary searches.
try{ini_set("memory_limit","255M");}catch(Exception $e){}

include "php/engine.php";
$script = false;
  error_reporting(0);
if(!isset($_POST["command"])){die("no command");}
if(isset($_POST["lan"]) &&  strlen($_POST["lan"])>0){$lang  = $_POST["lan"];}else{$lang = "English (International)" ;}
if(isset($_POST["note"]) &&  strlen($_POST["lan"])>0){$note  = $_POST["note"];}else{$note = "" ;}
if(isset($_POST["script"])){$script = true ;}
if(isset($_POST["sender"])){$sender = $_POST["sender"] ;}
if(isset($_POST["settingsfile"]) && strstr($_POST["settingsfile"],"/")===false && strstr($_POST["settingsfile"],'\\') ===false && strstr($_POST["settingsfile"],".")===false ){
$settingsfile = $_POST["settingsfile"] ;
}else{$settingsfile = "default-settings";}

$RequestedLangs = explode(",",$lang);




include ("settings/$settingsfile.php");
	

if( strtoupper($_POST["command"])=="WINSETUP" ){
	
	$words = explode(chr(1),stripcslashes($_POST["args"]));
	

	$suggestcountinit = 5;
	
	$error_type_words  	= array();
	$spell_check_words 	= array();
	$suggest_words 		= array();

	$suggestcount = 0;
	
	for($i=0;$i<count($words);$i++){
		$error_type_words[$i]="";
	if($suggestcount <$suggestcountinit+1){
	$suggest_words[$i]=	"";
	}
	
	$spell_check_words[$i] = $spellcheckObject -> SpellCheckWord( $words[$i])?"T":"F";	
				
				
		if($spell_check_words[$i]=="F"){
		$error_type_words[$i] =  $spellcheckObject ->ErrorTypeWord( $words[$i]);
			
		
		if($suggestcount <$suggestcountinit+1){
			$suggestcount++;
			$suggest_words[$i]=	 implode(chr(2),$spellcheckObject -> Suggestions( $words[$i] )) ;
		}
			
		}
	}
		
		echo		"WINSETUP";
		echo        chr(5);
		echo		$sender ;
		echo        chr(5);
		echo		implode("",$spell_check_words);
		echo        chr(5);
		echo		implode(chr(1),$error_type_words);
		echo        chr(5);
		echo		implode(chr(1),$suggest_words);
		echo        chr(5);
		echo 		implode(chr(2),$spellcheckObject ->ListDictionaries());
		

	}
	
	
	
	
	if( strtoupper($_POST["command"])=="WINSUGGEST" ){
	
	$words = explode(chr(1),stripcslashes($_POST["args"]));
	$suggest_words 		= array();
	
	
	
	

	$suggestcount = 0;
	
	
	for($i=0;$i<count($words);$i++){
		$suggest_words[$i]=	 implode(chr(2),$spellcheckObject -> Suggestions( $words[$i] )) ;
		}	
		
		echo		"WINSUGGEST";
		echo        chr(5);
		echo		$sender ;
		echo        chr(5);
		echo		implode(chr(1),$suggest_words);

		
	}
	
	if( strtoupper($_POST["command"])=="CTXSPELL"){
	
	$word = stripcslashes($_POST["args"]);
	
	
	
	$words = explode(chr(1),stripcslashes($_POST["args"]));
	$error_type_words  	= array();
	$spell_check_words 	= array();
	$suggest_words 		= array();
	
	for($i=0;$i<count($words);$i++){
	$error_type_words[$i]="";
	
	
	$spell_check_words[$i] = $spellcheckObject -> SpellCheckWord( $words[$i])?"T":"F";
	
		$error_type_words[$i] ="-"		;
		if($spell_check_words[$i]=="F"){
			$error_type_words[$i] =  $spellcheckObject ->ErrorTypeWord( $words[$i]);
			
		}
	}
	
		
		
		echo		"CTXSPELL";
		echo        chr(5);
		echo		$sender ;
		echo        chr(5);
		echo		implode("",$spell_check_words);
		echo        chr(5);
		echo		implode("",$error_type_words);}
		
	elseif( strtoupper($_POST["command"])=="CTXSUGGEST"){
	
	$word = stripcslashes($_POST["args"]);
	
		echo		"CTXSUGGEST";
		echo        chr(5);
		echo		$sender ;		
		echo        chr(5);
		echo		implode(chr(2),$spellcheckObject -> Suggestions( $word )) ;
	
	if($note=="ADDLANGS"){
		echo        chr(5);	
		echo		implode(chr(2),$spellcheckObject -> ListDictionaries( $word )) ;
			}
	}
	elseif( strtoupper($_POST["command"])=="RAWSPELL"){
	
	$word = stripcslashes($_POST["args"]);
	$ok = $spellcheckObject -> SpellCheckWord($word);
		echo		"RAWSPELL";
		echo        chr(5);
		echo		$sender ;		
		echo        chr(5);
		echo 		$word;
		echo        chr(5);
		echo 		$ok?"T":"F";
		echo        chr(5);
		
			if($ok){
			echo $word;	
			echo        chr(5);
			echo "";
			}else{
			echo		implode(chr(2),$spellcheckObject -> Suggestions( $word )) ;	
			echo        chr(5);
			echo		$spellcheckObject -> ErrorTypeWord( $word[$i]);
				}
		
	}
	
	if( strtoupper($_POST["command"])=="APISPELL"  ||strtoupper($_POST["command"])=="APISPELLARRAY"   ){
	
	$words = explode(chr(1),stripcslashes($_POST["args"]));
	
	$doSuggest  = ($note!=="NOSUGGEST");
	
	
	$error_type_words  	= array();
	$spell_check_words 	= array();
	$suggest_words 		= array();

	
	for($i=0;$i<count($words);$i++){
		$error_type_words[$i]="";
	    $suggest_words[$i]= array();
	
		$spell_check_words[$i] = $spellcheckObject -> SpellCheckWord( $words[$i])?"T":"F";	
				
				
		if($spell_check_words[$i]=="F"){
		$error_type_words[$i] =  $spellcheckObject ->ErrorTypeWord( $words[$i]);
			
		
		if($doSuggest){
			$suggest_words[$i]=	 implode(chr(2),$spellcheckObject -> Suggestions( $words[$i] )) ;
		}else{
				$suggest_words[$i]="";
			}
			
		}
	}
		
		echo		strtoupper($_POST["command"]);
		echo        chr(5);
		echo		$sender ;
		echo        chr(5);
		echo		implode("",$spell_check_words);
		echo        chr(5);
		echo		implode(chr(1),$error_type_words);
		echo        chr(5);
		echo		implode(chr(1),$suggest_words);
		echo        chr(5);
		echo 		implode(chr(1),$words);
		

	}
	
	if( strtoupper($_POST["command"])=="APIDYM"     ){
		$in = stripcslashes($_POST["args"]);
		echo		strtoupper("APIDYM");
		echo        chr(5);
		echo		$sender ;
		echo        chr(5);
		echo 		$in;
		echo        chr(5);
		echo		$spellcheckObject ->didYouMean($in);
		echo        chr(5);
		echo         $lang;
				
		}

	
	

if($script){echo '<script type="text/javascript">window.parent.livespell.ajax.pickupIframe(document.body.innerHTML)</script>';};
?>