function setCookie(c_name,value,expiredays)
{
var exdate=new Date();
exdate.setDate(exdate.getDate()+expiredays);
document.cookie=c_name+ "=" +escape(value)+
((expiredays==null) ? "" : ";expires="+exdate.toUTCString());
}

function getCookie(c_name)
{
if (document.cookie.length>0)
  {
  c_start=document.cookie.indexOf(c_name + "=");
  if (c_start!=-1)
    {
    c_start=c_start + c_name.length+1;
    c_end=document.cookie.indexOf(";",c_start);
    if (c_end==-1) c_end=document.cookie.length;
    return unescape(document.cookie.substring(c_start,c_end));
    }
  }
return "";
}

function checkCookie(c_name,value)
{
cook=getCookie(c_name);
if (cook!=null && cook!="")
  {
  deleteCookie(c_name);
  }
else
  {
  setCookie(c_name,value);
  }
}

function doAllandSubmit() {
	var element;
	if (document.getElementById && (element = document.getElementById("autotest"))) {
		if(document.getElementById("autotest").checked == true) {
			doAll();
			setTimeout('SubmitForm()',1250);
		}
	}
}

function SubmitForm() {
	if(document.getElementById("autotest").checked == true) {
		if (document.itemForm)
			document.itemForm.submit();
		else {
		var formlist = document.getElementsByTagName("form");
		formlist[0].submit();
		}
	}
}

function doAll() {
var forms = document.getElementsByTagName("form");

for (var i=0; i<forms.length; i++) 
	runAdmin(forms[i]);
}

function runAdmin(theForm){

var els = theForm.elements; 
	for(i=0; i<els.length; i++){ 

		switch(els[i].type){

			case "select-one" :
				var randomnumber=Math.floor(Math.random()*els[i].length)
				els[i].selectedIndex = randomnumber;
				
				while(els[i].value.length == 0) {
				var randomnumber=Math.floor(Math.random()*els[i].length)
				els[i].selectedIndex = randomnumber;
				}
				break;

			case "text":

				els[i].value= "AdminTest"+Math.floor(Math.random()*100);

				break;

			case "textarea":

				els[i].value= "AdminTest"+Math.floor(Math.random()*100);

				break;



			case "checkbox":

				if (theForm.hadCheck == true) {
				if (Math.floor(Math.random()*3) == 0) {
					els[i].checked = true;
					theForm.hadCheck = true;
					}
				}
				else {
					els[i].checked = true;
					theForm.hadCheck = true;
				}
				

				break;

			case "radio":
				if (theForm.hadRadio == true) {
				if (Math.floor(Math.random()*3) == 0) {
					els[i].checked = true;
					theForm.hadRadio = true;
					}
				}
				else {
					els[i].checked = true;
					theForm.hadRadio = true;
				}
				break;
				
				case "submit":
				
				if (document.getElementById("answer"+els[i].name)) {
					if (theForm.hadSubmit == true) {
					if (Math.floor(Math.random()*3) == 0) {
						document.getElementById("answer"+els[i].name).value=els[i].value;
						theForm.hadSubmit = true;
						}
					}
					else {
						document.getElementById("answer"+els[i].name).value=els[i].value;
						theForm.hadSubmit = true;
					}
				}
				break;

		}

	}

}