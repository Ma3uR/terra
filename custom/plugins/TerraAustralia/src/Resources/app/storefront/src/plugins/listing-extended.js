import DomAccess from 'src/helper/dom-access.helper';
import FilterBasePlugin from 'src/plugin/listing/filter-base.plugin';

export default class ListingExtended extends FilterBasePlugin {

    init() {
        this._initSelect();
        this.tempValue = null;
    }

    _initSelect() {
        this.trselect = DomAccess.querySelectorAll(this.el,  '.js-tr-listingperpage', false);

        if (this.trselect) {

            this.trselect.forEach((sel) => {
                sel.value = this.tempValue || this.options.perpage;
            });

            this._registerTrSelectEvents();
        }
    }

    /**
     * @private
     */
    _registerTrSelectEvents() {
        this.trselect.forEach((sel) => {
            sel.addEventListener('change', this.onChangePage.bind(this));
        });
    }

    onChangePage(event) {
        this.tempValue = event.target.value;
        this.listing.changeListing();
        //this.tempValue = null;
    }

    /**
     * @public
     */
    reset() {
    }

    /**
     * @public
     */
    resetAll() {
    }

    getValues() {
        if (this.tempValue !== null) {
            return { perpage: this.tempValue };
        }
        return { perpage: this.options.perpage };
    }

    afterContentChange() {
        this._initSelect();
    }

    /**
     * @return {Array}
     * @public
     */
    getLabels() {
        return [];
    }

}
