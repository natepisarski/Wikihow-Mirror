/*global WH, TAFFY*/
WH.VideoBrowser.Catalog = function Catalog() {

	/* Properties */

	this.videos = TAFFY();
	this.categories = TAFFY();
	this.maxCategorySize = 0;
	this.minCategorySize = 0;
	this.maxCategoryPopularity = 0;
	this.minCategoryPopularity = 0;

	/* Initialization */

	var catalog = this;
	var data = WH.VideoBrowser.data;
	var router = WH.VideoBrowser.router;
	var watched = JSON.parse( localStorage.getItem( 'video-browser' ) || '{}' );
	// Insert data from server
	catalog.videos.insert( data.videos );
	// Apply access times from localstorage
	watched.videos = watched.videos || {};
	catalog.videos().update( function () {
		var accessed = watched.videos[this.id];
		this.accessed = accessed !== undefined ? accessed : 0;
		this.watched = !!this.accessed;
		this.slug = this.title.replace( /\s+/g, '-' );
		this.pathname = router.link( '/' + this.slug );
		return this;
	} );

	// Insert data from server
	var counts = {};
	var categories = catalog.videos()
		.order( 'watched' )
		.get()
		.reduce( function ( categories, video ) {
			var i, len, title,
				titles = video.categories.split( ',' );
			for ( i = 0, len = titles.length; i < len; i++ ) {
				title = titles[i];
				if ( categories[titles[i]] === undefined ) {
					categories[titles[i]] = {
						title: title,
						size: 0,
						watched: 0,
						popularity: 0
					};
				}
				categories[titles[i]].popularity += parseInt( video.popularity );
				categories[titles[i]].size++;
				if ( video.watched ) {
					categories[titles[i]].watched++;
				}
			}
			return categories;
		}, {} );
	var keys = [];
	for ( var key in categories ) {
		keys.push( categories[key] );
	}
	catalog.categories.insert( keys );

	// Scale popularity - separate pass so rank has maxPopularity
	catalog.categories().update( function () {
		this.popularity /= this.size;
		if ( this.size > catalog.maxCategorySize ) {
			catalog.maxCategorySize = this.size;
		}
		if ( this.size < catalog.minCategorySize ) {
			catalog.minCategorySize = this.size;
		}
		if ( this.popularity > catalog.maxCategoryPopularity ) {
			catalog.maxCategoryPopularity = this.popularity;
		}
		if ( this.popularity < catalog.minCategoryPopularity ) {
			catalog.minCategoryPopularity = this.popularity;
		}
		return this;
	} );

	// Apply access times from localstorage
	watched.categories = watched.categories || {};
	catalog.categories().update( function () {
		var accessed = watched.categories[this.title];
		this.accessed = accessed !== undefined ? accessed : 0;
		this.rank = catalog.rankCategory( this );
		return this;
	} );
};

WH.VideoBrowser.Catalog.prototype.persist = function () {
	localStorage.setItem(
		'video-browser',
		JSON.stringify( {
			categories: this.categories().get()
				.reduce( function ( data, category ) {
					if ( category.accessed ) {
						data[category.title] = category.accessed;
					}
					return data;
				}, {} ),
			videos: this.videos().filter( { watched: true } ).get()
				.reduce( function ( data, video ) {
					data[video.id] = video.accessed;
					return data;
				}, {} )
		} )
	);
};

WH.VideoBrowser.Catalog.prototype.watchVideo = function ( video ) {
	var catalog = this,
		now = ( new Date() ).getTime();
	catalog.categories( video.categories.split( ',' ).map( function ( category ) {
		return { title: category };
	} ) ).update( function () {
		if ( !video.watched ) {
			this.watched++;
			this.rank = catalog.rankCategory( this );
		}
		this.accessed = now;
		return this;
	} );
	catalog.videos( video ).update( { watched: true, accessed: now } );
	catalog.persist();
};

WH.VideoBrowser.Catalog.prototype.rankCategory = function ( category ) {
	// Accessed score for recency of access in range [0..1]
	// 0 if more than 30 days ago, 1 if just now
	var period = 60 * 60 * 24 * 30;
	var start = ( new Date() ).getTime() - period;
	var accessed = Math.max( ( category.accessed - start ) / period, 0 );
	// Unwatched score for how much content is still available in range [0..1]
	// 0 = nothing left, 1 = everything left
	var unwatched = 1 - ( category.watched / category.size );
	// Size score for how large the category is compared to others
	// 0 = smallest category, 1 = largest category
	var size = ( category.size - this.minCategorySize ) /
		( this.maxCategorySize - this.minCategorySize );
	// Popularity score for how popular the videos in the category are compared to others
	// 0 = least popular, 1 = most popular
	var popularity = ( category.popularity - this.minCategoryPopularity ) /
		( this.maxCategoryPopularity - this.minCategoryPopularity );
	// Composite score in range [0..1]
	return ( accessed + popularity + unwatched * 0.5 + size * 0.25 ) / 2.75;
};
