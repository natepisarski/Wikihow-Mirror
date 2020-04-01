/*global WH, mw*/

WH.CopyArticleImages.ListComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			items: [],
			newItems: [],
			loading: false,
		};
	},
	render: function () {
		var state = this.state;

		var list = [];
		var newItems = [];

		if ( state.newItems.length ) {
			newItems = [ [ 'tr.cai-listItem',
				[ 'td',
					{ colspan: 5 },
					mw.message( 'cai-list-newitems', state.newItems.length ).parse()
				]
			] ];
		}

		list.push(
			[ 'table' ].concat(
				newItems,
				state.items.length ?
					[ [ 'tr',
						[ 'th', { colspan: 2 }, mw.message( 'cai-list-from' ).text() ],
						[ 'th', { colspan: 2 }, mw.message( 'cai-list-to' ).text() ],
						[ 'th', mw.message( 'cai-list-creator' ).text() ]
					] ] : [],
				state.items.length ?
					state.items.map( function ( item ) {
						return [ 'tr.cai-listItem',
							[ 'td', getLanguageName( item.fromLang ) ],
							[ 'td',
								[ 'a',
									{
										href: getLanguageUrlPrefix( item.fromLang ) + item.fromTitle,
										target: '_blank',
										title: 'ID: ' + item.fromAID
									},
									item.fromTitle.replace( /-/g, ' ' )
								]
							],
							[ 'td', getLanguageName( item.toLang ) ],
							[ 'td',
								item.toAID && item.toTitle ?
									[ 'a',
										{
											href: getLanguageUrlPrefix( item.toLang ) + item.toTitle,
											target: '_blank',
											title: 'ID: ' + item.toAID
										},
										item.toTitle.replace( /-/g, ' ' )
									] :
									mw.message( 'cai-list-itemnotfound' ).text()
							],
							[ 'td', item.creator || '-' ]
						];
					} ) :
					[ [ 'tr.cai-listEmpty', [ 'td', { colspan: 5 }, mw.message( 'cai-list-empty' ).text() ] ] ]
			)
		);

		return [ 'div.cai-list',
			[ 'div.cai-listHeading',
				state.loading ?
					[ 'span.cai-listRefresh',
						mw.message( 'cai-list-loading' ).text()
					] :
					[ 'a.cai-listRefresh',
						{ href: '#refresh', onclick: 'onClick' },
						mw.message( 'cai-list-refresh' ).text()
					],
				[ 'h2', mw.message( 'cai-list-title' ).text() ]
			]
		].concat( list );
	},
	load: function () {
		if ( this.state.loading ) {
			return;
		}
		var api = new mw.Api(),
			done = this.onQueryImageTransfersDone.bind( this ),
			fail = this.onQueryImageTransfersFail.bind( this ),
			params = { 
				action: 'query',
				list: 'imagetransfers'
			};
		var loading = api.get( params ).then( done, fail );
		this.change( { loading: true } );
		if ( this.props.onLoad ) {
			this.props.onLoad( loading );
		}
	},
	addItems: function ( items ) {
		// It would be nice to add the items right away, but since the items don't have complete,
		// information and might be duplicates of existing items being moved to the top, just show
		// a "loading" notice and reload the full list
		this.change( { newItems: items.queued.concat( items.failed ) } );
		this.load();
	},
	onClick: function ( e ) {
		this.load();
		return false;
	},
	onQueryImageTransfersDone: function ( data ) {
		// TODO: Handle success
		console.log( 'onQueryImageTransfersDone', arguments );
		var items = data.query.imagetransfers;
		this.change( { loading: false, items: items, newItems: [] } );
		return items;
	},
	onQueryImageTransfersFail: function ( error ) {
		// TODO: Handle error
		console.log( 'onQueryImageTransfersFail', arguments );
		this.change( { loading: false } );
	}
} );

function getLanguageName( lang ) {
	var langs = WH.CopyArticleImages.langs;
	for ( var i = 0, len = langs.length; i < len; i++ ) {
		if ( langs[i].code === lang ) {
			return langs[i].name;
		}
	}
	return '(' + lang + ')';
}
function getLanguageUrlPrefix( lang ) {
	var overrides = {
		en: 'https://www.wikihow.com/',
		it: 'https://www.wikihow.it/',
		vi: 'https://www.wikihow.vn/',
		cs: 'https://www.wikihow.cz/',
		ja: 'https://www.wikihow.jp/',
		tr: 'https://www.wikihow.com.tr/',
	};
	if ( lang in overrides ) {
		return overrides[lang];
	}
	return 'https://' + lang + '.wikihow.com/';
}