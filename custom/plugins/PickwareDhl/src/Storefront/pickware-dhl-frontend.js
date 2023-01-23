import { AddressFormPlugin } from './address-form-plugin/address-form-plugin.js';

const PluginManager = window.PluginManager;
PluginManager.register(
    'PickwareDhlAddressFormPlugin',
    AddressFormPlugin,
    '[pickware-dhl-address-form]',
);
