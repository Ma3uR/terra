import { Plugin } from '@pickware/shopware-storefront-adapter';

import { AddressFormSwitcher } from './address-form-switcher.js';
import { AddressTypeConfigurations } from './address-type-configurations.js';

export class AddressFormPlugin extends Plugin {
    init() {
        // Use a top level address form container to scope all DOM selections underneath it. There may be multiple
        // address forms on the current document (billing address form and shipping address form). But there is only
        // _one_ address form (the latter) that displays special address forms.
        this.container = this.el.closest('.pickware-dhl-component-address-form-container');
        if (!this.container) {
            return;
        }
        this.addressFormSwitcher = new AddressFormSwitcher(this.container);

        this.initialiseSpecialAddressForms();
        this.selectInitialAddressType();
        this.addChangeListenerToRadioButtons();
    }

    initialiseSpecialAddressForms() {
        AddressTypeConfigurations
            .filter((addressType) => addressType.isDhlSpecificAddressType)
            .forEach((addressType) => {
                const specialAddressStreetField = this.container.querySelector(
                    `.pickware-dhl-${addressType.key}-street`,
                );
                const specialAddressNumberField = this.container.querySelector(
                    `.pickware-dhl-${addressType.key}-number`,
                );
                if (!specialAddressStreetField || !specialAddressNumberField) {
                    return;
                }

                this.setInitialSpecialAddressNumberValue(
                    addressType,
                    specialAddressStreetField,
                    specialAddressNumberField,
                );
                this.addChangeListenerToSpecialAddressNumberField(
                    addressType,
                    specialAddressStreetField,
                    specialAddressNumberField,
                );
                this.setHiddenSpecialAddressValues(addressType);
            });
    }

    /**
     * Copies the special address number (packstation number, post office number) from the address street field to the
     * special address number file,d if the regular address street value matches the special address street prefix (i.e.
     * 'Packstation', 'Postfiliale').
     */
    setInitialSpecialAddressNumberValue(addressType, streetField, numberField) {
        const regexString = `${addressType.streetValuePrefix} (.*)`;
        const regex = new RegExp(regexString, 'g');
        const matches = regex.exec(streetField.value);
        if (!matches || matches.length < 1) {
            numberField.value = '';

            return;
        }

        numberField.value = matches[1];
    }

    /**
     * Adds a 'change' listener to each special address number field that updates the respective special address street
     * field with its value to form the actual street value that will be submitted: "{prefix} {number}".
     */
    addChangeListenerToSpecialAddressNumberField(addressType, streetField, numberField) {
        numberField.addEventListener('change', () => {
            streetField.value = `${addressType.streetValuePrefix} ${numberField.value}`;
        });
    }

    setHiddenSpecialAddressValues(addressType) {
        // Country is fixed to Germany
        const countrySelectSelector = `.pickware-dhl-${addressType.key}-country`;
        const countrySelect = this.container.querySelector(countrySelectSelector);
        const countryOptionGermany = this.container.querySelector(`${countrySelectSelector} option[iso="DE"]`);
        if (countrySelect && countryOptionGermany) {
            countrySelect.value = countryOptionGermany.getAttribute('value');
        }
    }

    selectInitialAddressType() {
        // All street fields initially hold the same information. Hence select _a_ street field with a static selector
        const addressStreetField = this.container.querySelector('.pickware-dhl-packstation-street');
        if (!addressStreetField) {
            return;
        }

        const matchedSpecialAddressType = AddressTypeConfigurations.find(
            (addressType) => addressStreetField.value
                && addressStreetField.value.startsWith(addressType.streetValuePrefix),
        );
        const regularAddressType = AddressTypeConfigurations.find(
            (addressType) => !addressType.isDhlSpecificAddressType,
        );
        // Select the regular address type by default if no special address type could be matched
        const initialAddressType = matchedSpecialAddressType || regularAddressType;

        const radioButton = this.container.querySelector(`.pickware-dhl-radio-button-${initialAddressType.key}`);
        if (radioButton) {
            radioButton.checked = 'checked';
            this.selectAddressType(initialAddressType.key);
        }
    }

    addChangeListenerToRadioButtons() {
        const radioButtons = this.container.querySelectorAll('input[type="radio"]');
        radioButtons.forEach((radioButton) => {
            // Radio button value must equal a address type key
            radioButton.addEventListener('change', () => this.selectAddressType(radioButton.value));
        });
    }

    selectAddressType(addressTypeKey) {
        const addressType = AddressTypeConfigurations.find(
            (addressType) => addressType.key === addressTypeKey,
        );
        this.addressFormSwitcher.showAddressForm(addressType);
    }
}
