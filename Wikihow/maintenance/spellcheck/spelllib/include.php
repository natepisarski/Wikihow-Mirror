<?php

require "core/php/engine.php";

 class SpellCheckButton extends SpellCheckInstance{
 	public $CheckInSitu = false;
    public $WindowMode = "modal";  
    public $Class = "";
    public $Style = "";
    public $Image = "";
    public $ImageRollOver = "";
    public $Text = "";
    public $FormToSubmit = "";
	public $Strict = true;
	public $ShowSummaryScreen = true;
	public $ShowMeanings = true;
	public $MeaningProvider = "http://www.thefreedictionary.com/{word}";
	public $UndoLimit = 20;
		 public $members = array("FormToSubmit","Strict","ShowSummaryScreen","ShowMeanings","MeaningProvider","UndoLimit","WindowMode","IgnoreAllCaps","IgnoreNumeric","CaseSensitive","CheckGrammar","Strict","Language","MultiDictionary","CSSTheme","SettingsFile","ID","HiddenButtons");
	
	public function SpellButton(){
		$instanceId = $this->ID ;
		$xhtml = "$instanceId.DrawSpellButton(".$this->binToStr($this->CheckInSitu) .", ".$this->outStr($this->Text) .",".  $this->outStr($this->Class).",".  $this->outStr($this->Style).");" ;
		
		return $this->addCodeBlocks($this->instanceScript().$xhtml);
	}
	public function SpellImageButton(){
		$instanceId = $this->ID ;
		$xhtml = "$instanceId.DrawSpellImageButton(".$this->binToStr($this->CheckInSitu).",". $this->outStr($this->Image).",".$this->outStr($this->ImageRollOver) .",".$this->outStr($this->Text).",". $this->outStr($this->Class).",".$this->outStr($this->Style).");";
		return $this->addCodeBlocks($this->instanceScript().$xhtml);
	}
	public function SpellLink(){
		$instanceId = $this->ID ;
		$xhtml = "$instanceId.DrawSpellLink(".$this->binToStr($this->CheckInSitu) .", ".$this->outStr($this->Text) .",".  $this->outStr($this->Class).",".  $this->outStr($this->Style).");";
		return $this->addCodeBlocks($this->instanceScript().$xhtml);
	}

}


  class SpellAsYouType extends SpellCheckInstance{     
	public $Delay =888;
		 public $members = array("Strict","Delay","IgnoreAllCaps","IgnoreNumeric","CaseSensitive","CheckGrammar","Strict","Language","MultiDictionary","CSSTheme","SettingsFile","ID","HiddenButtons");
	public function Activate(){
		$instanceId = $this->ID ;
		$xhtml = "$instanceId.ActivateAsYouTypeOnLoad();" ;
		return $this->addCodeBlocks($this->instanceScript().$xhtml);
	}
	}



  class SpellCheckInstance
  {
      public $InstallationPath = "/phpspellcheck/";
      public $Fields = "ALL";
      public $IgnoreAllCaps = true;
      public $IgnoreNumeric = true;
      public $CaseSensitive = true;
      public $CheckGrammar = true;
      public $Strict = true;
      public $Language = "English (International)";
      public $MultiDictionary = false;
      public $UserInterfaceLanguage = "en";
      public $CSSTheme = "classic";
      public $SettingsFile = "default-settings";
      public $ID = "";   
      


	 
	  	private $HiddenButtons="";
		private $a_hiddenButtons = array();
	
		public function HideButton($buttonName){
			array_push($this->a_hiddenButtons,$buttonName)	;
			$this->HiddenButtons = implode(",",$this->a_hiddenButtons);
		}
	 
	
	 public $members = array("FormToSubmit","Strict","ShowSummaryScreen","ShowMeanings","MeaningProvider","UndoLimit","Delay","WindowMode","IgnoreAllCaps","IgnoreNumeric","CaseSensitive","CheckGrammar","Strict","Language","MultiDictionary","CSSTheme","SettingsFile","ID","HiddenButtons");
	 private $members_default =array();
	
	
	function __construct() {
		
	      $this->getId();
	   
	for ($i=0;$i<count($this->members);$i++){
		$member = $this->members[$i];
	$this->members_default[$i] = $this->$member;
	}
	

	
	}


     private function getId()
     {
         if ($this->ID === "") {
             global $LIVESPELL_ICOUNTER;
             if (!isSet($LIVESPELL_ICOUNTER)) {
                 $LIVESPELL_ICOUNTER = 0;
             } else {
                 $LIVESPELL_ICOUNTER += 1;
             }
             $this->ID = "PHPLiveSpell_" . $LIVESPELL_ICOUNTER;
         }
     }

      protected function instanceScript()
      {
		
           	$instanceId = $this->ID;
            $fieldList =  $this->strFieldList();

          	  $strScript =  "var $instanceId = new LiveSpellInstance(); "; 	
			$strScript .= "$instanceId.ServerModel = \"php\" ;";
			$strScript .= "$instanceId.SetUserInterfaceLanguage ('".$this->UserInterfaceLanguage."');";
			
              $strScript .= "$instanceId.Fields = \"" . $fieldList . "\" ;";
				for ($i=0;$i<count($this->members);$i++){
					$member = $this->members[$i];
					$value = $this->$member ;
					$default = $this->members_default[$i];
				if($default !== $value){
					if ( is_string($value)){$value = "\"".htmlentities($value)."\"";} elseif (is_bool($value)){$value = $this->binToStr($value) ;}
				 		$strScript .= "$instanceId.$member = $value ;";   		
					}
				}
				
				if($this->isLocal()){
					$ipath= $this->InstallationPath;
					$strScript = "if(typeof(livespell)=='undefined'){/*only displayed on localhost*/document.write('phpspellcheck installation error - Please check the  InstallationPath:<b>$ipath</b><br>')}".$strScript;	
				}
          return $strScript;
      }


	private function strFieldList(){
	    	$fieldList = $this->Fields;
		    if (is_array($fieldList)) {  $fieldList = implode(",", $fieldList); }
		    return $fieldList;
	}
	

	
	 protected function addCodeBlocks($str)
      {
		return $this->includeJS()."<script type='text/javascript'>$str</script>";
      }

protected function isLocal(){

    	return $_SERVER['HTTP_HOST']=="localhost" || $_SERVER['HTTP_HOST']=="127.0.0.1";

	
}

protected function includeJS(){
$pmap = $this->InstallationPath;
if($pmap[strlen($pmap)-1]!=="/"){$pmap.="/";}
$pmap .= "include.js";
return "<script type='text/javascript' src='$pmap'></script>";

}
      protected function binToStr($in)
      {
          return $in ? "true" : "false";
      }
	 protected function outStr($in)
      {
          return "\"" . htmlentities($in) . "\"";
      }

  }
?>