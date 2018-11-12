/*global WH, mw*/
WH.VideoBrowser.SliderComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {};
		this.itemsElement = null;
		this.interval = null;
	},
	render: function () {
		var slider = this;
		return [ 'div.videoBrowser-slider',
			[ 'div.videoBrowser-slider-items',
				function ( el ) { slider.element = el; }
			].concat( this.props.items || [] ),
			[ 'div.videoBrowser-slider-forward', { onclick: 'onForwardClick' } ],
			[ 'div.videoBrowser-slider-back', { onclick: 'onBackClick' } ],
		];
	},
	onForwardClick: function ( e ) {
		this.scrollItems( 600 );
		return false;
	},
	onBackClick: function ( e ) {
		this.scrollItems( -600 );
		return false;
	},
	scrollItems: function ( distance ) {
		var slider = this;
		if ( this.element ) {
			var direction = distance > 0 ? 1 : -1;
			var step = Math.abs( distance ) / 10;
			var progress = 0;
			if ( slider.interval ) {
				clearInterval( slider.interval );
			}
			slider.interval = setInterval( function () {
				if ( slider.element ) {
					slider.element.scrollLeft += step * direction;
					progress += step;
					if ( progress >= Math.abs( distance ) ){
						clearInterval( slider.interval );
					}
				}
			}, 10 );
		}
	}
} );
