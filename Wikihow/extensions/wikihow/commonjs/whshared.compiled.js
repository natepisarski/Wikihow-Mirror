WH.shared=function(){function v(a){n.push(a)}function p(a,b){var c=null,e=0,d=function(){e=0;c=null;a.apply()};return function(){var f=z(),g=b-(f-e);0>=g||g>b?(c&&(clearTimeout(c),c=null),e=f,a.apply()):c||(c=setTimeout(d,g))}}function m(){for(var a=!1,b=window.innerHeight||document.documentElement.clientHeight,c=0;c<g.length;c+=1){var e=g[c];if(0!=e.useScrollLoader&&!e.isLoaded){a=e;var d=a.element.getBoundingClientRect(),f={top:d.top,bottom:d.bottom};0==a.lastTop-d.top&&window.scrollY!=a.lastY&&
(f.top-=window.scrollY,f.bottom-=window.scrollY);a.lastTop=d.top;a.lastY=window.scrollY;a=f;d=b;f=52;var h=2*d;f-=h;d+=h;(0==a.top&&0==a.bottom?0:a.top>=f&&a.top<=d||a.bottom>=f&&a.bottom<=d||a.top<=f&&a.bottom>=d)&&e.load();a=!0}}!a&&g.length&&(window.removeEventListener("scroll",k),k=null)}function q(a){if(a&&a.element)if(document.addEventListener&&a.finishedLoadingEvent){a.alt&&(a.element.alt=a.alt);a.loaderRemoved=!1;var b=document.createElement("div");b.className="loader";for(var c=0;3>c;c++){var e=
document.createElement("div");e.className="loader-dot";b.appendChild(e)}var d=document.createElement("div");d.className="loading-container";d.appendChild(b);a.element.parentElement.appendChild(d);a.element.addEventListener(a.finishedLoadingEvent,function(){a.loadedCallback&&a.loadedCallback();0==a.loaderRemoved&&(this.parentElement.removeChild(d),a.loaderRemoved=!0,"undefined"!==typeof a.element.classList&&a.element.classList.remove("img-loading-hide"))})}else"undefined"!==typeof a.element.classList&&
a.element.classList.remove("img-loading-hide")}function r(a){this.lastTop=a.getBoundingClientRect().top;this.lastY=window.scrollY;this.isVisible=this.isLoaded=!1;this.element=a;this.load=function(){}}function t(a){if(!a)return"";if(!u)return a;var b=a.split(".").pop();"jpg"!==b&&"png"!==b||!a.match(/images(_[a-z]{2})?\/thumb\//)||a.match(/(\.[a-zA-Z]+){2}$/)||(a+=".webp");return a}function A(a){r.call(this,a);this.src=a.getAttribute("data-src");this.load=function(){this.element.setAttribute("src",
this.src);this.isLoaded=!0}}function B(a){r.call(this,a);this.alt=a.alt;a.alt="";this.finishedLoadingEvent="load";this.src=a.getAttribute("data-src");if(window.WH.sizer&&!window.WH.sizer.isPhone()){var b=a.getAttribute("data-srclarge");null!=b&&(this.src=b)}this.src=t(this.src);a&&void 0!==a.classList&&a.classList.add("img-loading-hide");this.load=function(){this.element.setAttribute("src",this.src);this.isLoaded=!0;q(this)}}function C(a){r.call(this,a);this.finishedLoadingEvent="loadeddata";this.isPlaying=
!1;this.src="https://www.wikihow.com/video"+this.element.getAttribute("data-src");this.poster=this.element.getAttribute("data-poster");(this.poster=t(this.poster))&&"jpg"==this.poster.split(".").pop()&&u&&(this.poster+=".webp");this.noAutoplay=this.element.getAttribute("data-noautoplay");this.play=function(){this.element.play();this.isPlaying=!0};this.pause=function(){this.element.pause();this.isPlaying=!1};this.load=function(){this.element.setAttribute("poster",this.poster);!w||this.noAutoplay||
0!=window.WH.isMobile&&!0!==window.wgIsMainPage?this.finishedLoadingEvent=null:(this.element.setAttribute("src",this.src),this.play());this.isLoaded=!0;this.noAutoplay||q(this)}}function x(a){if(a=document.getElementById(a)){var b=null==h?!1:!0;if("img"===a.nodeName.toLowerCase()){var c=new B(a);b&&(c.useScrollLoader=!1,h.observe(c.element))}else if("video"===a.nodeName.toLowerCase())c=new C(a),b&&(c.useScrollLoader=!1,h.observe(c.element));else if("iframe"===a.nodeName.toLowerCase())c=new A(a),b&&
(c.useScrollLoader=!1,h.observe(c.element));else return;c&&g.push(c);b=a.getAttribute("data-width")||a.getAttribute("width");var e=a.getAttribute("data-height")||a.getAttribute("height");0<b&&(a.parentElement.style.paddingTop=e/b*100+"%");m();y?c.load():k||(k=p(m,500),window.addEventListener("scroll",k))}}var n=[],g=[],y=!1,z=Date.now||function(){return(new Date).getTime()},l=navigator.userAgent,u=l.match(/Linux/)&&l.match(/Android/)||l.match(/Opera/)||l.match(/Chrome/)&&!l.match(/Edge/),h=null;"IntersectionObserver"in
window&&(h=new IntersectionObserver(function(a,b){a.forEach(function(a){if(a.isIntersecting){for(var b=a.target,c=0;c<g.length;c+=1){var f=g[c];f.element==b&&f.load()}h.unobserve(a.target)}})},{rootMargin:"0px 0px 100% 0px"}));window.onresize=function(){n.forEach&&n.forEach(function(a){a()})};v(m);var w=function(){var a=window.document.createElement("video");a.setAttribute("muted","");a.setAttribute("playsinline","");a.setAttribute("webkit-playsinline","");a.muted=!0;a.playsinline=!0;a.webkitPlaysinline=
!0;a.setAttribute("height","0");a.setAttribute("width","0");a.style.position="fixed";a.style.top=0;a.style.width=0;a.style.height=0;a.style.opacity=0;try{var b=a.play();b&&b.catch&&b.then(function(){}).catch(function(){})}catch(c){}return!a.paused}();var k=p(m,500);window.addEventListener?window.addEventListener("scroll",k):y=!0;return{throttle:p,TOP_MENU_HEIGHT:52,autoPlayVideo:w,webpSupport:u,addScrollLoadItem:x,addScrollLoadItemByElement:function(a){var b=a.id;b||(b="id-"+Math.random().toString(36).substr(2,
16));a.id=b;x(b)},videoRoot:"https://www.wikihow.com/video",setupLoader:q,addResizeFunction:v,loadAllImages:function(){for(var a=0;a<g.length;a++){var b=g[a];b.isLoaded||b.load()}},loadAllEmbed:function(){for(var a=0;a<g.length;a++){var b=g[a];b.isLoaded||"iframe"==b.element.nodeName.toLowerCase()&&b.load()}},addLoadedCallback:function(a,b){for(var c=0;c<g.length;c++){var e=g[c];e.element.id==a&&(e.loadedCallback=b)}},showVideoPlay:function(a){a=a.element.parentElement.getElementsByClassName("m-video-intro-over");
"undefined"!==typeof a&&(a=a[0]);a&&(a.style.visibility="visible")},getCompressedImageSrc:t}}();
