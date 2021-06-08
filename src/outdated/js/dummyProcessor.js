CKEDITOR.plugins.add( 'dummyProcessor', {
   requires : [ 'htmlwriter' ],
   init : function( editor ) {
	  editor.dataProcessor = new CKEDITOR.dummyProcessor( editor );
   }
});

CKEDITOR.dummyProcessor = function( editor ) {
   this.editor = editor;
   this.writer = new CKEDITOR.htmlWriter();
   this.dataFilter = new CKEDITOR.htmlParser.filter();
   this.htmlFilter = new CKEDITOR.htmlParser.filter();

};

CKEDITOR.dummyProcessor.prototype = {
   toHtml : function( data, fixForBody ) {
	  /* all converting to html (like: data = data.replace( /</g, '&lt;' );) */
	  return data;
   }, toDataFormat : function( html, fixForBody ) {
	  /* all converting from html (like: html = html.replace( /<br><\/p>/gi, '\r\n');) */
	  return html;
   }
};