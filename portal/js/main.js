function makeDebugString(object, linePrefix)
{
	if (! linePrefix) {
		linePrefix = "";
	}

	out = "";
	for (var name in object) {
		out += linePrefix+name+": "+object[name]+"\n";
	}
	return out;
}

function order(form)
{
	var list = form.childNodes[1].childNodes[1].childNodes;
	for (var i = 0; i < list.length; i++)
	{
		list[i].firstChild.name = 'order[' + i + ']';
	}
}

function OnloadNotifier()
{
	this.observers = new Array();
	var oldOnload = window.onload;
	window.onload = this.notifyObservers;
	if (oldOnload) {
		this.addObserver(oldOnload);
	}
}

OnloadNotifier.prototype.addObserver = function(callback, context)
{
	this.observers.push([callback, context]);
}

OnloadNotifier.prototype.notifyObservers = function()
{
	var observers = onloadNotifier.observers;
	for (var i = 0; i < observers.length; i++) {
		observers[i][0].call(observers[i][1] instanceof Object ? observers[i][1] : this);
	}
}

var onloadNotifier = new OnloadNotifier();

function addHoverEffect()
{
	// Buttons
	var objects = new Array();

	var inputs = document.getElementsByTagName("input");
	for (var i = 0; i < inputs.length; i++)
	{
		if (inputs[i].className == "Button" || inputs[i].className == "AnswerButton") {
			inputs[i].hoverClassName = "ButtonHover";
			objects.push(inputs[i]);
		}
		else if (inputs[i].className == "Text") {
			inputs[i].hoverClassName = "TextHover";
			objects.push(inputs[i]);
		}
	}

	var buttons = document.getElementsByTagName("button");
	for (var i = 0; i < buttons.length; i++) {
		buttons[i].hoverClassName = "ButtonHover";
		objects.push(buttons[i]);
	}

	var textareas = document.getElementsByTagName("textarea");
	for (var i = 0; i < textareas.length; i++) {
		textareas[i].hoverClassName = "TextHover";
		objects.push(textareas[i]);
	}

	var selects = document.getElementsByTagName("select");
	for (var i = 0; i < selects.length; i++) {
		selects[i].hoverClassName = "TextHover";
		objects.push(selects[i]);
	}

	var disabledTabs = document.getElementsByTagName("span");
	for (var i = 0; i < disabledTabs.length; i++) {
		if (disabledTabs[i].className == "DisabledTab") {
			disabledTabs[i].hoverClassName = "DisabledTabHover";
			objects.push(disabledTabs[i]);
		}
	}

	for (var i = 0; i < objects.length; i++)
	{
		objects[i].oldonmouseover = objects[i].onmouseover;
		objects[i].onmouseover = function() {
			if (this.oldonmouseover) {
				this.oldonmouseover();
			}
			this.className += " "+this.hoverClassName;
		}

		objects[i].oldonmouseout = objects[i].onmouseout;
		objects[i].onmouseout = function() {
			if (this.oldonmouseout) {
				this.oldonmouseout();
			}
			this.className = this.className.replace(" "+this.hoverClassName, "");
		}
	}
}

onloadNotifier.addObserver(addHoverEffect);