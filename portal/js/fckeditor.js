FCKConfig.AutoDetectLanguage	= false ;

FCKConfig.ToolbarCanCollapse	= false ;

FCKConfig.ProcessHTMLEntities	= true ;
FCKConfig.IncludeGreekEntities	= true ;
FCKConfig.IncludeLatinEntities	= true ;

FCKConfig.Plugins.Add('feedbackdata');

FCKConfig.ToolbarSets["testmaker"] = [
	['Source','-','Save','NewPage','Preview','-','Templates'],
	['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],['TextColor','BGColor'],
	['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Image','Flash','Table','Rule','Smiley','SpecialChar'],
	['FitWindow','ShowBlocks','-','About'],
	'/',
	['FontFormat','FontName','FontSize']
	
] ;

FCKConfig.ToolbarSets["noImage"] = [
	['Source','-','Save','NewPage','Preview','-','Templates'],
	['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],['TextColor','BGColor'],
	['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Table','Rule','Smiley','SpecialChar'],
	['FitWindow','ShowBlocks','-','About'],
	'/',
	['FontFormat','FontName','FontSize']
] ;

FCKConfig.LinkBrowser = true ;
FCKConfig.LinkBrowserWindowWidth	= 666 ;	// 70%
FCKConfig.LinkBrowserWindowHeight	= 600 ;	// 70%

FCKConfig.ImageBrowser = true ;
FCKConfig.ImageBrowserWindowWidth  = 666 ;	// 70% ;
FCKConfig.ImageBrowserWindowHeight = 600 ;	// 70% ;

FCKConfig.FlashBrowser = true ;
FCKConfig.FlashBrowserWindowWidth  = 666 ;	//70% ;
FCKConfig.FlashBrowserWindowHeight = 600 ;	//70% ;

FCKConfig.ImageUpload = false;
FCKConfig.ImageDlgHideAdvanced = true;
