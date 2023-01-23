import Plugin from 'src/plugin-system/plugin.class'
import DomAccess from 'src/helper/dom-access.helper'
import CookieStorage from 'src/helper/storage/cookie-storage.helper';

const hostname = window.location.hostname;

export default class MndCookie extends Plugin {

    static options = {
        mndCookieSuffix: hostname.replace(/[^a-zA-Z]/g, ''),
        mndCookieExpire: 31,
        mndCookieType: 'banner'
    }

    init(){
        this.cookieBar = DomAccess.querySelector(this.el, '.mnd-cookie-bar');
        this.btnAcceptAll = DomAccess.querySelector(this.el, '.mnd-btn-accept-all');
        this.btnSettings = DomAccess.querySelector(this.el, '.mnd-btn-open-settings', false);
        this.btnSaveSettings = DomAccess.querySelector(this.el, '.mnd-btn-save-settings');

        this.btnSettingsDoc = DomAccess.querySelector(document, '.mnd-btn-open-settings', false);
        this.btnOptInGA = DomAccess.querySelector(document, '.mnd-btn-opt-in-ga', false);
        this.btnOptOutGA = DomAccess.querySelector(document, '.mnd-btn-opt-out-ga', false);
        this.btnOptInAll = DomAccess.querySelector(document, '.mnd-btn-opt-in-all', false);
        this.btnOptOutAll = DomAccess.querySelector(document, '.mnd-btn-opt-out-all', false);
        this.btnOptInCustom = DomAccess.querySelector(document, '.mnd-btn-opt-in-custom', false);
        this.btnOptOutCustom = DomAccess.querySelector(document, '.mnd-btn-opt-out-custom', false);

        this.registerEvents();
        this.checkCookieStatus();
        this.showNotice();
    }

    registerEvents() {
        this.btnAcceptAll.addEventListener('click', this.optInAll.bind(this));
        this.btnSaveSettings.addEventListener('click', this.saveSettings.bind(this));

        if(this.btnSettings) {
            this.btnSettings.addEventListener('click', this.openSettings.bind(this));
        }

        if(this.btnSettingsDoc) {
            this.btnSettingsDoc.addEventListener('click', this.openSettings.bind(this));
        }

        // Modal Btns
        const mndModalSettingsBtn = document.getElementById('mndModalSettings');
        const mndModalAcceptAllBtn = document.getElementById('mndModalAcceptAll');
        const mndSettingsModalAcceptAllBtn = document.getElementById('mndSettingsModalAcceptAll');

        if(mndModalSettingsBtn) {
            mndModalSettingsBtn.addEventListener('click', () => {
                this.openSettings()
            });
        }

        mndModalAcceptAllBtn.addEventListener('click', () => {
            this.optInAll()
        });

        mndSettingsModalAcceptAllBtn.addEventListener('click', () => {
            this.optInAll()
        });

        // Custom btns
        if(this.btnOptInGA) {
            this.btnOptInGA.addEventListener('click', this.optInGA.bind(this));
        }

        if(this.btnOptOutGA) {
            this.btnOptOutGA.addEventListener('click', this.optOutGA.bind(this));
        }

        if(this.btnOptInAll) {
            this.btnOptInAll.addEventListener('click', this.optInAll.bind(this));
        }

        if(this.btnOptOutAll) {
            this.btnOptOutAll.addEventListener('click', this.optOutAll.bind(this));
        }

        if(this.btnOptInCustom) {
            const cookieName = this.btnOptInCustom.dataset.mndCookieName;
            this.btnOptInCustom.addEventListener('click', () => { this.optInCustom(cookieName); });
        }

        if(this.btnOptOutCustom) {
            const cookieName = this.btnOptOutCustom.dataset.mndCookieName;
            this.btnOptOutCustom.addEventListener('click', () => { this.optOutCustom(cookieName); });
        }
    }

    showNotice() {
        const cookieName = 'mnd-accepted-'+this.options.mndCookieSuffix;
        const expire = this.options.mndCookieExpire;

        if(!CookieStorage.getItem(cookieName, 1, expire) && this.options.mndCookieActive == 1) {
            const type = this.options.mndCookieType;

            if(type == 'banner') {
                this.openBanner();
            } else {
                this.openModal();
            }
        }
    }

