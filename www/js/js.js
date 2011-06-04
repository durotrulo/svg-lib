$(function() {
	// DatePicker, @see http://nette.merxes.cz/date-picker/
	$("input.date").livequery(function () { // input[type=date] does not work in IE
        var el = $(this);
        var value = el.val();
        var date = (value ? $.datepicker.parseDate($.datepicker.W3C, value) : null);

        var minDate = el.attr("min") || null;
        if (minDate) minDate = $.datepicker.parseDate($.datepicker.W3C, minDate);
        var maxDate = el.attr("max") || null;
        if (maxDate) maxDate = $.datepicker.parseDate($.datepicker.W3C, maxDate);

        el.get(0).type = "text"; // changing via jQuery is prohibited, because of IE
        el.datepicker({
            minDate: minDate,
            maxDate: maxDate
        });
        el.val($.datepicker.formatDate(el.datepicker("option", "dateFormat"), date));
    });
    
    // show form erorrs as jGrowl
   	$("form ul.error li").livequery(function () {
    	var $this = $(this);
		$.jGrowl($this.text(), {
			theme: 'error',
			life: 7000
		});
		$this.remove();
	});
});
