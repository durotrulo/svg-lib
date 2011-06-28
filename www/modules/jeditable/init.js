$(function() {
 	$('.editable').livequery(function() {
 		$this = $(this);
 		$this.editable($this.data('editableProcessUrl'), {
	    	indicator 	: 'Saving...',
//	        tooltip   	: 'Click to edit...',
	        name		: $this.data('editableName'),
	        id			: 'elementId',
	        style		: 'inherit',
	        type    	: $this.data('editableType') || 'input',
	        submit    	: $this.data('editableSubmit') || '',
	        data: function(value, settings) {
		      	/* Convert <br> to newline. */
//		      	return value;
//		      	var retval = value.replace(/<br[\s\/]?>/gi, '\n');
		      	var retval = value.replace(/<br[\s\/]?>/gi, '');
		      	return retval;
		    },
	        event		: 'dblclick'
//	        ajaxoptions	: { type: 'GET' }
	    });
 	});
 	
 	$('.trigger-editable').livequery('click', function() {
//    	$(this).parent().prevAll('.editable').trigger('dblclick');
    	$(this).parent().parent().find('.editable').trigger('dblclick');
    });
});