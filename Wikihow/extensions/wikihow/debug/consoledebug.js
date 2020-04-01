// this will print MediaWiki debug log messages to the debug console
// and queries as well
window.WH.consoleDebug = (function () {

	function addConsoleDebug(data) {
		if (!data || !data.log || data.log.length == 0) {
			return;
		}

		if ($("table#mw-debug-console").length == 0) {
			info = mw.config.get('debugInfo');
			if (info) {
				info.log = info.log.concat(data.log);
			}
		} else {

			var entryTypeText;

			entryTypeText = function( entryType ) {
				switch ( entryType ) {
					case 'log':
						return 'Log';
					case 'warn':
						return 'Warning';
					case 'deprecated':
						return 'Deprecated';
					default:
						return 'Unknown';
				}
			};
			for (x in data.log) {
				entry = data.log[x];
				entry.typeText = entryTypeText( entry.type );

				$('<tr>' )
						.append( $( '<td>' )
							.text( entry.typeText )
							.addClass( 'mw-debug-console-' + entry.type )
						)
						.append( $( '<td>' ).html( entry.msg ) )
						.append( $( '<td>' ).text( entry.caller ) )
						.appendTo( $('table#mw-debug-console') );
			}

			rows = $('table#mw-debug-console tr').length;
			$("#mw-debug-console a.mw-debug-panelabel").html("Console (" + rows + ")");
		}
		delete data.log;
	}

	function addQueries(data) {
		if (!data || !data.queries || data.queries.length == 0) {
			return;
		}

		if ($("table#mw-debug-querylist").length == 0) {
			info = mw.config.get('debugInfo');
			if (info) {
				info.queries = info.log.concat(data.queries);
			}
		} else {
			for (x in data.queries) {
				query = data.queries[x];

				$( '<tr>' )
					.append( $( '<td>' ).text( 1 ) )
					.append( $( '<td>' ).text( query.sql ) )
					.append( $( '<td class="stats">' ).text( ( query.time * 1000 ).toFixed( 4 ) + 'ms' ) )
					.append( $( '<td>' ).text( query['function'] ) )
				.appendTo( $('table#mw-debug-querylist') );
			}

			rows = $('table#mw-debug-querylist tr').length - 1;
			$("#mw-debug-querylist a.mw-debug-panelabel").html("Queries (" + rows + ")");
		}

		delete data.queries;
	}

	// lots of this code was adapted from mediawiki.debug.js
	return function(data) {
		addConsoleDebug(data);
		addQueries(data);
	}
}());
