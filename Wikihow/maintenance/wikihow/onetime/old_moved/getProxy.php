<?
	$file = @fopen( "/dev/urandom", "r" );
        if ( $file ) {
                $secretKey = bin2hex( fread( $file, 32 ) );
                fclose( $file );
        } else {
                $secretKey = "";
                for ( $i=0; $i<8; $i++ ) {
                        $secretKey .= dechex(mt_rand(0, 0x7fffffff));
                }
                print "<li>Warning: \$wgSecretKey key is insecure, generated with mt_rand(). Consider changing it manually.</li>\n";
        }
	echo $secretKey;
?>
