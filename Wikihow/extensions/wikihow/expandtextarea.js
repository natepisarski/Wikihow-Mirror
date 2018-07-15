
function settextareacookie(id, val) {
	var date = new Date();
	date.setTime(date.getTime()+(30*24*60*60*1000));
	var expires = "; expires="+date.toGMTString();
	document.cookie = "txtarea_" + id+"="+val+expires+"; path=/";

}
function expandtext(id) {
	var textarea = document.getElementById(id);
	if (textarea.rows < 20 ) {
		textarea.rows += 4;
		settextareacookie(id, textarea.rows);
	}
}
function compresstext(id) {
	var textarea = document.getElementById(id);
	if (textarea.rows > 6)  {
		textarea.rows -= 4;
		settextareacookie(id, textarea.rows);
	}
}


