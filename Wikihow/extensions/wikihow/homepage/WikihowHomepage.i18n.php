<?php

$messages = [];

$messages['en'] = [
	'wh_in_other_langs' =>  <<<EOS
<a href='https://www.wikihow.com'>English</a>,
 <a href='https://es.wikihow.com'>espa&ntilde;ol</a>,
 <a href='https://www.wikihow.cz'>Čeština</a>,
 <a href='https://de.wikihow.com'>Deutsch,
 <a href='https://fr.wikihow.com'>Fran&ccedil;ais</a>,
 <a href='https://hi.wikihow.com'>हिन्दी</a>,
 <a href='https://id.wikihow.com'>Bahasa Indonesia</a>,
 <a href='https://www.wikihow.it'>Italiano</a>,
 <a href='https://ja.wikihow.com'>日本語</a>,
 <a href='https://nl.wikihow.com'>Nederlands</a>,
 <a href='https://pt.wikihow.com'>Portugu&ecirc;s</a>,
 <a href='https://ru.wikihow.com'>Русский</a>,
 <a href='https://ar.wikihow.com'>العربية</a>,
 <a href='https://th.wikihow.com'>ไทย</a>,
 <a href='https://www.wikihow.com.tr'>Türkçe</a>,
 <a href='https://www.wikihow.vn'>Tiếng Việt</a>,
 <a href='https://ko.wikihow.com'>한국어</a>,
 <a href='https://zh.wikihow.com'>中文</a>.
EOS
	,
];

$messages['es'] = [
	/**
	 * This message is already stored in the ES DB, but we redefine it here so links to the
	 * ES homepage from other langs don't point to the default ES homepage ("/Página-Principal").
	 * @see WikihowHomepage::getLanguageLinksForHomePage()
	 */
	'mainpage' =>  'Portada',
];
