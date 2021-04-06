!function(t,e){"function"==typeof define&&define.amd?define(["jquery"],e):"undefined"!=typeof exports?e(require("jquery")):(e(t.jquery),t.bootstrapSwitch={})}(this,(function(t){"use strict";function e(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function i(t,e){return[t.state?"on":"off",t.size,t.disabled?"disabled":void 0,t.readonly?"readonly":void 0,t.indeterminate?"indeterminate":void 0,t.inverse?"inverse":void 0,e?"id-"+e:void 0].filter((function(t){return null==t}))}function o(){return{state:this.$element.is(":checked"),size:this.$element.data("size"),animate:this.$element.data("animate"),disabled:this.$element.is(":disabled"),readonly:this.$element.is("[readonly]"),indeterminate:this.$element.data("indeterminate"),inverse:this.$element.data("inverse"),radioAllOff:this.$element.data("radio-all-off"),onColor:this.$element.data("on-color"),offColor:this.$element.data("off-color"),onText:this.$element.data("on-text"),offText:this.$element.data("off-text"),labelText:this.$element.data("label-text"),handleWidth:this.$element.data("handle-width"),labelWidth:this.$element.data("label-width"),baseClass:this.$element.data("base-class"),wrapperClass:this.$element.data("wrapper-class")}}function n(){var t=this,e=this.$on.add(this.$off).add(this.$label).css("width",""),i="auto"===this.options.handleWidth?Math.round(Math.max(this.$on.width(),this.$off.width())):this.options.handleWidth;return e.width(i),this.$label.width((function(e,o){return"auto"===t.options.labelWidth?o<i?i:o:t.options.labelWidth})),this.privateHandleWidth=this.$on.outerWidth(),this.privateLabelWidth=this.$label.outerWidth(),this.$container.width(2*this.privateHandleWidth+this.privateLabelWidth),this.$wrapper.width(this.privateHandleWidth+this.privateLabelWidth)}function s(){var t=this,e=0<arguments.length&&void 0!==arguments[0]?arguments[0]:this.ope;this.$container.css("margin-left",(function(){var i=[0,"-"+t.privateHandleWidth+"px"];return t.options.indeterminate?"-"+t.privateHandleWidth/2+"px":e?t.options.inverse?i[1]:i[0]:t.options.inverse?i[0]:i[1]}))}function a(t){return this.options.baseClass+"-"+t}function r(){var t=this,e=function(){t.setPrevOptions(),n.call(t),s.call(t),setTimeout((function(){return t.options.animate&&t.$wrapper.addClass(a.call(t,"animate"))}),50)};if(this.$wrapper.is(":visible"))e();else var i=window.setInterval((function(){return t.$wrapper.is(":visible")&&(e()||!0)&&window.clearInterval(i)}),50)}function l(){var t=this;return this.$element.on({"setPreviousOptions.bootstrapSwitch":function(){return t.setPrevOptions()},"previousState.bootstrapSwitch":function(){t.options=t.prevOptions,t.options.indeterminate&&t.$wrapper.addClass(a.call(t,"indeterminate")),t.$element.prop("checked",t.options.state).trigger("change.bootstrapSwitch",!0)},"change.bootstrapSwitch":function(e,i){e.preventDefault(),e.stopImmediatePropagation();var o=t.$element.is(":checked");s.call(t,o),o===t.options.state||(t.options.state=o,t.$wrapper.toggleClass(a.call(t,"off")).toggleClass(a.call(t,"on")),!i&&(t.$element.is(":radio")&&w('[name="'+t.$element.attr("name")+'"]').not(t.$element).prop("checked",!1).trigger("change.bootstrapSwitch",!0),t.$element.trigger("switchChange.bootstrapSwitch",[o])))},"focus.bootstrapSwitch":function(e){e.preventDefault(),t.$wrapper.addClass(a.call(t,"focused"))},"blur.bootstrapSwitch":function(e){e.preventDefault(),t.$wrapper.removeClass(a.call(t,"focused"))},"keydown.bootstrapSwitch":function(e){!e.which||t.options.disabled||t.options.readonly||(37===e.which||39===e.which)&&(e.preventDefault(),e.stopImmediatePropagation(),t.state(39===e.which))}})}function h(){var t=this;return this.$on.on("click.bootstrapSwitch",(function(e){return e.preventDefault(),e.stopPropagation(),t.state(!1),t.$element.trigger("focus.bootstrapSwitch")})),this.$off.on("click.bootstrapSwitch",(function(e){return e.preventDefault(),e.stopPropagation(),t.state(!0),t.$element.trigger("focus.bootstrapSwitch")}))}function p(){var t=this,e=void 0,i=void 0;this.$label.on({click:function(t){t.stopPropagation()},"mousedown.bootstrapSwitch touchstart.bootstrapSwitch":function(i){e||t.options.disabled||t.options.readonly||(i.preventDefault(),i.stopPropagation(),e=(i.pageX||i.originalEvent.touches[0].pageX)-parseInt(t.$container.css("margin-left"),10),t.options.animate&&t.$wrapper.removeClass(a.call(t,"animate")),t.$element.trigger("focus.bootstrapSwitch"))},"mousemove.bootstrapSwitch touchmove.bootstrapSwitch":function(o){if(null!=e){var n=(o.pageX||o.originalEvent.touches[0].pageX)-e;o.preventDefault(),n<-t.privateHandleWidth||0<n||(i=n,t.$container.css("margin-left",i+"px"))}},"mouseup.bootstrapSwitch touchend.bootstrapSwitch":function(o){if(e){if(o.preventDefault(),t.options.animate&&t.$wrapper.addClass(a.call(t,"animate")),i){var n=i>-t.privateHandleWidth/2;i=!1,t.state(t.options.inverse?!n:n)}else t.state(!t.options.state);e=!1}},"mouseleave.bootstrapSwitch":function(){t.$label.trigger("mouseup.bootstrapSwitch")}})}function c(){var t=this,e=this.$element.closest("label");e.on("click",(function(i){i.preventDefault(),i.stopImmediatePropagation(),i.target===e[0]&&t.toggleState()}))}function d(){function t(){return w(this).data("bootstrap-switch")}function e(){return w(this).bootstrapSwitch("state",this.checked)}var i=this.$element.closest("form");i.data("bootstrap-switch")||i.on("reset.bootstrapSwitch",(function(){window.setTimeout((function(){i.find("input").filter(t).each(e)}),1)})).data("bootstrap-switch",!0)}function u(t){var e=this;return w.isArray(t)?t.map((function(t){return a.call(e,t)})):[a.call(this,t)]}var f,m=(f=t)&&f.__esModule?f:{default:f},v=Object.assign||function(t){for(var e,i=1;i<arguments.length;i++)for(var o in e=arguments[i])Object.prototype.hasOwnProperty.call(e,o)&&(t[o]=e[o]);return t},$=function(){function t(t,e){for(var i,o=0;o<e.length;o++)(i=e[o]).enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}return function(e,i,o){return i&&t(e.prototype,i),o&&t(e,o),e}}(),w=m.default||window.jQuery||window.$,b=function(){function t(n){var s=this,f=1<arguments.length&&void 0!==arguments[1]?arguments[1]:{};e(this,t),this.$element=w(n),this.options=w.extend({},w.fn.bootstrapSwitch.defaults,o.call(this),f),this.prevOptions={},this.$wrapper=w("<div>",{class:function(){return i(s.options,s.$element.attr("id")).map((function(t){return a.call(s,t)})).concat([s.options.baseClass],u.call(s,s.options.wrapperClass)).join(" ")}}),this.$container=w("<div>",{class:a.call(this,"container")}),this.$on=w("<span>",{html:this.options.onText,class:a.call(this,"handle-on")+" "+a.call(this,this.options.onColor)}),this.$off=w("<span>",{html:this.options.offText,class:a.call(this,"handle-off")+" "+a.call(this,this.options.offColor)}),this.$label=w("<span>",{html:this.options.labelText,class:a.call(this,"label")}),this.$element.on("init.bootstrapSwitch",(function(){return s.options.onInit(n)})),this.$element.on("switchChange.bootstrapSwitch",(function(){for(var t=arguments.length,e=Array(t),i=0;i<t;i++)e[i]=arguments[i];var o=s.options.onSwitchChange.apply(n,e);!1===o&&(s.$element.is(":radio")?w('[name="'+s.$element.attr("name")+'"]').trigger("previousState.bootstrapSwitch",!0):s.$element.trigger("previousState.bootstrapSwitch",!0))})),this.$container=this.$element.wrap(this.$container).parent(),this.$wrapper=this.$container.wrap(this.$wrapper).parent(),this.$element.before(this.options.inverse?this.$off:this.$on).before(this.$label).before(this.options.inverse?this.$on:this.$off),this.options.indeterminate&&this.$element.prop("indeterminate",!0),r.call(this),l.call(this),h.call(this),p.call(this),d.call(this),c.call(this),this.$element.trigger("init.bootstrapSwitch",this.options.state)}return $(t,[{key:"setPrevOptions",value:function(){this.prevOptions=v({},this.options)}},{key:"state",value:function(t,e){return void 0===t?this.options.state:(this.options.disabled||this.options.readonly||this.options.state&&!this.options.radioAllOff&&this.$element.is(":radio")||(this.$element.is(":radio")?w('[name="'+this.$element.attr("name")+'"]').trigger("setPreviousOptions.bootstrapSwitch"):this.$element.trigger("setPreviousOptions.bootstrapSwitch"),this.options.indeterminate&&this.indeterminate(!1),this.$element.prop("checked",!!t).trigger("change.bootstrapSwitch",e)),this.$element)}},{key:"toggleState",value:function(t){return this.options.disabled||this.options.readonly?this.$element:this.options.indeterminate?(this.indeterminate(!1),this.state(!0)):this.$element.prop("checked",!this.options.state).trigger("change.bootstrapSwitch",t)}},{key:"size",value:function(t){return void 0===t?this.options.size:(null!=this.options.size&&this.$wrapper.removeClass(a.call(this,this.options.size)),t&&this.$wrapper.addClass(a.call(this,t)),n.call(this),s.call(this),this.options.size=t,this.$element)}},{key:"animate",value:function(t){return void 0===t?this.options.animate:this.options.animate===!!t?this.$element:this.toggleAnimate()}},{key:"toggleAnimate",value:function(){return this.options.animate=!this.options.animate,this.$wrapper.toggleClass(a.call(this,"animate")),this.$element}},{key:"disabled",value:function(t){return void 0===t?this.options.disabled:this.options.disabled===!!t?this.$element:this.toggleDisabled()}},{key:"toggleDisabled",value:function(){return this.options.disabled=!this.options.disabled,this.$element.prop("disabled",this.options.disabled),this.$wrapper.toggleClass(a.call(this,"disabled")),this.$element}},{key:"readonly",value:function(t){return void 0===t?this.options.readonly:this.options.readonly===!!t?this.$element:this.toggleReadonly()}},{key:"toggleReadonly",value:function(){return this.options.readonly=!this.options.readonly,this.$element.prop("readonly",this.options.readonly),this.$wrapper.toggleClass(a.call(this,"readonly")),this.$element}},{key:"indeterminate",value:function(t){return void 0===t?this.options.indeterminate:this.options.indeterminate===!!t?this.$element:this.toggleIndeterminate()}},{key:"toggleIndeterminate",value:function(){return this.options.indeterminate=!this.options.indeterminate,this.$element.prop("indeterminate",this.options.indeterminate),this.$wrapper.toggleClass(a.call(this,"indeterminate")),s.call(this),this.$element}},{key:"inverse",value:function(t){return void 0===t?this.options.inverse:this.options.inverse===!!t?this.$element:this.toggleInverse()}},{key:"toggleInverse",value:function(){this.$wrapper.toggleClass(a.call(this,"inverse"));var t=this.$on.clone(!0),e=this.$off.clone(!0);return this.$on.replaceWith(e),this.$off.replaceWith(t),this.$on=e,this.$off=t,this.options.inverse=!this.options.inverse,this.$element}},{key:"onColor",value:function(t){return void 0===t?this.options.onColor:(this.options.onColor&&this.$on.removeClass(a.call(this,this.options.onColor)),this.$on.addClass(a.call(this,t)),this.options.onColor=t,this.$element)}},{key:"offColor",value:function(t){return void 0===t?this.options.offColor:(this.options.offColor&&this.$off.removeClass(a.call(this,this.options.offColor)),this.$off.addClass(a.call(this,t)),this.options.offColor=t,this.$element)}},{key:"onText",value:function(t){return void 0===t?this.options.onText:(this.$on.html(t),n.call(this),s.call(this),this.options.onText=t,this.$element)}},{key:"offText",value:function(t){return void 0===t?this.options.offText:(this.$off.html(t),n.call(this),s.call(this),this.options.offText=t,this.$element)}},{key:"labelText",value:function(t){return void 0===t?this.options.labelText:(this.$label.html(t),n.call(this),this.options.labelText=t,this.$element)}},{key:"handleWidth",value:function(t){return void 0===t?this.options.handleWidth:(this.options.handleWidth=t,n.call(this),s.call(this),this.$element)}},{key:"labelWidth",value:function(t){return void 0===t?this.options.labelWidth:(this.options.labelWidth=t,n.call(this),s.call(this),this.$element)}},{key:"baseClass",value:function(){return this.options.baseClass}},{key:"wrapperClass",value:function(t){if(void 0===t)return this.options.wrapperClass;var e=t||w.fn.bootstrapSwitch.defaults.wrapperClass;return this.$wrapper.removeClass(u.call(this,this.options.wrapperClass).join(" ")),this.$wrapper.addClass(u.call(this,e).join(" ")),this.options.wrapperClass=e,this.$element}},{key:"radioAllOff",value:function(t){if(void 0===t)return this.options.radioAllOff;var e=!!t;return this.options.radioAllOff===e||(this.options.radioAllOff=e),this.$element}},{key:"onInit",value:function(t){return void 0===t?this.options.onInit:(this.options.onInit=t||w.fn.bootstrapSwitch.defaults.onInit,this.$element)}},{key:"onSwitchChange",value:function(t){return void 0===t?this.options.onSwitchChange:(this.options.onSwitchChange=t||w.fn.bootstrapSwitch.defaults.onSwitchChange,this.$element)}},{key:"destroy",value:function(){var t=this.$element.closest("form");return t.length&&t.off("reset.bootstrapSwitch").removeData("bootstrap-switch"),this.$container.children().not(this.$element).remove(),this.$element.unwrap().unwrap().off(".bootstrapSwitch").removeData("bootstrap-switch"),this.$element}}]),t}();w.fn.bootstrapSwitch=function(t){for(var e=arguments.length,i=Array(1<e?e-1:0),o=1;o<e;o++)i[o-1]=arguments[o];return Array.prototype.reduce.call(this,(function(e,o){var n=w(o),s=n.data("bootstrap-switch"),a=s||new b(o,t);return s||n.data("bootstrap-switch",a),"string"==typeof t?a[t].apply(a,i):e}),this)},w.fn.bootstrapSwitch.Constructor=b,w.fn.bootstrapSwitch.defaults={state:!0,size:null,animate:!0,disabled:!1,readonly:!1,indeterminate:!1,inverse:!1,radioAllOff:!1,onColor:"primary",offColor:"default",onText:"ON",offText:"OFF",labelText:"&nbsp",handleWidth:"auto",labelWidth:"auto",baseClass:"bootstrap-switch",wrapperClass:"wrapper",onInit:function(){},onSwitchChange:function(){}}}));
