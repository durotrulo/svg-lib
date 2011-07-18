$(function() {
	// make project types inline
	$('#frmitemForm-type-1').livequery(function() {
		$(this).prev('br').remove();
	});

	
	/* Check availability of item [project, user, package] via AJAX */
	var	timeoutHandle, 	// to be able to cancel timeout
		request;		// to be able to cancel XHR
	$('.checkAvailability').livequery('keyup change', function() {
		var $this = $(this),
			url = $this.data('nette-check-url'),
			timeout = 300;
	
		// abort timeout and running XHR
		clearTimeout(timeoutHandle);
		if (typeof(request) === 'object') {
			request.abort();
		};
		
		// set new timeout
		timeoutHandle = setTimeout(function() {
			var val = $this.val();
			if (val.length > 0) {
				url = url.replace('__NAME__', val);
				request = $.get(url, function(data, textStatus, jqXHR){
					var next = $this.next();
					if (next.length > 0) {
						next.replaceWith(data.availability);
					} else {
						$this.after(data.availability);
					}
				});
			} else {
				$this.next().remove();
			}
		}, timeout);
	});
});