/* -- (c) Aaron Schulz 2009 */
( function () {
	var showResults = function ( size, cidr ) {
		$( '#mw-checkuser-cidr-res' ).val( cidr );
		$( '#mw-checkuser-ipnote' ).text( size );
	};

	/*
	* This function calculates the common range of a list of
	* IPs. It should be set to update on keyUp.
	*/
	var updateCIDRresult = function () {
		var form = document.getElementById( 'mw-checkuser-cidrform' );
		if ( !form ) {
			return; // no JS form
		}
		form.style.display = 'inline'; // unhide form (JS active)
		var iplist = document.getElementById( 'mw-checkuser-iplist' );
		if ( !iplist ) {
			return; // no JS form
		}
		var text = iplist.value, ips;
		// Each line should have one IP or range
		if ( text.indexOf( '\n' ) !== -1 ) {
			ips = text.split( '\n' );
			// Try some other delimiters too...
		} else if ( text.indexOf( '\t' ) !== -1 ) {
			ips = text.split( '\t' );
		} else if ( text.indexOf( ',' ) !== -1 ) {
			ips = text.split( ',' );
		} else if ( text.indexOf( ' - ' ) !== -1 ) {
			ips = text.split( ' - ' );
		} else if ( text.indexOf( '-' ) !== -1 ) {
			ips = text.split( '-' );
		} else if ( text.indexOf( ' ' ) !== -1 ) {
			ips = text.split( ' ' );
		} else {
			ips = text.split( ';' );
		}
		var binPrefix = 0;
		var prefixCidr = 0;
		var prefix = '';
		var foundV4 = false;
		var foundV6 = false;
		var ipCount;
		var blocs;
		// Go through each IP in the list, get its binary form, and
		// track the largest binary prefix among them...
		for ( var i = 0; i < ips.length; i++ ) {
			// ...in the spirit of mediawiki.special.block.js, call this "addy"
			var addy = ips[ i ].replace( /^\s*|\s*$/, '' ); // trim
			// Match the first IP in each list (ignore other garbage)
			var ipV4 = mw.util.isIPv4Address( addy, true );
			var ipV6 = mw.util.isIPv6Address( addy, true );
			var ipCidr = addy.match( /^(.*)(?:\/(\d+))?$/ );
			// Binary form
			var bin = '';
			var x = 0, z = 0, start = 0, end = 0, ip, cidr, bloc, binBlock;
			// Convert the IP to binary form: IPv4
			if ( ipV4 ) {
				foundV4 = true;
				if ( foundV6 ) { // disjoint address space
					prefix = '';
					break;
				}
				ip = ipCidr[ 1 ];
				cidr = ipCidr[ 2 ] ? ipCidr[ 2 ] : null; // CIDR, if it exists
				// Get each quad integer
				blocs = ip.split( '.' );
				for ( x = 0; x < blocs.length; x++ ) {
					bloc = parseInt( blocs[ x ], 10 );
					binBlock = bloc.toString( 2 ); // concat bin with binary form of bloc
					while ( binBlock.length < 8 ) {
						binBlock = '0' + binBlock; // pad out as needed
					}
					bin += binBlock;
				}
				prefix = ''; // Rebuild formatted binPrefix for each IP
				// Apply any valid CIDRs
				if ( cidr ) {
					bin = bin.substring( 0, cidr ); // truncate bin
				}
				// Init binPrefix
				if ( binPrefix === 0 ) {
					binPrefix = bin;
					// Get largest common binPrefix
				} else {
					for ( x = 0; x < binPrefix.length; x++ ) {
					// binPrefix always smaller than bin unless a CIDR was used on bin
						if ( bin[ x ] === undefined || binPrefix[ x ] !== bin[ x ] ) {
							binPrefix = binPrefix.substring( 0, x ); // shorten binPrefix
							break;
						}
					}
				}
				// Build the IP in CIDR form
				prefixCidr = binPrefix.length;
				// CIDR too small?
				if ( prefixCidr < 16 ) {
					showResults( '!', '>' + Math.pow( 2, 32 - prefixCidr ) );
					return; // too big
				}
				// Build the IP in dotted-quad form
				for ( z = 0; z <= 3; z++ ) {
					bloc = 0;
					start = z * 8;
					end = start + 7;
					for ( x = start; x <= end; x++ ) {
						if ( binPrefix[ x ] === undefined ) {
							break;
						}
						bloc += parseInt( binPrefix[ x ], 10 ) * Math.pow( 2, end - x );
					}
					prefix += ( z === 3 ) ? bloc : bloc + '.';
				}
				// Get IPs affected
				ipCount = Math.pow( 2, 32 - prefixCidr );
				// Is the CIDR meaningful?
				if ( prefixCidr === 32 ) {
					prefixCidr = false;
				}
				// Convert the IP to binary form: IPv6
			} else if ( ipV6 ) {
				foundV6 = true;
				if ( foundV4 ) { // disjoint address space
					prefix = '';
					break;
				}
				ip = ipCidr[ 1 ];
				cidr = ipCidr[ 2 ] ? ipCidr[ 2 ] : null; // CIDR, if it exists
				// Expand out "::"s
				var abbrevs = ip.match( /::/g );
				if ( abbrevs && abbrevs.length > 0 ) {
					var colons = ip.match( /:/g );
					var needed = 7 - ( colons.length - 2 ); // 2 from "::"
					var insert = '';
					while ( needed > 1 ) {
						insert += ':0';
						needed--;
					}
					ip = ip.replace( '::', insert + ':' );
					// For IPs that start with "::", correct the final IP
					// so that it starts with '0' and not ':'
					if ( ip[ 0 ] === ':' ) {
						ip = '0' + ip;
					}
				}
				// Get each hex octant
				blocs = ip.split( ':' );
				for ( x = 0; x <= 7; x++ ) {
					bloc = blocs[ x ] ? blocs[ x ] : '0';
					var intBlock = hex2int( bloc ); // convert hex -> int
					binBlock = intBlock.toString( 2 ); // concat bin with binary form of bloc
					while ( binBlock.length < 16 ) {
						binBlock = '0' + binBlock; // pad out as needed
					}
					bin += binBlock;
				}
				prefix = ''; // Rebuild formatted binPrefix for each IP
				// Apply any valid CIDRs
				if ( cidr ) {
					bin = bin.substring( 0, cidr ); // truncate bin
				}
				// Init binPrefix
				if ( binPrefix === 0 ) {
					binPrefix = bin;
					// Get largest common binPrefix
				} else {
					for ( x = 0; x < binPrefix.length; x++ ) {
					// binPrefix always smaller than bin unless a CIDR was used on bin
						if ( bin[ x ] === undefined || binPrefix[ x ] !== bin[ x ] ) {
							binPrefix = binPrefix.substring( 0, x ); // shorten binPrefix
							break;
						}
					}
				}
				// Build the IP in CIDR form
				prefixCidr = binPrefix.length;
				// CIDR too small?
				if ( prefixCidr < 32 ) {
					showResults( '!', '>' + Math.pow( 2, 128 - prefixCidr ) );
					return; // too big
				}
				// Build the IP in dotted-quad form
				for ( z = 0; z <= 7; z++ ) {
					bloc = 0;
					start = z * 16;
					end = start + 15;
					for ( x = start; x <= end; x++ ) {
						if ( binPrefix[ x ] === undefined ) {
							break;
						}
						bloc += parseInt( binPrefix[ x ], 10 ) * Math.pow( 2, end - x );
					}
					bloc = bloc.toString( 16 ); // convert to hex
					prefix += ( z === 7 ) ? bloc : bloc + ':';
				}
				// Get IPs affected
				ipCount = Math.pow( 2, 128 - prefixCidr );
				// Is the CIDR meaningful?
				if ( prefixCidr === 128 ) {
					prefixCidr = false;
				}
			}
		}
		// Update form
		if ( prefix !== '' ) {
			var full = prefix;
			if ( prefixCidr !== false ) {
				full += '/' + prefixCidr;
			}
			showResults( '~' + ipCount, full );
		} else {
			showResults( '?', '' );
		}

	};

	// Utility function to convert hex to integers
	var hex2int = function ( hex ) {
		hex = hex.toLowerCase();
		var intform = 0;
		for ( var i = 0; i < hex.length; i++ ) {
			var digit = 0;
			switch ( hex[ i ] ) {
				case 'a':
					digit = 10;
					break;
				case 'b':
					digit = 11;
					break;
				case 'c':
					digit = 12;
					break;
				case 'd':
					digit = 13;
					break;
				case 'e':
					digit = 14;
					break;
				case 'f':
					digit = 15;
					break;
				default:
					digit = parseInt( hex[ i ], 10 );
					break;
			}
			intform += digit * Math.pow( 16, hex.length - 1 - i );
		}
		return intform;
	};

	$( function () {
		updateCIDRresult();
		$( '#mw-checkuser-iplist' ).on( 'keyup click', function () {
			updateCIDRresult();
		} );
	} );
}() );
