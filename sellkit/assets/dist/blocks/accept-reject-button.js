!function(e){var t={};function r(n){if(t[n])return t[n].exports;var o=t[n]={i:n,l:!1,exports:{}};return e[n].call(o.exports,o,o.exports,r),o.l=!0,o.exports}r.m=e,r.c=t,r.d=function(e,t,n){r.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},r.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},r.t=function(e,t){if(1&t&&(e=r(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(r.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)r.d(n,o,function(t){return e[t]}.bind(null,o));return n},r.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(t,"a",t),t},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r.p="",r(r.s=846)}({0:function(e,t,r){"use strict";e.exports=r(45)},1:function(e,t){e.exports=wp.i18n},100:function(e,t){e.exports=function(e){if(Array.isArray(e))return e}},101:function(e,t){e.exports=function(e,t){if("undefined"!=typeof Symbol&&Symbol.iterator in Object(e)){var r=[],n=!0,o=!1,l=void 0;try{for(var a,c=e[Symbol.iterator]();!(n=(a=c.next()).done)&&(r.push(a.value),!t||r.length!==t);n=!0);}catch(e){o=!0,l=e}finally{try{n||null==c.return||c.return()}finally{if(o)throw l}}return r}}},102:function(e,t){e.exports=function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}},14:function(e,t){e.exports=function(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}},2:function(e,t,r){var n=r(100),o=r(101),l=r(64),a=r(102);e.exports=function(e,t){return n(e)||o(e,t)||l(e,t)||a()}},287:function(e){e.exports=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","name":"sellkit-blocks/accept-reject-button","title":"Accept/Reject Button","category":"sellkit-blocks","textdomain":"sellkit","attributes":{"offerType":{"type":"string","default":"accept"},"title":{"type":"string","default":"Offer Action"},"textAlign":{"type":"string"},"width":{"type":"number"},"shadowColor":{"type":"string","default":""},"shadowHorizontal":{"type":"number","default":0},"shadowVertical":{"type":"number","default":0},"shadowBlur":{"type":"number","default":0},"shadowSpread":{"type":"number","default":0},"shadowPosition":{"type":"string","default":"outset"},"textShadowColor":{"type":"string","default":""},"textShadowBlur":{"type":"number","default":0},"textshadowVertical":{"type":"number","default":0},"textShadowHorizontal":{"type":"number","default":0},"dynamicClassNames":{"type":"string","default":""},"combinedStyle":{"type":"string","default":""}},"supports":{"anchor":true,"align":false,"alignWide":false,"color":{"__experimentalSkipSerialization":true,"gradients":true,"__experimentalDefaultControls":{"background":true,"text":true}},"typography":{"fontSize":true,"lineHeight":true,"__experimentalFontFamily":true,"__experimentalFontWeight":true,"__experimentalFontStyle":true,"__experimentalTextTransform":true,"__experimentalTextDecoration":true,"__experimentalLetterSpacing":true,"__experimentalDefaultControls":{"fontSize":true}},"reusable":false,"shadow":true,"__experimentalBorder":{"color":true,"radius":true,"style":true,"width":true,"__experimentalSkipSerialization":true,"__experimentalDefaultControls":{"color":true,"radius":true,"style":true,"width":true}},"spacing":{"margin":true,"padding":true,"__experimentalDefaultControls":{"margin":false,"padding":false}},"__experimentalSelector":".sellkit-accept-reject-button-block .wp-block-button__link"},"editorStyle":"file:../../../../assets/dist/blocks/accept-reject-button/editor.css","style":["wp-block-sellkit-accept-reject-button sellkit-accept-reject-button-widget","file:../../../../assets/dist/blocks/accept-reject-button/frontend.css"]}')},37:function(e,t){function r(){return e.exports=r=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var r=arguments[t];for(var n in r)Object.prototype.hasOwnProperty.call(r,n)&&(e[n]=r[n])}return e},r.apply(this,arguments)}e.exports=r},43:function(e,t,r){"use strict";
/*
object-assign
(c) Sindre Sorhus
@license MIT
*/var n=Object.getOwnPropertySymbols,o=Object.prototype.hasOwnProperty,l=Object.prototype.propertyIsEnumerable;function a(e){if(null==e)throw new TypeError("Object.assign cannot be called with null or undefined");return Object(e)}e.exports=function(){try{if(!Object.assign)return!1;var e=new String("abc");if(e[5]="de","5"===Object.getOwnPropertyNames(e)[0])return!1;for(var t={},r=0;r<10;r++)t["_"+String.fromCharCode(r)]=r;if("0123456789"!==Object.getOwnPropertyNames(t).map((function(e){return t[e]})).join(""))return!1;var n={};return"abcdefghijklmnopqrst".split("").forEach((function(e){n[e]=e})),"abcdefghijklmnopqrst"===Object.keys(Object.assign({},n)).join("")}catch(e){return!1}}()?Object.assign:function(e,t){for(var r,c,i=a(e),u=1;u<arguments.length;u++){for(var s in r=Object(arguments[u]))o.call(r,s)&&(i[s]=r[s]);if(n){c=n(r);for(var f=0;f<c.length;f++)l.call(r,c[f])&&(i[c[f]]=r[c[f]])}}return i}},45:function(e,t,r){"use strict";
/** @license React v16.13.1
 * react.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */var n=r(43),o="function"==typeof Symbol&&Symbol.for,l=o?Symbol.for("react.element"):60103,a=o?Symbol.for("react.portal"):60106,c=o?Symbol.for("react.fragment"):60107,i=o?Symbol.for("react.strict_mode"):60108,u=o?Symbol.for("react.profiler"):60114,s=o?Symbol.for("react.provider"):60109,f=o?Symbol.for("react.context"):60110,p=o?Symbol.for("react.forward_ref"):60112,b=o?Symbol.for("react.suspense"):60113,d=o?Symbol.for("react.memo"):60115,y=o?Symbol.for("react.lazy"):60116,m="function"==typeof Symbol&&Symbol.iterator;function h(e){for(var t="https://reactjs.org/docs/error-decoder.html?invariant="+e,r=1;r<arguments.length;r++)t+="&args[]="+encodeURIComponent(arguments[r]);return"Minified React error #"+e+"; visit "+t+" for the full message or use the non-minified dev environment for full errors and additional helpful warnings."}var v={isMounted:function(){return!1},enqueueForceUpdate:function(){},enqueueReplaceState:function(){},enqueueSetState:function(){}},O={};function j(e,t,r){this.props=e,this.context=t,this.refs=O,this.updater=r||v}function _(){}function g(e,t,r){this.props=e,this.context=t,this.refs=O,this.updater=r||v}j.prototype.isReactComponent={},j.prototype.setState=function(e,t){if("object"!=typeof e&&"function"!=typeof e&&null!=e)throw Error(h(85));this.updater.enqueueSetState(this,e,t,"setState")},j.prototype.forceUpdate=function(e){this.updater.enqueueForceUpdate(this,e,"forceUpdate")},_.prototype=j.prototype;var w=g.prototype=new _;w.constructor=g,n(w,j.prototype),w.isPureReactComponent=!0;var x={current:null},C=Object.prototype.hasOwnProperty,S={key:!0,ref:!0,__self:!0,__source:!0};function k(e,t,r){var n,o={},a=null,c=null;if(null!=t)for(n in void 0!==t.ref&&(c=t.ref),void 0!==t.key&&(a=""+t.key),t)C.call(t,n)&&!S.hasOwnProperty(n)&&(o[n]=t[n]);var i=arguments.length-2;if(1===i)o.children=r;else if(1<i){for(var u=Array(i),s=0;s<i;s++)u[s]=arguments[s+2];o.children=u}if(e&&e.defaultProps)for(n in i=e.defaultProps)void 0===o[n]&&(o[n]=i[n]);return{$$typeof:l,type:e,key:a,ref:c,props:o,_owner:x.current}}function E(e){return"object"==typeof e&&null!==e&&e.$$typeof===l}var P=/\/+/g,A=[];function $(e,t,r,n){if(A.length){var o=A.pop();return o.result=e,o.keyPrefix=t,o.func=r,o.context=n,o.count=0,o}return{result:e,keyPrefix:t,func:r,context:n,count:0}}function B(e){e.result=null,e.keyPrefix=null,e.func=null,e.context=null,e.count=0,10>A.length&&A.push(e)}function R(e,t,r){return null==e?0:function e(t,r,n,o){var c=typeof t;"undefined"!==c&&"boolean"!==c||(t=null);var i=!1;if(null===t)i=!0;else switch(c){case"string":case"number":i=!0;break;case"object":switch(t.$$typeof){case l:case a:i=!0}}if(i)return n(o,t,""===r?"."+V(t,0):r),1;if(i=0,r=""===r?".":r+":",Array.isArray(t))for(var u=0;u<t.length;u++){var s=r+V(c=t[u],u);i+=e(c,s,n,o)}else if(null===t||"object"!=typeof t?s=null:s="function"==typeof(s=m&&t[m]||t["@@iterator"])?s:null,"function"==typeof s)for(t=s.call(t),u=0;!(c=t.next()).done;)i+=e(c=c.value,s=r+V(c,u++),n,o);else if("object"===c)throw n=""+t,Error(h(31,"[object Object]"===n?"object with keys {"+Object.keys(t).join(", ")+"}":n,""));return i}(e,"",t,r)}function V(e,t){return"object"==typeof e&&null!==e&&null!=e.key?function(e){var t={"=":"=0",":":"=2"};return"$"+(""+e).replace(/[=:]/g,(function(e){return t[e]}))}(e.key):t.toString(36)}function H(e,t){e.func.call(e.context,t,e.count++)}function L(e,t,r){var n=e.result,o=e.keyPrefix;e=e.func.call(e.context,t,e.count++),Array.isArray(e)?T(e,n,r,(function(e){return e})):null!=e&&(E(e)&&(e=function(e,t){return{$$typeof:l,type:e.type,key:t,ref:e.ref,props:e.props,_owner:e._owner}}(e,o+(!e.key||t&&t.key===e.key?"":(""+e.key).replace(P,"$&/")+"/")+r)),n.push(e))}function T(e,t,r,n,o){var l="";null!=r&&(l=(""+r).replace(P,"$&/")+"/"),R(e,L,t=$(t,l,n,o)),B(t)}var N={current:null};function z(){var e=N.current;if(null===e)throw Error(h(321));return e}var M={ReactCurrentDispatcher:N,ReactCurrentBatchConfig:{suspense:null},ReactCurrentOwner:x,IsSomeRendererActing:{current:!1},assign:n};t.Children={map:function(e,t,r){if(null==e)return e;var n=[];return T(e,n,null,t,r),n},forEach:function(e,t,r){if(null==e)return e;R(e,H,t=$(null,null,t,r)),B(t)},count:function(e){return R(e,(function(){return null}),null)},toArray:function(e){var t=[];return T(e,t,null,(function(e){return e})),t},only:function(e){if(!E(e))throw Error(h(143));return e}},t.Component=j,t.Fragment=c,t.Profiler=u,t.PureComponent=g,t.StrictMode=i,t.Suspense=b,t.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED=M,t.cloneElement=function(e,t,r){if(null==e)throw Error(h(267,e));var o=n({},e.props),a=e.key,c=e.ref,i=e._owner;if(null!=t){if(void 0!==t.ref&&(c=t.ref,i=x.current),void 0!==t.key&&(a=""+t.key),e.type&&e.type.defaultProps)var u=e.type.defaultProps;for(s in t)C.call(t,s)&&!S.hasOwnProperty(s)&&(o[s]=void 0===t[s]&&void 0!==u?u[s]:t[s])}var s=arguments.length-2;if(1===s)o.children=r;else if(1<s){u=Array(s);for(var f=0;f<s;f++)u[f]=arguments[f+2];o.children=u}return{$$typeof:l,type:e.type,key:a,ref:c,props:o,_owner:i}},t.createContext=function(e,t){return void 0===t&&(t=null),(e={$$typeof:f,_calculateChangedBits:t,_currentValue:e,_currentValue2:e,_threadCount:0,Provider:null,Consumer:null}).Provider={$$typeof:s,_context:e},e.Consumer=e},t.createElement=k,t.createFactory=function(e){var t=k.bind(null,e);return t.type=e,t},t.createRef=function(){return{current:null}},t.forwardRef=function(e){return{$$typeof:p,render:e}},t.isValidElement=E,t.lazy=function(e){return{$$typeof:y,_ctor:e,_status:-1,_result:null}},t.memo=function(e,t){return{$$typeof:d,type:e,compare:void 0===t?null:t}},t.useCallback=function(e,t){return z().useCallback(e,t)},t.useContext=function(e,t){return z().useContext(e,t)},t.useDebugValue=function(){},t.useEffect=function(e,t){return z().useEffect(e,t)},t.useImperativeHandle=function(e,t,r){return z().useImperativeHandle(e,t,r)},t.useLayoutEffect=function(e,t){return z().useLayoutEffect(e,t)},t.useMemo=function(e,t){return z().useMemo(e,t)},t.useReducer=function(e,t,r){return z().useReducer(e,t,r)},t.useRef=function(e){return z().useRef(e)},t.useState=function(e){return z().useState(e)},t.version="16.13.1"},64:function(e,t,r){var n=r(65);e.exports=function(e,t){if(e){if("string"==typeof e)return n(e,t);var r=Object.prototype.toString.call(e).slice(8,-1);return"Object"===r&&e.constructor&&(r=e.constructor.name),"Map"===r||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?n(e,t):void 0}}},65:function(e,t){e.exports=function(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=new Array(t);r<t;r++)n[r]=e[r];return n}},8:function(e,t,r){var n;
/*!
  Copyright (c) 2017 Jed Watson.
  Licensed under the MIT License (MIT), see
  http://jedwatson.github.io/classnames
*/!function(){"use strict";var r={}.hasOwnProperty;function o(){for(var e=[],t=0;t<arguments.length;t++){var n=arguments[t];if(n){var l=typeof n;if("string"===l||"number"===l)e.push(n);else if(Array.isArray(n)&&n.length){var a=o.apply(null,n);a&&e.push(a)}else if("object"===l)for(var c in n)r.call(n,c)&&n[c]&&e.push(c)}}return e.join(" ")}e.exports?(o.default=o,e.exports=o):void 0===(n=function(){return o}.apply(t,[]))||(e.exports=n)}()},846:function(e,t,r){"use strict";r.r(t);var n=r(0),o=r(287),l=r(37),a=r.n(l),c=r(2),i=r.n(c),u=r(14),s=r.n(u),f=r(8),p=r.n(f),b=r(1);function d(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function y(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?d(Object(r),!0).forEach((function(t){s()(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):d(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}var m=wp.components,h=m.SelectControl,v=m.TextControl,O=m.PanelBody,j=m.Button,_=m.ButtonGroup,g=m.RangeControl,w=wp.blockEditor,x=w.useBlockProps,C=w.InspectorControls,S=w.RichText,k=w.BlockControls,E=w.AlignmentControl,P=w.__experimentalGetElementClassName,A=w.__experimentalUseColorProps,$=w.__experimentalUseBorderProps,B=w.PanelColorSettings,R=wp.element.useEffect;var V=wp.blocks.registerBlockType,H=wp.components,L=H.SVG,T=H.Path,N=o.name,z=o.title;V(N,{icon:{src:Object(n.createElement)(L,{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},Object(n.createElement)(T,{fillRule:"evenodd",clipRule:"evenodd",d:"M20 3.5H4C3.72386 3.5 3.5 3.72386 3.5 4V9C3.5 9.27614 3.72386 9.5 4 9.5H20C20.2761 9.5 20.5 9.27614 20.5 9V4C20.5 3.72386 20.2761 3.5 20 3.5ZM4 2C2.89543 2 2 2.89543 2 4V9C2 10.1046 2.89543 11 4 11H20C21.1046 11 22 10.1046 22 9V4C22 2.89543 21.1046 2 20 2H4ZM20 14.5H4C3.72386 14.5 3.5 14.7239 3.5 15V20C3.5 20.2761 3.72386 20.5 4 20.5H20C20.2761 20.5 20.5 20.2761 20.5 20V15C20.5 14.7239 20.2761 14.5 20 14.5ZM4 13C2.89543 13 2 13.8954 2 15V20C2 21.1046 2.89543 22 4 22H20C21.1046 22 22 21.1046 22 20V15C22 13.8954 21.1046 13 20 13H4ZM14.5606 5.49827C14.8358 5.18868 14.8079 4.71462 14.4983 4.43944C14.1887 4.16425 13.7146 4.19213 13.4395 4.50172L11.3333 6.87109L10.5606 6.00172C10.2854 5.69213 9.81132 5.66425 9.50174 5.93944C9.19215 6.21462 9.16426 6.68868 9.43945 6.99827L10.7728 8.49827C10.9151 8.65838 11.1191 8.74999 11.3333 8.74999C11.5476 8.74999 11.7516 8.65838 11.8939 8.49827L14.5606 5.49827ZM13.5303 15.4697C13.8232 15.7626 13.8232 16.2374 13.5303 16.5303L12.5607 17.5L13.5303 18.4697C13.8232 18.7626 13.8232 19.2374 13.5303 19.5303C13.2374 19.8232 12.7626 19.8232 12.4697 19.5303L11.5 18.5607L10.5303 19.5303C10.2374 19.8232 9.76256 19.8232 9.46967 19.5303C9.17678 19.2374 9.17678 18.7626 9.46967 18.4697L10.4393 17.5L9.46967 16.5303C9.17678 16.2374 9.17678 15.7626 9.46967 15.4697C9.76256 15.1768 10.2374 15.1768 10.5303 15.4697L11.5 16.4393L12.4697 15.4697C12.7626 15.1768 13.2374 15.1768 13.5303 15.4697Z",fill:"#000"}))},title:z,edit:function(e){var t=e.attributes,r=e.setAttributes,o=e.className,l=t.offerType,c=t.title,u=t.textAlign,f=t.width,d=t.shadowColor,m=t.shadowBlur,w=t.shadowSpread,V=t.shadowHorizontal,H=t.shadowVertical,L=t.shadowPosition,T=t.textShadowColor,N=t.textShadowBlur,z=t.textshadowVertical,M=t.textShadowHorizontal,D=function(e){return function(t){r(s()({},e,t))}},I=A(t),F=$(t),U={boxShadow:"".concat("inset"===L?"inset ":"").concat(V,"px ").concat(H,"px ").concat(m,"px ").concat(w,"px ").concat(d)},Z={textShadow:"".concat(M,"px ").concat(z,"px ").concat(N,"px ").concat(T)},q=p()(o,"wp-block-button__link",I.className,F.className,s()({},"has-text-align-".concat(u),u),P("button"));R((function(){var e,t;e=y(y(y(y({},F.style),I.style),U),Z),t=Object.entries(e).map((function(e){var t=i()(e,2),r=t[0],n=t[1],o=r.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g,"$1-$2").toLowerCase();return"".concat(o,": ").concat(n,";")})).join(" "),r({combinedStyle:t})}),[F.style,I.style,U,Z]),R((function(){r({dynamicClassNames:q})}),[q,r]);var W=x({className:q});return Object(n.createElement)(n.Fragment,null,Object(n.createElement)(C,{group:"settings"},Object(n.createElement)(O,{title:Object(b.__)("Content","sellkit"),initialOpen:!0},Object(n.createElement)(h,{label:Object(b.__)("Offer Type","sellkit"),value:l,options:[{label:Object(b.__)("Accept Offer","sellkit"),value:"accept"},{label:Object(b.__)("Reject Offer","sellkit"),value:"reject"}],onChange:function(e){r({offerType:e})}}),Object(n.createElement)(v,{label:Object(b.__)("Title","sellkit"),value:c,onChange:function(e){return r({title:e})}})),Object(n.createElement)(O,{title:Object(b.__)("Button Width","sellkit"),initialOpen:!1},Object(n.createElement)((function(e){var t=e.selectedWidth,r=e.ButtonSetAttributes;return Object(n.createElement)(_,{"aria-label":Object(b.__)("Button Width","sellkit")},[25,50,75,100].map((function(e){return Object(n.createElement)(j,{key:e,size:"small",variant:e===t?"primary":void 0,onClick:function(){var n;r({width:t===(n=e)?void 0:n})}},e,"%")})))}),{selectedWidth:f,ButtonSetAttributes:r}))),Object(n.createElement)(C,{group:"styles"},Object(n.createElement)(O,{title:Object(b.__)("Box Shadow","sellkit"),initialOpen:!1},Object(n.createElement)("div",{className:"sellkit-box-shadow-color"},Object(n.createElement)(B,{title:"",colorSettings:[{value:d,onChange:D("shadowColor"),label:Object(b.__)("Color","sellkit")}]})),Object(n.createElement)(g,{label:Object(b.__)("Horizontal","sellkit"),value:V,onChange:function(e){return r({shadowHorizontal:e})},min:-100,max:100}),Object(n.createElement)(g,{label:Object(b.__)("Vertical","sellkit"),value:H,onChange:function(e){return r({shadowVertical:e})},min:-100,max:100}),Object(n.createElement)(g,{label:Object(b.__)("Blur","sellkit"),value:m,onChange:function(e){return r({shadowBlur:e})},min:0,max:100}),Object(n.createElement)(g,{label:Object(b.__)("Spread","sellkit"),value:w,onChange:function(e){return r({shadowSpread:e})},min:-100,max:100}),Object(n.createElement)(h,{label:Object(b.__)("Shadow Position","sellkit"),value:L,options:[{label:Object(b.__)("Outset","sellkit"),value:"outset"},{label:Object(b.__)("Inset","sellkit"),value:"inset"}],onChange:function(e){return r({shadowPosition:e})}})),Object(n.createElement)(O,{title:Object(b.__)("Text Shadow","sellkit"),initialOpen:!1},Object(n.createElement)("div",{className:"sellkit-text-shadow-color"},Object(n.createElement)(B,{title:"",colorSettings:[{value:T,onChange:D("textShadowColor"),label:Object(b.__)("Color","sellkit")}]}),Object(n.createElement)(g,{label:Object(b.__)("Blur","sellkit"),value:N,onChange:function(e){return r({textShadowBlur:e})},min:0,max:100}),Object(n.createElement)(g,{label:Object(b.__)("Horizontal","sellkit"),value:M,onChange:function(e){return r({textShadowHorizontal:e})},min:-100,max:100}),Object(n.createElement)(g,{label:Object(b.__)("Vertical","sellkit"),value:z,onChange:function(e){return r({textshadowVertical:e})},min:-100,max:100})))),Object(n.createElement)(k,null,Object(n.createElement)(E,{value:u,onChange:function(e){r({textAlign:e})}})),Object(n.createElement)("div",{className:p()(o,s()({},"sellkit-accept-reject-button-block has-custom-width wp-block-button__width-".concat(f),f))},Object(n.createElement)(S,a()({},W,{tagName:"a",onChange:function(e){r({title:e})},style:y(y(y(y({},F.style),I.style),U),Z),value:c,placeholder:Object(b.__)("Offer Action","sellkit"),identifier:"text"}))))}})}});