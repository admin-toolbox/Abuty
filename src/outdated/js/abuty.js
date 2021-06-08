var language = window.navigator.userLanguage || window.navigator.language;

function setAttributes(e, attributes) {
	for(var key in attributes) {
		e.setAttribute(key, attributes[key]);
	}
}

var head = document.getElementsByTagName('head')[0];

var element = document.createElement('link');
setAttributes(element, {"href": "/scss/ui.scss", "rel": "stylesheet", "type": "text/css"});
head.appendChild(element);

var element = document.createElement('link');
setAttributes(element, {"href": "/css/abuty.css", "rel": "stylesheet", "type": "text/css"});
head.appendChild(element);

var element = document.createElement('link');
setAttributes(element, {"href": "//abuty.com/ajax/libs/ckeditor-plugins/codemirror/1.17.7/css/codemirror.min.css", "rel": "stylesheet", "type": "text/css"});
head.appendChild(element);

var element = document.createElement('script');
setAttributes(element, {"src": "//abuty.com/ajax/libs/ckeditor-plugins/codemirror/1.17.7/js/codemirror.min.js", "type": "text/javascript"});
head.appendChild(element);

var element = document.createElement('script');
setAttributes(element, {"src": "//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.9.2/ckeditor.js", "type": "text/javascript"});
head.appendChild(element);

var element = document.createElement('link');
setAttributes(element, {"href": "//stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css", "rel": "stylesheet", "type": "text/css"});
head.appendChild(element);


$(function(){$.ajax({url:"/bigfootcms/init",type:"POST",data:{vpath:window.location.pathname},dataType:"json",success:function(r){$('body').prepend(r.b);$('body').prepend(r.a);}});});
$(function(){
	var h = document.getElementsByTagName('head')[0];
	var e = document.createElement('script');
	e.setAttribute('type', 'text/javascript');
	e.setAttribute('src', "/js/fileuploader.js");
	h.appendChild(e);
});

/* HELPER FUNCTIONS FOR UI */
function abuty_ui_leftnav_enable() {
	$('div[id=abuty_actions]').html('<a class="prev">&laquo; Back</a><a class="next">More sresults &raquo;</a>');
	$(".scrollable").scrollable({ vertical: true, mousewheel: true });
	$('input[name=abuty_leftnav_search]').each(function() {
		var default_value = this.value;
		$(this).focus(function() {
			if(this.value == default_value) {
				this.value = '';
			}
		});
		$(this).blur(function() {
			if(this.value == '') {
				this.value = default_value;
			}
		});
	});
	return true;
}