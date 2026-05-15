<?php

require_once __DIR__ . '/../../views/auth/model/UserModel.php';

class DeliveryManagerController
{
    private UserModel $users;

    public function __construct(mysqli $conn)
    {
        $this->users = new UserModel($conn);
    }

    public function showSettingsAjax(): void
    {
        $deliveryManagerId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->users->findDeliveryManagerProfile($deliveryManagerId);

        if (!$profile) {
            http_response_code(404);
            echo '<p class="admin-error">Delivery manager profile not found.</p>';
            return;
        }

        require __DIR__ . '/../../views/deliveryManager/partials/settings.php';
    }

    public function showAgentsAjax(): void
    {
        $agents = $this->users->getDeliveryAgents();

        require __DIR__ . '/../../views/deliveryManager/partials/agents.php';
    }

    public function showZonesAjax(): void
    {
        $zones = $this->users->getDeliveryZones();

        require __DIR__ . '/../../views/deliveryManager/partials/zones.php';
    }

    public function profileAction(): void
    {
        $deliveryManagerId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->users->findDeliveryManagerProfile($deliveryManagerId);

        if (!$profile) {
            $this->jsonResponse(['success' => false, 'message' => 'Delivery manager profile not found.'], 404);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $errors = [];

        if ($name === '' || $email === '') {
            $errors[] = 'Please fill in name and email.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($this->users->emailExistsForAnotherUser($email, $deliveryManagerId)) {
            $errors[] = 'Another account already uses this email.';
        }

        $passwordHash = null;
        $wantsPasswordChange = $currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '';

        if ($wantsPasswordChange) {
            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errors[] = 'Fill in current password, new password, and confirm password to change password.';
            } elseif (!password_verify($currentPassword, $profile['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirm password do not match.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        $imagePath = null;
        if (!$errors) {
            $imagePath = $this->uploadProfileImage($errors);
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
            return;
        }

        $updated = $this->users->updateDeliveryManagerProfile($deliveryManagerId, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'profile_pic' => $imagePath,
            'password_hash' => $passwordHash,
        ]);

        if (!$updated) {
            $this->jsonResponse(['success' => false, 'message' => 'Could not update profile. Please try again.'], 422);
            return;
        }

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;

        if ($imagePath !== null) {
            $_SESSION['user']['profile_pic'] = $imagePath;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'name' => $name,
            'profile_pic' => $imagePath ?? ($_SESSION['user']['profile_pic'] ?? null),
        ]);
    }

    public function agentAction(): void
    {
        $action = $_POST['agent_action'] ?? 'save';
        $agentId = (int) ($_POST['agent_id'] ?? 0);

        if ($action === 'toggle') {
            if ($agentId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid delivery agent.'], 422);
                return;
            }

            $updated = $this->users->toggleDeliveryAgent($agentId);
            $this->jsonResponse([
                'success' => $updated,
                'message' => $updated ? 'Agent status updated.' : 'Agent status update failed.',
            ], $updated ? 200 : 422);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $vehicleType = trim($_POST['vehicle_type'] ?? '');
        $errors = [];

        if ($name === '' || $phone === '' || $vehicleType === '') {
            $errors[] = 'Please fill in name, phone, and vehicle type.';
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
            return;
        }

        $saved = $this->users->saveDeliveryAgent($agentId, [
            'name' => $name,
            'phone' => $phone,
            'vehicle_type' => $vehicleType,
            'is_active' => ($_POST['is_active'] ?? '') === '1' ? 1 : 0,
        ]);

        $this->jsonResponse([
            'success' => $saved,
            'message' => $saved ? 'Delivery agent saved.' : 'Delivery agent save failed.',
        ], $saved ? 200 : 422);
    }

    public function zoneAction(): void
    {
        $action = $_POST['zone_action'] ?? 'save';
        $zoneId = (int) ($_POST['zone_id'] ?? 0);

        if ($action === 'delete') {
            if ($zoneId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid delivery zone.'], 422);
                return;
            }

            $deleted = $this->users->deleteDeliveryZone($zoneId);
            $this->jsonResponse([
                'success' => $deleted,
                'message' => $deleted ? 'Delivery zone deleted.' : 'Delivery zone delete failed.',
            ], $deleted ? 200 : 422);
            return;
        }

        $zoneName = trim($_POST['zone_name'] ?? '');
        $deliveryFee = (float) ($_POST['delivery_fee'] ?? 0);
        $estimatedDays = (int) ($_POST['estimated_days'] ?? 0);
        $errors = [];

        if ($zoneName === '') {
            $errors[] = 'Please enter a zone name.';
        }

        if ($deliveryFee < 0) {
            $errors[] = 'Delivery fee cannot be negative.';
        }

        if ($estimatedDays <= 0) {
            $errors[] = 'Estimated delivery days must be greater than 0.';
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
            return;
        }

        $saved = $this->users->saveDeliveryZone($zoneId, [
            'zone_name' => $zoneName,
            'delivery_fee' => $deliveryFee,
            'estimated_days' => $estimatedDays,
        ]);

        $this->jsonResponse([
            'success' => $saved,
            'message' => $saved ? 'Delivery zone saved.' : 'Delivery zone save failed.',
        ], $saved ? 200 : 422);
    }

    private function uploadProfileImage(array &$errors): ?string
    {
        if (empty($_FILES['profile_pic']['name'])) {
            return null;
        }

        if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
            return null;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mimeType = mime_content_type($_FILES['profile_pic']['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Image must be JPG, PNG, or WEBP.';
            return null;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/profiles';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('profile_', true) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
            $errors[] = 'Could not save uploaded image.';
            return null;
        }

        return 'public/uploads/profiles/' . $fileName;
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
