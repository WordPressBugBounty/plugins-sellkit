(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

/**
 * Promotion Banner dismiss functionality.
 *
 */
(function ($, settings) {
  'use strict';

  if (!settings || !settings.ajaxUrl) {
    return;
  }

  $(function () {
    var $banners = $('.sellkit-promotion-banner[data-sellkit-promotion-id]');

    if (!$banners.length) {
      return;
    }
    /**
     * Send AJAX request to dismiss a promotion banner.
     *
     * @param {HTMLElement} bannerEl The banner element.
     */


    var sendDismiss = function sendDismiss(bannerEl) {
      var $banner = $(bannerEl);
      var promotionId = $banner.data('sellkit-promotion-id');
      var nonce = $banner.data('sellkit-promotion-nonce');

      if (!promotionId || !nonce) {
        return;
      }

      $.ajax({
        url: settings.ajaxUrl,
        type: 'POST',
        data: {
          action: 'sellkit_dismiss_admin_promotion',
          promotionId: promotionId,
          nonce: nonce
        }
      });
    };

    $banners.each(function () {
      var $banner = $(this);
      var $dismissBtn = $banner.find('.sellkit-promotion-banner__dismiss');

      if (!$dismissBtn.length) {
        return;
      }

      $dismissBtn.on('click', function (event) {
        event.preventDefault();
        $banner.fadeOut(200, function () {
          $banner.remove();
        });
        sendDismiss($banner[0]);
      });
    });
  });
})(window.jQuery, window.sellkitPromotionBanner || {});

},{}]},{},[1]);
