( function($, mw) {

function changePeriod(obj) {
	//alert(obj.value);
	window.location = '/Special:Leaderboard/' + lb_page + '?period=' + obj.value;
}

function showArticles(obj) {
	//alert('/Special:Leaderboard/' + lb_page + '?action=articles&lb_name=' + obj.name + '&period=' + lb_period);
	popModal('/Special:Leaderboard/' + lb_page + '?action=articles&lb_name=' + obj.name + '&period=' + lb_period, 600, 380);
}

// External methods
window.WH.Leaderboard = {
	changePeriod : changePeriod,
	showArticles : showArticles
};

}(jQuery, mediaWiki) );
