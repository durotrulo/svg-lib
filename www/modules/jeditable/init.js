$(function() {
 	$('.editable').livequery(function() {
 		$this = $(this);
 		$this.editable($this.data('editableProcessUrl'), {
	    	indicator 	: 'Saving...',
//	        tooltip   	: 'Click to edit...',
	        name		: $this.data('editableName'),
	        id			: 'elementId',
	        style		: 'inherit',
	        event		: 'dblclick'
//	        ajaxoptions	: { type: 'GET' }
	    });
 	});
 	
 	$('.trigger-editable').livequery('click', function() {
	    	$(this).parent().prevAll('.editable').trigger('dblclick');
    });
});