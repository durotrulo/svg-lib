function getHtmlClone(selector)
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


//function initCommonControls(origProjectsIdSelector, origTagsSelector, origComplexityIdSelector)
function initCommonControls()
{
	var filesHeader = $('#files .files-header');
	filesHeader.html(
		'<td>File name</td>' + 
		'<td class="projectCommonSelect">' + getHtmlClone(origProjectsIdSelector) + '</td>' + 
		'<td class="tagsCommonSelect">' + getHtmlClone(origTagsSelector) + '</td>' + 
		'<td class="complexityCommonSelect">' + getHtmlClone(origComplexityIdSelector) + '</td>' + 
		'<td>Top level file</td>'
	);
	
	// set common project for selected files
	filesHeader.find('.projectCommonSelect select').livequery('change', function(e){
		$('.fileUpload-item ' + projectsIdSelector).val($(this).val());
	});
	
	// set common complexity for selected files
	filesHeader.find('.complexityCommonSelect select').livequery('change', function(e){
		$('.fileUpload-item ' + complexityIdSelector).val($(this).val());
	});
}
	

	var origProjectsIdSelector = '#frmfileUploadForm-projects_id';
	var origComplexityIdSelector = '#frmfileUploadForm-complexity_id';
	var origTagsSelector = '#frmfileUploadForm-tags';
	var origTopFileSelector = '#frmfileUploadForm-is_top_file';
	var projectsIdSelector = '.project-select';
	var complexityIdSelector = '.complexity-select';
	var tagsSelector = '.tags-input';
	var tagsValSelector = '.tag-value';
	var topFileSelector = '.top-file-select';



