$(function() {
 	$('.editable').livequery(function() {
 		el = $(this);
 		el.editable(el.data('editableProcessUrl'), {
	    	indicator 	: 'Saving...',
	        tooltip   	: 'Click to edit...',
	        name		: el.data('editableName'),
	        id			: 'elementId',
	        style		: 'inherit'
//	        ajaxoptions	: { type: 'GET' }
	    });
 	});
});