import * as React from 'react';
import PropertyRenderer from './type/property-renderer';
import { ConfigValuesType } from './config-form';
import { FlagbitLocales } from './locales';
import postProperty from './api/post-property';

type CategoryInfo = {
    categoryCode: string;
    onSaveRef?: (saveFn: () => Promise<void>) => void;
};

export type ChangeState = (code: string, locale: string, value: any) => void;
type PropertyValueType = {
    [locale: string]: {
        locale: string | null;
        data: any;
    };
};
export type PropertyValuesType = {
    [code: string]: PropertyValueType;
};
type StateType = {
    propertyValues: PropertyValuesType;
    configValues: ConfigValuesType;
};
type ConfigResponse = { config: ConfigValuesType };
type PropertyResponse = { properties: PropertyValuesType };

const FetcherRegistry = require('pim/fetcher-registry');

class PropertyForm extends React.Component<CategoryInfo> {
    state: StateType = {
        propertyValues: {},
        configValues: {},
    };

    componentDidMount?(): void {
        FetcherRegistry.getFetcher('flagbit-category-config')
            .fetch(1, { cached: false })
            .then((response: ConfigResponse) => {
                this.setState({
                    propertyValues: this.state.propertyValues,
                    configValues: Array.isArray(response.config) ? {} : response.config,
                });
            });

        FetcherRegistry.getFetcher('flagbit-category-property')
            .fetch(this.props.categoryCode, { cached: false })
            .then((response: PropertyResponse) => {
                this.setState({
                    propertyValues: Array.isArray(response.properties) ? {} : response.properties,
                    configValues: this.state.configValues,
                });
            });

        if (this.props.onSaveRef) {
            this.props.onSaveRef(() => this.saveProperties());
        }
    }

    async saveProperties(): Promise<void> {
        if (Object.keys(this.state.propertyValues).length === 0) {
            return;
        }
        await postProperty.post(this.props.categoryCode, this.state.propertyValues);
    }

    render(): React.ReactNode {
        const onChange: ChangeState = (code: string, locale: string, value: any) => {
            const state = this.state;

            const propertyData = state.propertyValues[code] || { [locale]: { locale: locale, data: value } };
            const configEntry = state.configValues[code];
            if (!configEntry) {
                return;
            }
            const isLocalizable = configEntry.isLocalizable;

            propertyData[locale] = { locale: locale, data: value };

            FlagbitLocales.locales.getEnabledLocales(isLocalizable).forEach((currentLocale) => {
                if (!(currentLocale in propertyData)) {
                    propertyData[currentLocale] = { locale: currentLocale, data: '' };
                }
            });

            state.propertyValues[code] = propertyData;

            this.setState(state);
        };
        onChange.bind(this);

        const propertyRenderer = new PropertyRenderer(onChange, this.state.propertyValues, this.state.configValues);

        return (
            <React.Fragment>
                {propertyRenderer.render()}
                <input
                    id={'flagbit_category_properties_json'}
                    name={'flagbit_category_properties_json'}
                    hidden={true}
                    readOnly={true}
                    value={JSON.stringify(this.state.propertyValues)}
                />
            </React.Fragment>
        );
    }
}

export default PropertyForm;
