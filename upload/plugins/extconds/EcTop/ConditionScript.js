extconds_hooks['top'] = function(node, para, isNew) {
	var s = {
		dimBox: node.getElementsByTagName('select')[0],
		countFld: node.getElementsByTagName('input')[1],
		countBox: node.getElementsByTagName('select')[2],
		groupBox: node.getElementsByTagName('select')[3]
	};

	var constructBox = function(target, values, titles, use) {
		var oldVal = target.value;
		$A(target.getElementsByTagName('option')).select(function(e) {
			return e.className != 'Header';
		}).each(function(e) {
			e.parentNode.removeChild(e);
		});
		for (var i = 0; i < values.length; i++) {
			if (!use(i, values[i], titles[i])) continue;
			var node = document.createElement('option');
			node.value = values[i];
			node.innerHTML = titles[i];
			if (node.value == oldVal) node.selected = 'true';
			target.appendChild(node);
		}
	};

	// disable evil (=ungrouped) dimensions
	constructBox(s.dimBox, dimensionIds, dimensionTexts, function(seq, val, title) {
		return dimsToGrps[seq].length > 0;
	});

	var onDimBoxChange = function() {
		processField(s.dimBox, ident, function(v) {return v == ''});
		if ($F(s.dimBox) == '') return;

		var res = processField(s.dimBox, ident, function(val) {return (dimsToGrps[dimsSequence[val]].length == 0);});

		// update group box
		constructBox(s.groupBox, dimgroupIds, dimgroupTexts, function(seq, val, title) {
			return dimsToGrps[dimsSequence[s.dimBox.value]].include(seq);
		});
		onGroupBoxChange(s);
	};

	var onGroupBoxChange = function() {
		processField(s.groupBox, ident, function(v) {return v == ''});
		if ($F(s.groupBox) == '') return;

		var cnt = dimgroupCounts[grpsSequence[s.groupBox.value]];
		if (cnt > 0) {
			s.countBox.innerHTML = '';
			for (var i = 1; i <= cnt; i++) {
				var node = document.createElement('option');
				node.innerHTML = i.toString();
				s.countBox.appendChild(node);
			}
			var val = $F(s.countFld);
			if (val >= 1 && val < cnt) {
				s.countBox.value = val;
			}
		}
		onCountBoxChange(s);
	};

	var onCountBoxChange = function() {
		if (s.countBox.value) {
			s.countFld.value = s.countBox.value;
		} else if (s.countBox.selectedIndex != -1) {
			s.countFld.value = s.countBox.options[s.countBox.selectedIndex].text;
		}
	};

	Event.observe(s.dimBox, 'change', onDimBoxChange);
	Event.observe(s.groupBox, 'change', onGroupBoxChange);
	Event.observe(s.countBox, 'change', onCountBoxChange);

	// and call them for first init
	onDimBoxChange(s);
};
