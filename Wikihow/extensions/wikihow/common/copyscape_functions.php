<?php

/*
	PHP sample code for Copyscape Premium API
	
	Compatible with PHP 4.x or later with XML (expat) and curl compiled in
	
	You may install, use, reproduce, modify and redistribute this code, with or without
	modifications, subject to the general Terms and Conditions on the Copyscape website. 
	
	For any technical assistance please contact us via our website.
	
	15-Oct-2009: First version
	26-Jul-2010: Added support for private index functions
	09-Aug-2010: Added support for delete from private index operation
	
	Copyscape (c) Indigo Stream Technologies 2010 - http://www.copyscape.com/


	Instructions for use:
	
	1. Set the constants COPYSCAPE_USERNAME and COPYSCAPE_API_KEY below to your details.
	2. Call the appropriate API function, following the examples below.
	3. Use print_r to discover the structure of the output, which closely mirrors the XML.
	4. To run the examples provided, please uncomment the next line:
*/

	// $run_examples=true;

/*
	Error handling:
	
	* If a call failed completely (e.g. curl failed to connect), functions return false.
	* If the API returned an error, the response array will contain an 'error' element.
*/

/*
	A. Constants you need to change
*/


	define('COPYSCAPE_USERNAME', WH_COPYSCAPE_USERNAME);
	define('COPYSCAPE_API_KEY', WH_COPYSCAPE_API_KEY);

	define('COPYSCAPE_API_URL', 'http://www.copyscape.com/api/');
	
/*
	B. Functions for you to use (all accounts)
*/

	function copyscape_api_url_search_internet($url, $full=null) {
		return copyscape_api_url_search($url, $full, 'csearch');
	}
	
	function copyscape_api_text_search_internet($text, $encoding, $full=null) {
		return copyscape_api_text_search($text, $encoding, $full, 'csearch');
	}
	
	function copyscape_api_check_balance() {
		return copyscape_api_call('balance');
	}
	
/*
	C. Functions for you to use (only accounts with private index enabled)
*/
	
	function copyscape_api_url_search_private($url, $full=null) {
		return copyscape_api_url_search($url, $full, 'psearch');
	}
	
	function copyscape_api_url_search_internet_and_private($url, $full=null) {
		return copyscape_api_url_search($url, $full, 'cpsearch');
	}

	function copyscape_api_text_search_private($text, $encoding, $full=null) {
		return copyscape_api_text_search($text, $encoding, $full, 'psearch');
	}
	
	function copyscape_api_text_search_internet_and_private($text, $encoding, $full=null) {
		return copyscape_api_text_search($text, $encoding, $full, 'cpsearch');
	}
	
	function copyscape_api_url_add_to_private($url, $id=null) {
		$params['q']=$url;
		
		if (isset($id))
			$params['i']=$id;
			
		return copyscape_api_call('pindexadd', $params);
	}
	
	function copyscape_api_text_add_to_private($text, $encoding, $title=null, $id=null) {
		$params['e']=$encoding;
		
		if (isset($title))
			$params['a']=$title;

		if (isset($id))
			$params['i']=$id;
			
		return copyscape_api_call('pindexadd', $params, null, $text);
	}
	
	function copyscape_api_delete_from_private($handle) {
		$params['h']=$handle;
		
		return copyscape_api_call('pindexdel', $params);
	}
	
