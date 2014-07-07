function getCookie(name)
{
	var cookie = document.cookie;

	do {
		var currentName = cookie.substr(0, cookie.search('='));
		var currentValue = cookie.substring(currentName.length+1);

		var add = 1;
		if (currentValue.search(';') != -1) {
			currentValue = currentValue.substr(0, currentValue.search(';'));
			add++;
		}
		cookie = cookie.substr(currentName.length+currentValue.length+add);

		while (cookie.length > 0 && cookie.substr(0, 1) == " ") {
			cookie = cookie.substr(1);
		}

		if (currentName == name) {
			return currentValue;
		}
	} while (cookie != "");
}

function setCookie(name, value)
{
	document.cookie = name+"="+value;
}

function deleteCookie(name)
{
	currentValue = getCookie(name);
	if (currentValue != undefined) {
		document.cookie = name+"="+currentValue+"; expires=Thu, 01-Jan-1970 00:00:01 GMT;";
	}
}
