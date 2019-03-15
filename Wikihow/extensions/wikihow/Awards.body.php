<?php

class Awards extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Awards' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$out->setSquidMaxage( 3600 );

		$out->addHTML("

<style>
div.content
{
border-left:1px solid gray;
padding:1em;
}
</style>
<h1 class=\"firstHeading\">Vote here to help wikiHow win this award </a></h1>

<table valign=\"top\">
<tr><td width=\"220\">
<iframe width=\"210\" marginheight=\"0\" marginwidth=\"0\" frameborder=\"0\" height=\"390\" src=\"http://mashable.polldaddy.com/widget/x2.aspx?f=f&c=20&cn=230\"></iframe> <noscript><a href=\"http://mashable.com/2008/11/19/openwebawards-voting-1/\">Mashable Open Web Awards</a></noscript>

</td>
<td valign=\"top\">
<div class=\"content\">
<strong>Open web award for best wiki</strong>
<ul>
<li>Enter email address</li>
<li>Vote once per day until Dec 15th</li>
</ul>
</div>
</td>
</tr>

</table>

		");


	}

}