/*global $ */
$(function () {
//	$.error = myConsole.error;

	// nice checkboxes for top level files
//    $(".top-file-select :checkbox").livequery(function() {
    $(".top-file-select").livequery(function() {
    	$(this).iphoneStyle();
    });

    // submit form after each tag selected
	$('.tagsCommonSelect span.tag-value span').livequery(function() {
		var $this = $(this);
		var tagValue = $this.parent();
		$('.tag-control-helper')
			.not(tagValue.parent().find('.tag-control-helper'))
			.val($this.textNodes().eq(0).text())
			.change();
		tagValue.empty();
	});

	var isUploadInProgress = false;
	
	initCommonControls();

	var fileUploadForm = $('#frm-fileUploadForm');
	$('<div class="upload-button">Browse or drag-and-drop</div>').appendTo(fileUploadForm);
	
	var fileUploadFooter = $('.fileUpload-footer');
	
    fileUploadForm.fileUploadUI({
    	maxFilesCount: maxUploadedFilesCount, // max number of files to be uploaded on one go, defined in config.ini
    	previewAsCanvas: false,
//    	previewAsCanvas: true,
    	imageTypes: /^image\/(gif|jpeg|png|svg|svg\+xml)$/,
    	previewMaxWidth: 150,
    	previewMaxHeight: 113,
        uploadTable: $('#files'),
//	        downloadTable: $('#files'),
        buildUploadRow: function (files, index) {
        	fileUploads.add(files[index]);
          	fileUploadFooter.show();

          	return $('<tr class="fileUpload-item">' +
                '<td class="file_upload_preview"> <span class="file_upload_cancel"><button class="ui-state-default ui-corner-all" title="Cancel">' +
	                '<span class="ui-icon ui-icon-cancel">Cancel<\/span>' +
	                '<\/button>&nbsp;&nbsp;&nbsp;</span> <span class="filename">' + files[index].name + '</span> <span>' + formatFileSize(files[index].size) + '</span><br><\/td>' +
                '<td class="file_upload_project_id">' + getHtmlClone(origProjectsIdSelector) + '<\/td>' +
                '<td class="file_upload_tags">' + getHtmlClone(origTagsSelector) + '<\/td>' +
                '<td class="file_upload_complexity_id">' + getHtmlClone(origComplexityIdSelector) + '<\/td>' +
                '<td class="file_upload_is_top_file">' + getHtmlClone(origTopFileSelector) + '<\/td>' +
                '<td class="file_upload_progress"><div><\/div><\/td>' +
                '<td class="file_upload_start invisible">' +
                '<button class="ui-state-default ui-corner-all" title="Start Upload">' +
                '<span class="ui-icon ui-icon-circle-arrow-e">Start Upload<\/span>' +
                '<\/button><\/td><\/tr>');
        },
//	        buildDownloadRow: function (file) {
//	            return $('<tr><td>' + file.name + '<\/td><td>' + file.size + '</td><\/tr>');
//	        },
        beforeSend: function (event, files, index, xhr, handler, callBack) {
        	initTagInput();
        	handler.uploadRow.find('.file_upload_start button').click(function () {
	            // Poll every 100ms until some_condition is true,
				$.doTimeout(100, function() {
					if ( isUploadInProgress === false ) {
			  			isUploadInProgress = true;
				  		log(handler.uploadRow.find(topFileSelector).attr('checked'));
				  		// put data to original form to allow Nette validation
			            $(projectsIdSelector, handler.uploadForm).val(handler.uploadRow.find(projectsIdSelector).val());
		            	$(complexityIdSelector, handler.uploadForm).val(handler.uploadRow.find(complexityIdSelector).val());
		            	$(tagsSelector, handler.uploadForm).val(handler.uploadRow.find(tagsSelector).val());
		            	$(tagsValSelector, handler.uploadForm).html(handler.uploadRow.find(tagsValSelector).html());
//		            	$(topFileSelector, handler.uploadForm).val(handler.uploadRow.find(topFileSelector).val());
		            	$(topFileSelector, handler.uploadForm).attr('checked', !!handler.uploadRow.find(topFileSelector).attr('checked'));
				  		
				  		if (Nette.validateForm(handler.uploadForm.context)) {
				  			// show progress bar
				  			$('.file_upload_progress div', handler.uploadRow).show();
				  			
	            			callBack();
	            			// wait so callBack() get fired (upload() send ajax request)
				  			setTimeout(function() {
				  				isUploadInProgress = false;
				  			}, 100);
				  		} else {
			  				isUploadInProgress = false;
				  			return false; // cancel poll
				  		}
				  		
				    	return false;
				  	}
				  
				  	return true;
				});
	        });
	    },
	    
	    onAbort: function (event, files, index, xhr, handler) {
        	fileUploads.remove(files[index]);
        	
            handler.removeNode(handler.uploadRow);
            
            if (fileUploads.count === 0) {
            	fileUploadFooter.hide();
            }
        },
	    
	    initUpload: function (event, files, index, xhr, handler, callBack) {
		    // max. number of files constraints
	    	var files2upload = $('tr', this.uploadTable).length;
	    	if (index >= this.maxFilesCount || files2upload >= this.maxFilesCount) {
		    	$.error('You can upload max. ' + this.maxFilesCount + ' file(s).');
	    		return false;
	    	}
	    	
	    	if (files2upload === 0) {
	    		
	    	}
	    	
//	    	log(handler);
//	    	log(xhr);
//	    	log(handler.requestHeaders);

	    	$('#start_uploads').removeClass('invisible');
	    	
		    // skopcena initUpload z povodneho fileUploadUI	
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
//            handler.initUploadProgressAll();
	    	
		},
		
//		onLoad: function (event, files, index, xhr, handler) {
//		},
		
		// zaistim, aby server response bola spracovana Nette.success
		onComplete: function (event, files, index, xhr, handler) {
        	fileUploads.remove(files[index]);
//        	log('complete');
			handler.removeNode(handler.uploadRow);
			
            if (fileUploads.count === 0) {
            	fileUploadFooter.hide();
            }
            
		    var json = handler.response;
			$.Nette.success(json, xhr.statusText, xhr);
		}
    });
    
    // upload vsetkych suborov naraz
    $('#start_uploads').click(function () {
	    $('.file_upload_start button').click();
	});
});
