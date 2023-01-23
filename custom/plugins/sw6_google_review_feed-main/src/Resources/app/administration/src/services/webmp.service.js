const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class WebmpService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'webmp') {
        super(httpClient, loginService, apiEndpoint);
    }

    generateFeed(data = {}) {
        return this.httpClient
            .post(`${this.getApiBasePath()}/generate-feed`, data, {headers: this.getBasicHeaders()})
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('WebmpService', (container) => {
    const initContainer = Application.getContainer('init');

    return new WebmpService(initContainer.httpClient, container.loginService);
});
