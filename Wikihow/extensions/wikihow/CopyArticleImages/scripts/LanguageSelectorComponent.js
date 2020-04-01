/*global WH, mw*/

WH.CopyArticleImages.LanguageSelectorComponent = WH.Render.createComponent( {
	create: function () {
		this.state = { selection: WH.CopyArticleImages.preferences.selection };
		this.options = WH.CopyArticleImages.langs.filter( function ( lang ) {
			return lang.code !== 'en';
		} );
	},
	render: function () {
		// console.log( 'render', this.state.selection.join() );
		var state = this.state;
		return [ 'div.cai-languageSelector',
			[ 'p', mw.message( 'cai-languageselector-instructions' ).text() ],
			[ 'div.cai-languageselector-tools',
				[ 'a',
					{ href: '#select-all', onclick: 'onSelectAllClick' },
					mw.message( 'cai-languageselector-all' ).text()
				],
				[ 'a',
					{ href: '#select-none', onclick: 'onSelectNoneClick' },
					mw.message( 'cai-languageselector-none' ).text()
				]
			],
			[ 'ul' ].concat(
				this.options.map( function ( lang ) {
					return [ 'li',
						[ 'label',
							[ 'input', {
								type: 'checkbox',
								value: lang.code,
								checked: state.selection.indexOf( lang.code ) !== -1,
								onchange: 'onCheckboxChange'
							} ],
							[ 'span', lang.name ]
						]
					];
				} )
			)
		];
	},
	onCheckboxChange: function ( e ) {
		if ( e.target.checked ) {
			if ( this.state.selection.indexOf( e.target.value ) == -1 ) {
				this.changeSelection( this.state.selection.concat( [ e.target.value ] ) );
			}
		} else {
			this.changeSelection( this.state.selection.filter( function ( code ) {
				return code !== e.target.value;
			} ) );
		}
	},
	onSelectAllClick: function ( e ) {
		e.preventDefault();
		this.changeSelection( this.options.map( function ( lang ) {
			return lang.code;
		} ) );
		return false;
	},
	onSelectNoneClick: function ( e ) {
		e.preventDefault();
		this.changeSelection( [] );
		return false;
	},
	changeSelection: function ( selection ) {
		this.change( { selection: selection } );
		WH.CopyArticleImages.preferences.selection = selection;
		WH.CopyArticleImages.savePreferences();
		if ( this.props.onChange ) {
			this.props.onChange();
		}
	},
	getSelection: function () {
		return this.state.selection;
	}
} );
