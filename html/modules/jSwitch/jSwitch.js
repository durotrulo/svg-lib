(function($){
    $.fn.jSwitch = function(options) {
        var defaults = {
        	onLabel: 'On',
        	offLabel: 'Off'
        };
        options = $.extend(defaults, options);
        
        var obj = null;
        var obj_a = null;
        var obj_c = null;

        //return this.each(function() {
            // instantiate all the objects we'll use for easy access
            obj = $(this);
            obj_a = obj.find("a");
            
            console.log(obj_a);
            
            obj_c = obj.find("input[type=checkbox]");
            
            // disable selection and hide checkbox and disable dragging
            obj.attr('unselectable', 'on').css('MozUserSelect', 'none');
            obj_c.hide();
            obj_a.bind('dragstart', function(event) { event.preventDefault(); });
            
            if (obj_c.is(':checked')) {
            	handle("on");  
            } else {
            	handle("off");
            }
            
            obj_a.click(function(e) {
                e.preventDefault();
                if (obj_a.hasClass("on")) {
                	handle("off");
                } else {
                	handle("on");
                }
            });

            function handle(mode){
                if (mode == "on") {
                    obj_a.addClass("on").removeClass("off");
                    obj_c.attr('checked', true);
                } else {
                    obj_a.addClass("off").removeClass("on");
                    obj_c.attr('checked', false);
                }
            }
        //});
    };
})(jQuery);

$(function() {
    $(".switch").each(function() {
        $(this).jSwitch();
    });
});