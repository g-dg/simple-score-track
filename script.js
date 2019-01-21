// document.ready
$(function () {
	// restore scroll position
	var scrollData = sessionStorage.getItem('_scrollRestore');
	if (scrollData !== null) {
		scrollData = JSON.parse(scrollData);
		// check if this is the previous page
		if (scrollData.url === window.location.href) {
			// restore the scroll position
			$(window).scrollTop(scrollData.vert);
			$(window).scrollLeft(scrollData.horiz);
		}
	}
	// set the event handler
	$(window).scroll(function () {
		var scrollData = {
			url: window.location.href,
			vert: $(window).scrollTop(),
			horiz: $(window).scrollLeft()
		}
		sessionStorage.setItem('_scrollRestore', JSON.stringify(scrollData));
	});
	// save the current scroll location and window url
	$(window).scroll();
});
