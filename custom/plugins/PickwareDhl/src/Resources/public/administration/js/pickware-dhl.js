!function(e){var t={};function n(r){if(t[r])return t[r].exports;var i=t[r]={i:r,l:!1,exports:{}};return e[r].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(r,i,function(t){return e[t]}.bind(null,i));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=1)}([function(e){e.exports=JSON.parse('{"customerNumber":"2222222222","bookedProducts":[{"product":{"code":"V01PAK"},"participations":["04"],"returnParticipations":["01"]},{"product":{"code":"V62WP"},"participations":["01"],"returnParticipations":[]},{"product":{"code":"V53WPAK"},"participations":["01"],"returnParticipations":[]},{"product":{"code":"V54EPAK"},"participations":["01"],"returnParticipations":[]},{"product":{"code":"V55PAK"},"participations":["01"],"returnParticipations":[]}]}')},function(e,t,n){"use strict";n.r(t);var r={};n.r(r),n.d(r,"template",(function(){return T})),n.d(r,"default",(function(){return H}));var i={};n.r(i),n.d(i,"template",(function(){return M})),n.d(i,"default",(function(){return A}));var o=Shopware.Classes.ApiService,a=Shopware.Application,c=Shopware.Component,s=(Shopware.Context,Shopware.Defaults,Shopware.Data.Criteria,Shopware.Data.Entity,Shopware.Data.EntityCollection,Shopware.Data.EntityHydrator,Shopware.Data.Repository,Shopware.Data.ChangesetGenerator,Shopware.Locale),l=Shopware.Mixin,u=Shopware.Module;Shopware.Service,Shopware.State,Shopware.Utils;function f(e){var t=e.default;t.template=e.template,t.extendsFrom?c.extend(t.name,t.extendsFrom,t):t.overrideFrom?c.override(t.overrideFrom,t):c.register(t.name,t)}function p(e){return(p="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function h(e,t,n,r,i,o,a){try{var c=e[o](a),s=c.value}catch(e){return void n(e)}c.done?t(s):Promise.resolve(s).then(r,i)}function d(e){return function(){var t=this,n=arguments;return new Promise((function(r,i){var o=e.apply(t,n);function a(e){h(o,r,i,a,c,"next",e)}function c(e){h(o,r,i,a,c,"throw",e)}a(void 0)}))}}function g(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function m(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function v(e,t){return(v=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function w(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(e){return!1}}();return function(){var n,r=D(e);if(t){var i=D(this).constructor;n=Reflect.construct(r,arguments,i)}else n=r.apply(this,arguments);return b(this,n)}}function b(e,t){return!t||"object"!==p(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function D(e){return(D=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}var y=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&v(e,t)}(c,e);var t,n,r,i,o,a=w(c);function c(e,t){var n,r=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"pickware.dhl.shipping.label";return g(this,c),(n=a.call(this,e,t,r)).name="pickwareDhlConfigApiService",n}return t=c,(n=[{key:"checkDhlBcpCredentials",value:(o=d(regeneratorRuntime.mark((function e(t){var n,r,i,o;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return n=t.username,r=t.password,i=Object.assign(this.getBasicHeaders()),e.next=5,this.httpClient.post("/_action/pickware-dhl/check-dhl-bcp-credentials",{username:n,password:r},{headers:i});case 5:return o=e.sent,e.abrupt("return",o.data);case 7:case"end":return e.stop()}}),e,this)}))),function(e){return o.apply(this,arguments)})},{key:"fetchDhlContractData",value:(i=d(regeneratorRuntime.mark((function e(t){var n,r,i,o;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return n=t.username,r=t.password,i=Object.assign(this.getBasicHeaders()),e.next=5,this.httpClient.post("/_action/pickware-dhl/fetch-dhl-contract-data",{username:n,password:r},{headers:i});case 5:return o=e.sent,e.abrupt("return",o.data);case 7:case"end":return e.stop()}}),e,this)}))),function(e){return i.apply(this,arguments)})}])&&m(t.prototype,n),r&&m(t,r),c}(o);function C(e,t,n,r,i,o,a,c){var s,l="function"==typeof e?e.options:e;if(t&&(l.render=t,l.staticRenderFns=n,l._compiled=!0),r&&(l.functional=!0),o&&(l._scopeId="data-v-"+o),a?(s=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),i&&i.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(a)},l._ssrRegister=s):i&&(s=c?function(){i.call(this,(l.functional?this.parent:this).$root.$options.shadowRoot)}:i),s)if(l.functional){l._injectStyles=s;var u=l.render;l.render=function(e,t){return s.call(t),u(e,t)}}else{var f=l.beforeCreate;l.beforeCreate=f?[].concat(f,s):[s]}return{exports:e,options:l}}var S=function(e){const t={"de-DE":{"pw-dhl-config-module":{"pickware-dhl":"Versandetiketten DHL"}},"en-GB":{"pw-dhl-config-module":{"pickware-dhl":"Shipping labels DHL"}}};t&&Object.keys(t).forEach(e=>{s.extend(e,t[e])})},k=C({name:"pw-dhl-config",type:"plugin",color:"#9AA8B5",routes:{dhl:{component:"pw-dhl-config",path:"dhl",meta:{parentPath:"sw.settings.index"}}},settingsItem:{group:"plugins",to:"pw.dhl.config.dhl",iconComponent:"icons-pw-dhl-icon-plugin",backgroundEnabled:!1,label:"pw-dhl-config-module.pickware-dhl"}},void 0,void 0,!1,null,null,null);"function"==typeof S&&S(k),k.options.__file="src/Administration/config/pw-dhl-config-module.vue";var x=k.exports,O=n(0);function P(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function _(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?P(Object(n),!0).forEach((function(t){E(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):P(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function E(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function j(e,t,n,r,i,o,a){try{var c=e[o](a),s=c.value}catch(e){return void n(e)}c.done?t(s):Promise.resolve(s).then(r,i)}function $(e){return function(){var t=this,n=arguments;return new Promise((function(r,i){var o=e.apply(t,n);function a(e){j(o,r,i,a,c,"next",e)}function c(e){j(o,r,i,a,c,"throw",e)}a(void 0)}))}}var B={name:"pw-dhl-config",mixins:[l.getByName("notification")],inject:["pickwareDhlConfigApiService"],data:function(){return{isSaveSuccessful:!1,isLoading:!1,config:{},configDomain:"PickwareDhl.dhl"}},metaInfo:function(){return{title:this.$createTitle(this.$t("pw-dhl-config.title"))}},computed:{canCheckDhlBcpCredentials:function(){return this.config["".concat(this.configDomain,".username")]&&this.config["".concat(this.configDomain,".password")]&&!this.config["".concat(this.configDomain,".useTestingEndpoint")]},canFetchDhlContractData:function(){return this.config["".concat(this.configDomain,".username")]&&this.config["".concat(this.configDomain,".password")]&&!this.config["".concat(this.configDomain,".useTestingEndpoint")]||this.config["".concat(this.configDomain,".useTestingEndpoint")]}},methods:{onSave:function(){var e=this;return $(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return e.isLoading=!0,t.prev=1,t.next=4,e.$refs.systemConfig.saveAll();case 4:e.isSaveSuccessful=!0,e.createNotificationSuccess({title:e.$t("pw-dhl-config.controller.notifications.save.success.title"),message:e.$t("pw-dhl-config.controller.notifications.save.success.message")}),t.next=12;break;case 8:t.prev=8,t.t0=t.catch(1),e.isSaveSuccessful=!1,e.createNotificationError({title:e.$t("pw-dhl-config.controller.notifications.save.error.title"),message:e.$t("pw-dhl-config.controller.notifications.save.error.message",{errorMessage:t.t0.response.data.errors[0].detail})});case 12:e.isLoading=!1;case 13:case"end":return t.stop()}}),t,null,[[1,8]])})))()},onCheckDhlBcpCredentials:function(){var e=this;return $(regeneratorRuntime.mark((function t(){var n,r,i;return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return e.isLoading=!0,t.prev=1,t.next=4,e.pickwareDhlConfigApiService.checkDhlBcpCredentials({username:e.config["".concat(e.configDomain,".username")],password:e.config["".concat(e.configDomain,".password")]});case 4:(n=t.sent).areCredentialsValid?(r=e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.success.title.valid"),i=n.isSystemUser?e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.success.message.system-user"):e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.success.message.valid"),e.createNotificationSuccess({message:i,title:r})):e.createNotificationError({title:e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.success.title.invalid"),message:e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.success.message.invalid")}),t.next=11;break;case 8:t.prev=8,t.t0=t.catch(1),e.createNotificationError({title:e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.error.title"),message:e.$t("pw-dhl-config.controller.notifications.checkDhlBcpCredentials.error.message",{errorMessage:t.t0.response.data.errors[0].detail})});case 11:return t.prev=11,e.isLoading=!1,t.finish(11);case 14:case"end":return t.stop()}}),t,null,[[1,8,11,14]])})))()},onFetchDhlContractData:function(){var e=this;return $(regeneratorRuntime.mark((function t(){var n,r,i,o;return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:if(e.isLoading=!0,t.prev=1,n=e.$refs.systemConfig,r=n.salesChannelId,i=_({},n.actualConfigData[r]),o={},!i["".concat(e.configDomain,".useTestingEndpoint")]){t.next=10;break}o=O,t.next=13;break;case 10:return t.next=12,e.pickwareDhlConfigApiService.fetchDhlContractData({username:e.config["".concat(e.configDomain,".username")],password:e.config["".concat(e.configDomain,".password")]});case 12:o=t.sent;case 13:i["".concat(e.configDomain,".customerNumber")]=o.customerNumber,Object.keys(i).forEach((function(t){(t.startsWith("".concat(e.configDomain,".participation"))||t.startsWith("".concat(e.configDomain,".returnParticipation")))&&delete i[t]})),o.bookedProducts.forEach((function(t){if(t.participations.length>0){var n="".concat(e.configDomain,".participation").concat(t.product.code);i[n]=t.participations[0]}if(t.returnParticipations.length>0){var r="".concat(e.configDomain,".returnParticipation").concat(t.product.code);i[r]=t.returnParticipations[0]}})),n.$set(n.actualConfigData,n.salesChannelId,i),e.createNotificationSuccess({title:e.$t("pw-dhl-config.controller.notifications.fetchDhlContractData.success.title"),message:e.$t("pw-dhl-config.controller.notifications.fetchDhlContractData.success.message")}),t.next=23;break;case 20:t.prev=20,t.t0=t.catch(1),e.createNotificationError({title:e.$t("pw-dhl-config.controller.notifications.fetchDhlContractData.error.title"),message:e.$t("pw-dhl-config.controller.notifications.fetchDhlContractData.error.message",{errorMessage:t.t0.response.data.errors[0].detail})});case 23:return t.prev=23,e.isLoading=!1,t.finish(23);case 26:case"end":return t.stop()}}),t,null,[[1,20,23,26]])})))()},onConfigChange:function(e){this.config=e,this.isSaveSuccessful=!1}}},T='\n<sw-page :isLoading="isLoading">\n    <template #smart-bar-header>\n        <h2>\n            {{ $t(\'sw-settings.index.title\') }}\n            <sw-icon\n                small\n                name="small-arrow-medium-right"\n            />\n            {{ $t(\'pw-dhl-config.smart-bar.title\') }}\n        </h2>\n    </template>\n\n    <template #smart-bar-actions>\n        <sw-button\n            :disabled="isLoading || !canCheckDhlBcpCredentials"\n            @click="onCheckDhlBcpCredentials"\n        >\n            {{ $t(\'pw-dhl-config.smart-bar.buttons.checkDhlBcpCredentials.label\') }}\n        </sw-button>\n\n        <sw-button\n            :disabled="isLoading || !canFetchDhlContractData"\n            @click="onFetchDhlContractData"\n        >\n            {{ $t(\'pw-dhl-config.smart-bar.buttons.fetchDhlContractData.label\') }}\n        </sw-button>\n\n        <sw-button-process\n            variant="primary"\n            :isLoading="isLoading"\n            :processSuccess="isSaveSuccessful"\n            @click="onSave"\n        >\n            {{ $t(\'pw-dhl-config.smart-bar.buttons.save.label\') }}\n        </sw-button-process>\n    </template>\n\n    <template #content>\n        <sw-card-view>\n            <sw-system-config\n                ref="systemConfig"\n                :salesChannelSwitchable="true"\n                :domain="configDomain"\n                @config-changed="onConfigChange"\n            />\n        </sw-card-view>\n    </template>\n</sw-page>\n',R=function(e){const t={"en-GB":{"pw-dhl-config":{title:"DHL","smart-bar":{buttons:{save:{label:"Save"},fetchDhlContractData:{label:"Retrieve contract data"},checkDhlBcpCredentials:{label:"Check credentials"}},title:"Shipping labels DHL"},controller:{notifications:{save:{success:{title:"Success",message:"The configuration was saved successfully!"},error:{title:"Error",message:"The configuration could not be saved, because the following error occurred: {errorMessage}"}},checkDhlBcpCredentials:{success:{title:{valid:"Success",invalid:"Error"},message:{valid:"The credentials are valid!","system-user":"The credentials are valid! Note: This user is a system user, therefore the contract data cannot be retrieved automatically. But the creation of labels is possible without further ado.",invalid:"The credentials are NOT valid!"}},error:{title:"Error",message:"The credentials could not be verified, because the following error occurred: {errorMessage}"}},fetchDhlContractData:{success:{title:"Success",message:"The contract data were successfully imported from the business customer portal! Please remember to save the configuration."},error:{title:"Error",message:"The contract data could not be retrieved, because the following error occurred: {errorMessage}"}}}}}},"de-DE":{"pw-dhl-config":{title:"DHL","smart-bar":{buttons:{save:{label:"Speichern"},fetchDhlContractData:{label:"Vertragsdaten abrufen"},checkDhlBcpCredentials:{label:"Zugangsdaten prüfen"}},title:"Versandetiketten DHL"},controller:{notifications:{save:{success:{title:"Erfolg",message:"Die Konfiguration wurde erfolgreich gespeichert!"},error:{title:"Fehler",message:"Die Konfiguration konnte nicht gespeichert werden, da folgender Fehler aufgetreten ist: {errorMessage}"}},checkDhlBcpCredentials:{success:{title:{valid:"Erfolg",invalid:"Fehler"},message:{valid:"Die Zugangsdaten sind gültig!","system-user":"Die Zugangsdaten sind gültig! Hinweis: Dieser Benutzer ist ein Systembenutzer, daher können die Vertragsdaten nicht automatisch abgerufen werden. Die Erstellung von Label ist aber ohne Weiteres möglich.",invalid:"Die Zugangsdaten sind NICHT gültig!"}},error:{title:"Fehler",message:"Die Zugangsdaten konnten nicht geprüft werden, da folgender Fehler aufgetreten ist: {errorMessage}"}},fetchDhlContractData:{success:{title:"Erfolg",message:"Die Vertragsdaten wurden erfolgreich aus dem Geschäftskundenportal übernommen! Bitte denke daran, die Konfiguration zu speichern."},error:{title:"Fehler",message:"Die Vertragsdaten konnten nicht abgerufen werden, da folgender Fehler aufgetreten ist: {errorMessage}"}}}}}}};t&&Object.keys(t).forEach(e=>{s.extend(e,t[e])})},L=C(B,void 0,void 0,!1,null,null,null);"function"==typeof R&&R(L),L.options.__file="src/Administration/config/pw-dhl-config.vue";var H=L.exports;f(r),function(e){u.register(e.name,e)}(x),a.addServiceProvider("pickwareDhlConfigApiService",(function(){var e=a.getContainer("init"),t=a.getContainer("service");return new y(e.httpClient,t.loginService)}));var M='\n<svg\n    xmlns="http://www.w3.org/2000/svg"\n    width="2500"\n    height="1595"\n    viewBox="0 0 46.986 29.979"\n>\n    <path\n        fill="#ffcb01"\n        d="M0 0h46.986v29.979H0z"\n    />\n\n    <g fill="#d80613">\n        \x3c!-- We cannot wrap path\'s "d" attribute. Ignore until we move the SVG icons into files. --\x3e\n        \x3c!-- eslint-disable-next-line max-len vue/max-len --\x3e\n        <path d="M8.731 11.413L7.276 13.39h7.93c.401 0 .396.151.2.418-.199.27-.532.737-.735 1.012-.103.139-.289.392.327.392h3.243l.961-1.306c.596-.809.052-2.492-2.079-2.492l-8.392-.001z" />\n\n        \x3c!-- We cannot wrap path\'s "d" attribute. Ignore until we move the SVG icons into files. --\x3e\n        \x3c!-- eslint-disable-next-line max-len vue/max-len --\x3e\n        <path d="M6.687 17.854l2.923-3.972h3.627c.401 0 .396.152.2.418l-.74 1.008c-.103.139-.289.392.327.392h4.858c-.403.554-1.715 2.154-4.067 2.154H6.687zm16.738-2.155l-1.585 2.155h-4.181l1.585-2.155zm6.404-.488H19.604l2.796-3.798h4.179l-1.602 2.178h1.865l1.604-2.178h4.179zm-.359.488l-1.585 2.155h-4.179l1.585-2.155zm-28.748.85H6.88l-.336.457H.722zm0-.85h6.784l-.337.457H.722zm0 1.7h5.533l-.335.455H.722zm45.543-.393h-6.136l.337-.457h5.799zm0 .848h-6.759l.334-.455h6.425zm-5.174-2.155h5.174v.458h-5.51zm-2.678-4.286l-2.796 3.798h-4.429l2.798-3.798zm-7.583 4.286s-.305.418-.454.618c-.524.71-.061 1.536 1.652 1.536h6.712l1.585-2.154H30.83z" />\n    </g>\n</svg>\n',z=C({name:"icons-pw-dhl-icon-plugin"},void 0,void 0,!1,null,null,null);z.options.__file="src/Administration/icons/icons-pw-dhl-icon-plugin.vue";var A=z.exports;f(i)}]);