    openBanner() {
        this.cookieBar.classList.add('is--active');
    }

    closeBanner() {
        this.cookieBar.classList.remove('is--active');
    }

    openModal() {
        $('#mndCookieModal').modal('show');
    }

    closeModal() {
        $('#mndCookieModal').modal('hide');
    }

    checkCookieStatus() {
        const cookieActive = this.options.mndCookieActive;
        const gaActive = this.options.mndCookieGAActive;

        if(cookieActive == 1) {
            if(gaActive) {
                const gaString = this.options.mndCookieGAIds;
                const gaArray = gaString.split(',');
                let gaDisable = false;

                gaArray.forEach(function(el){
                    const gaProperty = el;
                    const disableStr = 'ga-disable-' + gaProperty;
                    if (document.cookie.indexOf(disableStr+'=') >= 0) {
                        gaDisable = true;
                    }
                });

                if(this.options.mndCookieGABehaviour == 'optOut' && gaDisable == false){
                    this.optInGA();
                }
            }

            const customCookie1Label = this.options.mndCookieCustom1Label;
            const customCookie1Name = this.options.mndCookieCustom1Name;
            if (customCookie1Label != '' && customCookie1Name != '' && this.options.mndCookieCustom1Behaviour == 'optOut' && !CookieStorage.getItem(customCookie1Name)) {
                this.optInCustom(customCookie1Name)
            }

            const customCookie2Label = this.options.mndCookieCustom2Label;
            const customCookie2Name = this.options.mndCookieCustom2Name;
            if (customCookie2Label != '' && customCookie2Name != '' && this.options.mndCookieCustom2Behaviour == 'optOut' && !CookieStorage.getItem(customCookie2Name)) {
                this.optInCustom(customCookie2Name)
            }

            const customCookie3Label = this.options.mndCookieCustom3Label;
            const customCookie3Name = this.options.mndCookieCustom3Name;
            if (customCookie3Label != '' && customCookie3Name != '' && this.options.mndCookieCustom3Behaviour == 'optOut' && !CookieStorage.getItem(customCookie3Name)) {
                this.optInCustom(customCookie3Name)
            }

            const customCookie4Label = this.options.mndCookieCustom4Label;
            const customCookie4Name = this.options.mndCookieCustom4Name;
            if (customCookie4Label != '' && customCookie4Name != '' && this.options.mndCookieCustom4Behaviour == 'optOut' && !CookieStorage.getItem(customCookie4Name)) {
                this.optInCustom(customCookie4Name)
            }

            const customCookie5Label = this.options.mndCookieCustom5Label;
            const customCookie5Name = this.options.mndCookieCustom5Name;
            if (customCookie5Label != '' && customCookie5Name != '' && this.options.mndCookieCustom5Behaviour == 'optOut' && !CookieStorage.getItem(customCookie5Name)) {
                this.optInCustom(customCookie5Name)
            }
        }
    }

