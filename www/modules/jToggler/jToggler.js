/*
 * jToggler - jQuery toggler plugin
 *
 * Copyright (c) 2010 Matus Matula
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.github.com/__TODO__
 *
 */

/**
 * Version 1.0
 *
 *
 * @name  jToggler
 * @type  jQuery
 * @param Hash    				options            		additional options 
 * @param String  				options[activeClass] 	class to toggle on toggler element (applied when toggleContent is visible)
 * @param String 				options[animType] 		type of animation [slide | fade]
 * @param String|Int 			options[animSpeed]  	speed of animation [slow | normal | fast | integer in ms]
 * @param String|jQuery el		options[target]  		element to be toggled
 * @param String|jQuery el		options[handle]  	 	element visually representing state (used for showing labels Show|Hide)
 * @param String  				options[showLabel]  	label for %handle% when target is NOT visible
 * @param String  				options[hideLabel]  	label for %handle% when target is visible
 * @param Function 			options[onComplete] 	function(settings, original) { ... } called after animation(hide/show) completed
 *             
 */

(function( $ ) {
	$.fn.jToggler = function( options ) 
	{
		var defaults = {
				activeClass	: 'active',
				animType	: 'slide', // fade, slide
				animSpeed	: 'normal', // slow, normal, fast
				target		: this.attr('data-nette-toggleTarget') || this.parent().find('.toggler-content'),
				handle		: this.find('span'),
				showLabel	: 'Show',
				hideLabel	: 'Hide',
				onComplete	: function() {}
			},
			settings = $.extend({}, defaults, options),
			target = settings.target,
			handle = settings.handle,
			onComplete = settings.onComplete,
			$this = this;		// for referencing 'this' inside other scope


		// callback after targetContent get visible
		var showCallback = function() {
			if (settings.handle) {
				handle.text(settings.hideLabel)
					.removeClass('hide');
			}
			$this.addClass(settings.activeClass);
			onComplete();
		};
		
		// callback after targetContent get hidden
		var hideCallback = function() {
			if (settings.handle) {
				handle.text(settings.showLabel)
					.addClass('hide');
			}
			$this.removeClass(settings.activeClass);
			onComplete();
		};
		
		// is target visible?
		var isVisible = function() {
			if (settings.animType === 'slide') {
				return target.css('display') !== 'none';
			} else if (settings.animType === 'fade') {
				return target.css('opacity') !== '0';
			}
		};
		
		// init on setup - no need to bother with classes in HTML
		if (isVisible()) {
			showCallback();
		} else {
			hideCallback();
		}
		
		// enable jQuery chain
		return this.click(function() {
			if (settings.animType === 'slide') {
				if (isVisible()) {
					target.slideUp(settings.animSpeed, hideCallback);
				} else {
					target.slideDown(settings.animSpeed, showCallback);
				}
			} else if (settings.animType === 'fade') {
				if (isVisible()) {
					target.fadeTo(settings.animSpeed, 0, hideCallback);
				} else {
					target.fadeTo(settings.animSpeed, 1, showCallback);
				}
			}
		});
	};
})( jQuery );