/*
	D. Some examples of use
*/

	if (@$run_examples) {
		$exampletext='We hold these truths to be self-evident, that all men are created equal, that they are endowed by their '.
			'Creator with certain unalienable rights, that among these are Life, Liberty, and the pursuit of Happiness. That to '.
			'secure these rights, Governments are instituted among Men, deriving their just powers from the consent of the '.
			'governed. That whenever any Form of Government becomes destructive of these ends, it is the Right of the People to '.
			'alter or to abolish it, and to institute new Government, laying its foundation on such principles and organizing '.
			'its powers in such form, as to them shall seem most likely to effect their Safety and Happiness. Prudence, indeed, '.
			'will dictate that Governments long established should not be changed for light and transient causes; and '.
			'accordingly all experience hath shown, that mankind are more disposed to suffer, while evils are sufferable, than '.
			'to right themselves by abolishing the forms to which they are accustomed. But when a long train of abuses and '.
			'usurpations, pursuing invariably the same Object evinces a design to reduce them under absolute Despotism, it is '.
			'their right, it is their duty, to throw off such Government, and to provide new Guards for their future security. '.
			'Such has been the patient sufferance of these Colonies; and such is now the necessity which constrains them to '.
			'alter their former Systems of Government. The history of the present King of Great Britain is a history of '.
			'repeated injuries and usurpations, all having in direct object the establishment of an absolute Tyranny over these '.
			'States. To prove this, let Facts be submitted to a candid world. He has refused his Assent to Laws, the most '.
			'wholesome and necessary for the public good. '.
			'We, therefore, the Representatives of the United States of America, in General Congress, Assembled, '.
			'appealing to the Supreme Judge of the world for the rectitude of our intentions, do, in the Name, and by Authority '.
			'of the good People of these Colonies, solemnly publish and declare, That these United Colonies are, and of Right '.
			'ought to be free and independent states; that they are Absolved from all Allegiance to the British Crown, and that '.
			'all political connection between them and the State of Great Britain, is and ought to be totally dissolved; and '.
			'that as Free and Independent States, they have full Power to levy War, conclude Peace, contract Alliances, '.
			'establish Commerce, and to do all other Acts and Things which Independent States may of right do. And for the '.
			'support of this Declaration, with a firm reliance on the Protection of Divine Providence, we mutually pledge to '.
			'each other our Lives, our Fortunes, and our sacred Honor.';

		my_echo_title('Response for a simple URL Internet search');		
		my_print_r(copyscape_api_url_search_internet('http://www.copyscape.com/example.html'));

		my_echo_title('Response for a URL Internet search with full comparisons for the first two results');
		my_print_r(copyscape_api_url_search_internet('http://www.copyscape.com/example.html', 2));

		my_echo_title('Response for a simple text Internet search');
		my_print_r(copyscape_api_text_search_internet($exampletext, 'ISO-8859-1'));
		
		my_echo_title('Response for a text Internet search with full comparisons for the first two results');
		my_print_r(copyscape_api_text_search_internet($exampletext, 'ISO-8859-1', 2));

		my_echo_title('Response for a check balance request');
		my_print_r(copyscape_api_check_balance());
	
		my_echo_title('Response for a URL add to private index request');
		my_print_r(copyscape_api_url_add_to_private('http://www.copyscape.com/example.html'));
	
		my_echo_title('Response for a text add to private index request');
		$response=copyscape_api_text_add_to_private($exampletext, 'ISO-8859-1', 'Extract from Declaration of Independence', 'EXAMPLE_1234');
		my_print_r($response);
		$handle=$response['handle'];

		my_echo_title('Response for a URL private index search');
		my_print_r(copyscape_api_url_search_private('http://www.copyscape.com/example.html'));

		my_echo_title('Response for a delete from private index request');
		my_print_r(copyscape_api_delete_from_private($handle));

		my_echo_title('Response for a text search of both Internet and private index with full comparisons for the first result (of each type)');
		my_print_r(copyscape_api_text_search_internet_and_private($exampletext, 'ISO-8859-1', 1));
	}
	
	function my_echo_title($title) {
		echo '<P><BIG><B>'.htmlspecialchars($title).':</B></BIG></P>';
		flush();
	}
	
	function my_print_r($variable) {
		echo '<PRE>'.htmlspecialchars(print_r($variable, true)).'</PRE><HR>';
		flush();
	}