    openSettings() {
        const expire = this.options.mndCookieExpire;

        // Google Analytics
        const gaActive = this.options.mndCookieGAActive;
        const analyticsBehaviour = this.options.mndCookieGABehaviour;
        let analyticsActive = false;

        if(gaActive) {
            if(analyticsBehaviour=='optIn' && CookieStorage.getItem('mnd-ga-opted-in')) {
                analyticsActive = true
            }

            if(analyticsBehaviour=='optOut') {
                analyticsActive = true;
                const gaString = this.options.mndCookieGAIds;
                const gaArray = gaString.split(',');

                gaArray.forEach(function(el){
                    const gaProperty = el;
                    const disableStr = 'ga-disable-' + gaProperty;
                    if (document.cookie.indexOf(disableStr+'=') >= 0) {
                        analyticsActive = false;

                    }
                });
            }

            this.toggleCheckbox('ga', analyticsActive);
        }

        /* Facebook Pixel */
        const fbActive = this.options.mndFbPixelActive;
        const fbBehaviour = this.options.mndFbPixelSetting;
        let pixelActive = false;

        if(fbActive=="true") {
            if(fbBehaviour=='1' && CookieStorage.getItem(this.options.mndCookieSuffix + '-mnd-fb-pixel') == 'optin') {
                pixelActive = true
            }

            if(fbBehaviour=='2' && CookieStorage.getItem(this.options.mndCookieSuffix + '-mnd-fb-pixel') != 'optout') {
                pixelActive = true
            }

            this.toggleCheckbox('fb', pixelActive);
        }

        // Custom cookies
        const customCookie1Label = this.options.mndCookieCustom1Label;
        const customCookie1Name = this.options.mndCookieCustom1Name;

        if (customCookie1Label != '' && customCookie1Name != '') {
            let mndCustomTrackingActive = false;

            if (CookieStorage.getItem(customCookie1Name)) {
                mndCustomTrackingActive = CookieStorage.getItem(customCookie1Name);
            }

            this.toggleCheckbox(customCookie1Name, mndCustomTrackingActive);
        }

        const customCookie2Label = this.options.mndCookieCustom2Label;
        const customCookie2Name = this.options.mndCookieCustom2Name;

        if (customCookie2Label != '' && customCookie2Name != '') {
            let mndCustom2TrackingActive = false;

            if (CookieStorage.getItem(customCookie2Name, 1, expire)) {
                mndCustom2TrackingActive = CookieStorage.getItem(customCookie2Name, 1, expire);
            }

            this.toggleCheckbox(customCookie2Name, mndCustom2TrackingActive);
        }

        const customCookie3Label = this.options.mndCookieCustom3Label;
        const customCookie3Name = this.options.mndCookieCustom3Name;

        if (customCookie3Label != '' && customCookie3Name != '') {
            let mndCustom3TrackingActive = false;

            if (CookieStorage.getItem(customCookie3Name, 1, expire)) {
                mndCustom3TrackingActive = CookieStorage.getItem(customCookie3Name, 1, expire);
            }

            this.toggleCheckbox(customCookie3Name, mndCustom3TrackingActive);
        }

        const customCookie4Label = this.options.mndCookieCustom4Label;
        const customCookie4Name = this.options.mndCookieCustom4Name;

        if (customCookie4Label != '' && customCookie4Name != '') {
            let mndCustom4TrackingActive = false;

            if (CookieStorage.getItem(customCookie4Name, 1, expire)) {
                mndCustom4TrackingActive = CookieStorage.getItem(customCookie4Name, 1, expire);
            }

            this.toggleCheckbox(customCookie4Name, mndCustom4TrackingActive);
        }

        const customCookie5Label = this.options.mndCookieCustom5Label;
        const customCookie5Name = this.options.mndCookieCustom5Name;

        if (customCookie5Label != '' && customCookie5Name != '') {
            let mndCustom5TrackingActive = false;

            if (CookieStorage.getItem(customCookie5Name, 1, expire)) {
                mndCustom5TrackingActive = CookieStorage.getItem(customCookie5Name, 1, expire);
            }

            this.toggleCheckbox(customCookie5Name, mndCustom5TrackingActive);
        }

        this.closeBanner();
        $('#mndSettingsModal').modal('show');

        $('#mndSettingsModal').on('shown.bs.modal', function () {
            if (!document.body.classList.contains('modal-open')) {
                document.body.classList.add('modal-open');
            }
        })
    }

    toggleCheckbox(type, active) {
        const gaOption = DomAccess.querySelector(document, '*[data-mnd-cookie-type="'+type+'"]');
        const privacyStatus = DomAccess.querySelector(gaOption, '.mnd-settings-status');
        const privacyStatusInActive = DomAccess.querySelector(gaOption, '.mnd-settings-status-inactive');
        const privacyStatusActive = DomAccess.querySelector(gaOption, '.mnd-settings-status-active');
        const privacyStatusCheckbox = DomAccess.querySelector(gaOption, '.custom-control-input');
        privacyStatus.style.display = 'none';

        if(active == true || active =='true') {
            privacyStatusInActive.style.display = 'none';
            privacyStatusActive.style.display = 'block';
            privacyStatusCheckbox.setAttribute('checked', true);
        } else {
            privacyStatusActive.style.display = 'none';
            privacyStatusInActive.style.display = 'block';
            privacyStatusCheckbox.removeAttribute('checked');
        }
    }

