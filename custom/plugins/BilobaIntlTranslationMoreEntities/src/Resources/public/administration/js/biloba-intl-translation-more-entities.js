(this.webpackJsonp=this.webpackJsonp||[]).push([["biloba-intl-translation-more-entities"],{"7IP1":function(t,a){t.exports='{% block sw_mail_template_detail_actions_save %}\n    <sw-button :disabled="isTranslationDisabled" :isLoading="isTranslating" @click="onTranslate">\n        {{ $tc(\'biloba-intl-translation.sw-product.detail.buttonTranslate\')}}\n    </sw-button>\n    {% parent %}\n{% endblock %}'},ONxU:function(t,a){t.exports='{% block sw_manufacturer_detail_actions_abort %}\n    <sw-button :disabled="isTranslationDisabled" :isLoading="isTranslating" @click="onTranslate">\n        {{ $tc(\'biloba-intl-translation.sw-product.detail.buttonTranslate\')}}\n    </sw-button>\n    {% parent %}\n{% endblock %}'},S30C:function(t,a){t.exports='{% block sw_product_stream_detail_actions %}\n    <template slot="smart-bar-actions">\n        <sw-button :disabled="isTranslationDisabled" :isLoading="isTranslating" v-tooltip.bottom="tooltipCancel" @click="onTranslate">\n            {{ $tc(\'biloba-intl-translation.sw-product.detail.buttonTranslate\')}}\n        </sw-button>\n    </template>\n    {% parent %}\n{% endblock %}'},XOUc:function(t,a){t.exports='{% block sw_category_smart_bar_abort %}\n    <sw-button :disabled="isTranslationDisabled" :isLoading="isTranslating" v-tooltip.bottom="tooltipCancel" @click="onTranslate">\n        {{ $tc(\'biloba-intl-translation.sw-product.detail.buttonTranslate\')}}\n    </sw-button>\n    {% parent %}\n{% endblock %}'},eR4o:function(t,a){t.exports='{% block sw_property_detail_smart_bar_actions %}\n    <template slot="smart-bar-actions">\n        <sw-button :disabled="isTranslationDisabled" :isLoading="isTranslating" v-tooltip.bottom="tooltipCancel" @click="onTranslate">\n            {{ $tc(\'biloba-intl-translation.sw-product.detail.buttonTranslate\')}}\n        </sw-button>\n    </template>\n    {% parent %}\n{% endblock %}'},wtJZ:function(t,a,i){"use strict";i.r(a);var e=i("eR4o"),n=i.n(e);const{StateDeprecated:s}=Shopware;Shopware.Component.override("sw-property-detail",{template:n.a,created(){this.syncService=Shopware.Service("syncService"),this.httpClient=this.syncService.httpClient,this.repository=this.repositoryFactory.create("biloba_intl_translation_config"),this.getConfig()},data:()=>({isTranslating:!1,isConfigAvailable:!1,isTranslationDisabled:!0,config:null}),computed:{languageStore:()=>s.getStore("language")},methods:{onChangeLanguage(t){this.isConfigAvailable=!1,this.isTranslationDisabled=!0,this.currentLanguageId=t,this.loadEntityData(),this.getConfig()},getConfig(){return this.httpClient.post("/_action/biloba-intl-translation/get-config",{languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{t.data.id&&(this.config=t.data,this.isConfigAvailable=!0)}).finally(()=>{this.isTranslationDisabled=0==this.isConfigAvailable})},onTranslate:function(){this.isTranslating=!0,this.httpClient.post("/_action/biloba-intl-translation-translation",{entity:"property_group",entityId:this.groupId,languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{if(t.data.status&&"error"==t.data.status)this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:t.data.message});else{for(let a in t.data)this.propertyGroup[a]=t.data[a];this.createNotificationInfo({title:this.$tc("biloba-intl-translation.general.notificationTranslated.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslated.message")})}}).catch(()=>{this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslatedError.message")})}).finally(()=>{this.isTranslating=!1})}}});var o=i("XOUc"),r=i.n(o);const{StateDeprecated:l}=Shopware;Shopware.Component.override("sw-category-detail",{template:r.a,created(){this.syncService=Shopware.Service("syncService"),this.httpClient=this.syncService.httpClient,this.repository=this.repositoryFactory.create("biloba_intl_translation_config"),this.getConfig()},data:()=>({isTranslating:!1,isConfigAvailable:!1,isTranslationDisabled:!0,config:null}),computed:{languageStore:()=>l.getStore("language")},methods:{onChangeLanguage(t){this.isConfigAvailable=!1,this.isTranslationDisabled=!0,this.currentLanguageId=t,this.setCategory(),this.getConfig()},getConfig(){return this.httpClient.post("/_action/biloba-intl-translation/get-config",{languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{t.data.id&&(this.config=t.data,this.isConfigAvailable=!0)}).finally(()=>{this.isTranslationDisabled=0==this.isConfigAvailable})},onTranslate:function(){this.isTranslating=!0,this.httpClient.post("/_action/biloba-intl-translation-translation",{entity:"category",entityId:this.category.id,languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{if(t.data.status&&"error"==t.data.status)this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:t.data.message});else{for(let a in t.data)this.category[a]=t.data[a];this.createNotificationInfo({title:this.$tc("biloba-intl-translation.general.notificationTranslated.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslated.message")})}}).catch(()=>{this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslatedError.message")})}).finally(()=>{this.isTranslating=!1})}}});var c=i("ONxU"),g=i.n(c);const{StateDeprecated:h}=Shopware;Shopware.Component.override("sw-manufacturer-detail",{template:g.a,created(){this.syncService=Shopware.Service("syncService"),this.httpClient=this.syncService.httpClient,this.repository=this.repositoryFactory.create("biloba_intl_translation_config"),this.getConfig()},data:()=>({isTranslating:!1,isConfigAvailable:!1,isTranslationDisabled:!0,config:null}),computed:{languageStore:()=>h.getStore("language")},methods:{onChangeLanguage(t){this.isConfigAvailable=!1,this.isTranslationDisabled=!0,this.currentLanguageId=t,this.loadEntityData(),this.getConfig()},getConfig(){return this.httpClient.post("/_action/biloba-intl-translation/get-config",{languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{t.data.id&&(this.config=t.data,this.isConfigAvailable=!0)}).finally(()=>{this.isTranslationDisabled=0==this.isConfigAvailable})},onTranslate:function(){this.isTranslating=!0,this.httpClient.post("/_action/biloba-intl-translation-translation",{entity:"product_manufacturer",entityId:this.manufacturer.id,languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{if(t.data.status&&"error"==t.data.status)this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:t.data.message});else{for(let a in t.data)this.manufacturer[a]=t.data[a];this.createNotificationInfo({title:this.$tc("biloba-intl-translation.general.notificationTranslated.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslated.message")})}}).catch(()=>{this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslatedError.message")})}).finally(()=>{this.isTranslating=!1})}}});var d=i("7IP1"),b=i.n(d);const{StateDeprecated:f}=Shopware;Shopware.Component.override("sw-mail-template-detail",{template:b.a,created(){this.syncService=Shopware.Service("syncService"),this.httpClient=this.syncService.httpClient,this.repository=this.repositoryFactory.create("biloba_intl_translation_config"),this.getConfig()},data:()=>({isTranslating:!1,isConfigAvailable:!1,isTranslationDisabled:!0,config:null}),computed:{languageStore:()=>f.getStore("language")},methods:{onChangeLanguage(t){this.isConfigAvailable=!1,this.isTranslationDisabled=!0,this.currentLanguageId=t,this.loadEntityData(),this.getConfig()},getConfig(){return this.httpClient.post("/_action/biloba-intl-translation/get-config",{languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{t.data.id&&(this.config=t.data,this.isConfigAvailable=!0)}).finally(()=>{this.isTranslationDisabled=0==this.isConfigAvailable})},onTranslate:function(){this.isTranslating=!0,this.httpClient.post("/_action/biloba-intl-translation-translation",{entity:"mail_template",mailTemplateId:this.mailTemplate.id,languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{if(t.data.status&&"error"==t.data.status)this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:t.data.message});else{for(let a in t.data)this.mailTemplate[a]=t.data[a];this.createNotificationInfo({title:this.$tc("biloba-intl-translation.general.notificationTranslated.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslated.message")})}}).catch(()=>{this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslatedError.message")})}).finally(()=>{this.isTranslating=!1})}}});var u=i("S30C"),p=i.n(u);const{StateDeprecated:m}=Shopware;Shopware.Component.override("sw-product-stream-detail",{template:p.a,created(){this.syncService=Shopware.Service("syncService"),this.httpClient=this.syncService.httpClient,this.repository=this.repositoryFactory.create("biloba_intl_translation_config"),this.getConfig()},data:()=>({isTranslating:!1,isConfigAvailable:!1,isTranslationDisabled:!0,config:null}),computed:{languageStore:()=>m.getStore("language")},methods:{onChangeLanguage(t){this.isConfigAvailable=!1,this.isTranslationDisabled=!0,this.languageId=t,this.loadEntityData(this.productStream.id).then(()=>{this.isLoading=!1}),this.getConfig()},getConfig(){return this.httpClient.post("/_action/biloba-intl-translation/get-config",{languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{t.data.id&&(this.config=t.data,this.isConfigAvailable=!0)}).finally(()=>{this.isTranslationDisabled=0==this.isConfigAvailable})},onTranslate:function(){this.isTranslating=!0,this.httpClient.post("/_action/biloba-intl-translation-translation",{entity:"product_stream",entityId:this.productStream.id,languageId:this.languageStore.getCurrentId()},{headers:this.syncService.getBasicHeaders()}).then(t=>{if(t.data.status&&"error"==t.data.status)this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:t.data.message});else{for(let a in t.data)this.productStream[a]=t.data[a];this.createNotificationInfo({title:this.$tc("biloba-intl-translation.general.notificationTranslated.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslated.message")})}}).catch(()=>{this.createNotificationError({title:this.$tc("biloba-intl-translation.general.notificationTranslatedError.title"),message:this.$tc("biloba-intl-translation.general.notificationTranslatedError.message")})}).finally(()=>{this.isTranslating=!1})}}})}},[["wtJZ","runtime"]]]);