WH.Hypothesis = new ( function () {
	var data = { cfg: {}, tpl: {} };

	function set( type, list ) {
		for ( key in list ) {
			data[type][key] = list[key];
		}
	}

	function get( type, key ) {
		return data[type][key];
	}

	this.config = {
		set: set.bind( null, 'cfg' ),
		get: get.bind( null, 'cfg' )
	};

	this.template = {
		set: set.bind( null, 'tpl' ),
		get: get.bind( null, 'tpl' ),
		render: function ( name, params ) {
			return Mustache.render( data.tpl[name], params );
		}
	};
} )();
