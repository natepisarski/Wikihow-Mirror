(function () {
  'use strict';

  window.defer = {
    debug: [],
    imgs: [],
    loadTimes: [],
    showConsole: false,
    allIn: false,
    // how frequently it checks the dom
    dur: 200,
    // how long it waits for the window to load
    domWait: 2000,
    domTimer: null,
    int: 0,
    loading: 0,
    loaded: 0,
    active: false,
    callback: null,

    listen: function () {
      this.active = true;
      this.addEvent(window, 'load', this.winLoaded);
      this.run();
      this.int = setInterval(defer.run, this.dur);
    },

    addCallback: function (callback) {
      this.callback = callback;
    },

    winLoaded: function () {
      defer.allIn = true;
    },

    run: function () {
      if (defer.allIn) {
        defer.removeLoaded();
      }

      if (defer.allIn && defer.imgs.length === 0) {
        defer.destroy();
        return;
      }
      // check for items in view
      defer.checkAll();
      // if none loading, load some background items
      if (defer.loading === 0) {
        defer.bg.check();
      }
    },

    add: function (el) {
      el.defer = {
        src: el.getAttribute('data-src'),
        index: this.imgs.length,
        loading: false,
        loaded: false
      };
      if (el.defer.src && el.defer.src.match(/images(_[a-z]{2})?\/thumb\//) && !el.defer.src.match(/(\.[a-zA-Z]+){2}$/) && this.supportsWebp()) {
        el.defer.src = el.defer.src + '.webp';
      }

      this.imgs.push(el);

      if (this.domTimer) {
        clearTimeout(this.domTimer);
      }

      this.domTimer = setTimeout(defer.winLoaded, this.domWait);

      if (!this.browserSupports()) {
        this.loadImg(el);
      } else if (!this.active) {
        this.listen();
      }
    },

    imgLoaded: function (event) {
      // this is the scope of event
      var el = defer.getTarget(event),
        now = new Date().getTime();

      if (el) {
        el.defer.loaded = true;
        defer.trace('#' + el.defer.index + ' loaded');
        defer.removeEvent(el, 'load', defer.imgLoaded);
        defer.loadTimes.push(now - el.defer.loadStart);

        if (defer.callback) {
          defer.callback(now);
        }
      }

      defer.loaded += 1;
      defer.loading -= 1;
    },

    imgError: function (event) {
      var img = defer.getTarget(event);
      if (img && img.defer) {
        img.defer.loaded = true;
      }
      defer.loading -= 1;
    },

    loadImg: function (el) {
      this.loading += 1;
      this.trace('#' + el.defer.index + ' requested');

      this.addEvent(el, 'load', this.imgLoaded);
      this.addEvent(el, 'error', this.imgError);

      el.src = el.defer.src;
      el.removeAttribute('style');
      el.defer.loading = true;
      el.defer.loadStart = new Date().getTime();
    },

    removeLoaded: function () {
      var i, tmp = [];
      for (i = 0; i < this.imgs.length; i += 1) {
        if (this.imgs[i].defer.loaded) {
          delete this.imgs[i].defer;
        } else {
          tmp.push(this.imgs[i]);
        }
      }
      this.imgs = tmp;
    },

    checkAll: function () {
      var i, el;
      for (i = 0; i < this.imgs.length; i += 1) {
        el = this.imgs[i];
        if (this.inView(el) && !el.defer.loading) {
          this.loadImg(el);
        }
      }
    },

    inView: function (el) {
      var box = el.getBoundingClientRect(),
        bottom = (window.innerHeight || document.documentElement.clientHeight);
      return (box.bottom >= 0 && box.top <= bottom);
    },

    destroy: function () {
      this.trace('shutdown');
      clearInterval(this.int);
      this.active = false;
      this.loading = 0;
      this.allIn = false;
      this.imgs = [];
      defer.bg.avg = 0;
      defer.bg.batchSize = 10;
      defer.bg.found = 0;
      // delete defer.bg;
      this.debug = [];
    },

    // utilities
    trace: function (obj, type) {
      if (window.console === undefined || !this.showConsole) {
        this.debug.push(obj);
        return;
      }

      type = type || 'log';
      console[type](obj);
    },

    supportsWebp: function() {
      if (typeof this.webp == 'undefined') {
        var nv = navigator.userAgent,
          webp = nv.match(/Linux/) && nv.match(/Android/) || // android browser
            nv.match(/Opera/) || // opera and opera mini
            nv.match(/Chrome/) && !nv.match(/Edge/);
        this.webp = !!webp;
      }
      return this.webp;
    },

    browserSupports: function () {
      // explicit check for IE <= 7. browser doesn't work and check doesn't work for IE < 8
      if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) {
        var iever = parseInt(RegExp.$1, 10);
        if (iever <= 7) {
          return false;
        }
      }
      var el = document.body;
      return el.getBoundingClientRect &&
        (window.innerHeight || document.documentElement.clientHeight) &&
        (el.attachEvent || el.addEventListener);
    },

    // shims, mostly for IE
    getTarget: function (event) {
      event = event || window.event;
      var target;
      if (event) {
        target = event.target || event.srcElement;
      }
      return target;
    },

    addEvent: function (el, name, callback) {
      if (el.addEventListener) { // Modern
        el.addEventListener(name, callback, false);
      } else if (el.attachEvent) { // Internet Explorer
        el.attachEvent('on' + name, callback);
      }
    },

    removeEvent: function (el, name, callback) {
      if (el.removeEventListener) { // Modern
        el.removeEventListener(name, callback);
      } else if (el.attachEvent) { // Internet Explorer
        el.detachEvent('on' + name, callback);
      }
    }

  };
}());

