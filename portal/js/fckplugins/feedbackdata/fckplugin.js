/*
 * testMaker feedback plugin for FCKeditor
 *
 * File Name: fckplugin.js
 * 	Plugin to insert/edit dynamic feedback data objects in the editor.
 *
 * File Authors:
 * 	Jan Krueger <jk@jan-krueger.net>
 */

// Register command
FCKCommands.RegisterCommand('FeedbackData', new FCKDialogCommand('FeedbackData', FCKLang.FbdataDlgTitle, FCKPlugins.Items['feedbackdata'].Path + 'fck_feedbackdata.html', 340, 170));

// Create toolbar button
var oFbdataItem = new FCKToolbarButton('FeedbackData', FCKLang.FbdataPlugin);
oFbdataItem.IconPath = FCKPlugins.Items['feedbackdata'].Path + 'feedbackdata.gif';

FCKToolbarItems.RegisterItem('FeedbackData', oFbdataItem);

// The feedback data "API"
var FCKFbdata = new Object();

FCKFbdata.Init = function()
{
	if (!FCK.EditorDocument._fbdata_css_loaded) {
		heads = FCK.EditorDocument.getElementsByTagName('HEAD');
		link = FCK.EditorDocument.createElement('LINK');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		d = new Date();
		link.href = FCKPlugins.Items.feedbackdata.Path + 'feedbackdata.css?' + d.getTime();
		heads[0].appendChild(link);
		FCK.EditorDocument._fbdata_css_loaded = true;
	}
}

// Add feedback data span
FCKFbdata.Add = function(data)
{
	this.Init();
	var oSpan = FCK.CreateElement('FEEDBACK');
	this.SetupFbDesign(oSpan, data);
}

FCKFbdata.IsFb = function(obj)
{
	return (obj.tagName == 'FEEDBACK' || (obj.tagName == 'IMG' && obj.className == 'FCK_Feedbackdata'));
}

FCKFbdata.SetupFbDesign = function(span, data)
{
	span.innerHTML = data.caption;
	for (var attr in data) {
		span[attr] = data[attr];
	}
	FCKFbdata.AddInternals(span);
}

FCKFbdata.StripInternals = function(obj)
{
	if (obj) {
		fb = FCK.EditorDocument.createElement('FEEDBACK');
		for (var attr in obj._fbdata) {
			if (!attr.match(/^_fb_/i)) continue;
			fb.setAttribute(attr, obj._fbdata[attr]);
		}
		fb.innerHTML = obj._fbdata._caption;

		obj.parentNode.replaceChild(fb, obj);
		return fb;
	}

	fbs = FCK.EditorDocument.getElementsByTagName('IMG');
	for (i = 0; i < fbs.length; i++) {
		if (!FCKFbData.IsFb(fbs[i])) continue;
		FCKFbdata.StripInternals(fbs[i]);
	}
}

FCKFbdata.AddInternals = function(obj)
{
	if (obj) {
		img = FCK.EditorDocument.createElement('IMG');
		type = (obj.type == 'img' ? 'img' : 'text');
		img.src = FCKPlugins.Items.feedbackdata.Path + 'fbdata-'+ type +'.png';
		label = FCKLang.FbdataTooltip + ': ' + obj.innerHTML;
		img.title = label;
		img.alt = label;
		img._fbdata = {_caption: obj.innerHTML};
		img.className = 'FCK_Feedbackdata';

		// To avoid it to be resized.
		img.onresizestart = function()
		{
			FCK.EditorWindow.event.returnValue = false;
			return false;
		}

		for (var i = 0; i < obj.attributes.length; i++) {
			attr = obj.attributes[i].nodeName;
			if (!attr.match(/^_fb_/i)) continue;
			img._fbdata[attr] = obj.getAttribute(attr);
		}

		obj.innerHTML = '';
		obj.parentNode.replaceChild(img, obj);

		return;
	}
	this.Init();
	fbs = FCK.EditorDocument.getElementsByTagName('FEEDBACK');
	for (i = 0; i < fbs.length; i++) {
		if (!FCKFbdata.IsFb(fbs[i])) continue;
		FCKFbdata.AddInternals(fbs[i]);
	}
}

// On Gecko we must do this trick so the user select all the SPAN when clicking on it.
FCKFbdata._SetupClickListener = function()
{
	if (!FCKBrowserInfo.IsGecko) return;

	FCKFbdata._ClickListener = function(e)
	{
		if (FCKFbdata.IsFb(e.target)) FCKSelection.SelectNode(e.target);
	}
	FCK.EditorDocument.addEventListener('click', FCKFbdata._ClickListener, true);
}

// Open the Placeholder dialog on double click.
FCKFbdata.OnDoubleClick = function(span)
{
	if (FCKFbdata.IsFb(span)) FCKCommands.GetCommand('FeedbackData').Execute();
}

FCK.RegisterDoubleClickHandler(FCKFbdata.OnDoubleClick, 'IMG');

FCKFbdata.Redraw = function()
{
	FCKFbdata.AddInternals();
	FCKFbdata._SetupClickListener();
}
FCK.Events.AttachEvent( 'OnAfterSetHTML', FCKFbdata.Redraw ) ;

// We must process the FEEDBACK tags to get rid of bogus attributes etc.
FCKXHtml.TagProcessors['img'] = function( node, htmlNode )
{
	if (FCKFbdata.IsFb(htmlNode)) {
		fb = FCKFbdata.StripInternals(htmlNode);
		return fb;
	}
	return node;
}

