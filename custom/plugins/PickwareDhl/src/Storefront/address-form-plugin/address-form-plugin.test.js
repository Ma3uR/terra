import { makeContainer, makeNode } from '../../../test/storefront/make-node.js';
import { AddressFormPlugin } from './address-form-plugin.js';
import { AddressTypeConfigurations } from './address-type-configurations.js';

jest.mock('@pickware/shopware-storefront-adapter', () => ({
    Plugin: jest.fn().mockImplementation(function (element) {
        this.el = element;
    }),
}));

const container = makeContainer();

const packStationAddressType = AddressTypeConfigurations.find(
    (addressType) => addressType.key === 'packstation',
);
const regularAddressType = AddressTypeConfigurations.find(
    (addressType) => addressType.key === 'regular',
);

describe('AddressFormPlugin', () => {
    let radioGroup;
    let radioButtonRegular;
    let radioButtonPackstation;
    let radioButtonPostOffice;
    let streetFieldPackstation;
    let streetFieldPostOffice;
    let specialNumberFieldPackstation;
    let specialNumberFieldPostOffice;

    beforeEach(async () => {
        jest.clearAllMocks();

        radioGroup = makeNode();
        radioGroup.closest = () => container;

        radioButtonRegular = makeNode();
        radioButtonPackstation = makeNode();
        radioButtonPostOffice = makeNode();
        streetFieldPackstation = makeNode();
        streetFieldPostOffice = makeNode();
        specialNumberFieldPackstation = makeNode();
        specialNumberFieldPostOffice = makeNode();

        container.nodesBySelector = {
            '.pickware-dhl-radio-button-regular': radioButtonRegular,
            '.pickware-dhl-radio-button-packstation': radioButtonPackstation,
            '.pickware-dhl-radio-button-post-office': radioButtonPostOffice,
            '.pickware-dhl-packstation-street': streetFieldPackstation,
            '.pickware-dhl-post-office-street': streetFieldPostOffice,
            '.pickware-dhl-packstation-number': specialNumberFieldPackstation,
            '.pickware-dhl-post-office-number': specialNumberFieldPostOffice,
        };
    });

    describe('when the plugin is initialized', () => {
        describe('when the special address number fields are initialized', () => {
            let radioGroupPlugin;

            beforeEach(async () => {
                radioGroupPlugin = new AddressFormPlugin(radioGroup);
            });

            it(
                'sets the initial special address number value when the street value corresponds to a special address',
                async () => {
                    expect.assertions(2);

                    streetFieldPackstation.value = 'Packstation PCKST';
                    radioGroupPlugin.init();

                    expect(specialNumberFieldPackstation.value).toBe('PCKST');
                    expect(specialNumberFieldPostOffice.value).toBe('');
                },
            );

            it('adds a \'change\' listener to all special address number fields', async () => {
                expect.assertions(2);

                jest.spyOn(specialNumberFieldPackstation, 'addEventListener').mockImplementation();
                jest.spyOn(specialNumberFieldPostOffice, 'addEventListener').mockImplementation();

                radioGroupPlugin.init();

                expect(specialNumberFieldPackstation.addEventListener).toHaveBeenCalledWith(
                    'change',
                    expect.any(Function),
                );
                expect(specialNumberFieldPostOffice.addEventListener).toHaveBeenCalledWith(
                    'change',
                    expect.any(Function),
                );
            });

            it('sets the hidden address field values (country selection)', async () => {
                expect.assertions(2);

                const countryFieldPackstation = makeNode();
                container.nodesBySelector['.pickware-dhl-packstation-country'] = countryFieldPackstation;
                const countryFieldPostOffice = makeNode();
                container.nodesBySelector['.pickware-dhl-post-office-country'] = countryFieldPostOffice;
                const countryOptionGermany = makeNode();
                countryOptionGermany.getAttribute = () => 'germany';
                container.nodesBySelector['.pickware-dhl-packstation-country option[iso="DE"]'] = countryOptionGermany;
                container.nodesBySelector['.pickware-dhl-post-office-country option[iso="DE"]'] = countryOptionGermany;

                radioGroupPlugin.init();

                expect(countryFieldPackstation.value).toBe('germany');
                expect(countryFieldPostOffice.value).toBe('germany');
            });
        });

        describe('when the initial address form is selected', () => {
            let radioGroupPlugin;

            beforeEach(async () => {
                radioGroupPlugin = new AddressFormPlugin(radioGroup);
                radioGroupPlugin.init();
                radioGroupPlugin.addressFormSwitcher = {
                    showAddressForm: jest.fn(),
                };
                delete radioButtonRegular.checked;
                delete radioButtonPackstation.checked;
                delete radioButtonPostOffice.checked;
            });

            describe('when the address is a regular address', () => {
                beforeEach(async () => {
                    streetFieldPackstation.value = 'Regular Street 123';
                });

                it('displays the regular address form', async () => {
                    expect.assertions(2);

                    radioGroupPlugin.selectInitialAddressType();

                    expect(radioGroupPlugin.addressFormSwitcher.showAddressForm).toHaveBeenCalledTimes(1);
                    expect(radioGroupPlugin.addressFormSwitcher.showAddressForm).toHaveBeenCalledWith(
                        regularAddressType,
                    );
                });

                it('checks the respective radio button', async () => {
                    expect.assertions(3);

                    radioGroupPlugin.selectInitialAddressType();

                    expect(radioButtonRegular.checked).toBe('checked');
                    expect(radioButtonPackstation.checked).toBeUndefined();
                    expect(radioButtonPostOffice.checked).toBeUndefined();
                });
            });

            describe('when the address is a special address', () => {
                beforeEach(async () => {
                    streetFieldPackstation.value = 'Packstation PCKST';
                });

                it('displays the respective special address form', async () => {
                    expect.assertions(2);

                    radioGroupPlugin.selectInitialAddressType();

                    expect(radioGroupPlugin.addressFormSwitcher.showAddressForm).toHaveBeenCalledTimes(1);
                    expect(radioGroupPlugin.addressFormSwitcher.showAddressForm).toHaveBeenCalledWith(
                        packStationAddressType,
                    );
                });

                it('checks the respective radio button', async () => {
                    expect.assertions(3);

                    radioGroupPlugin.selectInitialAddressType();

                    expect(radioButtonRegular.checked).toBeUndefined();
                    expect(radioButtonPackstation.checked).toBe('checked');
                    expect(radioButtonPostOffice.checked).toBeUndefined();
                });
            });
        });

        it('adds a \'change\' listener to all radio buttons', async () => {
            expect.assertions(3);

            jest.spyOn(radioButtonRegular, 'addEventListener').mockImplementation();
            jest.spyOn(radioButtonPackstation, 'addEventListener').mockImplementation();
            jest.spyOn(radioButtonPostOffice, 'addEventListener').mockImplementation();
            container.nodesBySelector['input[type="radio"]'] = [
                radioButtonRegular,
                radioButtonPackstation,
                radioButtonPostOffice,
            ];

            const radioGroupPlugin = new AddressFormPlugin(radioGroup);
            radioGroupPlugin.init();

            expect(radioButtonRegular.addEventListener).toHaveBeenCalledWith('change', expect.any(Function));
            expect(radioButtonPackstation.addEventListener).toHaveBeenCalledWith('change', expect.any(Function));
            expect(radioButtonPostOffice.addEventListener).toHaveBeenCalledWith('change', expect.any(Function));
        });
    });

    describe('when a special address number field emits a \'change\' event', () => {
        it('updates the respective street field value', async () => {
            expect.assertions(1);

            jest.spyOn(specialNumberFieldPackstation, 'addEventListener').mockImplementation();
            const radioGroupPlugin = new AddressFormPlugin(radioGroup);
            radioGroupPlugin.init();

            const numberFieldChangeEventHandler = specialNumberFieldPackstation.addEventListener.mock.calls[0][1];
            specialNumberFieldPackstation.value = 'new-value';
            numberFieldChangeEventHandler();

            expect(streetFieldPackstation.value).toBe('Packstation new-value');
        });
    });

    describe('when a radio button emits a \'change\' event', () => {
        it('shows the corresponding address form', async () => {
            expect.assertions(2);

            const radioGroupPlugin = new AddressFormPlugin(radioGroup);
            radioGroupPlugin.init();
            jest.spyOn(radioGroupPlugin.addressFormSwitcher, 'showAddressForm').mockImplementation();

            radioGroupPlugin.selectAddressType('packstation');

            expect(radioGroupPlugin.addressFormSwitcher.showAddressForm).toHaveBeenCalledTimes(1);
            expect(radioGroupPlugin.addressFormSwitcher.showAddressForm).toHaveBeenCalledWith(packStationAddressType);
        });
    });
});
