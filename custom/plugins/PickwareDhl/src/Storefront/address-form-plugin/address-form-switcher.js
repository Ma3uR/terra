import { AddressTypeConfigurations } from './address-type-configurations.js';

const cssClasses = {
    hidden: 'd-none',
    visible: 'd-block',
};

export class AddressFormSwitcher {
    constructor(container) {
        this.container = container;
        this.addressFormsByKey = {};
        this.addressFormContainerByKey = {};
        AddressTypeConfigurations.forEach((addressType) => {
            this.addressFormsByKey[addressType.key] = this.container.querySelector(
                `.pickware-dhl-${addressType.key}-address-form`,
            );
            this.addressFormContainerByKey[addressType.key] = this.container.querySelector(
                `.pickware-dhl-${addressType.key}-address-form-container`,
            );
        });
    }

    showAddressForm(addressTypeToShow) {
        this.toggleCompanyForm(!addressTypeToShow.isDhlSpecificAddressType);
        AddressTypeConfigurations
            .filter((addressType) => addressType.key !== addressTypeToShow.key)
            .forEach((addressType) => this.toggleAddressForm(addressType, false));
        this.toggleAddressForm(addressTypeToShow, true);

        if (addressTypeToShow.isDhlSpecificAddressType) {
            // There is unwanted behaviour in combination with Shopware's FormFieldTogglePlugin: the special address
            // form fields are disabled before they are removed in this method. And they are also enabled before they
            // are appended in this method again (hence they are _not_ enabled by Shopware's FormFieldTogglePlugin).
            // Therefore we have to enable them manually.
            this.enableSpecialAddressFormFields(addressTypeToShow);
        }
    }

    /**
     * Toggles an address form by removing it from the DOM or adding it back to its parent container.
     *
     * @param {object} addressType
     * @param {boolean} show
     */
    toggleAddressForm(addressType, show) {
        const addressForm = this.addressFormsByKey[addressType.key];
        const addressFormContainer = this.addressFormContainerByKey[addressType.key];

        if (!addressForm || !addressFormContainer) {
            return;
        }

        if (!show) {
            addressForm.remove();

            return;
        }

        if (addressFormContainer) {
            addressFormContainer.appendChild(addressForm);
        }
    }

    enableSpecialAddressFormFields(addressType) {
        const fields = this.container.querySelectorAll(
            `.pickware-dhl-${addressType.key}-address-form-container input`,
        );
        fields.forEach((field) => {
            field.disabled = '';
        });
        const countrySelect = this.container.querySelector(`.pickware-dhl-${addressType.key}-country`);
        if (countrySelect) {
            countrySelect.disabled = '';
        }
    }

    toggleCompanyForm(show) {
        // Toggle shopware's company form via CSS. These form values are optional. Therefore we can ignore the values
        // and simply hide them if not needed.
        const companyForm = this.container.querySelector(
            '.address-contact-type-company, .js-field-toggle-contact-type-company',
        );
        if (!companyForm) {
            return;
        }

        if (show) {
            this.showNode(companyForm);
        } else {
            this.hideNode(companyForm);
        }
    }

    hideNode(node) {
        if (!node) {
            return;
        }

        node.classList.remove(cssClasses.visible);
        node.classList.add(cssClasses.hidden);
    }

    showNode(node) {
        if (!node) {
            return;
        }

        node.classList.remove(cssClasses.hidden);
        node.classList.add(cssClasses.visible);
    }
}
