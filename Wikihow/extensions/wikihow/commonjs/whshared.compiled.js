WH.mediumScreenMinWidth=728;WH.largeScreenMinWidth=975;
WH.shared=function(){function y(a){q.push(a)}function r(a,b){var c=null,e=0,d=function(){e=0;c=null;a.apply()};return function(){var g=E(),f=b-(g-e);0>=f||f>b?(c&&(clearTimeout(c),c=null),e=g,a.apply()):c||(c=setTimeout(d,f))}}function n(){var a=!1,b=window.innerHeight||document.documentElement.clientHeight,c;for(c in f){var e=f[c];if(0!=e.useScrollLoader&&!e.isLoaded){a=e;var d=a.element.getBoundingClientRect(),g={top:d.top,bottom:d.bottom};0==a.lastTop-d.top&&window.scrollY!=a.lastY&&(g.top-=window.scrollY,
g.bottom-=window.scrollY);a.lastTop=d.top;a.lastY=window.scrollY;a=g;d=b;g=52;var h=2*d;g-=h;d+=h;(0==a.top&&0==a.bottom?0:a.top>=g&&a.top<=d||a.bottom>=g&&a.bottom<=d||a.top<=g&&a.bottom>=d)&&e.load();a=!0}}!a&&f.length&&(window.removeEventListener("scroll",k),k=null)}function t(a){if(a&&a.element)if(document.addEventListener&&a.finishedLoadingEvent){a.alt&&(a.element.alt=a.alt);a.loaderRemoved=!1;var b=document.createElement("div");b.className="loader";for(var c=0;3>c;c++){var e=document.createElement("div");
e.className="loader-dot";b.appendChild(e)}var d=document.createElement("div");d.className="loading-container";d.appendChild(b);a.element.parentElement.appendChild(d);a.element.addEventListener(a.finishedLoadingEvent,function(){a.loadedCallback&&a.loadedCallback();0==a.loaderRemoved&&(this.parentElement.removeChild(d),a.loaderRemoved=!0,"undefined"!==typeof a.element.classList&&a.element.classList.remove("img-loading-hide"))})}else"undefined"!==typeof a.element.classList&&a.element.classList.remove("img-loading-hide")}
function u(a){this.lastTop=a.getBoundingClientRect().top;this.lastY=window.scrollY;this.isVisible=this.isLoaded=!1;this.element=a;this.load=function(){}}function v(a){if(!a)return"";if(!w)return a;var b=a.split(".").pop();"jpg"!==b&&"png"!==b||!a.match(/images(_[a-z]{2})?\/thumb\//)||a.match(/(\.[a-zA-Z]+){2}$/)||(a+=".webp");return a}function F(a){u.call(this,a);this.src=a.getAttribute("data-src");this.load=function(){this.element.setAttribute("src",this.src);this.isLoaded=!0}}function G(a){u.call(this,
a);this.alt=a.alt;a.alt="";this.finishedLoadingEvent="load";this.src=a.getAttribute("data-src");if(x){var b=a.getAttribute("data-srclarge");null!=b&&(this.src=b)}this.src=v(this.src);a&&void 0!==a.classList&&a.classList.add("img-loading-hide");this.load=function(){this.element.setAttribute("src",this.src);this.isLoaded=!0;t(this)}}function H(a){u.call(this,a);this.finishedLoadingEvent="loadeddata";this.isPlaying=!1;this.src="https://www.wikihow.com/video"+this.element.getAttribute("data-src");this.poster=
this.element.getAttribute("data-poster");(this.poster=v(this.poster))&&"jpg"==this.poster.split(".").pop()&&w&&(this.poster+=".webp");this.noAutoplay=this.element.getAttribute("data-noautoplay");this.play=function(){this.element.play();this.isPlaying=!0};this.pause=function(){this.element.pause();this.isPlaying=!1};this.load=function(){this.element.setAttribute("poster",this.poster);!z||this.noAutoplay||!x&&!0!==window.wgIsMainPage?this.finishedLoadingEvent=null:(this.element.setAttribute("src",this.src),
this.play());this.isLoaded=!0;this.noAutoplay||t(this)}}function A(a){if(a=document.getElementById(a)){var b=null==l?!1:!0;if("img"===a.nodeName.toLowerCase()){var c=new G(a);if(b)c.useScrollLoader=!1,l.observe(c.element);else if(1==p){c.useScrollLoader=!1;var e=c;e.element.classList.remove("img-loading-hide");e.element.setAttribute("loading","lazy");e.load()}}else if("video"===a.nodeName.toLowerCase())c=new H(a),b&&(c.useScrollLoader=!1,l.observe(c.element));else if("iframe"===a.nodeName.toLowerCase())c=
new F(a),b&&(c.useScrollLoader=!1,l.observe(c.element));else return;c&&(f[c.element.id]=c);e=a.getAttribute("data-width")||a.getAttribute("width");var d=a.getAttribute("data-height")||a.getAttribute("height");0<e&&(a.parentElement.style.paddingTop=d/e*100+"%");b||n();B?c.load():k||b||(k=r(n,500),window.addEventListener("scroll",k))}}var q=[],f={},B=!1,E=Date.now||function(){return(new Date).getTime()},h=navigator.userAgent,w=h.match(/Linux/)&&h.match(/Android/)||h.match(/Opera/)||h.match(/Chrome/)&&
!h.match(/Edge/),m=window.innerWidth||document.documentElement.clientWidth;h=m<WH.mediumScreenMinWidth;m=!h&&m<WH.largeScreenMinWidth;var C=!h&&!m,x=C,p=!1,D="0px 0px 100% 0px",l=null;"loading"in HTMLImageElement.prototype&&(p=!0);1==WH.jsFastRender&&(p=!1,D="0px 0px 0px 0px");0==p&&"IntersectionObserver"in window&&(l=new IntersectionObserver(function(a,b){a.forEach(function(a){a.isIntersecting&&(f[a.target.id].load(),l.unobserve(a.target))})},{rootMargin:D}));window.onresize=function(){q.forEach&&
q.forEach(function(a){a()})};y(n);var z=function(){var a=window.document.createElement("video");a.setAttribute("muted","");a.setAttribute("playsinline","");a.setAttribute("webkit-playsinline","");a.muted=!0;a.playsinline=!0;a.webkitPlaysinline=!0;a.setAttribute("height","0");a.setAttribute("width","0");a.style.position="fixed";a.style.top=0;a.style.width=0;a.style.height=0;a.style.opacity=0;try{var b=a.play();b&&b.catch&&b.then(function(){}).catch(function(){})}catch(c){}return!a.paused}();var k=
r(n,500);window.addEventListener?window.addEventListener("scroll",k):B=!0;return{isDesktopSize:x,isSmallSize:h,isMedSize:m,isLargeSize:C,getScreenSize:function(){var a=document;var b=a.documentElement,c=a.body,e=c&&c.clientWidth,d=0;!b||!b.clientWidth||"CSS1Compat"!==a.compatMode&&e?e&&(d=c.clientWidth):d=b.clientWidth;a=d;return 0==a||a>=WH.largeScreenMinWidth?"large":a>=WH.mediumScreenMinWidth?"medium":"small"},throttle:r,TOP_MENU_HEIGHT:52,autoPlayVideo:z,webpSupport:w,addScrollLoadItem:A,addScrollLoadItemByElement:function(a){var b=
a.id;b||(b="id-"+Math.random().toString(36).substr(2,16));a.id=b;A(b)},videoRoot:"https://www.wikihow.com/video",setupLoader:t,addResizeFunction:y,loadAllImages:function(){for(var a in f){var b=f[a];b.isLoaded||b.load()}},loadAllEmbed:function(){for(var a in f){var b=f[a];b.isLoaded||"iframe"==b.element.nodeName.toLowerCase()&&b.load()}},addLoadedCallback:function(a,b){f[a].loadedCallback=b},showVideoPlay:function(a){a=a.element.parentElement.getElementsByClassName("m-video-intro-over");"undefined"!==
typeof a&&(a=a[0]);a&&(a.style.visibility="visible")},getCompressedImageSrc:v}}();
