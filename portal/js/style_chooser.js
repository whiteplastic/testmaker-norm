var chooserBox = null;
var fieldName = null;

/* open new color chooser */
function openBox(fieldname)
{
	/* close other color chooser */
	if (chooserBox)
	{
		chooserBox.className = '';
	}
	chooserBox = document.getElementById(fieldname);
	chooserBox.className = 'visible';
	fieldName = fieldname;
}

/* close color chooser and write value in field */
function writeColor(color)
{
	if (chooserBox)
	{
		document.getElementsByName(fieldName + "Input")[0].value = color;
		if(fieldName == "oabgcolor") updatePreview('bgcolor', color);
		if(fieldName == "fontcolor") updatePreview('fontcolor', color);
		chooserBox.className = '';
		fieldName = '';
	}
}

/* close color chooser */
function closeChooser()
{
	if (chooserBox)
	{
		chooserBox.className = '';
		fieldName = '';
	}
}

/* update font preview */
function updatePreview(option, value)
{
	var preview = document.getElementById('preview');
	var preview_fieldset = document.getElementById('preview_fieldset');
	if (option == 'bgcolor')
	{
		bgColor = value;
		preview_fieldset.style.backgroundColor = bgColor;
	}
	if (option == 'fontcolor')
	{
		fontColor = value;
		preview.style.color = fontColor;
	}
	if (option == 'type')
	{
		fontFamily = value;
		preview.style.fontFamily = fontFamily;
	}
	if (option == 'size')
	{
		fontSize = value;
		preview.style.fontSize = fontSize;
	}
	if (option == 'style')
	{
		if (value)
		{
			fontStyle = 'italic'
		}
		else
		{
			fontStyle = 'normal';
		}
		preview.style.fontStyle = fontStyle;
	}
	if (option == 'weight')
	{
		if (value)
		{
			fontWeight = 'bold';
		}
		else
		{
			fontWeight = 'normal';
		}
		preview.style.fontWeight = fontWeight;
	}
}

/* save  font settings
function writeFont()
{
	document.getElementsByName("fonttypeInput")[0].value = fontFamily;
	document.getElementsByName("fontsizeInput")[0].value = fontSize;
	if (fontStyle == 'italic')
	{
		document.getElementsByName("fontstyleInput")[0].checked = true;
	}
	else
	{
		document.getElementsByName("fontstyleInput")[0].checked = false;
	}
	if (fontWeight == 'bold')
	{
		document.getElementsByName("fontweightInput")[0].checked = true;
	}
	else
	{
		document.getElementsByName("fontweightInput")[0].checked = false;
	}
	chooserBox.className = '';
	fieldName = '';
}*/

function fillFontStyle()
{
	var fonttypeInput = document.getElementsByName("fonttypeInput")[0];
	var fontsizeInput = document.getElementsByName("fontsizeInput")[0];
	var fontstyleInput = document.getElementsByName("fontstyleInput")[0];
	var fontweightInput = document.getElementsByName("fontweightInput")[0];
	for(var i=1;i<fonttypeInput.length;i++)
	{
		type = fontFamily.substring(1, fontFamily.indexOf("'", 1));
		if(type == fonttypeInput.options[i].text)
		{
			fonttypeInput.options[i].selected = true;
			updatePreview('type', type);
		}
	}
	for(var i=1;i<fontsizeInput.length;i++)
	{
		if(fontSize == fontsizeInput.options[i].text)
		{
			fontsizeInput.options[i].selected = true; 
			updatePreview('size', fontSize);
		}
	}
	if(fontstyleInput.checked) updatePreview('style', true);
	if(fontweightInput.checked) updatePreview('weight', true);
	updatePreview('bgcolor', bgColor);
	updatePreview('fontcolor', fontColor);
}

function toggleStyleSettings() {
	var fieldsets = document.getElementsByTagName("fieldset");
	if (document.styleForm.use_parent_style.checked) {
		for (var i = 0; i < fieldsets.length; ++i) {
			if (fieldsets.item(i).getAttribute('name') == 'styleSettings') fieldsets[i].style.display = "none";
		}
	} else {
		for (var i = 0; i < fieldsets.length; ++i) {
			if (fieldsets.item(i).getAttribute('name') == 'styleSettings') fieldsets[i].style.display = "";
		}
	}
}
