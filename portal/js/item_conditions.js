// This class is currently tailored to the needs of managing display
// conditions. To extend it to other uses, changes will likely be required.
function TestAccess(blockId, itemId, resourceUrl)
{
	this.blockId = blockId;
	this.itemId = itemId;
	this.resourceUrl = resourceUrl;

	this.itemBlocks = [];

	this.getItemBlocks = function()
	{
		return this.itemBlocks;
	}

	this.setItemBlocks = function(itemBlocks) {
		this.itemBlocks = itemBlocks;
	}

	this.items = {};

	this.getItems = function(blockId, repeater)
	{
		if (! this.items[blockId]) {
			var testAccess = this;
			this.fetchContents({resource: "items", parent_id: blockId}, function(data) { testAccess.setItems(blockId, data); }, repeater);
		}
		return this.items[blockId];
	}

	this.setItems = function(blockId, items)
	{
		this.items[blockId] = items;
	}

	this.answers = {};

	this.getAnswers = function(itemId, repeater)
	{
		if (! this.answers[itemId]) {
			var testAccess = this;
			this.fetchContents({resource: "answers", parent_id: itemId}, function(data) { testAccess.setAnswers(itemId, data); }, repeater);
		}
		return this.answers[itemId];
	}

	this.setAnswers = function(itemId, answers)
	{
		this.answers[itemId] = answers;
	}

	this.fetchContents = function(parameters, updater, repeater)
	{
		var queryString = "";
		for (name in parameters) {
			if (queryString != "") {
				queryString += "&";
			}
			queryString += escape(name)+"="+escape(parameters[name]);
		}

		var ajaxArguments;

		ajaxArguments ={
			method: 'get',
			asynchronous: true,
			parameters: queryString,
			onSuccess: function(response) {
				if (updater) { updater(eval(response.responseText)) }
				if (repeater) { repeater(); }

			},
			onFailure: function(t) {
				alert("AJAX error\n\nURL: "+this.resourceUrl+"\nArguments:\n"+makeDebugString(ajaxArguments, "\t"));
			}
		};
		new Ajax.Request(this.resourceUrl, ajaxArguments);
	}
}

function replaceOuterHtml(node, newHtml)
{
	var container = document.createElement("div");
	container.appendChild(document.createElement(node.tagName));
	container.firstChild.outerHTML = newHtml;
	var clone = container.firstChild;
	node.parentNode.replaceChild(clone, node);

	return clone;
}

function RadioComponent(on, off, id, conditions)
{
	this.conditions = conditions;

	// Internet Explorer hack because the radio button logic does not change when simply changing the name
	var parentNode = on.parentNode;
	if (on.outerHTML) {
		on = replaceOuterHtml(on, on.outerHTML.replace(/\[\]/, "["+id+"]"));
		off = replaceOuterHtml(off, off.outerHTML.replace(/\[\]/, "["+id+"]"));
	} else {
		on.name = on.name.replace(/\[\]/, "["+id+"]");
		off.name = on.name;
	}

	on.onclick = function() {
		this.component.conditions.updateCompletion(this.component.nextComponent);
	}
	off.onclick = on.onclick;

	this.on = on;
	this.off = off;

	on.component = this;
	off.component = this;
}

RadioComponent.prototype.setValue = function(value)
{
	this.on.checked = (value == "yes");
	this.off.checked = (value == "no");
}

RadioComponent.prototype.setDisabled = function(disabled)
{
	this.on.disabled = disabled ? "disabled" : null;
	this.off.disabled = disabled ? "disabled" : null;
}

RadioComponent.prototype.isDisabled = function()
{
	return (this.on.disabled || this.off.disabled);
}

RadioComponent.prototype.focus = function()
{
	this.on.focus();
}

RadioComponent.prototype.isComplete = function()
{
	return this.on.checked || this.off.checked;
}

