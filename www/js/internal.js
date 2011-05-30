/**
 * handle ajax response when in file detail view - adding and deleting tags
 * @param array data
 * @param string textual status of response
 * @param jqXHR
 * 
 * @return void
 */
function fileDetailTags(payload, textStatus, jqXHR)
{
	if (payload.actions) {
		for (var i in payload.actions) {
			switch (payload.actions[i])
			{
				case 'unbindTag':
					$.Nette.remove('#tag-' + payload.tagId);
					$.colorbox.resize();
				break;
	
				case 'addTag':
					var taglist = $('#file-detail-modal .file-detail-taglist ul');
					var tag = buildTagListItem(payload.tags[0], payload.fileId);
					$(tag).opacityHide().appendTo(taglist).opacityFadeIn();

					$.colorbox.resize();

					break;
					
//				case 'info':
//					$.Nette.showInfo(payload.actions[i]);
//					break;
			}
		}
	}
}

/**
 * build HTML formatted tag UL list
 * @param array tags [keys => id, userLevel, name]
 * @return array of formatted li tags
 */
function buildTagList(tags, fileId)
{
	var items = [];
	$.each(tags, function(key, val) {
		items.push(buildTagListItem(val, fileId));
	});
	
	return items;
}


/**
 * build HTML formatted tag LI item
 * @param array tags [keys => id, userLevel, name]
 * @return HTML LI tag
 */
function buildTagListItem(tag, fileId)
{
	var 
		isDeleteAllowed = true,
		replaceStr
		;
	var ret = '<li id="tag-' + tag.id + '" class="user-level-' + tag.userLevel + '">' + tag.name + '__delLink__</li>';
	if (isDeleteAllowed) {
		replaceStr = '<span><a class="ajax" data-nette-spinner="#tagSpinner" rel="nohistory" data-nette-confirm="%delete%" href="' + linkUnbindTag.replace('__fileId__', fileId).replace('__tagId__', tag.id)  + '">X</a></span>';
	} else {
		replaceStr = '';
	}
	return ret.replace('__delLink__', replaceStr);
	
//	return '<li id="tag-' + tag.id + '" class="user-level-' + tag.userLevel + '">' + tag.name + '</li>';
}


/**
 * get textual values of taglist items
 * @return Array
 */
function getTaglistItemValues()
{
	vals = [];
	taglist.find('li').each(function() {
		vals.push($(this).textNodes().eq(0).text());
	});
	return vals;
}

/**
 * toggle visibility of 'Add Tag' prompt and form for adding tags (binding to files)
 * @param bool show form?
 */
function toggleBindTagContainer(showForm)
{
	var container = $('.bindTagContainer');
	var prompt = container.find('span');
	var form = container.find('form');
	if (showForm) {
		prompt.hide();
		form.show();
	} else {
		form.hide();
		prompt.show();
	}
//	$.colorbox.resize();
}


$(function() {
	$.Nette.addDummyUpdateSnippet('#snippet--menu');
	$.Nette.addCallback(fileDetailTags);

	
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
	
	
	
	/* FILE DETAIL VIEW */
	$('.bindTagContainer span.addTagPrompt').livequery('click', function(e) {
		toggleBindTagContainer(true);
	});
	/* FILE DETAIL VIEW END */
	
	
	
	/* LIGHTBOXES */
	
	$('.lb-owner a.toggler').livequery('click', function(e){
		var 
			$this = $(this),
			snippet = $this.next();
		
		// if content has been already loaded
		if (snippet.html().length > 0) {
			// hide
			if (snippet.is(':visible')) {
				snippet.slideUp();
				
			// show loaded content
			} else {
				snippet.slideDown();
			}
			
			// prevent ajax load
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		}
	});
	
	
	/* LIGHTBOXES END */
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
