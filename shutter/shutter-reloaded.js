// initial idea from Shutter by Andrew Sutherland, http://code.jalenack.com/
function mkShutter( shlink, setid, inset ) {

// edit the variables below to change the "Loading" and the "Click to close" text
// ***************************************************************************

var shLoading = 'L O A D I N G';
var shClose = 'Click to Close';

// ***************************************************************************


  var shNewDisplay, shNewShutter, shfile, shTitle, prevlink, nextlink, previmg, nextimg;
  
  this.hideShutter = function() {
    shNewDisplay = document.getElementById('shNewDisplay');
    shNewDisplay.parentNode.removeChild(shNewDisplay);
    shutter = document.getElementById('shNewShutter');
    shutter.parentNode.removeChild(shutter);
    showSelectBoxes();
    showFlash();
  }

  this.shShowImg = function() {
    if ( document.getElementById('shNewShutter') ) {
      var shWrap = document.getElementById('shWrap');
      if ( shWrap.style.visibility == 'visible' ) return;
      
      var shTopImg = document.getElementById('shTopImg');
      var shTextWrap = document.getElementById('shTextWrap');
      var shWaitBar = document.getElementById('shWaitBar');
      if ( shWaitBar ) shWaitBar.parentNode.removeChild(shWaitBar); 

      var wiH = window.innerHeight ? window.innerHeight : 0;
      var dbH = document.body.clientHeight ? document.body.clientHeight : 0;
      var deH = document.documentElement ? document.documentElement.clientHeight : 0;
      
      if( wiH > 0 ) {
        var wHeight = ( (wiH - dbH) > 1 && (wiH - dbH) < 30 ) ? dbH : wiH;
        var wHeight = ( (wHeight - deH) > 1 && (wHeight - deH) < 30 ) ? deH : wHeight;
      } else var wHeight = ( deH > 0 ) ? deH : dbH;

      var deW = document.documentElement ? document.documentElement.clientWidth : 0;
      var dbW = window.innerWidth ? window.innerWidth : document.body.clientWidth;
      var wWidth = ( deW > 1 ) ? deW : dbW;

      var capH = shTextWrap.clientHeight ? shTextWrap.clientHeight : 24;
      var shHeight = wHeight - 15 - capH;
      if ( shTopImg.height > shHeight ) {
        shTopImg.width = shTopImg.width * (shHeight / shTopImg.height);
        shTopImg.height = shHeight;
      }

      if ( shTopImg.width > (wWidth - 16) ) {
        shTopImg.height = shTopImg.height * ((wWidth - 16) / shTopImg.width);
        shTopImg.width = wWidth - 16;
      }
      
      var top = (wHeight - shTopImg.height - capH - 5) * 0.45;
      var mtop = (top > 3) ? Math.floor(top) : 3;

      shWrap.style.margin = mtop + 'px auto auto auto';
      shWrap.style.visibility = 'visible';
    }
  }

  // from lightbox by Lokesh Dhakar - http://www.huddletogether.com
  this.showSelectBoxes = function() {
	var selects = document.getElementsByTagName("select");
	for (i = 0; i < selects.length; i++) {
		selects[i].style.visibility = "visible";
	}
  }

  this.hideSelectBoxes = function() {
	var selects = document.getElementsByTagName("select");
	for (i = 0; i < selects.length; i++) {
		selects[i].style.visibility = "hidden";
	}
  }

  this.showFlash = function() {
	var flashObjects = document.getElementsByTagName("object");
	for (i = 0; i < flashObjects.length; i++) {
		flashObjects[i].style.visibility = "visible";
	}

	var flashEmbeds = document.getElementsByTagName("embed");
	for (i = 0; i < flashEmbeds.length; i++) {
		flashEmbeds[i].style.visibility = "visible";
	}
  }

  this.hideFlash = function() {
	var flashObjects = document.getElementsByTagName("object");
	for (i = 0; i < flashObjects.length; i++) {
		flashObjects[i].style.visibility = "hidden";
	}

	var flashEmbeds = document.getElementsByTagName("embed");
	for (i = 0; i < flashEmbeds.length; i++) {
		flashEmbeds[i].style.visibility = "hidden";
	}
  }

  shfile = shutterLinks[shlink].slice(shutterLinks[shlink].lastIndexOf('/')+1);
  if ( document.links[shlink].title && document.links[shlink].title != shfile ) shTitle = document.links[shlink].title;
  else shTitle = '&nbsp;';

  if ( inset != -1 ) {
    if ( inset > 1 ) prevlink = 'javascript:mkShutter(' + shutterSets[setid][inset - 2] + ',' + setid + ',' + (inset - 1) +')';
    else prevlink = '';

    if ( inset < (shutterSets[setid].length) ) nextlink = 'javascript:mkShutter(' + shutterSets[setid][inset] + ',' + setid + ',' + (inset + 1) +')';
    else nextlink = '';
  }
  
  if ( document.getElementById('shNewShutter') == null ) {
    shNewShutter = document.createElement('div');
    shNewShutter.setAttribute('id','shNewShutter');
    document.getElementsByTagName('body')[0].appendChild(shNewShutter);
    hideSelectBoxes();
    hideFlash();
    shNewShutter.onclick = hideShutter;
  }

  if ( document.getElementById('shNewDisplay') == null ) {
    shNewDisplay = document.createElement('div');
    shNewDisplay.setAttribute('id','shNewDisplay');
    document.getElementsByTagName('body')[0].appendChild(shNewDisplay);
  } else { shNewDisplay = document.getElementById('shNewDisplay'); }
  
  shNewDisplay.innerHTML = '<div id="shWaitBar">'+shLoading+'</div><table id="shWrap" style="visibility:hidden;"><tr><td colspan="3"><img src="' + shutterLinks[shlink] + '" id="shTopImg" onload="shShowImg();" onclick="hideShutter();" title="'+shClose+'" /></td></tr><tr id="shTextWrap"><td class="sh_arrows"><a href="' + prevlink + '" id="sh_prev">&lt;&lt;</a></td><td id="shTitle">' + shTitle + '</td><td class="sh_arrows"><a href="' + nextlink + '" id="sh_next">&gt;&gt;</a></td></tr></table>';
  shNewDisplay.innerHTML += '<div style="display:none">-----------------------------</div>'; // ugly ie6 html comments/dub. characters fix
    
  // preload
  if ( prevlink ) {
    previmg = new Image();
    previmg.src = shutterLinks[shutterSets[setid][inset - 2]];
  } else { document.getElementById('sh_prev').style.visibility = 'hidden'; }

  if ( nextlink ) {
    nextimg = new Image();
    nextimg.src = shutterLinks[shutterSets[setid][inset]];
  } else { document.getElementById('sh_next').style.visibility = 'hidden'; }
    
  window.setTimeout(function(){if(document.getElementById('shWaitBar'))document.getElementById('shWaitBar').style.display = 'block'},2000);
}
