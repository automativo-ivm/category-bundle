import $ from 'jquery';

const router = require('pim/router');

class PostProperty {
    post(categoryCode: string, properties: any): JQuery.jqXHR {
        return $.ajax({
            url: router.generate('flagbit_category.internal_api.category_property_post', { identifier: categoryCode }),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(properties),
        });
    }
}

export default new PostProperty();
