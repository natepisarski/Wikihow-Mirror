/*global WH, $, mw*/

WH.CopyArticleImages = WH.CopyArticleImages || {};

try {
	WH.CopyArticleImages.preferences = JSON.parse( mw.cookie.get( 'copyarticleimages' ) );
} catch ( error ) {
	WH.CopyArticleImages.preferences = { selection: [] };
}
WH.CopyArticleImages.savePreferences = function () {
	mw.cookie.set( 'copyarticleimages', JSON.stringify( WH.CopyArticleImages.preferences ) );
};

$( function () {
	// Create
	var app = WH.CopyArticleImages.app = new WH.CopyArticleImages.MainComponent();
	WH.Render( app, document.getElementById( 'cai' ) );
} );
