!function(e){var t={};function i(l){if(t[l])return t[l].exports;var n=t[l]={i:l,l:!1,exports:{}};return e[l].call(n.exports,n,n.exports,i),n.l=!0,n.exports}i.m=e,i.c=t,i.d=function(e,t,l){i.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:l})},i.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},i.t=function(e,t){if(1&t&&(e=i(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var l=Object.create(null);if(i.r(l),Object.defineProperty(l,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var n in e)i.d(l,n,function(t){return e[t]}.bind(null,n));return l},i.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return i.d(t,"a",t),t},i.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},i.p="",i(i.s=819)}({1:function(e,t){e.exports=wp.i18n},819:function(e,t,i){"use strict";i.r(t);var l,n,o=i(1);l=jQuery,n=JSON.parse(window.wc_country_select_params.countries),window.checkout=new function(){function e(e,t){if(e.attr("multiple"))return e.addClass("filled"),e.removeClass("empty"),void e.parents(".sellkit-widget-checkout-fields").find(".free-label").css("display","flex");if(_.isEmpty(e.val())?(e.addClass("empty"),e.removeClass("filled"),e.parents(".sellkit-widget-checkout-fields").find(".free-label").css("display","none")):(e.addClass("filled"),e.removeClass("empty"),e.parents(".sellkit-widget-checkout-fields").find(".free-label").css("display","flex")),"hidden"===e.attr("type")&&e.parents(".sellkit-widget-checkout-fields").addClass("sellkit-hide-completely"),"change"===t){if("billing_country"===e.attr("id")){var i=document.getElementById("sellkit-billing_state");if(!_.isNull(i)&&"SELECT"===i.nodeName&&l("#sellkit-billing_state option").length<1){var c=l("#sellkit-billing_state"),a=c.parent().parent().parent();a.removeClass("sellkit-checkout-field-select");var u=c.attr("placeholder"),d=document.createElement("input");d.setAttribute("type","text"),d.setAttribute("id","sellkit-billing_state"),d.setAttribute("name","billing_state"),d.setAttribute("placeholder",u),c.remove(),a.find(".woocommerce-input-wrapper").append(d)}var r=e.val(),p=n[r];if(!_.isNull(i)&&"INPUT"===i.nodeName&&!_.isEmpty(p)){var k=l("#sellkit-billing_state"),h=k.attr("placeholder"),f=k.parent().parent().parent();f.addClass("sellkit-checkout-field-select"),(_.isUndefined(h)||_.isEmpty(h))&&(h=Object(o.__)("State","sellkit")),k.remove();var m=document.createElement("select");for(var g in m.setAttribute("name","billing_state"),m.setAttribute("id","sellkit-billing_state"),m.setAttribute("placeholder",h),m.addEventListener("change",(function(){s()})),p){var v=document.createElement("option");v.value=g,v.innerHTML=p[g],m.appendChild(v)}f.find(".woocommerce-input-wrapper").append(m)}}if("shipping_country"===e.attr("id")){var y=document.getElementById("sellkit-shipping_state");if(!_.isNull(y)&&"SELECT"===y.nodeName&&l("#sellkit-shipping_state option").length<1){var b=l("#sellkit-shipping_state"),w=b.parent().parent().parent();w.removeClass("sellkit-checkout-field-select");var F=b.attr("placeholder"),x=document.createElement("input");x.setAttribute("type","text"),x.setAttribute("id","sellkit-shipping_state"),x.setAttribute("name","shipping_state"),x.setAttribute("placeholder",F),b.remove(),w.find(".woocommerce-input-wrapper").append(x)}var C=e.val(),j=n[C];if(!_.isNull(y)&&"INPUT"===y.nodeName&&!_.isEmpty(j)){var E=l("#sellkit-shipping_state"),D=E.attr("placeholder"),A=E.parent().parent().parent();A.addClass("sellkit-checkout-field-select"),(_.isUndefined(D)||_.isEmpty(D))&&(D=Object(o.__)("State","sellkit")),E.remove();var q=document.createElement("select");for(var S in q.setAttribute("name","shipping_state"),q.setAttribute("id","sellkit-shipping_state"),q.setAttribute("placeholder",D),q.addEventListener("change",(function(){s()})),j){var N=document.createElement("option");N.value=S,N.innerHTML=j[S],q.appendChild(N)}A.find(".woocommerce-input-wrapper").append(q)}}}}function t(){l(".sellkit-one-page-checkout-product-qty").off("change").on("change",(function(){var e,t;l(this).attr("readonly",!0),wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"change_cart_item_qty",qty:l(this).val(),id:l(this).attr("data-id"),mode:"edit",related_checkout:l("#sellkit_current_page_id").val(),nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).always((function(){l(document.body).trigger("update_checkout"),l(".sellkit-one-page-checkout-product-qty").attr("readonly",!1)}))})),l(".sellkit-one-page-checkout-payment-methods").find("input[name=payment_method]:checked").parent().parent().next().show(),l(".sellkit-one-page-pay-method").on("click",(function(){l(".sellkit_payment_box").hide(),l(this).parent().parent().next().show()})),l(".sellkit-checkout-widget-order-summary-tfoot").children().length<=3&&(l(".cart-subtotal td").css("padding-bottom","8px"),l(".cart-subtotal th").css("padding-bottom","8px"))}function i(){l(".sellkit-apply-coupon").off("click").on("click",(function(){var e,t;wp.ajax.post({beforeSend:function(){l(".sellkit-apply-coupon").css("opacity",.5)},action:"sellkit_block_checkout_ajax_handler",sub_action:"apply_coupon",code:jQuery(".sellkit-custom-coupon-form").find(".sellkit-coupon").val(),nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).done((function(){l(document.body).trigger("update_checkout"),l(".sellkit-apply-coupon").css("opacity",1)})).fail((function(){l(".sellkit-apply-coupon").css("opacity",1)}))}))}function c(){l(".sellkit-coupon-toggle").length&&l(".sellkit-custom-coupon-form").css("display","none"),l(".sellkit-coupon-toggle").off("click").on("click",(function(){var e="row",t=l(".sellkit-custom-coupon-form").css("display"),i="";l(window).width()<600&&(e="column"),i="none"===t?"inline-flex":"none",l(".sellkit-custom-coupon-form").css({display:i,flexDirection:e})}))}function s(){var e,t;wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"set_customer_details_ajax",country:document.querySelector("#billing_country")?document.getElementById("billing_country").value:"",state:document.querySelector("#sellkit-billing_state")?document.getElementById("sellkit-billing_state").value:"",shipping_country:document.querySelector("#shipping_country")?document.getElementById("shipping_country").value:"",shipping_state:document.querySelector("#sellkit-shipping_state")?document.getElementById("sellkit-shipping_state").value:"",nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).always((function(){l(document.body).trigger("update_checkout")}))}function a(){l(".sellkit-checkout-single-bundle-item-quantity").on("change",(function(){var e,t,i=l(this).attr("data-id"),n=l(this).val();!1!==l(this).parent().parent().find(".sellkit-checkout-bundle-item").is(":checked")&&wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"sellkit_checkout_modify_cart_by_bundle_products",key:i,qty:n,nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).always((function(){l(document.body).trigger("update_checkout")}))})),l(".sellkit-checkout-bundle-item").off("change").on("change",(function(){var e,t,i=l(this).attr("type");l(".sellkit-checkout-bump-order-products").each((function(){l(this).is(":checked")&&"radio"===i&&l(this).trigger("click")}));var n=l(this).val(),o=l(this).parent().parent().find(".sellkit-checkout-single-bundle-item-quantity").val(),c=l("#sellkit_current_page_id").val(),s="add";!1===l(this).is(":checked")&&(s="remove"),wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"sellkit_checkout_modify_cart_by_bundle_products",id:n,qty:o,checkout_id:c,type:i,modify:s,nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).always((function(){l(document.body).trigger("update_checkout")}))}))}function u(){l(".wc_payment_methods hr.sellkit-checkout-widget-divider").each((function(e,t){l(t).css("display",l(t).prevAll("li").first().css("display"))}))}function d(){l("form.checkout").on("checkout_place_order",(function(){return!(l("#sellkit_funnel_has_upsell").length>0&&"upsell"===l("#sellkit_funnel_has_upsell").val()&&(i=function(){var e,t,i=(null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.url.assets)+"img/spinner.png",l=document.createElement("img");return l.setAttribute("src",i),l.setAttribute("width","35px"),l.setAttribute("class","sellkit-upsell-downsell-preloader"),l},n=function(e){setTimeout((function(){"thankyou"===e.next_type&&(l("body").css("overflow","auto"),l(".sellkit_funnel_upsell_popup").css("display","none"),l("#sellkit_funnel_has_upsell").val("done"),l("#place_order").trigger("click")),"upsell"!==e.next_type&&"downsell"!==e.next_type||(function(e){l("body").css("overflow","hidden"),l(".sellkit-upsell-popup").css("display","none"),l("#sellkit_funnel_popup_step_id").val(e.next_id),l(".sellkit_funnel_upsell_popup").css({"z-index":"100",display:"none"}),l(".sellkit_funnel_upsell_popup_"+e.next_id).css({"z-index":101,display:"block"})}(e),o())}),1e3)},o=function(){l(".sellkit_funnel_upsell_popup .sellkit-upsell-accept-button").off().on("click",(function(){var e,t,i=l(this).parents(".sellkit_funnel_upsell_popup"),o=i.find(".identify").val(),c=l("#sellkit_current_page_id").val();l(".sellkit-upsell-popup").css("display","flex"),i.find(".sellkit-upsell-updating").addClass("active"),wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"perform_upsell_accept_button",upsell_id:o,checkout_id:c,nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).done((function(e){l(document.body).trigger("update_checkout"),i.find(".sellkit-upsell-updating").removeClass("active"),i.find(".sellkit-upsell-accepted").addClass("active"),n(e)})).fail((function(e){console.error(e)}))})),l(".sellkit_funnel_upsell_popup .sellkit-upsell-reject-button").off().on("click",(function(){var e,t,o=l(this).parents(".sellkit_funnel_upsell_popup"),c=o.find(".identify").val(),s=i();o.find(".sellkit-upsell-downsell-preloader").remove(),l(s).insertAfter(o.find(".sellkit-accept-reject-button-widget .sellkit-upsell-reject-button")),wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"perform_upsell_reject_button",upsell_id:c,nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).done((function(e){n(e)})).fail((function(e){console.error(e)}))}))},c=l("#sellkit_current_page_id").val(),s=i(),l("button[type=submit]").find("img").remove(),l("button[type=submit]").append(s),wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"call_funnel_popups",step:c,nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).done((function(e){n(e),l("button[type=submit]").find("img").remove()})).fail((function(e){console.error(e),l("button[type=submit]").find("img").remove()})),1));var e,t,i,n,o,c,s}))}this.init=function(){l("body").addClass("contains-sellkit-checkout");var r,p,k,h,f,m,g,v,y,b,w,F,x,C,j,E,D,A,q=l('[name="billing-method"].sellkit-billing-method-a');q.length>0&&q[0].checked&&q[0].click(),function(){var e=l("#billing_email"),t=l(".jupiter-checkout-widget-email-search"),i=l(".sellkit-checkout-widget-email-error"),n=l(".sellkit-checkout-widget-email-empty"),o=l(".sellkit-checkout-widget-password-field"),c=o.find("#register_pass"),s=l(".sellkit-checkout-widget-username-field"),a=s.find("input"),u=l(".login-wrapper"),d=l(".create-desc"),r=l("#createaccount");e.on("keyup",(function(){var e=this;setTimeout((function(){var c,a,r=l(e).val();if(_.isEmpty(r))return n.show().css("display","inline-block"),i.hide(),d.css("display","none"),o.addClass("login_hidden_section"),s.addClass("login_hidden_section"),u.addClass("login_hidden_section"),void t.hide();var h=function(e){return/^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i.test(e)}(r);if(l("#createaccount").prop("checked",!1),!1===h)return i.show().css("display","inline-block"),n.hide(),d.css("display","none"),o.addClass("login_hidden_section"),s.addClass("login_hidden_section"),u.addClass("login_hidden_section"),void t.hide();n.hide(),i.hide(),t.show().css("display","inline-block"),wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"search_for_email",email:r,dataType:"json",nonce:null===(c=window)||void 0===c||null===(a=c.sellkit_block_checkout)||void 0===a?void 0:a.nonce}).done((function(){p()})).fail((function(){k()}))}),500)})),a.on("keyup",(function(){var e=this;setTimeout((function(){var t,i,n=l(e).val();wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"search_for_username",user:n,dataType:"json",nonce:null===(t=window)||void 0===t||null===(i=t.sellkit_block_checkout)||void 0===i?void 0:i.nonce}).done((function(){l(".sellkit-checkout-widget-username-error").hide()})).fail((function(){l(".sellkit-checkout-widget-username-error").show()}))}),500)})),r.on("click",(function(){l(this).is(":checked")?(c.length>0&&o.removeClass("login_hidden_section"),a.length>0&&s.removeClass("login_hidden_section")):(s.addClass("login_hidden_section"),o.addClass("login_hidden_section"))}));var p=function(){d.css("display","none"),o.removeClass("login_hidden_section"),s.addClass("login_hidden_section"),u.removeClass("login_hidden_section"),t.hide()},k=function(){d.css("display","flex"),o.addClass("login_hidden_section"),s.addClass("login_hidden_section"),u.addClass("login_hidden_section"),t.hide()}}(),r=l(".login-submit"),p=l(".login-mail"),k=l(".login-pass"),h=l(".login-result"),r.on("click",(function(){var e,t;""!==p.val()&&""!==k.val()?wp.ajax.post({beforeSend:function(){l(".login-submit").css("opacity","0.5")},action:"sellkit_block_checkout_ajax_handler",sub_action:"auth_user",email:p.val(),pass:k.val(),nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).done((function(){location.reload()})).fail((function(e){l(".login-submit").css("opacity","1"),l(".login-result").html(e).css({color:"red"})})):h.text(Object(o.__)("Both Field required.","sellkit")).css({color:"red"})})),f=l(".sellkit-one-page-checkout-billing"),m=f.find(".method-a"),g=f.find(".method-b"),v=l("#shipping_first_name"),y=l("#shipping_last_name"),b=l("#shipping_address_1"),w=l("#shipping_address_2"),F=l("#shipping_country"),x=l("#sellkit-shipping_state"),C=l("#shipping_postcode"),j=l("#shipping_city"),m.on("click",(function(){f.find(".woocommerce-billing-fields__field-wrapper").hide(),l("#billing_first_name").val(v.val()),l("#billing_last_name").val(y.val()),l("#billing_address_1").val(b.val()),l("#billing_address_2").val(w.val()),l("#billing_country").val(F.val()).trigger("change"),l("#sellkit-billing_state").val(x.val()).trigger("change"),l("#billing_postcode").val(C.val()),l("#billing_city").val(j.val())})),g.on("click",(function(){l(".inner_wrapper").css("height","auto"),f.find(".woocommerce-billing-fields__field-wrapper").css("display","inline-table")})),function(){l(window).width()<600&&l("#order_review").addClass("sellkit-mobile-multistep-order-summary");var e=l(".summary_toggle > .title, .summary_toggle > .sellkit-checkout-summary-toggle-up, .summary_toggle > .icon"),t=l(".summary_toggle");e.on("click",(function(){l("#order_review").toggle(),"Hide order summary"===t.find(".title").text()?(t.find(".title").text(Object(o.__)("Show order summary","sellkit")),t.find(".sellkit-checkout-summary-toggle-up").hide(),t.find(".sellkit-checkout-summary-toggle-down").show(),t.css("border-bottom-width","0px")):(t.find(".title").text(Object(o.__)("Hide order summary","sellkit")),t.find(".sellkit-checkout-summary-toggle-up").show(),t.find(".sellkit-checkout-summary-toggle-down").hide(),t.css("border-bottom-width","1px")),l("#sellkit-checkout-multistep-inner-wrap").css("height","auto")}))}(),E=l(".sellkit-checkout-widget-breadcrumb-mobile, .sellkit-checkout-widget-breadcrumb-desktop"),D=l(".sellkit-multistep-checkout-first"),E.find(".information").on("click",(function(){l(".go-to-first").click()})),E.find(".shipping").on("click",(function(){"none"===D.css("display")?l(".go-to-second").click():l(".go-to-shipping").click()})),E.find(".payment").on("click",(function(){l(".information").removeClass("current").addClass("inactive, blue-line")})),l((function(){var e=l(".sellkit-one-page-checkout-shipping .sellkit-widget-checkout-fields");e.each((function(t){l(e[t+1]).length?l(e[t+1]).offset().top>l(e[t]).offset().top&&l(e[t]).addClass("sellkit-checkout-excluded-wrapper-fields"):l(e[t]).addClass("sellkit-checkout-excluded-wrapper-fields")}))})),l((function(){l(".sellkit-widget-checkout-fields").find("input, select, textarea").each((function(){"SELECT"===this.nodeName&&l(this).parent().parent().parent().addClass("sellkit-checkout-field-select"),e(l(this),"load")}))})),l(document).on("mousemove change",(function(t){l(".sellkit-widget-checkout-fields").find("input, select, textarea").each((function(){"SELECT"===this.nodeName&&l(this).parent().parent().parent().addClass("sellkit-checkout-field-select"),e(l(this),t.type)}))})),l(".sellkit-checkout-local-fields").find("input, select, hidden, textarea, #sellkit-billing_state ,#sellkit-shipping_state, .validate-email").each((function(){var t=this,i=l(this).parent().parent().parent().find(".free-label"),o=l(this).val();_.isEmpty(o)||(l(this).addClass("filled").removeClass("empty"),i.css({display:"flex"})),l(this).on("change input focusout",(function(i){if(l(t).val()||l(t).find("option").length?(l(t).addClass("filled"),l(t).removeClass("empty"),l(t).parents(".sellkit-widget-checkout-fields").find(".free-label").css("display","flex")):(l(t).addClass("empty"),l(t).removeClass("filled"),l(t).parents(".sellkit-widget-checkout-fields").find(".free-label").hide()),"focusout"===i.type){e(l(t),"focusout");var o=l(t).parent().parent().parent();if(o.hasClass("validate-required")){if("INPUT"===t.nodeName&&"checkbox"===l(t).attr("type")&&!t.checked)return void o.find(".sellkit-required-validation").css("display","inline-flex");if(_.isEmpty(l(t).val()))return void o.find(".sellkit-required-validation").css("display","inline-flex");o.find(".sellkit-required-validation").css("display","none")}}if("change"===i.type&&("billing_country"===l(t).attr("id")||"shipping_country"===l(t).attr("id"))){var c=l(t).val(),s=n[c],a="sellkit-shipping_state";"billing_country"===l(t).attr("id")&&(a="sellkit-billing_state");var u=document.getElementById(a);if(_.isNull(u))return;for(var d in l(u).empty(),s){var r=document.createElement("option");r.value=d,r.innerHTML=s[d],u.appendChild(r)}}}))})),jQuery(document).ajaxComplete((function(){t(),i(),c(),u()})),u(),t(),i(),c(),A="",document.querySelector("#shipping_country")&&(A=l("#shipping_country").val(),l("#shipping_country").val(l("#shipping_country option:eq(1)").val()),l("#shipping_country").val(A).trigger("change")),document.querySelector("#billing_country")&&(A=l("#billing_country").val(),l("#billing_country").val(l("#billing_country option:eq(1)").val()),l("#billing_country").val(A).trigger("change")),l("#shipping_country, #billing_country, #sellkit-shipping_state, #sellkit-billing_state").on("change",(function(){s()})),a(),l(".sellkit-checkout-bump-order-products").on("click",(function(){var e,t,i;i=l(this).is(":checked")?"add":"remove",wp.ajax.post({action:"sellkit_block_checkout_ajax_handler",sub_action:"change_cart_item_qty",qty:l(this).attr("data-qty"),id:l(this).val(),mode:i,related_checkout:l("#sellkit_current_page_id").val(),nonce:null===(e=window)||void 0===e||null===(t=e.sellkit_block_checkout)||void 0===t?void 0:t.nonce}).always((function(){l(document.body).trigger("update_checkout")}))})),l("#place_order").on("click",(function(){var e=0,t=setInterval((function(){if(e+=1,l(".wc_payment_method > .woocommerce-NoticeGroup-checkout").length>0){var i=l(".wc_payment_method").find(".woocommerce-NoticeGroup-checkout").html();l(".wc_payment_method").find(".woocommerce-NoticeGroup-checkout").remove(),l(".woocommerce-notices-wrapper").first().append(i),l(document.body).animate({scrollTop:l(".woocommerce-notices-wrapper").offset().top-100},500),clearInterval(t)}e>20&&clearInterval(t)}),500)})),d(),window.sellkitCheckoutMakeSureJsWorks=function(){t(),i(),c(),u()},l("#sellkit-add-notes-to-order-box").on("change",(function(){l(this).parent().next().toggle()}))},this.init()}}});