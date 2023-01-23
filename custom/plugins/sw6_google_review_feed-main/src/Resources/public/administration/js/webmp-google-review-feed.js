(this.webpackJsonp=this.webpackJsonp||[]).push([["webmp-google-review-feed"],{"5Jnq":function(e,n){e.exports='{% block sw_system_config %}\n    <div class="sw-system-config">\n        <div class="sw-system-config__global-sales-channel-switch"\n             v-if="salesChannelSwitchable && config.length > 1">\n            <sw-sales-channel-switch :label="$tc(\'sw-settings.system-config.labelSalesChannelSelect\')"\n                                     @change-sales-channel-id="customOnSalesChannelChanged">\n            </sw-sales-channel-switch>\n        </div>\n        {% block sw_system_config_content_card %}\n            <sw-card v-for="card, index in config"\n                     :key="index"\n                     :class="`sw-system-config__card--${index}`"\n                     :isLoading="isLoading"\n                     :title="getInlineSnippet(card.title)">\n                <slot name="beforeElements" v-bind="{ card, config: actualConfigData[currentSalesChannelId] }"></slot>\n                <template #toolbar\n                          v-if="salesChannelSwitchable && config.length === 1">\n                    <sw-sales-channel-switch :label="$tc(\'sw-settings.system-config.labelSalesChannelSelect\')"\n                                             @change-sales-channel-id="onSalesChannelChanged">\n                    </sw-sales-channel-switch>\n                </template>\n                <template v-if="!isLoading">\n                    <template v-for="element in card.elements">\n                        <slot name="card-element" v-bind="{ element: getElementBind(element), config: actualConfigData[currentSalesChannelId], card }">\n                            {% block sw_system_config_content_card_field %}\n                                <sw-form-field-renderer @input="onElementInput(element, currentSalesChannelId, actualConfigData[currentSalesChannelId][element.name])" v-bind="getElementBind(element)"\n                                                        v-model="actualConfigData[currentSalesChannelId][element.name]"\n                                                        :currentSalesChannelId="currentSalesChannelId"\n                                                        :actualConfigData="actualConfigData">\n                                </sw-form-field-renderer>\n                            {% endblock %}\n                        </slot>\n                    </template>\n                    <slot name="card-element-last"/>\n                </template>\n                <slot name="afterElements" v-bind="{ card, config: actualConfigData[currentSalesChannelId] }"></slot>\n                <slot v-if="index === last" name="lastCard" v-bind="{ config: actualConfigData[currentSalesChannelId] }"></slot>\n            </sw-card>\n        {% endblock %}\n    </div>\n{% endblock %}\n'},"5PFB":function(e,n,t){},CZJs:function(e,n,t){var a=t("UhQc");"string"==typeof a&&(a=[[e.i,a,""]]),a.locals&&(e.exports=a.locals);(0,t("SZ7m").default)("25d5b988",a,!0,{})},"Eu+/":function(e,n){Shopware.Module.register("webmasterei-settings",{type:"plugin",name:"webmastereiSettings.general.name",title:"webmastereiSettings.general.title",description:"webmastereiSettings.general.description",color:"#23ac70",routes:{config:{component:"webmasterei-plugin-config",path:"config/:namespace",meta:{parentPath:"sw.settings.index"}}}})},G1EP:function(e,n){e.exports='<div class="webm-feed-component" v-if="this.availableDomains.length >= 1">\n    <h3>Api token</h3>\n    <div class="sw-block-field">\n        <div class="sw-block-field__block access-key">\n            <input type="text" v-model="accessToken" :label="\'Access token\'" :disabled="true">\n            <sw-button @click="generateAccessKey" variant="primary">Generate new access key</sw-button>\n        </div>\n    </div>\n    <br><br>\n\n    <h3>Feed Urls</h3>\n    <span>Select domains</span>\n    <sw-multi-select\n        :options="availableDomains"\n        labelProperty="url"\n        valueProperty="url"\n        v-model="selectedDomains">\n    </sw-multi-select>\n\n    <div class="sw-field sw-block-field sw-contextual-field sw-field--text sw-form-field-renderer sw-field--default">\n        <input v-for="url in this.feedUrls" :key="url" class="webm-feed-url" type="text" disabled="disabled"\n               :value="url">\n    </div>\n\n    <sw-button @click="generateFeed" :isLoading="isLoadingGeneration" variant="primary" class="mb32 ml5">\n        Generate feed\n    </sw-button>\n</div>\n\n<div class="webm-feed-component-placeholder" v-else>\n    <h2>Please select sale channel with available domains</h2>\n</div>\n'},GIEK:function(e,n){e.exports='{% block sw_system_config %}\n    <webmasterei-system-config :domain="domain"\n                      salesChannelSwitchable\n                      :salesChannelId="salesChannelId"\n                      ref="systemConfig">\n    </webmasterei-system-config>\n{% endblock %}\n'},I5Ns:function(e){e.exports=JSON.parse('{"webmasterei":{"general":{"generateFeed":"Feed generieren","generateAccessKey":"API Key erstellen","mainMenuItemGeneral":"Google Review Feed"},"notification":{"success":{"title":"Erfolg","feedGenerated":"Der Feed wurde erfolgreich generiert!"},"error":{"title":"Error"}}}}')},LPrk:function(e,n,t){var a=t("5PFB");"string"==typeof a&&(a=[[e.i,a,""]]),a.locals&&(e.exports=a.locals);(0,t("SZ7m").default)("672af948",a,!0,{})},NHdU:function(e){e.exports=JSON.parse('{"webmasterei":{"general":{"generateFeed":"Generate feed","generateAccessKey":"Generate access key","mainMenuItemGeneral":"Google Review Feed"},"notification":{"success":{"title":"Success","feedGenerated":"The feed has successfully generated!"},"error":{"title":"Error"}}}}')},QRXE:function(e,n){e.exports='{% block sw_settings_content_card_slot_plugins %}\n    {% parent %}\n\n    <sw-settings-item :label="$tc(\'webmasterei.general.mainMenuItemGeneral\')"\n                      :to="{ name: \'webmasterei.settings.config\', params: { namespace: \'WebmpGoogleReviewFeed\' } }"\n                      :backgroundEnabled="false">\n        <template #icon>\n            <img class="sw-settings-index__webmasterei-icon" :src="\'webmpgooglereviewfeed/plugin.png\' | asset">\n        </template>\n    </sw-settings-item>\n{% endblock %}\n'},Ridh:function(e,n){function t(e){return(t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function a(e,n){if(!(e instanceof n))throw new TypeError("Cannot call a class as a function")}function s(e,n){for(var t=0;t<n.length;t++){var a=n[t];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}function i(e,n){return(i=Object.setPrototypeOf||function(e,n){return e.__proto__=n,e})(e,n)}function o(e){var n=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(e){return!1}}();return function(){var t,a=l(e);if(n){var s=l(this).constructor;t=Reflect.construct(a,arguments,s)}else t=a.apply(this,arguments);return r(this,t)}}function r(e,n){return!n||"object"!==t(n)&&"function"!=typeof n?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):n}function l(e){return(l=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}var c=Shopware.Application,d=Shopware.Classes.ApiService,u=function(e){!function(e,n){if("function"!=typeof n&&null!==n)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(n&&n.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),n&&i(e,n)}(c,e);var n,t,r,l=o(c);function c(e,n){var t=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"webmp";return a(this,c),l.call(this,e,n,t)}return n=c,(t=[{key:"generateFeed",value:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return this.httpClient.post("".concat(this.getApiBasePath(),"/generate-feed"),e,{headers:this.getBasicHeaders()}).then((function(e){return d.handleResponse(e)}))}}])&&s(n.prototype,t),r&&s(n,r),c}(d);c.addServiceProvider("WebmpService",(function(e){var n=c.getContainer("init");return new u(n.httpClient,e.loginService)}))},UhQc:function(e,n,t){},mTye:function(e,n,t){"use strict";t.r(n);t("Ridh");var a=t("G1EP"),s=t.n(a),i=(t("LPrk"),Shopware),o=i.Component,r=i.Utils,l=i.Mixin;o.register("webmasterei-input-feed-url",{name:"webmasterei-input-feed-url",template:s.a,inject:["systemConfigApiService","repositoryFactory","WebmpService"],props:{actualConfigData:Object,currentSalesChannelId:String},mixins:[l.getByName("notification")],data:function(){return{isLoadingGeneration:!1,salesChannelRepository:this.repositoryFactory.create("sales_channel"),availableDomains:this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.domains"]?this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.domains"]:[],selectedDomains:this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.currentDomains"]?this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.currentDomains"]:[]}},computed:{accessToken:function(){return this.currentSalesChannelId?this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.accessToken"]:""},feedUrls:function(){if(!this.currentSalesChannelId)return[];var e=[];return this.selectedDomains.forEach(function(n){e.push("".concat(n,"/webmasterei/google-product-review/").concat(this.accessToken,"/feed"))}.bind(this)),e}},methods:{generateAccessKey:function(){var e=this;this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.currentDomains"]=this.selectedDomains,this.selectedDomains?this.salesChannelRepository.get(this.currentSalesChannelId,Shopware.Context.api,this.salesChannelCritera).then((function(n){null!==n&&0!==n.domains.length||(e.actualConfigData[e.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.feedUrls"]=[]),e.actualConfigData[e.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.accessToken"]=r.createId();var t=[];e.selectedDomains.forEach(function(e){t.push("".concat(e,"/webmasterei/google-product-review/").concat(this.accessToken,"/feed"))}.bind(e)),e.actualConfigData[e.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.feedUrls"]=t})):this.actualConfigData[this.currentSalesChannelId]["WebmpGoogleReviewFeed.settings.feedUrls"]=[]},generateFeed:function(){var e=this;this.isLoadingGeneration=!0,this.WebmpService.generateFeed().then((function(n){n.success&&e.createNotificationSuccess({title:e.$tc("webmasterei.notification.success.title"),message:e.$tc("webmasterei.notification.success.feedGenerated")}),e.isLoadingGeneration=!1})).catch((function(n){e.createNotificationError({title:e.$tc("webmasterei.notification.error.title"),message:n})}))}}});var c=t("GIEK"),d=t.n(c);Shopware.Mixin;Shopware.Component.extend("webmasterei-plugin-config","sw-plugin-config",{template:d.a,inject:["systemConfigApiService"],computed:{domain:function(){return"WebmpGoogleReviewFeed.settings"}}});var u=t("5Jnq"),f=t.n(u),m=Shopware,g=m.Component,h=m.Mixin,p=Shopware.Utils,w=(p.object,p.types,Shopware.Data.Criteria);g.extend("webmasterei-system-config","sw-system-config",{name:"webmasterei-system-config",template:f.a,mixins:[h.getByName("notification"),h.getByName("sw-inline-snippet")],inject:["systemConfigApiService","repositoryFactory"],data:function(){return{selectedDomains:[]}},computed:{last:function(){return Object.keys(this.config).length-1},salesChannelRepository:function(){return this.repositoryFactory.create("sales_channel")},salesChannelCritera:function(){var e=new w;return e.addAssociation("domains"),e}},methods:{customOnSalesChannelChanged:function(e){var n=this;this.onSalesChannelChanged(e),this.salesChannelRepository.get(e,Shopware.Context.api,this.salesChannelCritera).then((function(t){var a=null!==t&&t.domains.length>0?t.domains:[];n.actualConfigData[e]["WebmpGoogleReviewFeed.settings.domains"]=a}))},onElementInput:function(e,n,t){"WebmpGoogleReviewFeed.settings.accessToken"===e.name&&this.onAccessTokenInput(n,t)},onAccessTokenInput:function(e,n){var t=this;null===e?this.actualConfigData[e]["WebmpGoogleReviewFeed.settings.feedUrls"]="":this.salesChannelRepository.get(e,Shopware.Context.api,this.salesChannelCritera).then((function(a){if(null!==a&&a.domains.length>0){var s=t.actualConfigData[e]["WebmpGoogleReviewFeed.settings.domains"],i=[];s.forEach((function(e){var t="".concat(e.url,"/webmasterei/google-product-review/").concat(n,"/feed");i.push(t)})),t.actualConfigData[e]["WebmpGoogleReviewFeed.settings.feedUrls"]=i}else t.actualConfigData[e]["WebmpGoogleReviewFeed.settings.feedUrls"]=""}))}},watch:{selectedDomains:function(){console.log("changed! 1")}}});var b=t("QRXE"),v=t.n(b);t("CZJs");Shopware.Component.override("sw-settings-index",{template:v.a});t("Eu+/");var C=t("I5Ns"),y=t("NHdU");Shopware.Locale.extend("de-DE",C),Shopware.Locale.extend("en-GB",y)}},[["mTye","runtime","vendors-node"]]]);