    saveSettings() {
        const expire = this.options.mndCookieExpire;

        // Save GA status
        const gaActive = this.options.mndCookieGAActive;
        const analyticsIds = this.options.mndCookieGAIds;

        if(gaActive && analyticsIds != '') {
            const gaOption = DomAccess.querySelector(document, '*[data-mnd-cookie-type="ga"]');
            const privacyStatusCheckbox = DomAccess.querySelector(gaOption, '.custom-control-input');

            this.toggleCheckbox('ga', privacyStatusCheckbox.checked);

            if (privacyStatusCheckbox.checked) {
                this.optInGA()
            } else {
                this.optOutGA()
            }
        }

        // Save FP status
        const fbActive = this.options.mndFbPixelActive;
        if(fbActive=="true") {
            const fbOption = DomAccess.querySelector(document, '*[data-mnd-cookie-type="fb"]', false);
            const privacyStatusCheckbox = DomAccess.querySelector(fbOption, '.custom-control-input', false);

            if(fbOption) {
                this.toggleCheckbox('fb', privacyStatusCheckbox.checked);

                if (privacyStatusCheckbox.checked) {
                    this.optInFB()
                } else {
                    this.optOutFB()
                }
            }
        }

        // Save custom cookies
        const customCookie1Label = this.options.mndCookieCustom1Label;
        const customCookie1Name = this.options.mndCookieCustom1Name;

        if (customCookie1Label != '' && customCookie1Name != '') {
            const option = DomAccess.querySelector(document, '*[data-mnd-cookie-type="'+customCookie1Name+'"]');
            const privacyStatusCheckbox = DomAccess.querySelector(option, '.custom-control-input');
            let mndCustomTrackingActive = false;

            if (CookieStorage.getItem(customCookie1Name)) {
                mndCustomTrackingActive = CookieStorage.getItem(customCookie1Name);
            }

            this.toggleCheckbox(customCookie1Name, mndCustomTrackingActive);

            if (privacyStatusCheckbox.checked) {
                this.optInCustom(customCookie1Name);
            } else {
                this.optOutCustom(customCookie1Name);
            }
        }

        const customCookie2Label = this.options.mndCookieCustom2Label;
        const customCookie2Name = this.options.mndCookieCustom2Name;

        if (customCookie2Label != '' && customCookie2Name != '') {
            const option = DomAccess.querySelector(document, '*[data-mnd-cookie-type="'+customCookie2Name+'"]');
            const privacyStatusCheckbox = DomAccess.querySelector(option, '.custom-control-input');
            let mndCustomTrackingActive = false;

            if (CookieStorage.getItem(customCookie2Name)) {
                mndCustomTrackingActive = CookieStorage.getItem(customCookie2Name);
            }

            this.toggleCheckbox(customCookie2Name, mndCustomTrackingActive);

            if (privacyStatusCheckbox.checked) {
                this.optInCustom(customCookie2Name);
            } else {
                this.optOutCustom(customCookie2Name);
            }
        }

        const customCookie3Label = this.options.mndCookieCustom3Label;
        const customCookie3Name = this.options.mndCookieCustom3Name;

        if (customCookie3Label != '' && customCookie3Name != '') {
            const option = DomAccess.querySelector(document, '*[data-mnd-cookie-type="'+customCookie3Name+'"]');
            const privacyStatusCheckbox = DomAccess.querySelector(option, '.custom-control-input');
            let mndCustomTrackingActive = false;

            if (CookieStorage.getItem(customCookie3Name)) {
                mndCustomTrackingActive = CookieStorage.getItem(customCookie3Name);
            }

            this.toggleCheckbox(customCookie3Name, mndCustomTrackingActive);

            if (privacyStatusCheckbox.checked) {
                this.optInCustom(customCookie3Name);
            } else {
                this.optOutCustom(customCookie3Name);
            }
        }

        const customCookie4Label = this.options.mndCookieCustom4Label;
        const customCookie4Name = this.options.mndCookieCustom4Name;

        if (customCookie4Label != '' && customCookie4Name != '') {
            const option = DomAccess.querySelector(document, '*[data-mnd-cookie-type="'+customCookie4Name+'"]');
            const privacyStatusCheckbox = DomAccess.querySelector(option, '.custom-control-input');
            let mndCustomTrackingActive = false;

            if (CookieStorage.getItem(customCookie4Name)) {
                mndCustomTrackingActive = CookieStorage.getItem(customCookie4Name);
            }

            this.toggleCheckbox(customCookie4Name, mndCustomTrackingActive);

            if (privacyStatusCheckbox.checked) {
                this.optInCustom(customCookie4Name);
            } else {
                this.optOutCustom(customCookie4Name);
            }
        }

        const customCookie5Label = this.options.mndCookieCustom5Label;
        const customCookie5Name = this.options.mndCookieCustom5Name;

        if (customCookie5Label != '' && customCookie5Name != '') {
            const option = DomAccess.querySelector(document, '*[data-mnd-cookie-type="'+customCookie5Name+'"]');
            const privacyStatusCheckbox = DomAccess.querySelector(option, '.custom-control-input');
            let mndCustomTrackingActive = false;

            if (CookieStorage.getItem(customCookie5Name)) {
                mndCustomTrackingActive = CookieStorage.getItem(customCookie5Name);
            }

            this.toggleCheckbox(customCookie5Name, mndCustomTrackingActive);

            if (privacyStatusCheckbox.checked) {
                this.optInCustom(customCookie5Name);
            } else {
                this.optOutCustom(customCookie5Name);
            }
        }

        // Set cookie for cookie acceptance
        const cookieName = 'mnd-accepted-'+this.options.mndCookieSuffix;
        CookieStorage.setItem(cookieName, 1, expire);
        CookieStorage.setItem('cookie-preference', 1, expire);

        // Hide settings and banner/modal
        $('#mndSettingsModal').modal('hide');

        const type = this.options.mndCookieType;

        if(type == 'banner') {
            this.closeBanner()
        } else {
            this.closeModal()
        }

        this.reloadAfter();
    }

