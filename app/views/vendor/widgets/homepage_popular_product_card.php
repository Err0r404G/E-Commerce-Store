<?php

class PopularProductCard {
    private $title;
    private $viewAllLink;
    private $products;

    /**
     * PopularProductCard Constructor
     * 
     * @param string $title - Card title (e.g., 'Popular Products')
     * @param string $viewAllLink - Link for "View All" button
     * @param array $products - Array of product objects with: image, name, sales, price
     */
    public function __construct($title, $viewAllLink, $products = []) {
        $this->title = $title;
        $this->viewAllLink = $viewAllLink;
        $this->products = $products;
    }

    public function render() {
        echo "
        <div class='popular-products-card'>
            <div class='popular-header'>
                <h3 class='popular-title'>{$this->title}</h3>
                <a href='{$this->viewAllLink}' class='view-all-link'>View All</a>
            </div>
            
            <div class='popular-products-list'>
        ";
        
        foreach ($this->products as $product) {
            echo "
                <div class='popular-product-item'>
                    <div class='popular-product-image'>
                        <img src='{$product['image']}' alt='{$product['name']}'>
                    </div>
                    
                    <div class='popular-product-info'>
                        <h4 class='popular-product-name'>{$product['name']}</h4>
                        <p class='popular-product-sales'>{$product['sales']} Sales</p>
                    </div>
                    
                    <div class='popular-product-price'>
                        {$product['price']}
                    </div>
                </div>
            ";
        }
        
        echo "
            </div>
        </div>
        ";
    }
}

// Usage Example:
// \$products = [
//     [
//         'image' => '/path/to/watch.jpg',
//         'name' => 'Minimalist Watch',
//         'sales' => '328 Sales',
//         'price' => '\$12,840'
//     ],
//     [
//         'image' => '/path/to/headphones.jpg',
//         'name' => 'Pro Headphones',
//         'sales' => '312 Sales',
//         'price' => '\$15,600'
//     ],
//     [
//         'image' => '/path/to/runners.jpg',
//         'name' => 'Sport Runners',
//         'sales' => '256 Sales',
//         'price' => '\$8,960'
//     ]
// ];
//
// \$card = new PopularProductCard('Popular Products', '/products', \$products);
// \$card->render();
?>
