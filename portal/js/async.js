/* ***************************************************************************
 * Helper functions for asynchronous processing
 * **************************************************************************/

Async = {

	/* Pops up the async processing notice and disables inputs in a container */
	disableUi: function(elem, notice)
	{
		$(notice).hide();
		$(notice).className = 'AsyncNotice';
		Effect.Appear($(notice), {duration: 0.5});

		if ($('para_add_button')) this.messUpInputs($('para_add_button'), 'disable');

		this.disabledNotice = notice;
		this.messUpInputs(elem, 'disable');
	},

	/* Counterpart to disableUi */
	enableUi: function(elem)
	{
		if ($(this.disabledNotice)) {
			Effect.Fade($(this.disabledNotice), {duration: 0.5});
		}

		if ($('para_add_button')) this.messUpInputs($('para_add_button'), 'enable');

		this.messUpInputs(elem, 'enable');
	},

	getStatusAttribute: function(req, name)
	{
		return this.getStatusElem(req).getAttribute(name);
	},
	getStatusContent: function(req, name)
	{
		return this.getStatusElem(req).firstChild.nodeValue;
	},
	getStatusElem: function(req)
	{
		var xml = req.responseXML;
		return xml.getElementsByTagName('status')[0];
	},
	hasStatus: function(req)
	{
		return req.responseXML.getElementsByTagName('status').length > 0;
	},

	/* Disables/enables all input elements in a given form node */
	messUpInputs: function(elem, op)
	{
		if ($(elem)) elem = $(elem);
		var inputs = $A($(elem).getElementsByTagName('input'));
		//execute following elements
		var inputs2 = new Array();
		for( var i = 0; i < inputs.length; i++)
		{
			var input = inputs[i];
			if(input.id != 'check_correctness') inputs2.push(input);
		}
		inputs = inputs2.concat($A($(elem).getElementsByTagName('button')));
		inputs = inputs.concat($A($(elem).getElementsByTagName('select')));

		inputs.each(function(input) {
			switch (op) {
			case 'enable':
				input.removeAttribute('disabled');
				break;
			case 'disable':
				input['disabled'] = 'disabled';
				break;
			case 'show':
				Element.show(input);
				break;
			case 'hide':
				Element.hide(input);
				break;
			}
		})
	},

	/* Extracts a numeric ID suffix from an element node */
	numId: function(elem)
	{
		var the_id = $(elem).id;
		var num_id = the_id.match(/\d+$/);
		return num_id;
	},

	/* Replaces a node by a piece of (escaped) HTML code */
	replaceByHTML: function(elem, html)
	{
		if ($(elem)) {
			var node = $(elem);
			node.id = '__tmp_whoopwhoop';
			new Insertion.Before(node, html);
			Element.remove(node);
		}
	}
}

