initModal();

function initModal() {
	$('#cm_input').focus();
	
	//closing logic
	$('.created_modal_close').click(function() {
		action = $(this).attr('id');
		if (action == 'created_modal_signup') return false; //already logs
		
		var skip_checks = (action == 'created_modal_close') ? true : false;
		
		gatTrack('Author_engagement', action, 'Publishing_popup');
		
		var processed = processPop(skip_checks);
		if (processed) $.modal.close();
	});
	
	//toggle
	$('#created_modal_toggle').click(function() {
		if ($(this).hasClass('fa-toggle-on')) {
			$(this).removeClass('fa-toggle-on').addClass('fa-toggle-off');
			if ($('#cm_input_box_1')) $('#cm_input_box_1').slideUp();
		}
		else {
			$(this).removeClass('fa-toggle-off').addClass('fa-toggle-on');
			if ($('#cm_input_box_1')) $('#cm_input_box_1').slideDown();
		}
	});
	
	//info circle
	$('#created_modal_info').hover(function() {
		$('#info_tooltip').fadeIn({queue: false, duration: 150});
		$('#info_tooltip').animate({ top: "+=13px" }, 150);
	}, function() {
		$('#info_tooltip').fadeOut({queue: false, duration: 130});
		$('#info_tooltip').animate({ top: "-=13px" }, 130);
	});
	
	//share on the facebook
	$('#created_modal_share_fb').click(function() {
		gatTrack('Author_engagement', 'facebook_share', 'Publishing_popup');
		share_article('facebook');
	});
	
	//share on the twitter
	$('#created_modal_share_tw').click(function() {
		gatTrack('Author_engagement', 'twitter_share', 'Publishing_popup');
		share_article('twitter');
	});
	
	//share via email button
	$('#created_modal_share_em').click(function() {
		gatTrack('Author_engagement', 'email_share', 'Publishing_popup');
		$('#created_modal_share_by_email').slideDown(function() {
			$('#cm_share_input').focus();
		});
	});
	
	//anon signup button
	$('#created_modal_signup').click(function() {
		gatTrack('Author_engagement', 'anon_signup', 'Publishing_popup');
		window.location.href = '/index.php?title=Special:UserLogin&type=signup';
	});
	
	$('#cm_input').change(function() {
		$('#created_modal_toggle').removeClass('fa-toggle-off').addClass('fa-toggle-on');
	});
}

//the processPop opens after the close...
function processPop(skip_checks) {
	target_article = wgTitle;

	//notify author about this article?
	if ($('#created_modal_toggle')) {
		article_watch = $('#created_modal_toggle').hasClass('fa-toggle-on') ? 1 : 0;
		email = $('#cm_input') ? $('#cm_input').val() : '';
		
		if (email && !skip_checks) {
			var isValid = mw.util.validateEmail( email );
			if (!isValid) {
				displayEmailError();
				return false;
			}
		}
			
		if (article_watch == 1 && email == '' && !skip_checks) {
			//you want notifications but you don't want to give us an address? whoops...
			displayEmailError();
			return false;
		}
		else {
			$.post('/Special:AuthorEmailNotification',{ 
				action: 'addNotification', 
				email: email, 
				target: target_article,
				value: article_watch
			});
			if ($('#created_modal_toggle').hasClass('fa-toggle-on')) {
				gatTrack("Author_engagement","Email_updates","Publishing_popup");		
			}
		}
	}
		
	//show future dialogs?
	if ($("#created_modal_checkbox").prop('checked')) {
		$.post('/Special:AuthorEmailNotification',{ 
			action: 'updatePreferences', 
			dontshow: 1 
		});
		gatTrack("Author_engagement","Reject_pub_pop","Reject_pub_pop");
	}
	
	//sharing with friends via email?
	if ($('#cm_share_input').val() != '') {
		$.post('/Special:CreatepageEmailFriend', {
			friends: $('#cm_share_input').val(),
			target: target_article
		});
		gatTrack("Author_engagement","Author_mail_friends","Publishing_popup");
	}
	
	//all good
	return true;
}

//user chose to have email notifications
//but didn't enter an email address
function displayEmailError() {
	$('#cm_input_box_1').css('border-color','#EDA03C');
	$('#created_modal_email_error').slideDown();
}

function share_article(who) {

	switch (who) {

		case 'email':
			window.location='http://' + window.location.hostname + '/Special:EmailLink/' + window.location.pathname;
			break;
		case 'facebook':
			var d=document,f='http://www.facebook.com/share',
				l=d.location,e=encodeURIComponent,p='.php?src=bm&v=4&i=1178291210&u='+e(l.href)+'&t='+e(d.title);1; try{ if(!/^(.*\.)?facebook\.[^.]*$/.test(l.host))throw(0);share_internal_bookmarklet(p)}catch(z){a=function(){if(!window.open(f+'r'+p,'sharer','toolbar=0,status=0,resizable=0,width=626,height=436'))l.href=f+p};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else{a()}}void(0);
			break;
		case 'twitter':
			shareTwitter();
			break;
		case 'delicious':
			window.open('http://del.icio.us/post?v=4&partner=whw&noui&jump=close&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title),'delicious','toolbar=no,width=700,height=400');
			void(0);
			break;
		case 'stumbleupon':
			window.open('http://www.stumbleupon.com/submit?url='+encodeURIComponent(location.href)); void(0);
			break;
		case 'digg':
			window.open(' http://digg.com/submit?phase=2&url=' + encodeURIComponent(location.href) + '&title=' + encodeURIComponent(document.title) + '&bodytext=&topic=');
			break;
		case 'blogger':
			window.open('http://www.blogger.com/blog-this.g?&u=' +encodeURIComponent(location.href)+ '&n=' +encodeURIComponent(document.title), 'blogger', 'toolbar=no,width=700,height=400');
			void(0);
			break;
		case 'google':
			(function(){var a=window,b=document,c=encodeURIComponent,d=a.open("https://plus.google.com/share?url="+c(b.location),"bkmk_popup","left="+((a.screenX||a.screenLeft)+10)+",top="+((a.screenY||a.screenTop)+10)+",height=420px,width=550px,resizable=1,alwaysRaised=1");a.setTimeout(function(){d.focus()},300)})();
			break;
	}
}