function selectValue(select, value)
{
	for (var i = 0; i < select.options.length; i++) {
		if (select.options[i].value == value) {
			select.selectedIndex = i;
			select.onchange();
			return i;
		}
	}
	return null;
}

function Conditions(nodeId, conditions, testAccess)
{
	this.node = $(nodeId);	// workaround for ie: should extend the element with prototype functions now
	this.length = 0;
	this.nextId = 0;

	this.testAccess = testAccess;

	var template = this.node.select('[class="ItemCondition"]')[0];
	this.template = template.cloneNode(true);
	this.container = template.parentNode;
	template.parentNode.removeChild(template);
	// Clear preselections by the browser
	this.template.select('[class="ItemConditionChosenOn"]')[0].checked = false;
	this.template.select('[class="ItemConditionChosenOff"]')[0].checked = false;

	if (! conditions || conditions.length == 0) {
		this.add();
	}
	else {
		for (var i = 0; i < conditions.length; i++) {
			this.add(conditions[i]);
		}
	}
}

Conditions.prototype.getConditionFromNode = function(node)
{
	if (! node) {
		node = this.template;
	}

	var idField = node.select('[class="ItemConditionId"]')[0];
	var itemBlockSelect = node.select('[class="ItemConditionItemBlocks"]')[0];
	var itemSelect = node.select('[class="ItemConditionItems"]')[0];
	var answerSelect = node.select('[class="ItemConditionAnswers"]')[0];
	var chosenOn = node.select('[class="ItemConditionChosenOn"]')[0];
	var chosenOff = node.select('[class="ItemConditionChosenOff"]')[0];

	var condition = {
		"id": idField.value,
		"item_block_id": itemBlockSelect.options[itemBlockSelect.options.selectedIndex].value,
		"item_id": itemSelect.options[itemSelect.options.selectedIndex].value,
		"answer_id": answerSelect.options[answerSelect.options.selectedIndex].value,
		"chosen": chosenOn.checked ? "yes" : (chosenOff.checked ? "no" : null)
	};

	var out = "";
	for (var name in condition) {
		out += name+" = "+condition[name]+"\n";
	}

	return condition;
}

Conditions.prototype.add = function(condition, after)
{
	var node = this.template.cloneNode(true);
	var id = this.nextId++;

	if (! condition) {
		condition = this.getConditionFromNode(after);
	}

	if (after) {
		condition.id = 0;
	}

	var components = {
		idField: node.select('[class="ItemConditionId"]')[0],
		addButton: node.select('[class="Button ItemConditionAdd"]')[0],
		removeButton: node.select('[class="Button ItemConditionRemove"]')[0],
		itemBlockSelect: node.select('[class="ItemConditionItemBlocks"]')[0],
		itemSelect: node.select('[class="ItemConditionItems"]')[0],
		answerSelect: node.select('[class="ItemConditionAnswers"]')[0]
	};

	for (name in components) {
		components[name].name = components[name].name.replace(/\[\]/, "["+id+"]");
	}

	components.chosen = new RadioComponent(
		node.select('[class="ItemConditionChosenOn"]')[0],
		node.select('[class="ItemConditionChosenOff"]')[0],
		id
	);

	components.completion = new Object();
	components.completion.imgComplete = node.select('[class="Complete"]')[0],
	components.completion.imgIncomplete = node.select('[class="Incomplete"]')[0],
	components.completion.update = function()
	{
		if (this.previousComponent.isComplete()) {
			this.imgComplete.style.display="inline";
			this.imgIncomplete.style.display="none";
		} else {
			this.imgComplete.style.display="none";
			this.imgIncomplete.style.display="inline";
		}
	}

	for (name in components) {
		components[name].conditions = this;
	}

	components.addButton.onclick = function() {
		this.conditions.add(null, node);
	}

	components.removeButton.onclick = function() {
		this.conditions.remove(node);
	}

	components.itemBlockSelect.onchange = function() {
		this.conditions.updateItems(this.nextComponent, this.options[this.options.selectedIndex].value, true);
	}
	components.itemBlockSelect.isComplete = function() {
		return this.options[this.options.selectedIndex].value != "";
	}

	components.itemSelect.onchange = function() {
		this.conditions.updateAnswers(this.nextComponent, this.options[this.options.selectedIndex].value, true);
	}
	components.itemSelect.isComplete = function() {
		return this.options[this.options.selectedIndex].value != "";
	}

	components.answerSelect.onchange = function() {
		this.conditions.updateChosen(this.nextComponent, this.options[this.options.selectedIndex].value ? "none" : "", true);
	}
	components.answerSelect.isComplete = function() {
		return this.options[this.options.selectedIndex].value != "";
	}

	components.chosen.updateCompletion = function(chosen) {
		this.conditions.updateCompletion(chosen, node);
	}

	this.bindComponent(components.itemBlockSelect, components.itemSelect);
	this.bindComponent(components.itemSelect, components.answerSelect);
	this.bindComponent(components.answerSelect, components.chosen);
	this.bindComponent(components.chosen, components.completion);

	if (! after || ! after.nextSibling) {
		this.container.appendChild(node);
	} else {
		this.container.insertBefore(node, after.nextSibling);
	}

	this.updateItemBlocks(components.itemBlockSelect);
	components.idField.value = condition.id;
	selectValue(components.itemBlockSelect, condition.item_block_id);
	selectValue(components.itemSelect, condition.item_id);
	selectValue(components.answerSelect, condition.answer_id);

	// This has to happen after the node has been added to the DOM, because otherwise IE screws up
	this.updateChosen(components.chosen, condition.chosen);

	this.length++;
}

