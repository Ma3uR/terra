const ApiService = Shopware.Classes.ApiService;

class TanmarNgProductReviewsTestmailApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'tanmar/productreviews/testmail') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Sends a test invitation mail to the store owner
     *
     * @param {string|null} salesChannelId
     * @returns {Promise|Object}
     */
    testInvitation(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/invitation`;

        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * Sends a test notification mail to the store owner
     *
     * @param {string|null} salesChannelId
     * @returns {Promise|Object}
     */
    testNotification(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/notification`;

        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * Sends a test coupon/thank you mail to the store owner
     *
     * @param {string|null} salesChannelId
     * @returns {Promise|Object}
     */
    testCoupon(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/coupon`;

        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

}

export default TanmarNgProductReviewsTestmailApiService;
