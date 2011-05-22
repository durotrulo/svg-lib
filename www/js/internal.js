$.Nette.addDummyUpdateSnippet('#snippet--menu');

$(function() {
	// show options if shown last time
	loadOptionsVisibility();
	
	// FilesPresenter thumb size control
	/*
	$('.thumbsizeControl a').each(function(index) {
		$(this).click(function(e) {
			setThumbSize(index+1);
			return false;
		});
	});
	*/
	$('.thumbsizeControl a').live('click', function(e) {
		var self = $(this);
		setThumbSize(self.attr('title'));
		
		// set active class for link
		$('.thumbsizeControl a').removeClass('active');
		self.addClass('active');
		
		return false;
	});
	
	
	$('.options-pannel .toggler').live('click', function(e) {
		var self = $(this);
		var content = self.siblings('div');
		var togglerSpan = $('span', this);
		setOptionsVisibility(content, togglerSpan);
	});
	
	
	
	$('.upload-icon').click(function(e) {
		$('.fileUploadArea').slideToggle();
	});
	
	$('.cancelUpload').click(function(e) {
		$('.fileUploadArea').slideUp();
	});
	
	
	// FilesPresenter complexity form
	$('#frmcomplexityForm-complexity_id').live('change', function(e) {
		$(this).closest('form').submit();
	});
	
});


/**
 * sets .options (in)visible according to cookie set
 **/
function loadOptionsVisibility()
{
	if ($.cookie("optionsVisible") === 'true') {
		setOptionsVisibility($('.options'), $('.toggler span'));
	}
}



/**
 * sets .options (in)visible and sets cookie accordingly
 * @param jQuery Element
 * @param jQuery Element
 **/
function setOptionsVisibility(content, togglerSpan)
{
//	var isVisible = content.is(':visible');
	var isVisible = content.css('opacity') !== '0';
	if (isVisible) {
//			content.hide('fast', function(){
//		content.fadeOut('fast', function(){
		content.fadeTo('fast', 0, function(){
			togglerSpan.text('Show')
				.addClass('hide');
			setOptVisCookie(!isVisible);
		});
	} else {
//			content.show('fast', function() {
//		content.fadeIn('fast', function() {
		content.fadeTo('fast', 1, function() {
			togglerSpan.text('Hide')
				.removeClass('hide');
			setOptVisCookie(!isVisible);
		});
	}
}


/**
 * sets cookie for options visibility to remember last state
 * @param bool
 **/
function setOptVisCookie(isVisible)
{
	$.cookie(
		"optionsVisible", 
		isVisible, 
		{
			expires: 365,
			path: '/' // path must be specified to be compatible with server-side cookies
		}
	);
}


/**
 * sets cookie for persistence, class for parent container and src to required $size
 * @param enum [small|medium|large]
 **/
function setThumbSize($size)
{
	//cookie set
	$.cookie(
		"thumbSize", 
		$size, 
		{
			expires: 365,
			path: '/' // path must be specified to be compatible with server-side cookies
//			domain: 'jquery.shaddow.sk'
		}); 
	
	// set class for parent container
	$('.itemList').replaceAttr('class', /thumbSize-(small|medium|large)/, 'thumbSize-' + $size);
	$('.itemList img').replaceAttr('src', /(small|medium|large)/, $size);
	
}
