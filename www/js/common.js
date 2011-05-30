var debug = debug || false;

$(function() {
	$baseUri = '{{$baseUri}}';

	$.error = myConsole.error;

//  	$('input[text], textarea').live('focus', function(){
  	$('input[text], textarea').not('#dialog input[text], #dialog textarea').live('focus', function(){ // v dialogu nechcem selectovat
        this.select();
    });

    $('select.sumbitOnChange').livequery('change', function(e) {
    	$(this).closest('form').submit();
    });
    
    // remove everything what should NOT be visible if javascript enabled
	$('.noJS').livequery(function () {
//		var parentTag = $(this).parent().get(0).tagName;
		var el = $(this);
		if (el.hasClass('noJS-tr')) { // after '-' is parent tag that shall be removed
			el.closest('tr').remove();
		} else {
			$(this).remove();
		}
	});

	// remove class that ensures non-js compatibility
	$('.noJSfallback').removeClass('noJSfallback');

	// show everything what should be visible ONLY if javascript enabled
    $('.onlyJS').show();
    
    /* spam protection */
 	$(".nospam").livequery(function () {
 		$(this).hide();
 	});
    $("input.nospam").livequery(function () {
    	$(this).val("no" + "spam");
    });

    //	vsetko co sa zacina na 'external' tak sa otvara do noveho okna..da sa to pekne rozdelit do skupin [fork, visual, ..]
	//	dal som tu aj nofollow odkazy
	$('a[rel*=external], a[rel=nofollow]').each(function(key) {
		el = $(this);
		el.attr('target', el.attr('rel'));
		el.attr('rel', 'nofollow');
	});
	
	// confirm for non-ajaxed links [ajaxed handled in jquery.nette.js]
	$('a[data-nette-confirm]:not(.ajax)').live('click', $.Nette.confirm);
	
	// simulate placeholder behaviour
	$('[placeholder]').livequery(function () {
		$(this).placeholder();
	});
	
	
	// linky kt. treba implementovat 
	$('.all a[href=-]')
		.css({
			backgroundColor: 'red',
			color: 'white',
			padding: '1px 3px'
		})
		.click(function(e){
			log('to be implemented!');
	});
		
	// loads content via ajax - url stored in data-nette-contentLink attribute
	$('[data-nette-contentLink]').each(function(k, el) {
		el = $(el);
		var link = el.attr('data-nette-contentLink');
		
		$.get(link, function(payload, textStatus, XMLHttpRequest){
			el.html(payload.data);
		});
	});
	
	/*
    // skrývání flash zpráviček
	$("div.flash").livequery(function () {
		var el = $(this);
		setTimeout(function () {
			el.animate({"opacity": 0}, 2000);
			el.slideUp();
		}, 7000);
	});
   */
});

// moja vlastna konzola .. volam vsade $.error, kt. je namapovany na toto
window.myConsole={
	"error": function() {
		if (debug && isset(window.console)) {
			window.console.error.apply(window.console,arguments);
		} else {
			var errorMsg = arguments[0];
			alert(errorMsg);
		}
	}
};

function isset(v)
{
	return !(v === null || v === undefined);
}

function empty(v)
{
	return !(isset(v) && v.length > 0);
}

function log(msg)
{
	if (!debug) {return false;}
	
	// In the first line I am using 'window.x' instead of just 'x'. This works because all variables are also defines as properties of the window object. 
	// 'x === undefined' will only work if the variable has actually been declared with 'var x' (but not assigned a value). Therefore it is safer to use 'window.x' which will not result in an error if the variable hasn't been declared.
	if(window.console) 
	{
		console.log(msg);
	} else if (window.opera && window.opera.postError) {
		window.opera.postError(msg);
	} else {
		alert(msg);
	}
}

/**
 * @see testy/jqueryUI for complete form widget
 * requires jQuery UI CSS a jQuery UI Buttons
 *
 */
function applyUItoForm(formId)
{
	$('#' + formId)
//		.addClass('ui-widget-content')
		.addClass('ui-widget')
		.find('input[type=text], input[type=password], textarea')
			.addClass('ui-state-default ui-widget-content ui-corner-all')
			.bind({
			  	focusin: function() {
				   	$(this).toggleClass('ui-state-focus');
				},
			   	focusout: function() {
				    $(this).toggleClass('ui-state-focus');
				}
			  });

	$('.button').button();
}


/**
 * allows pretty way to replace attr with regex
 * @param string attribute name
 * @param regex to search in attribute
 * @param string
 * ex: $('.itemList').replaceAttr('class', /thumbSize-(small|medium|large)/, 'thumbSize-small');
 */
jQuery.fn.replaceAttr = function(attrName, searchRE, rep) { 
    return this.attr( 
        attrName, 
        function() { return jQuery(this).attr(attrName).replace(searchRE, rep); } 
    ); 
}; 


/**
 * find text nodes within element
 *
 * @see http://refactormycode.com/codes/341-jquery-all-descendent-text-nodes-within-a-node#refactor_12159
 */
jQuery.fn.textNodes = function() {
  	var ret = [];
  	this.contents().each( function() {
	    var fn = arguments.callee;
      	if ( this.nodeType == 3 || $.nodeName(this, "br") ) 
        	ret.push( this );
      	else $(this).contents().each(fn);
  	});
  	
  	return $(ret);
}


/*
 * fadeIn() using opacity - suitable when can not use fadeIn() - typically for display:inline-block 
 * @return jQuery
 */
jQuery.fn.opacityFadeIn = function(duration, callback) {
	return $(this).animate({
		opacity: 1
	}, duration, callback);
}


/*
 * fadeOut() using opacity - suitable when can not use fadeOut() - typically for display:inline-block 
 * @return jQuery
 */
jQuery.fn.opacityFadeOut = function(duration, callback) {
	return $(this).animate({
		opacity: 0
	}, duration, callback);
}


/*
 * show() using opacity - suitable when can not use show() - typically for display:inline-block 
 * @return jQuery
 */
jQuery.fn.opacityShow = function() {
	return $(this).css('opacity', 1);
}


/*
 * hide() using opacity - suitable when can not use hide() - typically for display:inline-block 
 * @return jQuery
 */
jQuery.fn.opacityHide = function() {
	return $(this).css('opacity', 0);
}
