
function bxaf_trim(string) {
	return string.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}


function bxaf_is_number(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function bxaf_in_array(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}

function bxaf_validate_email(email) {
    pattern = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;

	return pattern.test(email) ;

}



function bxaf_find_and_load_library(type, to_be_found, to_be_loaded){
	var found = false;
	if(type == 'css'){
		for (var i = 0; i < document.styleSheets.length; i++) {
		    if (document.styleSheets[i].href && document.styleSheets[i].href.match(to_be_found)) {
		        // console.log("There is a request for the css file.");
		        // if (document.styleSheets[i].cssRules.length == 0) {
		            // console.log("Request for the css file failed.");

		        //     break;
		        // } else {
		            // console.log("Request for the css file is successful.");
					found = true;
		            break;
		        // }
		    }
		}
		if(! found && to_be_loaded !== undefined && to_be_loaded != ''){
			var link = document.createElement("link");
		    link.rel = "stylesheet";
		    link.href = to_be_loaded;

		    document.getElementsByTagName("head")[0].appendChild(link);
		}
	}
	else if(type == 'js'){
		$("script").each(function() {
			if ($(this).attr('src') !== undefined && $(this).attr('src').match(to_be_found)) {
				found = true;
			}
		});

		if(! found && to_be_loaded !== undefined && to_be_loaded != ''){
			var script = document.createElement('script');
			script.onload = function() {
				// alert("Script loaded and ready");
			};
			script.src = to_be_loaded;
			document.getElementsByTagName('head')[0].appendChild(script);
		}
	}
	return found;
}



$(document).ready(function() {

    // // Check/Uncheck All
    $(document).on('change', '.bxaf_checkbox', function() {
        if($(this).hasClass('bxaf_checkbox_all')){
            $('.bxaf_checkbox_one').prop ('checked', $(this).is(':checked') );
        }
        else if( $(this).hasClass('bxaf_checkbox_one') ){
            var checked = true;
            $('.bxaf_checkbox_one').each(function(index, element) {
                if (! element.checked ) checked = false;
            });
            $('.bxaf_checkbox_all').prop ('checked', checked);
        }
    });

    $('.scroll').on('click', function(event) {
        if (this.hash !== "") {
            event.preventDefault();
            var hash = this.hash;
            $('html, body').animate({
                scrollTop: $(hash).offset().top
            }, 800, function() {
                window.location.hash = hash;
            });
        }
    });

});