Conditions.prototype.remove = function(node)
{
	this.container.removeChild(node);
	this.length--;

	if (this.length == 0) {
		this.add();
	}
}

Conditions.prototype.bindComponent = function(left, right)
{
	left.nextComponent = right;
	right.previousComponent = left;
}

Conditions.prototype.updateItemBlocks = function(itemBlocks)
{
	fillList(itemBlocks, this.testAccess.getItemBlocks());
	this.updateItems(itemBlocks.nextComponent);
}

Conditions.prototype.updateItems = function(items, itemBlockId, focus)
{
	var list;

	if (items.previousComponent.isComplete()) {
		items.disabled=null;
	} else {
		items.disabled="disabled";
	}

	if (! itemBlockId) {
		list = [];
	} else {
		var context = this;
		list = this.testAccess.getItems(itemBlockId, function() { context.updateItems(items, itemBlockId, focus); });
	}

	fillList(items, list);
	this.updateAnswers(items.nextComponent);

	if (focus && ! items.disabled) {
		items.focus();
	}
}

Conditions.prototype.updateAnswers = function(answers, itemId, focus)
{
	var list;

	if (answers.previousComponent.isComplete()) {
		answers.disabled=null;
	} else {
		answers.disabled="disabled";
	}

	if (! itemId) {
		list = [];
	} else {
		var context = this;
		list = this.testAccess.getAnswers(itemId, function() { context.updateAnswers(answers, itemId, focus); });
	}

	fillList(answers, list);
	this.updateChosen(answers.nextComponent);

	if (focus && ! answers.disabled) {
		answers.focus();
	}
}

Conditions.prototype.updateChosen = function(chosen, value, focus)
{
	var newValue;

	chosen.setDisabled(! chosen.previousComponent.isComplete());

	if (! value) {
		newValue = "";
	} else {
		newValue = value;
	}

	chosen.setValue(newValue);
	this.updateCompletion(chosen.nextComponent);

	if (focus && ! chosen.isDisabled()) {
		chosen.focus();
	}
}

Conditions.prototype.updateCompletion = function(completion)
{
	completion.update();
}

function fillList(list, options)
{
	list.options.length = 1;
	if (options) {
		for (var i = 0; i < options.length; i++) {
			list.options[i+1] = new Option(options[i][0], options[i][1], false, false);
		}
	}
}
