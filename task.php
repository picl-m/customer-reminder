<?php

$goods = [
    1 => 180, // Biofinity (6 lenses)
    2 => 90, // Biofinity (3 lenses)
    3 => 30, // Focus Dailies (30 lenses)
];

// Customers orders with customer ID as key
$customerOrders = [
    1 => [
        '2015-04-01' => [
            [1, 2, '-2.00'],
            [1, 2, '-3.00'],
        ],
    ],
    2 => [
        '2014-10-01' => [
            [3, 2, '-1.50'],
            [3, 2, '-3.50'],
            ],
        '2015-01-01' => [
            [3, 2, '-1.50'],
            [3, 2, '-3.50'],
            ],
        '2015-04-15' => [
            [3, 1, '-1.50'],
            [3, 1, '-3.50'],
        ],
    ],
    3 => [
        '2014-08-01' => [
            [2, 2, '+0.50'],
        ],
    ],
];

function getCustomerRemindDate($orders, $goods) {
    // factor of how long customer uses lenses compared to their standard duration
    // smaller than 1 = customer uses lenses quickly, larger than 1 = customer uses lenses longer
    $historyFactor = 1;

    if (count($orders) > 1) {
        $orderHistoryFactors = [];

        $lastDate = null;
        $prevOrder = null;

        foreach($orders as $index => $order) {
            $orderDate = new DateTime($index);

            if (isset($lastDate)) {
                foreach ($prevOrder as $item) {
                    // multiply goods duration by quantity
                    $expectedDuration = $goods[$item[0]] * $item[1];

                    if (count($prevOrder) === 2) {
                        // if customer ordered two lenses assume they use a different lense
                        // on each eye and make the duration two times longer
                        $expectedDuration *= 2;
                    }
                    
                    $daysDiff = $lastDate->diff($orderDate)->days;
                    $orderHistoryFactors[] .= $daysDiff / $expectedDuration;
                }
            }

            $prevOrder = $order;
            $lastDate = $orderDate;
        }

        // set factor as the average factor between all customer orders
        $historyFactor = array_sum($orderHistoryFactors) / count($orderHistoryFactors);
    }


    $lastOrder = end($orders);
    $standardDuration = null;

    // loop finds item with shortest duration and sets it as the standard duration
    // so the customer gets reminded as soon as any of his items might run out
    foreach ($lastOrder as $item) {
        // multiply most recent orders items duration by their quantity
        $itemDuration = $goods[$item[0]] * $item[1];

        if (count($lastOrder) === 2) {
            // if customer ordered two lenses assume they use a different lense
            // on each eye and make the duration two times longer
            $itemDuration *= 2;
        }

        if (!isset($standardDuration) || $itemDuration < $standardDuration) {
            $standardDuration = $itemDuration;
        }
    }

    $finalDuration = floor($standardDuration * $historyFactor);

    // return the date when a reminder should be sent
    $remindDate = new DateTime(array_key_last($orders));
    $remindDate->modify("+$finalDuration days");
    return $remindDate;
}

// go trough all customers and use the function
foreach ($customerOrders as $customer => $orders) {
    $remindDate = getCustomerRemindDate($orders, $goods);
    echo 'REMIND CUSTOMER ', $customer, ' ON: ', date_format($remindDate, 'Y-m-d'), PHP_EOL;
}