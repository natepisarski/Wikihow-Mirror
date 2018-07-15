
window.WH = window.WH || {};
window.WH.Utils = window.WH.Utils || {};

window.WH.Utils.PushLoad = (function () {
  "use strict";

  function PushLoad ($container) {
    this.$container = $container;
    this.$container.addClass('slide-container');
  }

  PushLoad.prototype = {
    addSlide: function (html) {
      $(window).resize($.proxy(this, 'updateHeight'));
      this.$prevSlide = this.$currentSlide || null;

      this.$currentSlide = $('<div class="slide"></div>').html(html);
      this.$container.append(this.$currentSlide);

      setTimeout($.proxy(this, 'transIn'), 100);
    },

    transIn: function () {
        if (this.$prevSlide) {
          this.$prevSlide.removeClass('trans-in').addClass('trans-out');
        }

        setTimeout($.proxy(this, 'cleanup'), 1000);
        this.$currentSlide.addClass('trans-in');
        this.updateHeight();
    },

    updateHeight: function () {
        this.$container.outerHeight(this.$currentSlide.outerHeight());
    },

    updateHeightDelayed: function (delay) {
        delay = delay || 0;
        setTimeout(function () {
            $(window).trigger('resize');
        }, delay);
    },

    cleanup: function () {
      this.$container.find('.slide.trans-out').first().remove();
    }
  };

  return PushLoad;
}());
