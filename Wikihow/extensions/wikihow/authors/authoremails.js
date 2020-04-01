
function send_test(item) {
	var url = "/Special:AuthorEmailNotification?target="+item+"&action=testsend";
	new Ajax.Request(url, {
	method: 'get',
	onSuccess: function(transport) {
		alert('Email submitted. '+ url);
	}

	});

	return false;
}

function aeNotification(obj, pageid) {
	var url = "";

	if (obj.checked == true) {
		url = "/Special:AuthorEmailNotification?target="+pageid+"&action=update&watch=1";
	} else {
		url = "/Special:AuthorEmailNotification?target="+pageid+"&action=update&watch=0";
	}

	setNotification(url);
	return false;
}

function setNotification(url) {
	if (wgUserName == null) {
		//don't know what we have
		//alert('invalid call parameters');
		return false;
	}

	new Ajax.Request(url, {
	method: 'get',
	onSuccess: function(transport) {
	}

	});

	return false;
}

function aenReorder(obj) {
	if  (document.getElementById('icon_navi_down')) {
		window.location = '/Special:AuthorEmailNotification?orderby=time_asc'; 
	} else {
		window.location = '/Special:AuthorEmailNotification';
	}
}


function getCookie(c_name) {
	if (document.cookie.length > 0) {
		c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1) {
			c_start=c_start + c_name.length+1;
			c_end=document.cookie.indexOf(";",c_start);
			if (c_end==-1) c_end=document.cookie.length;
			return unescape(document.cookie.substring(c_start,c_end));
		}
	}
	return "";
}

function deleteCookie(name) {
	if ( getCookie(name) )
		document.cookie = name + "=;expires=Thu, 01-Jan-1970 00:00:01 GMT";
}
