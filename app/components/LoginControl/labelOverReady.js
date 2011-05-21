$(function() {

	//	uz v common.js
//	$('.onlyJS').show();
	
  	/****************************** LABEL OVER LOGIN FORM SOLUTION FOR CREDENTIALS STORED IN BROWSER ******************************************/
  	//	labelover na password v loginForm
  	$('.loginFrm label.onlyJS').labelOver('over-apply');
	
  	var pswInput = $('#frmloginForm-password');
  	var loginInput = $('#frmloginForm-login');
	var focusTimer; // holds Interval instance
  	// aby sa nezobrazil label aj cez ulozene credentials v browseri
  	window.setTimeout(function() {
  		loginInput.focus().blur();
  		pswInput.focus().blur();
  	}, 150);
  	
  	loginInput.focus(function(){
  		if (pswInput.val() == '') {
  			if (focusTimer) {window.clearInterval(focusTimer);} // ak uz bezi, tak zrusime, aby sme sa vyhli slucke
	  		focusTimer = window.setInterval(function() {
		  		if (pswInput.val() != '') {
		  			pswInput.focus().blur();
		  			window.clearInterval(focusTimer);
		  		}
	  		}, 150);
  		}
  	}).blur(function(){
  		window.clearInterval(focusTimer);
  	});
  	/****************************** LABEL OVER LOGIN FORM END ******************************************/
  	
});
