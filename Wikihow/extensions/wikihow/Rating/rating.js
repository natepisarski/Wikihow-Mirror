WH.ratings.bindShowInputFields = function () {
	var show_inner_div = function () {
		$('.article_rating_inner').show();
	};

	$(document).on('focus', '.article_rating_textarea', show_inner_div);
	$(document).on('click', '.article_rating_detail,.article_rating_textarea', show_inner_div);
};

