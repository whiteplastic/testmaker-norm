
/* 'Wechsle Darstellung 061005' (c) cybaer@binon.net - http://Coding.binon.net/Toggle */
/* Lizenz CC <http://creativecommons.org/licenses/by-nc-sa/2.5/> */
function toggle(type,element,toggleID) {
 var i, j, t, type0, start=0, result=0;
 var obj, objName, objFirst=-1, objLast=-1, objCount, exceptions, lastArgument, xSwitch;
 var toggleDisplay, displayType, displayStyle, displayXStyle;
 var toggleVisibility, visibilityStyle, visibilityXStyle;
 var toggleOpacity, opacityType, opacityStyle, opacityXStyle, opacityStyleCSS, opacityXStyleCSS, opacityStyleMoz, opacityXStyleMoz, opacityStyleKHTML, opacityXStyleKHTML, opacityStyleIE, opacityXStyleIE;
 var toggleColor, colorType, colorXType, colorStyle, colorXStyle;
 var toggleBack, backType, backXType, backStyle, backXStyle;
 var toggleBorder, borderType, borderXType, borderStyle, borderXStyle;
 var toggleAttribute="gid"; // hier ggf. gewuenschten Standard-Attribut-Namen eintragen (z.B. "id")
 var showStatus=200; // hier eintragen, ab wieviel Elementen ein Bearbeitungshinweis erfolgen soll

 type=(type)?type.toLowerCase():"fold";

 if(element) {
  i=element.indexOf("{"); j=element.indexOf("}",i);
  if(i>=0 && j>=0) {
  objFirst=parseInt(element.substring(i+1,element.indexOf("-",i)));
  objLast=parseInt(element.substring(element.indexOf("-",i)+1,j));
  element=element.substring(0,i);
  }
 }

 if(!element || type=="test") {
  result=(document.getElementsByTagName)?true:false;
 } else {

  toggleDisplay=(type.indexOf("fold")+1);    // Style display verwenden
  toggleVisibility=(type.indexOf("hide")+1); // Style visibility verwenden
  toggleOpacity=(type.indexOf("trans")+1);   // Style opacity & Co. verwenden
  toggleColor=(type.indexOf("color")+1);     // Style color verwenden
  toggleBack=(type.indexOf("back")+1);       // Style background-color verwenden
  toggleBorder=(type.indexOf("border")+1);   // Style border-color verwenden

  if(toggleDisplay) {
   displayType="";
   i=type.indexOf("fold:");
   if(i>=0) {
    i+=4; j=type.indexOf(" ",i)
    displayType=type.substring(i+1,(j<0)?type.length:j);
    displayType=(displayType=="none")?"":displayType;
   }
   if(type.indexOf("unfold")>=0) {
    displayStyle=displayType; displayXStyle="none";
   } else {
    displayStyle="none"; displayXStyle=displayType;
   }
  }

  if(toggleVisibility) {
   if(type.indexOf("unhide")>=0) {
    visibilityStyle="visible"; visibilityXStyle="hidden";
   } else {
    visibilityStyle="hidden"; visibilityXStyle="visible";
   }
  }

  if(toggleOpacity) {
   opacityType=50;
   opacityXType=0;
   i=type.indexOf("trans:");
   if(i>=0) {
    i+=5; j=type.indexOf(" ",i)
    opacityType=type.substring(i+1,(j<0)?type.length:j);
    i=opacityType.indexOf("/");
    if(i>=0) {
     j=opacityType.indexOf(" ",i)
     opacityXType=opacityType.substring(i+1,(j<0)?opacityType.length:j);
     opacityType=opacityType.substring(0,i)+((j<0)?"":opacityType.substring(j,opacityType.length));
    }
   }
   opacityStyle=Math.min(100,Math.max(0,100-parseInt(opacityType)));
   opacityXStyle=Math.min(100,Math.max(0,100-parseInt(opacityXType)));
   opacityStyleCSS=""+opacityStyle/100;
   opacityXStyleCSS=""+opacityXStyle/100;
   opacityStyleMoz=opacityStyleCSS;
   opacityXStyleMoz=opacityXStyleCSS;
   opacityStyleKHTML=opacityStyleCSS;
   opacityXStyleKHTML=opacityXStyleCSS;
   opacityStyleIE="alpha(opacity="+opacityStyle+")";
   opacityXStyleIE="alpha(opacity="+opacityXStyle+")";
  }

  if(toggleColor) {
   colorType="#000000";
   colorXType="#FFFFFF";
   i=type.indexOf("color:");
   if(i>=0) {
    i+=5; j=type.indexOf(" ",i)
    colorType=type.substring(i+1,(j<0)?type.length:j);
    i=colorType.indexOf("/");
    if(i>=0) {
     j=colorType.indexOf(" ",i)
     colorXType=colorType.substring(i+1,(j<0)?colorType.length:j);
     colorType=colorType.substring(0,i)+((j<0)?"":colorType.substring(j,colorType.length));
    }
   }
   colorStyle=colorType;
   colorXStyle=colorXType;
  }

  if(toggleBack) {
   backType="#FFFFFF";
   backXType="#000000";
   i=type.indexOf("back:");
   if(i>=0) {
    i+=4; j=type.indexOf(" ",i)
    backType=type.substring(i+1,(j<0)?type.length:j);
    i=backType.indexOf("/");
    if(i>=0) {
     j=backType.indexOf(" ",i)
     backXType=backType.substring(i+1,(j<0)?backType.length:j);
     backType=backType.substring(0,i)+((j<0)?"":backType.substring(j,backType.length));
    }
   }
   backStyle=backType;
   backXStyle=backXType;
  }

  if(toggleBorder) {
   borderType="transparent";
   borderXType="black";
   i=type.indexOf("border:");
   if(i>=0) {
    i+=6; j=type.indexOf(" ",i)
    borderType=type.substring(i+1,(j<0)?type.length:j);
    i=borderType.indexOf("/");
    if(i>=0) {
     j=borderType.indexOf(" ",i)
     borderXType=borderType.substring(i+1,(j<0)?borderType.length:j);
     borderType=borderType.substring(0,i)+((j<0)?"":borderType.substring(j,borderType.length));
    }
   }
   borderStyle=borderType;
   borderXStyle=borderXType;
  }

  if(document.getElementById && document.getElementById(element)) {
   obj=document.getElementById(element);
   result=1;

   if(toggleDisplay) {
    obj.style.display=displayStyle;
   }

   if(toggleVisibility) {
    obj.style.visibility=visibilityStyle;
   }

   if(toggleOpacity) {
    obj.style.filter=opacityStyleIE;
    obj.style.MozOpacity=opacityStyleMoz;
    obj.style.KhtmlOpacity=opacityStyleKHTML;
    obj.style.opacity=opacityStyleCSS;
   }

   if(toggleColor) {
    obj.style.color=colorStyle;
   }

   if(toggleBack) {
    obj.style.backgroundColor=backStyle;
   }

   if(toggleBorder) {
    obj.style.borderColor=borderStyle;
   }

  } else if(document.getElementsByTagName) {
   i=element.indexOf(":");
   if(i>=0) {
    toggleAttribute=element.substring(i+1,element.length);
    element=element.substring(0,i);
   }

   if(document.getElementsByTagName(element).length && toggleID) {

    lastArgument=(typeof(toggle.arguments[toggle.arguments.length-1])=="boolean")?-1:0;
    xSwitch=(lastArgument)?toggle.arguments[toggle.arguments.length-1]:false;
    exceptions=toggleID; for(i=3;i<(toggle.arguments.length+lastArgument);i++) { exceptions+=toggle.arguments[i]+"|"; }
    i=exceptions.indexOf("|"); t=""; while(i>=0) { t+=exceptions.substring(start,i+1)+toggleID; start=i+1; i=exceptions.indexOf("|",start); if(!i) { break; } }
    exceptions=t.substring(0,t.length-toggleID.length);

    obj=document.getElementsByTagName(element);
    objFirst=(objFirst<0)?0:Math.max(0,objFirst);
    objLast=(objLast<0)?obj.length:Math.min(obj.length,objLast);
    objCount=(objLast-objFirst)+1;
    for(i=objFirst;i<objLast;i++) {
     objName=obj[i].getAttribute(toggleAttribute);
     if(objName && objName.substring(0,toggleID.length)==toggleID) {
      result++;
      toggleException=(exceptions.indexOf(objName+"|")>=0)?true:false;
      if(objCount>showStatus) { window.status="Bearbeitetes Element: "+(result)+"/"+objCount; }

      if(toggleDisplay) {
       if(xSwitch) {
        obj[i].style.display=(toggleException)?displayXStyle:displayStyle;
       } else if(!toggleException) {
        obj[i].style.display=displayStyle;
       }
      }

      if(toggleVisibility) {
       if(xSwitch) {
        obj[i].style.visibility=(toggleException)?visibilityXStyle:visibilityStyle;
       } else if(!toggleException) {
        obj[i].style.visibility=visibilityStyle;
       }
      }

      if(toggleOpacity) {
       if(xSwitch) {
        obj[i].style.filter=((toggleException)?opacityXStyleIE:opacityStyleIE);
        obj[i].style.MozOpacity=((toggleException)?opacityXStyleMoz:opacityStyleMoz);
        obj[i].style.KhtmlOpacity=((toggleException)?opacityXStyleKHTML:opacityStyleKHTML);
        obj[i].style.opacity=((toggleException)?opacityXStyleCSS:opacityStyleCSS);
       } else if(!toggleException) {
        obj[i].style.filter=opacityStyleIE;
        obj[i].style.MozOpacity=opacityStyleMoz;
        obj[i].style.KhtmlOpacity=opacityStyleKHTML;
        obj[i].style.opacity=opacityStyleCSS;
       }
      }

      if(toggleColor) {
       if(xSwitch) {
        obj[i].style.color=(toggleException)?colorXStyle:colorStyle;
       } else if(!toggleException) {
        obj[i].style.color=colorStyle;
       }
      }

      if(toggleBack) {
       if(xSwitch) {
        obj[i].style.backgroundColor=(toggleException)?backXStyle:backStyle;
       } else if(!toggleException) {
        obj[i].style.backgroundColor=backStyle;
       }
      }

      if(toggleBorder) {
       if(xSwitch) {
        obj[i].style.borderColor=(toggleException)?borderXStyle:borderStyle;
       } else if(!toggleException) {
        obj[i].style.borderColor=borderStyle;
       }
      }

     }
    }
    if(objCount>showStatus) { window.status=" "; }
   } else {
    result=0;
   }
  } else {
   result=false;
  }
 }
 return result;
}

