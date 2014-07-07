/*
 * Mess with conditions
 ****************************************************************************/

var currentParagraph;

function showConditions(para)
{
	currentParagraph = para;
	var spans = $A($('conds_proto').getElementsByTagName('span'));
	spans.each(function(input) {
		Async.disableUi(input, para +'_notice');
	});
	$(para).hide();
	$('conds_proto').show();
}

function hideConditions()
{
	var para = currentParagraph;
	$('conds_proto').hide();
	$(para).show();
	var spans = $A($('conds_proto').getElementsByTagName('span'));
	spans.each(function(input) {
		Async.enableUi(input, para +'_notice');
	});
}

function addCondition(type)
{
	var para = currentParagraph;
	var spans = $A($('conds_proto').getElementsByTagName('span'));
	spans.each(function(input) {
		Async.enableUi(input, para +'_notice');
	});

	var paraCondList = $(para +'_conditions');
	var paraCondNew = $('proto_'+ type);
	newNode = document.createElement('li');
	newNode.innerHTML = paraCondNew.innerHTML;
	paraCondList.insertBefore(newNode, $(para +'_conds_new'));

	initCondition(type, newNode, true);

	hideConditions();
}

function initCondition(type, node, isNew)
{
	if (extconds_hooks[type]) extconds_hooks[type]($(node), currentParagraph, isNew);
}

function initExistingConditions(para)
{
	var conds = $A($(para +'_conditions').getElementsByTagName('li'));
	conds.each(function(cond) {
		if (cond.className == '') return;
		initCondition(cond.className, cond, false);
	});
}

function delCondition(target)
{
	var paraCond = target;
	var paraCondList = paraCond.parentNode;
	paraCondList.removeChild(paraCond);
}

/*
 * Helper functions for condition scripts
 ****************************************************************************/

function ident(val) { return val; }
function ftrue(val) { return true; }
function ffalse(val) { return false; }

// Processes a field using callbacks.
function processField(target, process, checkInvalid)
{
	var res = process(target.value);
	if (checkInvalid(res)) {
		target.className = 'invalid';
	} else {
		target.className = '';
		target.value = res;
	}
	return res;
}

// Parses a field as a float. May change input.
function getFloat(target)
{
	target.value = target.value.replace(/,/, '.');
	return processField(target, parseFloat, isNaN);
}

// Parses a field as an integer.
function getInt(target, signed)
{
	return processField(target, parseInt, function(val) {return isNaN(val) || (!signed && val < 0)});
}

// Gets a dimension sequence from a select box.
function getDimSequence(target)
{
	return dimSequence[target.value];
}

/*
 * Edit paragraph
 ****************************************************************************/

function addParagraph(url)
{
	Async.disableUi('paragraphs', 'para_add_notice');
	var pars = 'id=0';
	var asyncRequest = new Ajax.Request(
		url,
		{
			method: 'post',
			parameters: pars,
			onComplete: processEditParagraph,
			onFailure: doOutputError
		}
	);
}

function editParagraph(target, url)
{
	var para = $(target);
	var id = Async.numId(target);

	Async.disableUi('paragraphs', target +'_notice');
	var pars = 'id='+ id;
	var asyncRequest = new Ajax.Request(
		url,
		{
			method: 'post',
			parameters: pars,
			onComplete: processEditParagraph,
			onFailure: doOutputError
		}
	);
}

function processEditParagraph(req)
{
	if (Async.hasStatus(req)) {
		doOutputError({statusText: Async.getStatusContent(req)});
		return;
	}
	Async.messUpInputs('paragraphs', 'hide');
	if ($('para_add_button')) Element.hide($('para_add_button'));

	var item = req.responseXML.getElementsByTagName('content')[0];
	var itemId = item.getAttribute('id');
	if ($(itemId)) {
		var text = '';
		var subitem = item.firstChild;
		while (subitem) {
			text += subitem.nodeValue;
			subitem = subitem.nextSibling;
		}
		Async.replaceByHTML(itemId, text);
		initExistingConditions(itemId);
	} else {
		new Insertion.Bottom('paragraphs', item.firstChild.nodeValue);
	}
	Async.enableUi('paragraphs');
}

