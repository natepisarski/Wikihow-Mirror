var $jscomp=$jscomp||{};$jscomp.scope={};$jscomp.ASSUME_ES5=!1;$jscomp.ASSUME_NO_NATIVE_MAP=!1;$jscomp.ASSUME_NO_NATIVE_SET=!1;$jscomp.defineProperty=$jscomp.ASSUME_ES5||"function"==typeof Object.defineProperties?Object.defineProperty:function(c,g,f){c!=Array.prototype&&c!=Object.prototype&&(c[g]=f.value)};$jscomp.getGlobal=function(c){return"undefined"!=typeof window&&window===c?c:"undefined"!=typeof global&&null!=global?global:c};$jscomp.global=$jscomp.getGlobal(this);
$jscomp.polyfill=function(c,g,f,m){if(g){f=$jscomp.global;c=c.split(".");for(m=0;m<c.length-1;m++){var l=c[m];l in f||(f[l]={});f=f[l]}c=c[c.length-1];m=f[c];g=g(m);g!=m&&null!=g&&$jscomp.defineProperty(f,c,{configurable:!0,writable:!0,value:g})}};$jscomp.polyfill("Object.is",function(c){return c?c:function(c,f){return c===f?0!==c||1/c===1/f:c!==c&&f!==f}},"es6","es3");
$jscomp.polyfill("Array.prototype.includes",function(c){return c?c:function(c,f){var g=this;g instanceof String&&(g=String(g));var l=g.length;f=f||0;for(0>f&&(f=Math.max(f+l,0));f<l;f++){var n=g[f];if(n===c||Object.is(n,c))return!0}return!1}},"es7","es3");
$jscomp.checkStringArgs=function(c,g,f){if(null==c)throw new TypeError("The 'this' value for String.prototype."+f+" must not be null or undefined");if(g instanceof RegExp)throw new TypeError("First argument to String.prototype."+f+" must not be a regular expression");return c+""};$jscomp.polyfill("String.prototype.includes",function(c){return c?c:function(c,f){return-1!==$jscomp.checkStringArgs(this,c,"includes").indexOf(c,f||0)}},"es6","es3");
$jscomp.findInternal=function(c,g,f){c instanceof String&&(c=String(c));for(var m=c.length,l=0;l<m;l++){var n=c[l];if(g.call(f,n,l,c))return{i:l,v:n}}return{i:-1,v:void 0}};$jscomp.polyfill("Array.prototype.find",function(c){return c?c:function(c,f){return $jscomp.findInternal(this,c,f).v}},"es6","es3");
WH.ads=function(){function c(){var a=!1;null!=document.hidden?a=document.hidden:null!=document.mozHidden?a=document.mozHidden:null!=document.webkitHidden?a=document.webkitHidden:null!=document.msHidden&&(a=document.msHidden);return a}function g(a,b,d){for(var e=[],c=0;c<b.length;c++)e.push(gptAdSlots[b[c]]);apstag.fetchBids({slots:a,timeout:d},function(a){googletag.cmd.push(function(){apstag.setDisplayBids();for(var a=0;a<e.length;a++)setDFPTargeting(e[a],dfpKeyVals);googletag.pubads().refresh(e)})})}
function f(a){for(var b=a.adTargetId,d=gptAdSlots[b].getAdUnitPath(),e=gptAdSlots[b].getSizes(),c=[],f=0;f<e.length;f++){var h=[];h.push(e[f].getWidth());h.push(e[f].getHeight());c.push(h)}g([{slotID:b,slotName:d,sizes:c}],[b],a.apsTimeout)}function m(a,b,d){gptAdSlots[a]&&dfpKeyVals[gptAdSlots[a].getAdUnitPath()]&&(dfpKeyVals[gptAdSlots[a].getAdUnitPath()][b]=d)}function l(a){var b=a.adTargetId,d=a.gptLateLoad,e=a.getRefreshValue();googletag.cmd.push(function(){d&&googletag.display(b);m(b,"refreshing",
e);setDFPTargeting(gptAdSlots[b],dfpKeyVals);googletag.pubads().refresh([gptAdSlots[b]])})}function n(){(function(){var a=document.createElement("script");a.async=!0;a.type="text/javascript";a.src="https://securepubads.g.doubleclick.net/tag/js/gpt.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})();googletag.cmd.push(function(){defineGPTSlots();googletag.pubads().addEventListener("slotRenderEnded",function(a){WH.ads&&WH.ads.slotRendered(a.slot,a.size,a)});googletag.pubads().addEventListener("impressionViewable",
function(a){WH.ads&&WH.ads.impressionViewable(a.slot)})})}function B(a){var b=window.document.createElement("ins");b.setAttribute("data-ad-client","ca-pub-9543332082073187");a.adLabelClass?b.setAttribute("class","adsbygoogle "+a.adLabelClass):b.setAttribute("class","adsbygoogle");var d=a.slot;if(d){b.setAttribute("data-ad-slot",d);d=null;var e=0<=document.cookie.indexOf("ccpa_out=")?!0:!1;e?(b.setAttribute("data-restrict-data-processing",1),"intro"==a.type&&(d=2385774741)):"intro"==a.type&&(d=2001974826);
a.adElement.getAttribute("data-ad-format")&&b.setAttribute("data-ad-format",a.adElement.getAttribute("data-ad-format"));a.adElement.getAttribute("data-full-width-responsive")&&b.setAttribute("data-full-width-responsive",a.adElement.getAttribute("data-full-width-responsive"));"middlerelated"==a.type&&(b.setAttribute("data-ad-format","fluid"),b.setAttribute("data-ad-layout-key","-fb+5w+4e-db+86"));e="display:inline-block;width:"+a.width+"px;height:"+a.height+"px;";var c=["method","qa","tips","warnings"];
"small"==a.adSize&&c.includes(a.type)&&(e="display:block;height:"+a.height+"px;");b.style.cssText=e;a.adTargetId&&(window.document.getElementById(a.adTargetId).appendChild(b),a.channels&&(d=d?a.channels+","+d:a.channels),d||(d=""),"undefined"===typeof adsbygoogle&&(window.adsbygoogle=[]),(window.adsbygoogle=window.adsbygoogle||[]).push({params:{google_ad_channel:d}}))}}function L(a){var b=document.documentElement.clientWidth;switch(a){case "intro":b-=30;break;case "method":b-=20;break;case "related":b-=
14;break;default:b-=20}return b}function C(a){var b=a.parentElement;this.element=a;this.adElement=b;this.height=this.adElement.offsetHeight;this.adTargetId=a.id;a=1==this.adElement.getAttribute("data-small");var d=1==this.adElement.getAttribute("data-medium"),e=1==this.adElement.getAttribute("data-large"),A=!1;if(a&&WH.shared.isSmallSize||d&&WH.shared.isMedSize||e&&WH.shared.isLargeSize)A=!0;if(A){this.gptLateLoad=1==this.adElement.getAttribute("data-lateload");this.service=this.adElement.getAttribute("data-service");
this.apsload=1==this.adElement.getAttribute("data-apsload");this.slot=this.adElement.getAttribute("data-slot");this.adunitpath=this.adElement.getAttribute("data-adunitpath");this.channels=this.adElement.getAttribute("data-channels");this.mobileChannels=this.adElement.getAttribute("data-mobilechannels");this.refreshable=1==this.adElement.getAttribute("data-refreshable");this.slotName=this.adElement.getAttribute("data-slot-name");this.refreshType=this.adElement.getAttribute("data-refresh-type");if(this.sizesArray=
this.adElement.getAttribute("data-sizes-array"))this.sizesArray=JSON.parse(this.sizesArray);this.type=this.adElement.getAttribute("data-type");"rightrail"==this.type&&(this.position="initial");this.notfixedposition=1==this.adElement.getAttribute("data-notfixedposition");this.viewablerefresh=1==this.adElement.getAttribute("data-viewablerefresh");this.renderrefresh=1==this.adElement.getAttribute("data-renderrefresh");this.width=this.adElement.getAttribute("data-width");this.height=this.adElement.getAttribute("data-height");
a&&WH.shared.isSmallSize&&(this.adSize="small",this.channels=this.mobileChannels,this.slot=this.adElement.getAttribute("data-smallslot")||this.slot,this.height=this.adElement.getAttribute("data-smallheight")||this.height,this.width=L(this.type),this.service=this.adElement.getAttribute("data-smallservice")||this.service);d&&WH.shared.isMedSize&&(this.adSize="medium",this.slot=this.adElement.getAttribute("data-mediumslot")||this.slot,this.height=this.adElement.getAttribute("data-mediumslot")||this.height,
this.width=this.adElement.getAttribute("data-mediumwidth")||this.width,this.service=this.adElement.getAttribute("data-mediumservice")||this.service);e&&WH.shared.isLargeSize&&(this.adSize="large");"adsense"!=this.service||this.slot?(this.instantLoad=1==this.adElement.getAttribute("data-instantload"),this.adLabelClass=this.adElement.getAttribute("data-adlabelclass"),this.instantLoad=1==this.adElement.getAttribute("data-instantload"),this.apsTimeout=this.adElement.getAttribute("data-aps-timeout"),this.refreshtimeout=
!1,this.refreshNumber=1,this.maxRefresh=this.adElement.getAttribute("data-max-refresh"),this.refreshTime=(this.refreshTime=this.adElement.getAttribute("data-refresh-time"))?parseInt(this.refreshTime):3E4,this.firstRefresh=!0,this.firstRefreshTime=(this.firstRefreshTime=this.adElement.getAttribute("data-first-refresh-time"))?parseInt(this.firstRefreshTime):this.refreshTime,this.useScrollLoader=!0,this.observerLoading=1==this.adElement.getAttribute("data-observerloading"),this.getRefreshTime=function(){return 1==
this.firstRefresh?(this.firstRefresh=!1,this.firstRefreshTime):this.refreshTime},this.getRefreshValue=function(){if(0==this.refreshNumber&&!this.refreshable)return"not";this.refreshNumber++;return 20<this.refreshNumber?"max":this.refreshNumber.toString()},this.load=function(){if(1!=this.isLoaded){if("dfp"==this.service)if(0==gptRequested&&(n(),gptRequested=!0),this.apsload){var a=this;gptAdSlots[this.adTargetId]?f(a):googletag.cmd.push(function(){f(a)})}else l(this);else"dfplight"==this.service?insertDFPLightAd(this):
B(this);this.isLoaded=!0}},this.refresh=function(){var a=this;if(c())setTimeout(function(){a.refresh()},5E3);else{this.lastRefreshScrollY=window.scrollY;var b=window.innerHeight||document.documentElement.clientHeight,d=this.element.getBoundingClientRect();if(r(d,b,!1,a))if(b=this.getRefreshValue(),this.maxRefresh&&b>this.maxRefresh)this.refreshable=!1;else if("adsense"!=this.service&&m(this.adTargetId,"refreshing",b),this.apsload)f(this);else{var e=this.adTargetId;googletag.cmd.push(function(){setDFPTargeting(gptAdSlots[e],
dfpKeyVals);googletag.pubads().refresh([gptAdSlots[e]])})}else setTimeout(function(){a.refresh()},5E3)}},this.show=function(){this.adElement.style.display="block"},this.instantLoad&&this.load()):(this.disabled=!0,b.style.display="none")}else this.disabled=!0,b.style.display="none"}function D(a){C.call(this,a)}function M(a){C.call(this,a);a.parentElement.style.display="none";this.scrollToTimer=null;this.lastScrollPositionY=0;this.maxNonSteps=parseInt(this.adElement.getAttribute("data-maxnonsteps"));
this.maxSteps=parseInt(this.adElement.getAttribute("data-maxsteps"));this.updateVisibility=function(){if(1>this.maxNonSteps&&1>this.maxSteps)t&&(window.removeEventListener("scroll",t),t=null);else if(this.lastScrollPositionY=window.scrollY,10<this.lastScrollPositionY){null!==this.scrollToTimer&&clearTimeout(this.scrollToTimer);var a=this;this.scrollToTimer=setTimeout(function(){a.load()},1E3)}};this.load=function(){a:{var a=window.innerHeight||document.documentElement.clientHeight;for(var d=document.getElementsByClassName("section"),
e=null,c=!1,f=0;f<=d.length;f++){if(f==d.length){var h=document.getElementById("ur_mobile");if(!h)break}else h=d[f];if("aiinfo"!=h.id){if(1==c){h=h.getElementsByClassName("section_text");if(!h||!h[0]){a=null;break a}h=h[0];var g=h.getBoundingClientRect();if(g.bottom>=screenTop&&g.bottom<=a)continue;break}if("intro"!=h.id){if(h.classList.contains("steps")){g=h.getElementsByClassName("steps_list_2");if(!g||!g[0])continue;h=a;g=g[0].childNodes;for(var k=null,l=!1,m=0;m<g.length;m++){var n=g[m];if("LI"==
n.nodeName){if(1==l){k=n;break}var p=n.getBoundingClientRect();if(r(p,h,!1,this))if(p.bottom>=screenTop&&p.bottom<=h)l=!0;else{k=n;break}}}h=k;if(!h)continue;e=h;break}if((h=h.getElementsByClassName("section_text"))&&h[0]&&(h=h[0],g=h.getBoundingClientRect(),r(g,a,!1,this)))if(g.bottom>=screenTop&&g.bottom<=a)c=!0;else{e=h;break}}}}a=e}a&&(d="LI"==a.tagName,d&&1>this.maxSteps||!d&&1>this.maxNonSteps||(e=a.getElementsByTagName("INS"),0<e.length||(e=a.getElementsByClassName("wh_ad_inner"),0<e.length||
(0<a.getElementsByClassName("addTipElement").length&&a.classList.add("has_scrolltoad"),e=document.createElement("div"),e.className=d?"wh_ad_inner step_ad scrollto_wrap":"wh_ad_inner scrollto_wrap",a.appendChild(e),a=e,a.id||(a.id="scrollto-ad-"+E),this.adTargetId=a.id,B(this),d?this.maxSteps--:this.maxNonSteps--,E++))))}}function r(a,b,d,e){var c=u;d&&(d=b,e instanceof D&&(d=2*b),c=0-d,b+=d);return a.top>=c&&a.top<=b||a.bottom>=c&&a.bottom<=b||a.top<=c&&a.bottom>=b||e.last&&a.top<=c?!0:!1}function F(a,
b){if(!a.isLoaded&&0!=a.useScrollLoader){var d=a.element.getBoundingClientRect();r(d,b,!0,a)&&a.load()}}function G(a,b){var d=a.adElement.getBoundingClientRect();if(!r(d,b,!1,a))return"fixed"==a.position&&(a.element.style.position="absolute",a.element.style.top="0",a.element.style.bottom="auto",a.position="top"),d.height;b=u+parseInt(a.height);if(d.bottom<b&&!a.last)"bottom"!=a.position&&(a.element.style.position="absolute",a.element.style.top="auto",a.element.style.bottom="0",a.position="bottom");
else if(d.top<=u){"fixed"!=a.position&&(a.element.style.position="fixed",a.isFixed=!0,a.position="fixed");b=u;if(a.last){var e=window.scrollY+u+parseInt(a.height),c=document.documentElement.scrollHeight-H;e>c&&(b-=e-c)}a.element.style.top=b+"px"}else"top"!=a.position&&(a.element.style.position="absolute",a.element.style.top="0",a.element.style.bottom="auto",a.position="top");return d.height}function N(){for(var a=window.innerHeight||document.documentElement.clientHeight,b=[],d=0;d<k.length;d++){var e=
k[d];e.notfixedposition||(b[d]=G(e,a))}a=k;if(WH.shared.isLargeSize&&!(I||40<=J||"complete"!=document.readyState)){J++;d=0;if(e=document.getElementById("sidebar"))d=e.offsetHeight;e=0;var c=document.getElementById("article");c&&(e=c.offsetHeight);if(0<e&&0<d&&d>e){e=parseInt((d-e+10)/3);c=!1;for(d=0;d<a.length;d++){var f=b[d]-e;if(600>f){c=!0;break}a[d].element.style.height=f+"px"}if(1==c){for(d=1;d<a.length;d++)b=a[d].element,b.parentElement.removeChild(b);a.length=1}I=!0}}}function x(){for(var a=
!0,b=window.innerHeight||document.documentElement.clientHeight,d=0;d<k.length;d++){var c=k[d];c.isLoaded||(a=!1,F(c,b))}for(d in v)c=v[d],c.isLoaded||(a=!1,F(c,b));a&&(window.removeEventListener("scroll",w),w=null)}function O(){y.updateVisibility()}var k=[],I=!1,J=0,z={},y,E=0,p,v={},w=null,t=null,K=null,u=WH.shared.TOP_MENU_HEIGHT,H=WH.shared.BOTTOM_MARGIN,q=null,P="0px 0px "+2*(window.innerHeight||document.documentElement.clientHeight)+"px 0px";"IntersectionObserver"in window&&(q=new IntersectionObserver(function(a,
b){a.forEach(function(a){if(a.isIntersecting){var b=a.target,c=v[b.id];c&&c.load();for(var d=0;d<k.length;d+=1)c=k[d],c.element==b&&c.load();q.unobserve(a.target)}})},{rootMargin:P}));WH.isMobile&&(H=314);return{init:function(){var a=(window.innerWidth||document.documentElement.clientWidth)>=WH.largeScreenMinWidth;q||(w=WH.shared.throttle(x,100),window.addEventListener("scroll",w));1==a&&(K=WH.shared.throttle(N,10),window.addEventListener("scroll",K));document.addEventListener("DOMContentLoaded",
function(){x()},!1);WH.shared&&WH.shared.addResizeFunction(x)},addBodyAd:function(a){a=document.getElementById(a);var b=new D(a);var c=null==q?!1:!0;b.disabled||("rightrail"==b.type?(b.last=!0,0<k.length&&(k[k.length-1].last=!1),k.push(b),c&&b.observerLoading&&0==b.instantLoad&&(b.useScrollLoader=!1,q.observe(b.element))):"toc"==b.type?(p=b,b.adElement.style.display="none"):"scrollto"==b.type?(y=new M(a),y.disabled)||(t=WH.shared.throttle(O,100),window.addEventListener("scroll",t)):"quiz"==b.type?
(z[b.adElement.parentElement.id]=b,b.adElement.parentElement.addEventListener("change",function(a){a=this.id;z[a]&&(b.adElement.classList.remove("hidden"),z[a].load())})):(v[b.element.id]=b,c&&b.observerLoading&&0==b.instantLoad&&(b.useScrollLoader=!1,q.observe(b.element))),"dfp"==b.service&&googletag.cmd.push(function(){googletag.display(b.adTargetId)}))},loadTOCAd:function(a){if(p){var b=$(a).next(".section").find(".steps_list_2 > li:first");$(a).hasClass("mw-headline")&&(b=$(a).parents(".section:first").find(".steps_list_2 > li:first"));
b.length&&(b.append($(p.adElement)),p.load(),p.adElement.style.display="block",p=null)}},slotRendered:function(a,b,c){var d;for(c=0;c<k.length;c++){var f=k[c];gptAdSlots[f.adTargetId]==a&&(d=f)}d&&(d.height=d.element.offsetHeight,a=window.innerHeight||document.documentElement.clientHeight,d.extraChild&&b&&300>parseInt(b[1])?d.extraChild.style.visibility="visible":d.extraChild&&(d.extraChild.style.visibility="hidden"),d.notfixedposition||G(d,a),d.refreshable&&d.renderrefresh&&setTimeout(function(){d.refresh()},
d.getRefreshTime()))},impressionViewable:function(a){for(var b,c=0;c<k.length;c++){var e=k[c];gptAdSlots[e.adTargetId]==a&&(b=e)}b&&(b.height=b.element.offsetHeight,b.refreshable&&b.viewablerefresh&&setTimeout(function(){b.refresh()},b.getRefreshTime()))},apsFetchBids:g}}();WH.ads.init();
