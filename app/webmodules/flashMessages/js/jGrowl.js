/* transform all flash messages to jGrowl messages, removing original */
$(function() {
    $("div.flash").livequery(function () {
    	var $this = $(this);
		$.jGrowl($this.text(), {
			theme: $this.attr('class').split(' ')[1], // info | success | warning | error
			life: 7000
		});
		$this.remove();
	});
});