function sendParagraph(target, doSave, url)
{
	var para = $(target);
	var id = Async.numId(para);
	var data = '';

	var conds = $(target + '_conditions');
	if (conds && doSave) {
		if ($A(conds.getElementsByClassName('invalid')).length > 0) {
			errorsInConditions();
			return;
		}
	}

	// Get stuff from FCKEditor
	if (doSave) {
		Try.these(function() {
			var fck = FCKeditorAPI.GetInstance('fckcontent');
			document.forms[target +'_form'].fckcontent.value = fck.GetXHTML();
		});
	}

	Async.disableUi('paragraphs', target +'_notice');
	$(target +'_form').enable();
	var data = $(target +'_form').serialize();
	var pars = 'save='+ (doSave ? '1' : '0') +'&id='+ id +'&'+ data;

	var asyncRequest = new Ajax.Request(
		url,
		{
			method: 'post',
			parameters: pars,
			onComplete: processSendParagraph,
			onFailure: doOutputError
		}
	);
}

function processSendParagraph(req)
{
	if (Async.hasStatus(req)) {
		var st = Async.getStatusAttribute(req, 'type');
		if (st == 'ok') {
			location.reload();
			if($('para_add_button')) Async.enableUi('para_add_button');
			Async.enableUi('paragraphs');	
			return;
		}
		doOutputError({statusText: Async.getStatusContent(req)});
		return;
	}
	alert('Software implementation bug: asynchronous status code missing');
}

/*
 * Delete paragraph
 ****************************************************************************/

function delParagraph(target)
{
	Effect.Appear($(target + '_delete'), {duration: 0.5});
}

function doDelParagraph(target, url)
{
	var para = $(target);
	var id = Async.numId(para);

	Async.disableUi('paragraphs', target +'_notice');
	var pars = 'id='+ id;
	var asyncRequest = new Ajax.Request(
		url,
		{
			method: 'post',
			parameters: pars,
			onComplete: processDelParagraph,
			onFailure: doOutputError
		}
	);
}

function cancelDelParagraph(target)
{
	Effect.Fade($(target + '_delete'), {duration: 0.5});
}

function processDelParagraph(req)
{
	var status = Async.getStatusAttribute(req, 'type');
	var id;

	switch (status) {
	case 'ok':
		id = Async.getStatusAttribute(req, 'id');
		break;
	case 'ok_disabled':
		id = Async.getStatusAttribute(req, 'id');
		// Show as disabled
		var elem = $('para_'+ id);
		elem.getElementsByClassName('contents')[0].addClassName('disabled');
		Element.remove(elem.getElementsByClassName('_para_action_buttons')[0]);
		Async.enableUi('paragraphs');
		return;
	case 'fail':
		doOutputError({statusText: Async.getStatusContent(req)});
		return;
	}

	Effect.Fade($('para_'+ id), {duration: 0.5, afterFinish: function(obj) {
		Element.remove($('para_'+ id));
	}});
	Async.enableUi('paragraphs');
}

/*
 * Move paragraph
 ****************************************************************************/

function moveParagraph(target, dir, url)
{
	var para = $(target);
	var paraList = para.parentNode;
	var whereTo;
	var lowerId;
	var upperId;
	var usId;
	if (dir == 'up') {
		whereTo = para.previousSibling;
		lowerId = Async.numId(para);
		upperId = Async.numId(whereTo);
	} else {
		whereTo = para.nextSibling;
		lowerId = Async.numId(whereTo);
		upperId = Async.numId(para);
	}
	usId = Async.numId(para);
	if (!whereTo) return;

	Async.disableUi('paragraphs', target +'_notice');
	var pars = 'upper='+ upperId +'&lower='+ lowerId +'&id='+ usId;
	var asyncRequest = new Ajax.Request(
		url,
		{
			method: 'post',
			parameters: pars,
			onComplete: processMoveParagraph,
			onFailure: doOutputError
		}
	);
}

function processMoveParagraph(req)
{
	var status = Async.getStatusAttribute(req, 'type');
	var idLower;
	var idUpper;
	var idUs;

	switch (status) {
	case 'ok':
		idLower = Async.getStatusAttribute(req, 'lower');
		idUpper = Async.getStatusAttribute(req, 'upper');
		idUs = Async.getStatusAttribute(req, 'id');
		break;
	default:
		doOutputError({statusText: Async.getStatusContent(req)});
		return;
	}


	// recover items
	var lower = $('para_'+ idLower);
	var upper = $('para_'+ idUpper);
	var us = $('para_'+ idUs);
	var list = lower.parentNode;
	Async.enableUi('paragraphs');
	Effect.Fade(us, {duration: 0.5, afterFinish: function(obj) {
		list.removeChild(lower);
		list.insertBefore(lower, upper);
		Effect.Appear(us, {duration: 0.5});
	}});
}

/*
 * Utility functions
 ****************************************************************************/

function doOutputError(req)
{
	alert(req.statusText);
	Async.enableUi('paragraphs');
}
