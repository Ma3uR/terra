<?php

namespace Tanmar\ProductReviews\Components;

use Exception;

/**
 * Helferklasse für SampleArticles
 *
 * damit von anderen Plugins auch drauf zugegriffen werden kann
 */
class TanmarProductReviewsHelper {

    public function getOrderHash($order) {
        if (!is_object($order)) {
            return 'orderdata_error_no_hash';
        }
        try {
            $custom = $order->getOrderCustomer();

            $check_order_array = array(
                $custom->getCustomerNumber(),
                $custom->getEmail(),
                $order->getOrderNumber(),
            );

            $hash_string = implode('#', $check_order_array);
            $hash = sha1($hash_string);
        } catch (Exception $ex) {
            $hash = 'orderdata_error_hash_exception';
        }
        return $hash;
    }

    public function getReviewDetails($orderID) {
        $result = [
            'tm_review_status' => 0,
            'tm_review_sent' => 0,
            'orderhash' => 0,
            'vouchercode' => 0,
            'tm_review_optin' => 0,
        ];

        $data = [
            'status' => $result['tm_review_status'],
            'sent' => $result['tm_review_sent'],
            'hash' => $result['orderhash'],
            'voucher' => $result['vouchercode'],
            'optin' => $result['tm_review_optin'],
        ];
        return $data;
    }

}
