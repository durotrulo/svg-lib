$(function() {

	// @see http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
	var config = {
		toolbar :
	        [
			    ['Source', 'Undo','Redo','-','Styles', 'Format', 'Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', '-', 'Link', 'Unlink', '-', 'About']
//				    '/', // newline
	        ],
	        format_tags: 'p;h1;h2;h3;h4;div',
	        language: 'sk',
	        skin: 'office2003',
	       
//	        autoUpdateElement: true,
	        
	        /*
			 * Simple HTML5 doctype
			 */
			docType : '<!DOCTYPE HTML>',
			resize_maxWidth: '100%',
			height: '400px',
			stylesSet :
				[
					{ name : 'Red', element : 'span', attributes: {'class': 'red'}}
				],
				
			/* CKFINDER settings @see http://docs.cksource.com/CKFinder_2.x/Developers_Guide/PHP/CKEditor_Integration_V1 */
			filebrowserBrowseUrl : $baseUri + 'js/ckeditor/ckfinder/ckfinder.html',
//			filebrowserImageBrowseUrl : /'{{$baseUri}}webtemp/richtext/ckeditor/ckfinder/ckfinder.html?type=Images',
//			filebrowserFlashBrowseUrl : '{{$baseUri}}webtemp/richtext/ckeditor/ckfinder/ckfinder.html?type=Flash',
//			filebrowserUploadUrl : $baseUri + 'webtemp/richtext/ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files'
			filebrowserUploadUrl : $baseUri + 'js/ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files&currentFolder=/'
	};

	// Initialize the editor.
	// Callback function can be passed and executed after full instance creation.
	$('textarea.wysiwyg').ckeditor(
		function() {},
		config
	);

});