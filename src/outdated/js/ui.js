/* Editor-for-content related */
$('div[id=abuty_toolbar] a[href=add]').on('click', function(){
	var rail = $('div[id=abuty]').attr('rail');
	$('div[id=abuty_details]').slideUp('slow');
	$('div[id=abuty_toolbar_dialog]').show();
   		$.ajax({
        url: rail+'/add/new',
        type: "GET",
        cache: false,
       	dataType: 'json',
		success: function(response) {
				$('div[id=abuty_toolbar_dialog]').html(response.display);
        	return false;
    	}
	});
	$('div[id=abuty_toolbar] a[href=add]').hide();
	return false;
});
$('div[id=abuty_toolbar_dialog] a[href=close]').on('click',function(){
	$('div[id=abuty_toolbar_dialog]').hide();
	$('div[id=abuty_details]').slideDown('fast');
	$('div[id=abuty_toolbar] a[href=add]').show();
	return false;
});
$('div[id=abuty_toolbar_dialog]').hide();

$('a[id=phpedit]').hide();

$(function(){
	$(document).on('keypress',function(event){if(event.which===26&&event.ctrlKey) {
		$('div[id=abutyControlPanel]').slideToggle();
	}}); /* CTRL-Z */
	$('html').on('click', function(e){
		if ( $('#bfControlPanel').is(':visible')) {
			if (e.target.id == 'abuty' || $(e.target).parents('#abuty').length > 0) {
				
			} else {
				$('#bfBadge').fadeOut(function(){
					$('#bfControlPanel').slideUp(function(){
						$('#bfControlPanelToggle').fadeIn();
					});
				});
			}
		} else {
			if (e.target.id == 'bfControlPanelToggle' ) {
				$('#bfControlPanelToggle').slideUp(function(){
					$('#bfControlPanel').slideDown(function(){
						$('#bfBadge').slideDown();
					});
				});
			}
		}
	});

	
	$("#abuty").on("change", 'input, textarea, select, checkbox', function() {
		var name = $(this).attr("name");
		var value = $(this).val();
		var rail = $('#abuty').attr("rail");

		$('div[id=abutyStatusBar]').removeClass('idle');
		$('div[id=abutyStatusBar]').addClass('animated').fadeIn('slow');
		var id = $('[selected=selected] a').attr('id');

		$.ajax({
			url: rail+"/update/"+id, cache: false, async: false, type: "POST", data: { vpath: "{{requested_vpath}}", name : name, value : value, rail: rail, id: id}, dataType: "json",
			success: function(XHR){
				if ( XHR.announce != undefined ) {
					$(XHR.announce).fadeIn(300).insertAfter($('#abuty'));
					$('div[class=rpc_msg_warn], div[class=rpc_msg_ok], div[class=rpc_msg_error]').delay(2000).animate({"top":"-=80px"},1500).animate({"top":"-=0px"},1000).animate({"opacity":"0"},700);
				}
				if ( XHR.display != undefined ) {
					$('div[id=abutyPanel]').html(XHR.display);
				}
				if ( XHR.leftnav != undefined ) {
					$.ajax({
						url: XHR.leftnav, dataType: 'json', type: 'GET', data: {xhr: true}, async: true,
						success: function(XHR) {
							if ( XHR.display != undefined ) { $('div[id=abuty_leftnav]').html(XHR.display); }
							if ( XHR.javascript != undefined ) { eval(XHR.javascript); }
							if ( XHR.javascript != undefined ) { $('#abuty_preview').show(); } else { $('#abuty_preview').hide(); }
						}, error: function(XMLHttpRequest, textStatus, errorThrown) {
							alert("There was an error fetching a replacement panel.");
						}
					});
				}
				if ( XHR.screen != undefined ) {
					$.ajax({
						url: XHR.screen, dataType: 'json', data: {xhr: true}, cache: false, async: false,
						success: function(XHR) {
							$('div[id=abutyPanel]').slideUp('fast').html(XHR.display).slideDown('slow');
						}, error: function(XMLHttpRequest, textStatus, errorThrown) {
							alert("There was an error fetching a replacement panel.");
						}
					});
				}
				if ( XHR.javascript != undefined ) { eval(XHR.javascript); }
				if ( XHR.window != undefined ) { $('div[id=abutyPanel]').html(XHR.display); }
				var tabs = $('div[id=abutyPanel] ul[id=abutySecondaryTabs]').html();
				if ( tabs != undefined ) {
					$('div[id=abutyPanel] ul[id=abutySecondaryTabs]').remove();
					$('div[id=abutyStatusBar] ul[id=abutySecondaryTabs]').fadeOut(300, function() {
					$('div[id=abutyStatusBar] ul[id=abutySecondaryTabs]').html(tabs).fadeIn(300);
				});
			}
			$('div[id=abutyStatusBar]').removeClass('animated').addClass('idle').show();
			}, error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('<div class="rpc_msg_err">'+textStatus+' : '+errorThrown+'</div>').fadeIn(300).insertAfter($('body')).delay(2000).animate({"top":"-=80px"},1500).animate({"top":"-=0px"},1000).animate({"opacity":"0"},700);
			}
		});
		$('div[id=abutyStatusBar]').removeClass('animated').addClass('idle').show();
		return false;
	});	
	
	/* Upper navigation tabs */
	$('#bfControls').on("click", 'ul[id=abutyPrimaryTabs] li a', function(e) {
		var tabCurrent = $('#abuty').attr("tabCurrent");
		var tabCurrentRreplacement = this.href.substr(this.href.lastIndexOf('/') + 1);
		var rail = $(this).attr('href');
		if ( tabCurrent != undefined && tabCurrent != tabCurrentRreplacement ) {
			$('#bfStatusBar').html('<div id="abuty_actions">&nbsp;</div>').removeClass('idle').addClass('animated');
			$('ul[id=abutyPrimaryTabs] li').removeClass('current');
			$('#bfStatusBar').removeClass('idle').addClass('animated');
		}
		if ( tabCurrent == tabCurrentRreplacement ) {
			return false;
		}
		$('#abuty').attr("rail", rail).attr("tabCurrent", tabCurrentRreplacement);
		$(this).parent().addClass('current');
		$('#bfPanel').slideUp(100, function(){
			$.ajax({
				url: rail, data: {vpath: '{{requested_vpath}}'}, dataType: "json", cache: false,
				success: function(response) {
					if ( response.SecondaryTabs != undefined ) {
						$('#bfStatusBar').html('<div id="abuty_actions">&nbsp;</div>').removeClass('idle').addClass('animated').fadeIn('slow');
						var tabs = $('#bfSecondaryTabs').html(response.SecondaryTabs);
					}
					if ( response.display != undefined ) {
						$('#bfPanel').fadeOut(300, function(){
							$('#bfPanel').html(response.display).slideDown();
						});
					} else {
						$('#bfPanel').empty().append("<h1><p>Error: Error loading panel</p></h1>").slideDown('fast');
					}
					$('#bfStatusBar').removeClass('animated').addClass('idle').show();
					$('#bfSecondaryTabs').appendTo('#bfStatusBar');
				}, error: function(XMLHttpRequest, textStatus, errorThrown){
					$('#bfStatusBar').removeClass('animated').addClass('idle').show();
					$('div[id=abutySecondaryTabs]').empty();
					$('ul[id=abutySecondaryTabs]').appendTo('#bfStatusBar');
					$('div[id=content').html("The requested panel was not loaded.");
				}
			});
		});
		e.preventDefault();
	});
	
	/* Lower navigation tabs */
	 $('#bfControls').on("click", 'ul[id=abutySecondaryTabs] li a', function(e) {
		var rail = $(this).attr('href');
		$('#abuty').attr("rail", rail);
		$('#abuty_actions').html('<div id="abuty_actions">&nbsp;</div>');
		$('#bfSecondaryTabs li').removeClass('current');
		$(this).parent().addClass('current');
		$('#bfStatusBar').removeClass('idle').addClass('animated');
		$('#bfPanel').fadeOut(200, function(){
			$('#bfPanel').empty();
		});
		$.ajax({
			url: rail, type: "GET", data: {vpath: "{{requested_vpath}}"}, dataType: "json", cache: false, async: false,
			success: function(response) {
				if ( response.SecondaryTabs != undefined ) {
					$('div[id=abutyStatusBar]').html('<div id="abuty_actions">&nbsp;</div>').removeClass('idle').addClass('animated').fadeIn('slow');
					var tabs = $('#bfSecondaryTabs').html(response.SecondaryTabs);
				}
				
				$('#bfPanel').fadeIn(400, function(){
					if ( response.display != undefined ) { $('div[id=abutyPanel]').html(response.display); }
					if ( response.height != undefined ) { $('div[id=abutyPanel]').css("height",response.height); }
					if ( response.javascript != undefined ) { eval(response.javascript); }
					$('div[id=abutyStatusBar]').removeClass('animated').addClass('idle').show();
					$('div[id=abuty_toolbar_dialog]').hide().html();
				});
				$('#bfPanel').fadeIn(300);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				if ( response.display != undefined ) { $('div[id=abutyPanel]').html(response.display); }
				if ( response.height != undefined ) { $('div[id=abutyPanel]').css("height",response.height); }
				if ( response.javascript != undefined ) { eval(response.javascript); }
				$('div[id=abutyStatusBar]').removeClass('animated').addClass('idle').show();
				$('div[id=abutyPanel]').html(errorThrown);
			}
		});
		 e.preventDefault();
	});
	 
$('#abuty').on('click', '#bfControlEditorLoad', function(){
	var width = $('#content').width();
	var height = $('#content').height();
	var editor_link_html = "";
	$('div[id=abutyControlPanel]').slideUp(function(){});
	$.ajax({
		url: '/{{uuid}}/content',
		type: "POST",
		data: {vpath: '{{requested_vpath}}', width: width, height: height, mode: 'editor'},
		cache: false,
		dataType: 'json',
		success: function(response) {
			$('#bfControlEditorLoad').hide();
			$('#content').html(response.display);
			eval(response.javascript);
		}
	});
	return false;
});	 
	 
	$('div[id=abutyControlPanel]').hide();
	$('div[id=abutyPanel]').html(""); 
	$('div[id=abuty_leftnav] a').on('click', function() {
		/* resize details */
		var bfPanel = $('div[id=abutyPanel]').width();
		var leftnav_width = $('div[id=abuty_leftnav]').width();
		var preview_width = $('div[id=abuty_preview]').width();
		var details_width = $('div[id=abuty_details]').width();
		var new_width = (bfPanel - 3 - leftnav_width - preview_width-4); //5
		$('div[id=abuty_details]').animate({width: new_width}, "fast");
	
		var id = $(this).attr('id');
		$('div[selected=true]').removeAttr('selected');
		$('div[id=abuty_preview]').empty();
		$('div[id=abuty_details]').html('<span id="wait">Please wait...</span>').slideUp('fast');
		$('div[id=abuty_toolbar_dialog]').slideUp(900).html();
		$(this).parent().attr('selected', 'true');
		var rail = $('#abuty').attr("rail");
		$.ajax({
			url: rail+"/details/"+id, global: false, type: "GET", dataType: "json",
			success: function(XHR){
				if ( XHR.details != undefined ) { $('div[id=abuty_details]').empty().append(XHR.details).slideDown('fast').show(); }
				if ( XHR.preview != undefined ) {
					$('div[id=abuty_preview]').empty().append(XHR.preview).slideDown('fast');
				} else {
					$('#abuty_preview').hide();
				}
				if ( XHR.javascript != undefined ) { eval(XHR.javascript); }
				$('div[id=abutyStatusBar]').removeClass('animated').addClass('idle').show();
				$('a[id=cmspreviewx]').hide();
			}
		});
		return false;
	});
	
	$('a[id=cmspreview]').on('click', function() {
		var href = $(this).attr('href');
		$.ajax({
			url: href, cache: false,
			success: function(response) {
				$('div[id=abuty_preview]').html(response).show();
				$('a[id=cmspreview]').hide();
				$('a[id=cmspreviewx]').show();
			}
		});
		return false;
	});
	$('a[id=cmspreviewx]').on('click', function() {
		$('a[id=cmspreview]').show();
		$('a[id=cmspreviewx]').hide();
		$('div[id=abuty_preview]').hide();
		
		/* resize details after hide */
		var preview_width = $('div[id=abuty_preview]').width();
		var details_width = $('div[id=abuty_details]').width();
		var new_width = (details_width + preview_width);
		$('div[id=abuty_details]').css("border-right","0px");
		$('div[id=abuty_details]').animate({width: new_width}, "fast");
		
		return false;
	});
	$('a[id=cmspreviewx]').hide();
	

	
	$("#bfPanel textarea" ).on('keyup', function (e) { if(e.keyCode == 13) { return false; } });
	/*
	$('div[class=qq-upload-drop-area]').on('click', function(){ $(this).hide(); });
	*/
	
	$('div[id=abuty_leftnav_search]').change(function(){return false;}).keyup(function() {
		var query = $('input[name=abuty_leftnav_search]').val();
	});
	$('div[id=abuty_leftnav_search]').change(function(){return false;}).keyup(function() {
		var query = $('input[name=abuty_leftnav_search]').val();
	});
	
/*
var $opener = $("[data-open-prompt]");

ReactiveListener.add($("[data-open-prompt]")[0], {
  Pointer2d: [{
      range: [0, 1],
      maxDist: 100,
      forceMax: true,
      directional: true,
    }, {
      range: [1, 1],
      maxDist: 500,
      forceMax: true,
      directional: true,
    },{
      callback: function(opts) {
        var dist = opts.dist["Pointer2d"];
        if (dist === 0) {
          return false;
        } else {
          if (dist < 100) {
            $('#bfControlPanel').css({opacity: 1});
          } else {
            $('#bfControlPanel').css({opacity: 1 });
          }
        }
      }
    }
  ]
});

ReactiveListener.start();

$opener.click(function() {
  if ($opener.hasClass("open")) {
    $opener.removeClass("open");
    ReactiveListener.start();
    $('#bfControlPanel').css({ opacity: 1});
  } else {
    $opener.addClass("open");
    ReactiveListener.stop();
    $('#bfControlPanel').css({ opacity: 1});
  }
});
*/
	
	
	
	
	
	
	
	
	
	
	
	
});