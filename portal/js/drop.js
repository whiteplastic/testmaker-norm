
/* This function sets the width of all select boxes on the 
 * item page to the width of the widest select box
 */
function setDropSize()
{
	var form = document.forms['itemForm'];
	var itemID = form.elements['item_id'].value;
	var items = itemID.split("_");
	if(items.length > 1)				// more than one item on the page
	{
		var maxWidth = 0;
		var select = Array(items.length);
		for(var i=0;i<items.length;i++)		// go through the items and test whether it's a selectbox or not
		{
			var inputElement = document.getElementById('answer'+items[i]);
			if(inputElement != null && inputElement.nodeName == "SELECT")
			{
				var width = inputElement.offsetWidth;
				if(width > maxWidth) maxWidth = width;
				select[i] = inputElement;
			}
			else select[i] = null;
		}
		// set width of all selectboxes to the maximum width
		for(var i=0;i<items.length;i++) if(select[i] != null) select[i].style.width = maxWidth+'px';
	}
}
