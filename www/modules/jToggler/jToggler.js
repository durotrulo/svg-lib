(function( $ ) {
	$.fn.jToggler = function( options ) 
	{
		// for referencing 'this' inside other scope
		var $this = this;
		var defaults = {
				activeClass	: 'active',
				animType	: 'slide', // fade, slide
				animSpeed	: 'normal', // slow, normal, fast
				target		: $this.attr('data-nette-toggleTarget') || $this.parent().find('.toggler-content'), // selector which should be toggled [array of elements]
				handle		: $this.find('span'), // element visually representing state (used for showing labels Show|Hide)
				showLabel	: 'Show',
				hideLabel	: 'Hide',
				callback	: function() {}
			},
			settings = $.extend({}, defaults, options),
			target = settings.target,
			handle = settings.handle,
			callback = settings.callback;

		var showCallback = function() {
			if (settings.handle) {
				handle.text(settings.hideLabel)
					.removeClass('hide');
			}
			$this.addClass(settings.activeClass);
			callback();
		};
		
		var hideCallback = function(){
			if (settings.handle) {
				handle.text(settings.showLabel)
					.addClass('hide');
			}
			$this.removeClass(settings.activeClass);
			callback();
		};
		
		// enable jQuery chain
		return this.click(function() {
			var isVisible;
			if (settings.animType === 'slide') {
				isVisible = target.css('display') !== 'none';
				if (isVisible) {
					target.slideUp(settings.animSpeed, hideCallback);
				} else {
					target.slideDown(settings.animSpeed, showCallback);
				}
			} else if (settings.animType === 'fade') {
				isVisible = target.css('opacity') !== '0';
				if (isVisible) {
					target.fadeTo(settings.animSpeed, 0, hideCallback);
				} else {
					target.fadeTo(settings.animSpeed, 1, showCallback);
				}
			}
		});
	};
})( jQuery );