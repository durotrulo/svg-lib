// $.error mapping
var debug = true;
window.myConsole={
	"error": function() {
		if (debug) {
			console.error.apply(console,arguments);
		} else {
			var errorMsg = arguments[0];
			alert(errorMsg);
		}
	}
};


function getCloneHtml(selector)
{
	return $('<div>').append($(selector).clone().removeAttr('id')).remove().html();
}


function roundDecimal(num, dec)
{
    return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
}

function formatFileSize(bytes)
{
    if (isNaN(bytes) || bytes === null) {
        return '';
    }
    if (bytes >= 1000000000) {
        return roundDecimal(bytes / 1000000000, 2) + ' GB';
    }
    if (bytes >= 1000000) {
        return roundDecimal(bytes / 1000000, 2) + ' MB';
    }
    return roundDecimal(bytes / 1000, 2) + ' KB';
}


var fileUploads = {
	count: 0, // number of files to be uploaded
	size: 0, // total size of all files
	
	updateFilesInfo: function()
    {
    	$('.filesInfo').text(this.count + ' files | ' +  formatFileSize(this.size) + ' total');
    },
    
    add: function(file)
    {
    	this.count++;
    	this.size += file.size;
    	this.updateFilesInfo();
    },
     
    remove: function(file)
    {
    	this.count--;
    	this.size -= file.size;
    	this.updateFilesInfo();
    }
};
	
/*global $ */
$(function () {
	$.error = myConsole.error;
	
	var dropZoneSel = '#frmitemForm-main_img'; // must be selector, not working when $(..)
	var fileUploadForm = $('.fileUploadForm');
	
	$('<table id="files"></table>').insertAfter($(dropZoneSel));
	
    fileUploadForm.fileUploadUI({
    	maxFilesCount: 1, // added for check
    	dropZone: $(dropZoneSel),
        uploadTable: $('#files'),
//	        downloadTable: $('#files'),
        buildUploadRow: function (files, index) {
        	fileUploads.add(files[index]);
          	
           	return $('<tr class="fileUpload-item">' +
                '<td class="file_upload_preview"><span class="filename">' + files[index].name + '</span> <span>' + formatFileSize(files[index].size) + '</span><br></td>' +
                '<td class="file_upload_progress"><div></div></td>' +
                '<td class="file_upload_cancel"><button class="ui-state-default ui-corner-all" title="Cancel"><span class="ui-icon ui-icon-cancel">Cancel</span></button></td>' +
                '</tr>');
        },
//	        buildDownloadRow: function (file) {
//	            return $('<tr><td>' + file.name + '<\/td><td>' + file.size + '</td><\/tr>');
//	        },

		// allows uploading on click (not automatically ondrop or onselect)
        beforeSend: function (event, files, index, xhr, handler, callBack) {
        	
        	// to prevent submitting form when canceling image to upload
    	 	handler.uploadRow.find(handler.cancelSelector).click(function (e) {
        	 	return false;
            });
            
        	// to prevent submitting form via ajax
            if (isset(handler.uploadForm)) {
	        	$(handler.uploadForm.context).replaceAttr('class', /\s*ajax\s*/, '');
            // when dragging files, uploadForm is not set
            } else {
	        	fileUploadForm.replaceAttr('class', /\s*ajax\s*/, '');
            }
        	
        	// submit only if form passed Nette validation
        	$('#frmitemForm-save').click(function (e) {
	            if (Nette.validateForm(handler.uploadForm.context)) {
                    $.Nette.showSpinner();
	            	callBack();
	            }
	            
	            e.stopImmediatePropagation();
	            e.preventDefault();
	    		return false;
	        });
	    },
	    
	    onAbort: function (event, files, index, xhr, handler) {
        	fileUploads.remove(files[index]);
            handler.removeNode(handler.uploadRow);
            
            if (fileUploads.count === 0) {
           		$(dropZoneSel).show();
           }

           $.Nette.hideSpinner();
        },
	    
	    initUpload: function (event, files, index, xhr, handler, callBack) {
		    // max. number of files constraints
	    	var files2upload = $('tr', this.uploadTable).length;
	    	if (index >= this.maxFilesCount || files2upload >= this.maxFilesCount) {
		    	$.error('You can upload max. ' + this.maxFilesCount + ' file(s).');
	    		return false;
	    	}
	    	
          	$(dropZoneSel).hide();
	    	
		    // original initUpload copied from fileUploadUI	
	    	handler.initUploadRow(event, files, index, xhr, handler);
            handler.addNode(
                handler.uploadTable,
                handler.uploadRow,
                function () {
                    if (typeof handler.beforeSend === 'function') {
                        handler.beforeSend(event, files, index, xhr, handler, callBack);
                    } else {
                        callBack();
                    }
                }
            );
		},
		
		// make sure server response is processed by Nette.success
		onComplete: function (event, files, index, xhr, handler) {
        	fileUploads.remove(files[index]);
			handler.removeNode(handler.uploadRow);

			$.Nette.hideSpinner();

            if (fileUploads.count === 0) {
            }
            
		    var json = handler.response;
			$.Nette.success(json, xhr.statusText, xhr);
		}
    });
});
