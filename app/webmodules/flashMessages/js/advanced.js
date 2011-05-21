/*****************************************************************************/
/* Kazda flash sprava sa po $showTime schova								 */
/* Ku kazdej flash sprave prida krizik na zatvorenie [div.flash-msg-close],  */
/* ak su aspon 2, zobrazi Close All button [div.flash-msg-close-all]		 */
/*****************************************************************************/

//	zrusi vsetky livequery predchadzajuce a nastavi nove
$(function() {
	$("div.flash").expire().livequery(function () {
		afterInjectFlashMsg($(this));
	});
});

var $visibleMsgsCount = 0; // pocet viditelnych flash spraviciek pre casovo inkrementalne schovavanie
var $closeAllMsgs = null; // box na zatvorenie vsetkych flash sprav
var $animTime = 1000; // dlzka trvania animacie spriehladnovania v ms [slideUp je 2x rychlejsi]
var $showTime = 5000; // ako dlho bude zobrazena sprava -> inkrementalne narasta, cize 3.sprava sa schova po 3 * $showTime
function hideFlashMsg(el)
{
	// ak uz je zobrazena iba 1 sprava, animaciou schovame aj closeAll
	if ($visibleMsgsCount == 1) {
		hideAllFlashMsgs();
	} else {
		el.animate({'opacity': 0}, $animTime)
		  .slideUp($animTime / 2, function(){
				$(this).remove();
				$visibleMsgsCount--;
			});
	}
}

function hideAllFlashMsgs()
{
	$(".flashes div")
		.fadeOut($animTime, function(){
			$(this).remove();
			$closeAllMsgs = null;
		});
	
	$visibleMsgsCount = 0;
}

function afterInjectFlashMsg(el)
{
	$visibleMsgsCount++;

	// pridame krizik na zatvorenie
	$('<div class="flash-msg-close" />').click(function() {
		hideFlashMsg(el);
	}).appendTo(el);
	
	setTimeout(function () {
		hideFlashMsg(el);
	}, $visibleMsgsCount * $showTime); // inkrementalne sa schovavaju po 7 sekundach
	
	//	close all button
	if ($visibleMsgsCount > 2 && $closeAllMsgs == null) {
		$closeAllMsgs = $('<div class="flash-msg-close-all" />')
			.text('Close All')
			.click(function(){
				hideAllFlashMsgs();
			})
			.appendTo(el.parent());
	}
}
