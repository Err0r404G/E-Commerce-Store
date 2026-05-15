<?php
require_once __DIR__ . '/../models/SellerModel.php';

class SellerController {
    private $sellerModel;

    public function __construct($db) {
        $this->sellerModel = new SellerModel($db);
    }

    public function shopProfile() {
        $seller_id = $_SESSION['user_id'];
        $profile   = $this->sellerModel->getShopProfile($seller_id);

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'shop_name'          => $_POST['shop_name'],
                'business_category'  => $_POST['business_category'],
                'shop_description'   => $_POST['shop_description'],
                'shop_logo'          => $this->uploadLogo()
            ];

            $this->sellerModel->saveShopProfile($seller_id, $data);
            header('Location: ?page=shop_profile&success=1');
            exit;
        }

        // Load view
        require_once __DIR__ . '/../views/vendor/vendor_shop_profile_screen.php';
    }

    private function uploadLogo() {
        if (!empty($_FILES['shop_logo']['name'])) {
            $upload_dir = __DIR__ . '/../../public/uploads/logos/';
            $filename   = time() . '_' . $_FILES['shop_logo']['name'];
            move_uploaded_file($_FILES['shop_logo']['tmp_name'], $upload_dir . $filename);
            return 'uploads/logos/' . $filename;
        }
        return null;
    }
}