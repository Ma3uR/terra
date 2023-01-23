import { makeContainer, makeNode } from '../../../test/storefront/make-node.js';
import { AddressFormSwitcher } from './address-form-switcher.js';
import { AddressTypeConfigurations } from './address-type-configurations.js';

const container = makeContainer();

const packStationAddressType = AddressTypeConfigurations.find(
    (addressType) => addressType.key === 'packstation',
);
const regularAddressType = AddressTypeConfigurations.find(
    (addressType) => addressType.key === 'regular',
);

describe('AddressFormSwitcher', () => {
    let regularAddressForm;
    let regularAddressFormContainer;
    let packstationAddressForm;
    let packstationAddressFormContainer;
    let postOfficeAddressForm;
    let postOfficeAddressFormContainer;

    beforeEach(async () => {
        jest.clearAllMocks();

        regularAddressForm = makeNode();
        regularAddressFormContainer = makeNode();
        packstationAddressForm = makeNode();
        packstationAddressFormContainer = makeNode();
        postOfficeAddressForm = makeNode();
        postOfficeAddressFormContainer = makeNode();

        container.nodesBySelector = {
            '.pickware-dhl-regular-address-form': regularAddressForm,
            '.pickware-dhl-regular-address-form-container': regularAddressFormContainer,
            '.pickware-dhl-packstation-address-form': packstationAddressForm,
            '.pickware-dhl-packstation-address-form-container': packstationAddressFormContainer,
            '.pickware-dhl-post-office-address-form': postOfficeAddressForm,
            '.pickware-dhl-post-office-address-form-container': postOfficeAddressFormContainer,
        };
    });

    describe('showAddressForm()', () => {
        it('removes all address forms that do not correspond to the given address type', async () => {
            expect.assertions(7);

            jest.spyOn(regularAddressForm, 'remove').mockImplementation();
            jest.spyOn(regularAddressFormContainer, 'appendChild').mockImplementation();
            jest.spyOn(packstationAddressForm, 'remove').mockImplementation();
            jest.spyOn(packstationAddressFormContainer, 'appendChild').mockImplementation();
            jest.spyOn(postOfficeAddressForm, 'remove').mockImplementation();
            jest.spyOn(postOfficeAddressFormContainer, 'appendChild').mockImplementation();

            const addressFormSwitcher = new AddressFormSwitcher(container);
            addressFormSwitcher.showAddressForm(packStationAddressType);

            expect(regularAddressForm.remove).toHaveBeenCalledTimes(1);
            expect(packstationAddressForm.remove).toHaveBeenCalledTimes(0);
            expect(postOfficeAddressForm.remove).toHaveBeenCalledTimes(1);

            expect(regularAddressFormContainer.appendChild).toHaveBeenCalledTimes(0);
            expect(postOfficeAddressFormContainer.appendChild).toHaveBeenCalledTimes(0);
            expect(packstationAddressFormContainer.appendChild).toHaveBeenCalledTimes(1);
            expect(packstationAddressFormContainer.appendChild).toHaveBeenCalledWith(packstationAddressForm);
        });

        describe('when the given address type is not a DHL special address type', () => {
            it('shows the company form', async () => {
                expect.assertions(2);

                const companyForm = makeNode();
                jest.spyOn(companyForm.classList, 'remove').mockImplementation();
                jest.spyOn(companyForm.classList, 'add').mockImplementation();

                container.nodesBySelector[
                    '.address-contact-type-company, .js-field-toggle-contact-type-company'
                ] = companyForm;

                const addressFormSwitcher = new AddressFormSwitcher(container);
                addressFormSwitcher.showAddressForm(regularAddressType);

                expect(companyForm.classList.remove).toHaveBeenCalledWith('d-none');
                expect(companyForm.classList.add).toHaveBeenCalledWith('d-block');
            });
        });

        describe('when the given address type is a DHL special address type', () => {
            it('enables all special address form fields', async () => {
                expect.assertions(3);

                const specialAddressField1 = { disabled: true };
                const specialAddressField2 = { disabled: true };
                container.nodesBySelector['.pickware-dhl-packstation-address-form-container input'] = [
                    specialAddressField1,
                    specialAddressField2,
                ];
                const specialAddressCountryField = { disabled: true };
                container.nodesBySelector['.pickware-dhl-packstation-country'] = specialAddressCountryField;

                const addressFormSwitcher = new AddressFormSwitcher(container);
                addressFormSwitcher.showAddressForm(packStationAddressType);

                expect(specialAddressField1.disabled).toBe('');
                expect(specialAddressField2.disabled).toBe('');
                expect(specialAddressCountryField.disabled).toBe('');
            });

            it('hides the company form', async () => {
                expect.assertions(2);

                const companyForm = makeNode();
                jest.spyOn(companyForm.classList, 'remove').mockImplementation();
                jest.spyOn(companyForm.classList, 'add').mockImplementation();

                container.nodesBySelector[
                    '.address-contact-type-company, .js-field-toggle-contact-type-company'
                ] = companyForm;

                const addressFormSwitcher = new AddressFormSwitcher(container);
                addressFormSwitcher.showAddressForm(packStationAddressType);

                expect(companyForm.classList.add).toHaveBeenCalledWith('d-none');
                expect(companyForm.classList.remove).toHaveBeenCalledWith('d-block');
            });
        });
    });
});
