function selectCheck(parent)
{
	var images = parent.parentNode.parentNode.getElementsByTagName('img');
	for (var i = 0; i < images.length; i++) {
		images[i].style.border = "1px solid #ccc";
	}
	var input = parent.getElementsByTagName("input")[0];
	input.checked = true;
	parent.getElementsByTagName("img")[0].style.border = "1px solid #000";
}
