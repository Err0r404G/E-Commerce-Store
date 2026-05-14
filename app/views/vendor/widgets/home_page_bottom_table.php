<?php

class RecentOrdersTable {
    private $title;
    private $filterLink;
    private $orders;

    /**
     * RecentOrdersTable Constructor
     * 
     * @param string $title - Table title (e.g., 'Recent Orders')
     * @param string $filterLink - Link for filter button
     * @param array $orders - Array of order objects with: orderId, customer, date, amount, status, actionLink
     *                        Status can be: 'shipped', 'processing', 'pending', 'cancelled'
     */
    public function __construct($title, $filterLink, $orders = []) {
        $this->title = $title;
        $this->filterLink = $filterLink;
        $this->orders = $orders;
    }

    private function getStatusClass($status) {
        switch(strtolower($status)) {
            case 'shipped':
                return 'status-shipped';
            case 'processing':
                return 'status-processing';
            case 'pending':
                return 'status-pending';
            case 'cancelled':
                return 'status-cancelled';
            default:
                return 'status-pending';
        }
    }

    private function getStatusText($status) {
        return strtoupper($status);
    }

    public function render() {
        echo "
        <div class='recent-orders-card'>
            <div class='table-header'>
                <h3 class='table-title'>{$this->title}</h3>
                <button class='filter-btn' onclick=\"window.location.href='{$this->filterLink}'\">
                    <i class='fas fa-filter'></i>
                    Filter
                </button>
            </div>
            
            <div class='table-wrapper'>
                <table class='orders-table'>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
        ";
        
        foreach ($this->orders as $order) {
            $statusClass = $this->getStatusClass($order['status']);
            $statusText = $this->getStatusText($order['status']);
            
            echo "
                        <tr>
                            <td><span class='order-id'>{$order['orderId']}</span></td>
                            <td>{$order['customer']}</td>
                            <td>{$order['date']}</td>
                            <td><span class='amount'>{$order['amount']}</span></td>
                            <td><span class='status {$statusClass}'>{$statusText}</span></td>
                            <td><a href='{$order['actionLink']}' class='action-link'>Details</a></td>
                        </tr>
            ";
        }
        
        echo "
                    </tbody>
                </table>
            </div>
        </div>
        ";
    }
}

// Usage Example:
// \$orders = [
//     [
//         'orderId' => '#VC-0842',
//         'customer' => 'Alex Thompson',
//         'date' => 'Oct 24, 2024',
//         'amount' => '\$128.50',
//         'status' => 'shipped',
//         'actionLink' => '/order/VC-0842'
//     ],
//     [
//         'orderId' => '#VC-0841',
//         'customer' => 'Sarah Jenkins',
//         'date' => 'Oct 24, 2024',
//         'amount' => '\$342.00',
//         'status' => 'processing',
//         'actionLink' => '/order/VC-0841'
//     ],
//     [
//         'orderId' => '#VC-0840',
//         'customer' => 'Michael Chen',
//         'date' => 'Oct 23, 2024',
//         'amount' => '\$89.99',
//         'status' => 'pending',
//         'actionLink' => '/order/VC-0840'
//     ],
//     [
//         'orderId' => '#VC-0839',
//         'customer' => 'Emma Rodriguez',
//         'date' => 'Oct 23, 2024',
//         'amount' => '\$1,240.00',
//         'status' => 'shipped',
//         'actionLink' => '/order/VC-0839'
//     ]
// ];
//
// \$table = new RecentOrdersTable('Recent Orders', '#', \$orders);
// \$table->render();
?>
