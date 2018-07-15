( function ( mw, $ ) {
	WH.uciFeedback = (function() {
		$('#uci_images').on('click', ".uci_img_flag", function(e) {
			e.preventDefault();
			e.stopPropagation();

			var uci_img = this;
			mw.loader.using( ['jquery.ui.dialog'], function () {
				var msg = '<p class="uci_margin_5px">Are you sure you want to flag this image as inappropriate?</p>';
				var buttons = '<p class="uci_controls"><input type="button" class="button primary uci_button" value="Submit"></input><a href="#" class="uci_cancel">Cancel</a></s></p>';
				$("#dialog-box").html(msg + '' + buttons);
				$("#dialog-box").dialog( {
					modal: true,
					title: "Flag inappropriate image",
					width: 340,
					position: 'center',
					closeText: 'x',
				});
				$('.uci_button').unbind();
				$('.uci_button').on('click', function(e) {
					var imagePageId = $(uci_img).parent().attr('pageid');
					var isSidebar = false;
					if ( $(uci_img).parents('#uci_sidebox').length > 0 ) {
						isSidebar = true;
					}

					$.post('/Special:PicturePatrol', {
						flag: true,
						pageId: imagePageId,
						hostPage: wgTitle,
						sidebar: isSidebar
						},
						function (result) {
							$(uci_img).parent().remove();
						}
					);

					$('#dialog-box').dialog('close');
					return false;
				});
				$('.uci_cancel').unbind();
				$('.uci_cancel').on('click', function(e) {
					e.preventDefault();
					$('#dialog-box').dialog('close');
					return false;
				});
				return false;
			});
		});
	}());
}( mediaWiki, jQuery ) );
