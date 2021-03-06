/**
 * Plugin to build the ComfortUpdater Buttons
 * @param {Object} options
 */
$.fn.buildComfortButtons = function(options)
{
	// Will be used later for animation params
	var defauts={};  
	var params=$.extend(defauts, options); 
	
	return this.each(function(){
		$(this).on('click', function(e){
			// First, we check the required branches, depending of the user choice "Show update notifications: " ( #updateBranch generated by php update controller inside the view)
			$url = $("#updateBranch").val() == "both" ? "update/sa/getbothbuttons" : "update/sa/getstablebutton";
			
			//We show the loader
			$("#updatesavailable").empty();	$("#ajaxLoading").show();
		    
		    // We request and append the html button  
		    $.ajax({
				url : $url,
				type : 'GET',
				dataType : 'html', 
				
				// html contains the buttons
				success : function(html, statut){
						$("#ajaxLoading").hide();
						$("#updatesavailable").empty().append(html);
				},
				error :  function(html, statut){
					$("#ajaxLoading").hide();
					$("#updatesavailable").empty().append($url+"<br/><span class='error'>you have an error, or a notice, inside your local installation of limesurvey. See : <br/></span>");
					$("#updatesavailable").append(html.responseText);
				}
		    });
		});
	});
};