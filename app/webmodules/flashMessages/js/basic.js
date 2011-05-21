$(function() {

    // skrývání flash zpráviček - zakladne .. pokrocilejsie pomocou flashMsgs.js
    $("div.flash").livequery(function () {
		var el = $(this);
		setTimeout(function () {
			el.animate({"opacity": 0}, 2000);
			el.slideUp();
		}, 7000);
	});
});