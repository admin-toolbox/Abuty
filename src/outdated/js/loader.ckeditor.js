CKEDITOR.plugins.addExternal('dummyProcessor', '/{{uuid}}/dummyProcessor.js');
CKEDITOR.plugins.addExternal('codemirror', 'https://abuty.com/ajax/libs/ckeditor-plugins/codemirror/1.17.7/plugin.js');
CKEDITOR.plugins.addExternal('autogrow', 'https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.9.2/plugins/autogrow/plugin.js');

CKEDITOR.config.codemirror = {
	theme: 'default',
	lineNumbers: true,
	lineWrapping: true,
	matchBrackets: true,
	autoCloseTags: true,
	autoCloseBrackets: true,
	enableSearchTools: true,
	enableCodeFolding: true,
	enableCodeFormatting: true,
	autoFormatOnStart: true,
	autoFormatOnModeChange: true,
	autoFormatOnUncomment: true,
	mode: 'htmlmixed',
	showSearchButton: true,
	showTrailingSpace: true,
	highlightMatches: true,
	showFormatButton: true,
	showCommentButton: true,
	showUncommentButton: true,
	showAutoCompleteButton: true,
	styleActiveLine: true
};

CKEDITOR.replace( 'cms_content',  {
	  height: '{{height}}'
	, removeFormatAttributes: ''
	, allowedContent: true
	, protectedSource: ['/\r|\n/g']
	, indentationChars: '\t'
	, selfClosingEnd: ' />'
	, dataIndentationChars: '  '
	, tabSpaces: 2
	, toolbar: [
		{ name: 'clipboard', items: [ 'Undo', 'Redo' ] },
		{ name: 'styles', items: [ 'Styles', 'Format', 'Source','Preview','-','Templates']},
		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
		{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
		{ name: 'links', items: [ 'Link', 'Unlink' ] },
		{ name: 'insert', items: [ 'Image', 'EmbedSemantic', 'Table' ] },
		{ name: 'tools', items: [ 'Maximize' ] },
		{ name: 'editing', items: [ 'Scayt' ] },
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',    groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		{ name: 'colors' },
		{ name: 'about' }			
	]
	, customConfig: ''
	, extraPlugins: 'dummyProcessor,codemirror,autogrow'
	/* , removePlugins: 'image' */
	, contentsCss: [ 'https://cdnjs.cloudflare.com/ajax/libs/foundation/6.4.3/css/foundation.min.css' ]
	//, bodyClass: 'article-editor'
	, format_tags: 'p;h1;h2;h3;pre'
	//, removeDialogTabs: 'image:advanced;link:advanced'
});
   
$('#cms_javascript').css("height", {{height}});
$('#cms_javascript').css("width", '100%');

$('#cms_stylesheet').css("height", {{height}});
$('#cms_stylesheet').css("width", '100%');

$('a[id=save]').on('click', function(){
	var vpath = window.location.pathname;
	$(window).scrollTop();
	var content = CKEDITOR.instances['cms_content'].getData();
	var js = $('textarea[id=cms_javascript]').val();
	var css = $('textarea[id=cms_stylesheet]').val();
	$('div[id=control_panel_status_bar]').removeClass('idle').addClass('animated').show();
	$('a[id=save]').html("Saving...");
	$.ajax({
		url: "/{{uuid}}/content/update",
		data: {vpath: vpath, content: content, js: js, css: css},
	type: "POST",
		cache: false,
		success: function(message) {
			$('a[id=save]').html("Saved!").delay(2000).html("Save changes.");
			$('#content').scrollTop();
			$('#control_panel_window').empty().append(message);
			$('div[id=control_panel_status_bar]').removeClass('animated').addClass('idle').show();
			$('<div class="rpc_msg_ok">Your changes have been saved.</div>').fadeIn(300).insertAfter($('body')).delay(2000).animate({"top":"-=80px"},1500).animate({"top":"-=0px"},1000).animate({"opacity":"0"},700);
		}
	});
	return false;
});
$('a[test=x]').on('click', function(){
	$.ajax({
		url: '/{{uuid}}/content', type: "POST", data: { vpath: "{{vpath}}" }, dataType: "json", cache: false,
		success: function(response) {
			$('#bfControlPanelToggle').show();    
			$('#content').html(response.display);
			$('<div class="rpc_msg_ok">Now showing the live version.</div>').fadeIn(300).insertAfter($('body')).delay(2000).animate({"top":"-=80px"},1500).animate({"top":"-=0px"},1000).animate({"opacity":"0"},700);
		}, error: function(XMLHttpRequest, textStatus, errorThrown) {
			$('<div class="rpc_msg_err">Could not fetch content from database.</div>').fadeIn(300).insertAfter($('body')).delay(2000).animate({"top":"-=80px"},1500).animate({"top":"-=0px"},1000).animate({"opacity":"0"},700);
		}
	});
	return false;
});  

$('div[id=bigfoot]').append('<div id="bfEditorToolbarTop" style="top: 0px; position: abolute;"></div>');
$('div[id=bigfoot]').append('<div id="bfEditorToolbarBottom" style="bottom: 0px; position: abolute;"></div>');

/* var myCodeMirror = CodeMirror.fromTextArea('[id=cms_javascript]'); */