    optInAll() {
        const expire = this.options.mndCookieExpire;
        const cookieName = 'mnd-accepted-'+this.options.mndCookieSuffix;
        const gaActive = this.options.mndCookieGAActive;
        const fbActive = this.options.mndFbPixelActive;
        const type = this.options.mndCookieType;

        CookieStorage.setItem('cookie-preference', 1, expire); // Shopware default permissions

        if(gaActive) {
            this.optInGA();
        }

        if(fbActive=="true") {
            this.optInFB();
        }

        this.optInSw();

        const customCookie1Label = this.options.mndCookieCustom1Label;
        const customCookie1Name = this.options.mndCookieCustom1Name;
        if (customCookie1Label != '' && customCookie1Name != '') {
            this.optInCustom(customCookie1Name)
        }

        const customCookie2Label = this.options.mndCookieCustom2Label;
        const customCookie2Name = this.options.mndCookieCustom2Name;
        if (customCookie2Label != '' && customCookie2Name != '') {
            this.optInCustom(customCookie2Name)
        }

        const customCookie3Label = this.options.mndCookieCustom3Label;
        const customCookie3Name = this.options.mndCookieCustom3Name;
        if (customCookie3Label != '' && customCookie3Name != '') {
            this.optInCustom(customCookie3Name)
        }

        const customCookie4Label = this.options.mndCookieCustom4Label;
        const customCookie4Name = this.options.mndCookieCustom4Name;
        if (customCookie4Label != '' && customCookie4Name != '') {
            this.optInCustom(customCookie4Name)
        }

        const customCookie5Label = this.options.mndCookieCustom5Label;
        const customCookie5Name = this.options.mndCookieCustom5Name;
        if (customCookie5Label != '' && customCookie5Name != '') {
            this.optInCustom(customCookie5Name)
        }

        CookieStorage.setItem(cookieName, 1, expire);

        // Hide banners/modals
        if(type == 'banner') {
            this.closeBanner()
        } else {
            this.closeModal()
        }

        $('#mndSettingsModal').modal('hide');

        this.reloadAfter();
    }

