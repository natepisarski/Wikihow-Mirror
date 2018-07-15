<?php
if (!defined('MEDIAWIKI')) die();

class FeaturedRSSFeed extends RSSFeed {

        /**
         * Original implementation in parent class: Feed.php#ChannelFeed
         */
        function outXmlHeader() {
            global $wgStylePath, $wgStyleVersion;

            $this->httpHeaders();
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<?xml-stylesheet type="text/css" href="' .
                htmlspecialchars( wfExpandUrl( "$wgStylePath/common/feed.css?$wgStyleVersion", PROTO_CURRENT ) ) .
                '"?' . ">\n";
        }

        function outHeader() {
                global $wgCanonicalServer;

                $this->outXmlHeader();
		?><rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
        <channel>
                <title><?= $this->getTitle() ?></title>
                <copyright>This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 2.5 License, except where noted otherwise.</copyright>
                <link><?= $this->getUrl() ?></link>
                <description><?= $this->getDescription() ?></description>
                <language><?= $this->getLanguage() ?></language>
                <lastBuildDate><?= $this->formatTime( wfTimestampNow() ) ?></lastBuildDate>
                <ttl>20</ttl>
                <image>
                        <title>wikiHow</title>
                        <width>144</width>
                        <height>37</height>
                        <link><?= $this->getUrl() ?></link>
                        <url><?= $wgCanonicalServer ?>/skins/WikiHow/wikiHow.gif</url>
                </image>
<?php
        }

        function outItem( $item ) {
        ?>
                <item>
                        <title><?= $item->getTitle() ?></title>
                        <link><?= $item->getUrl() ?></link>
                        <guid isPermaLink="true"><?= $item->getUrl() ?></guid>
                        <description><?= $item->getDescription() ?></description>
                        <?php if( $item->getDate() ) { ?><pubDate><?= $this->formatTime( $item->getDate() ) ?></pubDate><?php } ?>
                        <?php if( $item->getAuthor() ) { ?><dc:creator><?= $item->getAuthor() ?></dc:creator><?php }?>
                </item>
<?php
        }

        function outHeaderFullFeed() {
                global $wgCanonicalServer;

                $this->outXmlHeader();

		?><rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" >
        <channel>
                <title><?= $this->getTitle() ?></title>
                <copyright>This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 2.5 License, except where noted otherwise.</copyright>
                <link><?= $this->getUrl() ?></link>
                <description><?= $this->getDescription() ?></description>
                <language><?= $this->getLanguage() ?></language>
                <lastBuildDate><?= $this->formatTime( wfTimestampNow() ) ?></lastBuildDate>
                <ttl>20</ttl>
                <image>
                        <title>wikiHow</title>
                        <width>144</width>
                        <height>37</height>
                        <link><?= $this->getUrl() ?></link>
                        <url><?= $wgCanonicalServer ?>/skins/WikiHow/whlogo-rssfeed-20140707.jpg</url>
                </image>
<?php
        }

        function outItemFullFeed( $item, $content, $images ) {
			global $wgLanguageCode;
        ?>
                <item>
                        <title><?= $item->getTitle() ?></title>
                        <link><?= $item->getUrl() ?></link>
                        <guid isPermaLink="true"><?= $item->getUrl() ?></guid>
                        <description><?= $item->getDescription() ?></description>
                        <content:encoded><![CDATA[<?= $content ?>]]></content:encoded>
                        <?php if( $item->getDate() && $wgLanguageCode == 'en') { ?>
								<pubDate><?= $this->formatTime( $item->getDate() ) ?></pubDate>
								<?php } ?>
                        <?php if( $item->getAuthor() ) { ?><dc:creator><?= $item->getAuthor() ?></dc:creator><?php }?>
                        <?php if (isset($images)) {
                           foreach ($images as $i) { $this->outImageMRSS($i); }
                        } ?>
                </item>
<?php
        }

        function outHeaderMRSS() {
                global $wgCanonicalServer;

                $this->outXmlHeader();
?>
        <rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">
        <channel>
                <title><?= $this->getTitle() ?></title>
                <copyright>This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 2.5 License, except where noted otherwise.</copyright>
                <link><?= $this->getUrl() ?></link>
                <description><?= $this->getDescription() ?></description>
                <language><?= $this->getLanguage() ?></language>
                <lastBuildDate><?= $this->formatTime( wfTimestampNow() ) ?></lastBuildDate>
                <ttl>20</ttl>
                <image>
                        <title>wikiHow</title>
                        <width>144</width>
                        <height>37</height>
                        <link><?= $this->getUrl() ?></link>
                        <url><?= $wgCanonicalServer ?>/skins/WikiHow/whlogo-rssfeed-20140707.jpg</url>
                </image>
<?php
        }

        function outImageMRSS($img) {
				global $wgCanonicalServer;
?>
<media:content url="<?= wfGetPad( $img['src'] ) ?>" type="<?= $img['mime'] ?>" medium="image" />
<?php
        }

        function outItemMRSS( $item, $images ) {
?>
                <item>
                        <title><?= $item->getTitle() ?></title>
                        <link><?= $item->getUrl() ?></link>
                        <guid isPermaLink="true"><?= $item->getUrl() ?></guid>
                        <description><?= $item->getDescription() ?></description>
                        <?php if( $item->getDate() ) { ?><pubDate><?= $this->formatTime( $item->getDate() ) ?></pubDate><?php } ?>
                        <?php if( $item->getAuthor() ) { ?><dc:creator><?= $item->getAuthor() ?></dc:creator><?php }?>
                        <?php foreach ($images as $i) { $this->outImageMRSS($i); } ?>
                </item>
<?php
        }
}
