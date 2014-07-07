// Toggles the reverse state of a question
function toggleReverse(qId)
{
	var checkbox = document.getElementById('item_reverse_' + qId)
	var reverse = checkbox.checked

	var answersList = document.getElementById('item_' + qId).getElementsByTagName('input')
	var cnt = reverse ? answersList.length : 1
	for (var i = 0; i < answersList.length; i++) {
		var item = answersList[i]
		item.value = cnt.toString()
		document.getElementById('score_' + item.id).innerHTML = cnt.toString()
		if (reverse) cnt--; else cnt++
	}
	return true
}

// Determine preset values for scale
var outerList = document.getElementById('item_scales')
var questions = outerList.getElementsByTagName('ul')

for (var i = 0; i < questions.length; i++) {
	// Skip groups
	if (questions[i].className == '') continue;

	var qId = questions[i].id.match(/^item_([0-9]+)$/)[1]

	var innerList = questions[i].getElementsByTagName('input')

	var reverse = (innerList[0].value.toString() > 1)
	var checkbox = document.getElementById("item_reverse_" + qId)
	checkbox.checked = reverse
	toggleReverse(qId)
}