    optOutAll() {
        this.optOutGA();
        this.optOutFB();
        this.optOutSw();

        const customCookie1Label = this.options.mndCookieCustom1Label;
        const customCookie1Name = this.options.mndCookieCustom1Name;
        if (customCookie1Label != '' && customCookie1Name != '') {
            this.optOutCustom(customCookie1Name)
        }

        const customCookie2Label = this.options.mndCookieCustom2Label;
        const customCookie2Name = this.options.mndCookieCustom2Name;
        if (customCookie2Label != '' && customCookie2Name != '') {
            this.optOutCustom(customCookie2Name)
        }

        const customCookie3Label = this.options.mndCookieCustom3Label;
        const customCookie3Name = this.options.mndCookieCustom3Name;
        if (customCookie3Label != '' && customCookie3Name != '') {
            this.optOutCustom(customCookie3Name)
        }

        const customCookie4Label = this.options.mndCookieCustom4Label;
        const customCookie4Name = this.options.mndCookieCustom4Name;
        if (customCookie4Label != '' && customCookie4Name != '') {
            this.optOutCustom(customCookie4Name)
        }

        const customCookie5Label = this.options.mndCookieCustom5Label;
        const customCookie5Name = this.options.mndCookieCustom5Name;
        if (customCookie5Label != '' && customCookie5Name != '') {
            this.optOutCustom(customCookie5Name)
        }
    }

    optInGA() {
        const gaString = this.options.mndCookieGAIds;
        const gaArray = gaString.split(',');
        const expire = this.options.mndCookieExpire;

        gaArray.forEach(function(el){
            const gaProperty = el;
            const disableStr = 'ga-disable-' + gaProperty;
            if (document.cookie.indexOf(disableStr+'=') >= 0) {
                CookieStorage.removeItem(disableStr);
            }
        });

        CookieStorage.setItem('mnd-ga-opted-in', true, expire);
        CookieStorage.setItem('google-analytics-enabled', 1, expire); // Shopware google analytics permissions

        try {
            const GoogleAnalyticsPlugin = PluginManager.getPluginInstances('GoogleAnalytics');

            if (GoogleAnalyticsPlugin && GoogleAnalyticsPlugin[0]) {
                GoogleAnalyticsPlugin[0].startGoogleAnalytics();
            }
        }
        catch (e) {
        }
    }

    optOutGA() {
        const gaString = this.options.mndCookieGAIds;
        const gaArray = gaString.split(',');
        const expire = this.options.mndCookieExpire;
        const allCookies = document.cookie.split(';');
        const gaCookies = /^(_swag_ga|_gat_gtag)/;

        gaArray.forEach(function(el){
            const gaProperty = el;
            const disableStr = 'ga-disable-' + gaProperty;
            if (document.cookie.indexOf(disableStr+'=') <= 0) {
                CookieStorage.setItem(disableStr, true, expire);
                window[disableStr] = true;
            }
        });

        CookieStorage.removeItem('mnd-ga-opted-in');
        CookieStorage.removeItem('google-analytics-enabled');

        // remove ga cookies
        allCookies.forEach(cookie => {
            const cookieName = cookie.split('=')[0].trim();
            if (!cookieName.match(gaCookies)) {
                return;
            }

            CookieStorage.removeItem(cookieName);
        });

    }

    optInFB() {
        mndCookie.mndSetPixelOptIn();
    }

    optOutFB() {
        mndCookie.mndSetPixelOptOut();
    }

    optInSw() {
        const expire = this.options.mndCookieExpire;
        CookieStorage.setItem('wishlist-enabled', 1, expire);
    }

    optOutSw() {
        CookieStorage.removeItem('wishlist-enabled');
    }

    optInCustom(cookieName) {
        const expire = this.options.mndCookieExpire;
        CookieStorage.setItem(cookieName, true, expire);
    }

    optOutCustom(cookieName) {
        const expire = this.options.mndCookieExpire;
        CookieStorage.setItem(cookieName, false, expire);
    }

    updateQueryString(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }

    reloadAfter() {
        const reloadAfter = this.options.mndCookieReloadAfter;
        const reloadAddUtm = this.options.mndCookieReloadAddUtm;

        if(reloadAfter) {
            let newUrl = this.updateQueryString(window.location.href, 'utm_referrer', encodeURIComponent(document.referrer));

            if(reloadAddUtm) {
                window.location.href = newUrl;
            } else {
                window.location.reload();
            }
        }
    }

    static getCookieStatus(cookieName) {
        const status = CookieStorage.getItem(cookieName);
        return status;
    }
}

window.MndCookie = MndCookie;
