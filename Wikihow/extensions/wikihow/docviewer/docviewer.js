$(document).ready(function() {

	$('#dv_dls li').hover(
		function(){
			$(this).find(".sample_hover").fadeIn(100);
		},
		function(){
			$(this).find(".sample_hover").fadeOut(100);
		}
	);

    $('#sampleAccuracyYes').click(function(e){
        e.preventDefault();
        WH.ratings.rateItem(1, wgSampleName, 'sample', 'desktop');
    });

    $('#sampleAccuracyNo').click(function(e){
        e.preventDefault();
        WH.ratings.rateItem(0, wgSampleName, 'sample', 'desktop');
    });

});