(function () {
  'use strict';

  defer.bg = {
    enabled: true,
    batchSize: 10,
    avg: 0,
    found: 0,

    speeds: [
      {
        lbl: 'Modem',
        speed: 4000,
        batch: 1
      },
      {
        lbl: 'EDGE',
        speed: 1000,
        batch: 3
      },
      {
        lbl: 'DSL',
        speed: 100,
        batch: 5
      },
      {
        lbl: 'Cable',
        speed: 50,
        batch: 10
      }
    ],

    check: function () {
      if (!this.enabled) {
        return;
      }
      this.findSpeed();
      this.findImgs();
    },

    findImgs: function () {
      var i = 0, el;

      while (i < defer.imgs.length) {
        el = defer.imgs[i];

        if (!el.defer.loading) {
          this.found += 1;
          defer.trace('bg found #' + el.defer.index + ' | ' + this.found + ' of ' + this.batchSize + ' limit');
          defer.loadImg(el);
        }

        if (this.batchFull()) {
          this.found = 0;
          break;
        }
        i += 1;
      }
    },

    batchFull: function () {
      return this.batchSize <= this.found;
    },

    findSpeed: function () {
      if (defer.loadTimes.length === 0) {
        this.batchSize = this.speeds[0].batch;
        return;
      }

      var sum = 0,
        x,
        len = defer.loadTimes.length,
        div = len,
        i,
        group;

      for (x = 0; x < len; x += 1) {
        if (!isNaN(defer.loadTimes[x]) && defer.loadTimes[x] > 0) {
          sum += parseFloat(defer.loadTimes[x], null);
        } else {
          div -= 1;
        }
      }

      this.avg = div > 0 ? (sum / div).toPrecision(5) : 0;
      defer.trace(this.avg + ' avg DL time');

      for (i = 0; i < this.speeds.length; i += 1) {
        group = this.speeds[i];
        if (this.avg > group.speed) {
          //defer.trace(group.lbl + ' speed determined');
          this.batchSize = group.batch;
          break;
        }
      }
    }
  };
}());
