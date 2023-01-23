import TanmarNgProductReviewsTestmailApiService
    from '../service/tanmar-productreviews-testmail.api.service';

const { Application } = Shopware;

const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'TanmarNgProductReviewsTestmailApiService',
    (container) => new TanmarNgProductReviewsTestmailApiService(initContainer.httpClient, container.loginService)
);