/*
	E. Functions used internally
*/

	function copyscape_api_url_search($url, $full=null, $operation='csearch') {
		$params['q']=$url;

		if (isset($full))
			$params['c']=$full;
		
		return copyscape_api_call($operation, $params, array(2 => array('result' => 'array')));
	}
	
	function copyscape_api_text_search($text, $encoding, $full=null, $operation='csearch') {
		$params['e']=$encoding;

		if (isset($full))
			$params['c']=$full;

		return copyscape_api_call($operation, $params, array(2 => array ('result' => 'array')), $text);
	}

	function copyscape_api_call($operation, $params=array(), $xmlspec=null, $postdata=null) {
		$url=COPYSCAPE_API_URL.'?u='.urlencode(COPYSCAPE_USERNAME).
			'&k='.urlencode(COPYSCAPE_API_KEY).'&o='.urlencode($operation);
		
		foreach ($params as $name => $value)
			$url.='&'.urlencode($name).'='.urlencode($value);
		
		$curl=curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, isset($postdata));
		
		if (isset($postdata))
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		
		$response=curl_exec($curl);
		curl_close($curl);
		
		if (strlen($response))
			return copyscape_read_xml($response, $xmlspec);
		else
			return false;
	}
	
	function copyscape_read_xml($xml, $spec=null) {
		global $copyscape_xml_data, $copyscape_xml_depth, $copyscape_xml_ref, $copyscape_xml_spec;
		
		$copyscape_xml_data=array();
		$copyscape_xml_depth=0;
		$copyscape_xml_ref=array();
		$copyscape_xml_spec=$spec;
		
		$parser=xml_parser_create();
		
		xml_set_element_handler($parser, 'copyscape_xml_start', 'copyscape_xml_end');
		xml_set_character_data_handler($parser, 'copyscape_xml_data');
		
		if (!xml_parse($parser, $xml, true))
			return false;
			
		xml_parser_free($parser);
		
		return $copyscape_xml_data;
	}

	function copyscape_xml_start($parser, $name, $attribs) {
		global $copyscape_xml_data, $copyscape_xml_depth, $copyscape_xml_ref, $copyscape_xml_spec;
		
		$copyscape_xml_depth++;
		
		$name=strtolower($name);
		
		if ($copyscape_xml_depth==1)
			$copyscape_xml_ref[$copyscape_xml_depth]=&$copyscape_xml_data;
		
		else {
			if (!is_array($copyscape_xml_ref[$copyscape_xml_depth-1]))
				$copyscape_xml_ref[$copyscape_xml_depth-1]=array();
				
			if (@$copyscape_xml_spec[$copyscape_xml_depth][$name]=='array') {
				if (!is_array(@$copyscape_xml_ref[$copyscape_xml_depth-1][$name])) {
					$copyscape_xml_ref[$copyscape_xml_depth-1][$name]=array();
					$key=0;
				} else
					$key=1+max(array_keys($copyscape_xml_ref[$copyscape_xml_depth-1][$name]));
				
				$copyscape_xml_ref[$copyscape_xml_depth-1][$name][$key]='';
				$copyscape_xml_ref[$copyscape_xml_depth]=&$copyscape_xml_ref[$copyscape_xml_depth-1][$name][$key];

			} else {
				$copyscape_xml_ref[$copyscape_xml_depth-1][$name]='';
				$copyscape_xml_ref[$copyscape_xml_depth]=&$copyscape_xml_ref[$copyscape_xml_depth-1][$name];
			}
		}
	}

	function copyscape_xml_end($parser, $name) {
		global $copyscape_xml_depth, $copyscape_xml_ref;
		
		unset($copyscape_xml_ref[$copyscape_xml_depth]);

		$copyscape_xml_depth--;
	}
	
	function copyscape_xml_data($parser, $data) {
		global $copyscape_xml_depth, $copyscape_xml_ref;

		if (is_string($copyscape_xml_ref[$copyscape_xml_depth]))
			$copyscape_xml_ref[$copyscape_xml_depth].=$data;
	}
	
?>
