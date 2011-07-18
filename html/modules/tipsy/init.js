/**
 *
 * @see http://onehackoranother.com/projects/jquery/tipsy/
 *
 */
$(function() {
	$('.menu-icon').tipsy({
//		gravity: $.fn.tipsy.autoNS, // select north/south gravity, respectively, based on the element's location in the viewport. 
		gravity: 's', // select north/south gravity, respectively, based on the element's location in the viewport. 
		fade: true,
		opacity: 0.7
	});
});