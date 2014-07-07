extconds_hooks['interval'] = function(node, para, isNew) {
	var s = {
		dimBox: node.getElementsByTagName('select')[0],
		minFld: node.getElementsByTagName('input')[1],
		maxFld: node.getElementsByTagName('input')[2]
	};

	onMinFldChange = function() {
		var min = getFloat(s.minFld);
		var max = getFloat(s.maxFld);
		if (!isNaN(min) && isNaN(max) || max < min) {
			s.maxFld.value = min;
			processField(s.maxFld, ident, ffalse);
		}
	};

	onMaxFldChange = function() {
		var min = getFloat(s.minFld);
		var max = getFloat(s.maxFld);
		if (!isNaN(max) && isNaN(min) || min > max) {
			s.minFld.value = max;
			processField(s.minFld, ident, ffalse);
		}
	};

	Event.observe(s.minFld, 'change', onMinFldChange);
	Event.observe(s.maxFld, 'change', onMaxFldChange);

	onMinFldChange();
	onMaxFldChange();

};
