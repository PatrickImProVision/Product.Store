<?php

namespace App\Controllers;

use App\Models\SeoSettingModel;
use App\Models\WebSettingModel;
use App\Models\ProductModel;

class Store extends BaseController
{
    private function getVisitorProfile(): array
    {
        $session = session();
        $profile = $session->get('visitor_profile');

        if (! is_array($profile) || empty($profile['id'])) {
            $profile = [
                'id'   => 'guest_' . bin2hex(random_bytes(6)),
                'name' => 'Guest Visitor',
                'type' => 'guest',
            ];
            $session->set('visitor_profile', $profile);
        }

        return $profile;
    }

    private function getBasketForVisitor(): array
    {
        $session = session();
        $profile = $this->getVisitorProfile();
        $all     = $session->get('basket_profiles');

        if (! is_array($all)) {
            $all = [];
        }

        // Migrate legacy single-basket data into visitor basket.
        if (! isset($all[$profile['id']])) {
            $legacy = $session->get('basket');
            $all[$profile['id']] = is_array($legacy) ? $legacy : [];
            $session->remove('basket');
            $session->set('basket_profiles', $all);
        }

        return is_array($all[$profile['id']]) ? $all[$profile['id']] : [];
    }

    private function saveBasketForVisitor(array $basket): void
    {
        $session = session();
        $profile = $this->getVisitorProfile();
        $all     = $session->get('basket_profiles');

        if (! is_array($all)) {
            $all = [];
        }

        $all[$profile['id']] = $basket;
        $session->set('basket_profiles', $all);
    }

    private function ensureProductsTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS products (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    quantity INT NOT NULL DEFAULT 0,
                    description TEXT NULL,
                    remote_image VARCHAR(2048) NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );

            // Backward compatibility for existing products tables.
            try {
                $db->query('ALTER TABLE products ADD COLUMN remote_image VARCHAR(2048) NULL');
            } catch (\Throwable $e) {
                // Ignore "duplicate column" errors.
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function jsonResponse(bool $success, string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge([
            'success'    => $success,
            'message'    => $message,
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
        ], $extra));
    }

    public function SEO_Settings()
    {
        $isPost = $this->request->is('post');

        try {
            $db = \Config\Database::connect();

            // Ensure the seo_settings table exists for this minimal setup.
            $db->query(
                'CREATE TABLE IF NOT EXISTS seo_settings (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    meta_title VARCHAR(255) NOT NULL DEFAULT "",
                    meta_description TEXT NULL,
                    meta_keywords TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
        } catch (\Throwable $e) {
            if ($isPost) {
                return $this->jsonResponse(false, 'Database connection failed. Please check DB settings.');
            }

            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $seoModel = new SeoSettingModel();
        $seo      = $seoModel->first();

        if ($isPost) {
            $data = [
                'meta_title'       => trim((string) $this->request->getPost('meta_title')),
                'meta_description' => trim((string) $this->request->getPost('meta_description')),
                'meta_keywords'    => trim((string) $this->request->getPost('meta_keywords')),
            ];

            $saved = $seo !== null
                ? $seoModel->update($seo['id'], $data)
                : $seoModel->insert($data);

            if (! $saved) {
                return $this->jsonResponse(false, 'Unable to save SEO settings.');
            }

            return $this->jsonResponse(true, 'SEO settings saved successfully.');
        }

        return view('store/seo_settings', [
            'seo'     => $seo,
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Settings()
    {
        $isPost = $this->request->is('post');

        try {
            $db = \Config\Database::connect();

            $db->query(
                'CREATE TABLE IF NOT EXISTS web_settings (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL DEFAULT "",
                    description TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
        } catch (\Throwable $e) {
            if ($isPost) {
                return $this->jsonResponse(false, 'Database connection failed. Please check DB settings.');
            }

            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $webModel = new WebSettingModel();
        $web      = $webModel->first();

        if ($isPost) {
            $data = [
                'title'       => trim((string) $this->request->getPost('title')),
                'description' => trim((string) $this->request->getPost('description')),
            ];

            $saved = $web !== null
                ? $webModel->update($web['id'], $data)
                : $webModel->insert($data);

            if (! $saved) {
                return $this->jsonResponse(false, 'Unable to save web settings.');
            }

            return $this->jsonResponse(true, 'Web settings saved successfully.');
        }

        return view('store/web_settings', [
            'web'     => $web,
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function index()
    {
        if (! $this->ensureProductsTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $search       = trim((string) $this->request->getGet('q'));
        $builder      = $productModel->orderBy('id', 'DESC');

        if ($search !== '') {
            $builder = $builder
                ->groupStart()
                ->like('name', $search)
                ->orLike('description', $search)
                ->orLike('price', $search)
                ->groupEnd();
        }

        $products = $builder->findAll();

        $viewData = [
            'products' => $products,
            'search'   => $search,
            'message'  => session()->getFlashdata('message'),
        ];

        if ($this->request->isAJAX()) {
            return view('store/partials/products_feed', $viewData);
        }

        return view('store/products_index', $viewData);
    }

    public function Search_Whisper()
    {
        if (! $this->ensureProductsTable()) {
            return $this->response->setJSON([
                'success'     => false,
                'suggestions' => [],
            ]);
        }

        $q = trim((string) $this->request->getGet('q'));

        if ($q === '') {
            return $this->response->setJSON([
                'success'     => true,
                'suggestions' => [],
            ]);
        }

        $productModel = new ProductModel();
        $rows = $productModel
            ->select('name')
            ->like('name', $q)
            ->orderBy('name', 'ASC')
            ->findAll(8);

        $suggestions = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $suggestions[] = $name;
            }
        }

        $suggestions = array_values(array_unique($suggestions));

        return $this->response->setJSON([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }

    public function Product_Create()
    {
        $isPost = $this->request->is('post');

        if (! $this->ensureProductsTable()) {
            if ($isPost) {
                return $this->jsonResponse(false, 'Database connection failed. Please check DB settings.');
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();

        if ($isPost) {
            $name        = trim((string) $this->request->getPost('name'));
            $price       = (float) $this->request->getPost('price');
            $quantity    = (int) $this->request->getPost('quantity');
            $description = trim((string) $this->request->getPost('description'));
            $remoteImage = trim((string) $this->request->getPost('remote_image'));

            if ($name === '') {
                if ($this->request->isAJAX()) {
                    return $this->jsonResponse(false, 'Product name is required.');
                }

                return redirect()->to(site_url('Store/Product/Create'))->with('message', 'Product name is required.');
            }

            $saved = $productModel->insert([
                'name'        => $name,
                'price'       => $price,
                'quantity'    => $quantity,
                'description' => $description,
                'remote_image'=> $remoteImage,
            ]);

            if (! $saved) {
                return $this->jsonResponse(false, 'Unable to create product.');
            }

            if ($this->request->isAJAX()) {
                return $this->jsonResponse(true, 'Product created successfully.', ['redirect' => site_url('Store/Index')]);
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Product created successfully.');
        }

        return view('store/product_form', [
            'mode'    => 'create',
            'action'  => site_url('Store/Product/Create'),
            'product' => null,
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function Product_Edit(int $id)
    {
        $isPost = $this->request->is('post');

        if (! $this->ensureProductsTable()) {
            if ($isPost) {
                return $this->jsonResponse(false, 'Database connection failed. Please check DB settings.');
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product === null) {
            if ($isPost) {
                return $this->jsonResponse(false, 'Product not found.');
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Product not found.');
        }

        if ($isPost) {
            $name        = trim((string) $this->request->getPost('name'));
            $price       = (float) $this->request->getPost('price');
            $quantity    = (int) $this->request->getPost('quantity');
            $description = trim((string) $this->request->getPost('description'));
            $remoteImage = trim((string) $this->request->getPost('remote_image'));

            if ($name === '') {
                if ($this->request->isAJAX()) {
                    return $this->jsonResponse(false, 'Product name is required.');
                }

                return redirect()->to(site_url('Store/Product/Edit/' . $id))->with('message', 'Product name is required.');
            }

            $saved = $productModel->update($id, [
                'name'        => $name,
                'price'       => $price,
                'quantity'    => $quantity,
                'description' => $description,
                'remote_image'=> $remoteImage,
            ]);

            if (! $saved) {
                return $this->jsonResponse(false, 'Unable to update product.');
            }

            if ($this->request->isAJAX()) {
                return $this->jsonResponse(true, 'Product updated successfully.', ['redirect' => site_url('Store/Index')]);
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Product updated successfully.');
        }

        return view('store/product_form', [
            'mode'    => 'edit',
            'action'  => site_url('Store/Product/Edit/' . $id),
            'product' => $product,
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function Product_View(int $id)
    {
        if (! $this->ensureProductsTable()) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product === null) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Product not found.');
        }

        return view('store/product_view', [
            'product' => $product,
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function Basket_Add(int $id)
    {
        if (! $this->ensureProductsTable()) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product === null) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Product not found.');
        }

        $basket = $this->getBasketForVisitor();

        if (! isset($basket[$id])) {
            $basket[$id] = [
                'id'       => (int) $product['id'],
                'name'     => (string) $product['name'],
                'price'    => (float) $product['price'],
                'quantity' => 1,
            ];
        } else {
            $basket[$id]['quantity']++;
        }

        $this->saveBasketForVisitor($basket);

        return redirect()->to(site_url('Store/Product/View/' . $id))->with('message', 'Product added to basket.');
    }

    public function Basket_Index()
    {
        $profile = $this->getVisitorProfile();
        $basket  = $this->getBasketForVisitor();

        $grandTotal = 0.0;

        foreach ($basket as &$item) {
            $itemPrice    = (float) ($item['price'] ?? 0);
            $itemQuantity = (int) ($item['quantity'] ?? 0);
            $item['total'] = $itemPrice * $itemQuantity;
            $grandTotal   += $item['total'];
        }
        unset($item);

        return view('store/basket_index', [
            'profile'    => $profile,
            'basket'     => $basket,
            'grandTotal' => $grandTotal,
            'message'    => session()->getFlashdata('message'),
        ]);
    }

    public function Basket_Delete(int $id)
    {
        $basket = $this->getBasketForVisitor();

        if (isset($basket[$id])) {
            unset($basket[$id]);
            $this->saveBasketForVisitor($basket);

            if ($this->request->isAJAX()) {
                return $this->jsonResponse(true, 'Item removed from basket.');
            }

            return redirect()->to(site_url('Store/Basket/Index'))->with('message', 'Item removed from basket.');
        }

        if ($this->request->isAJAX()) {
            return $this->jsonResponse(false, 'Item not found in basket.');
        }

        return redirect()->to(site_url('Store/Basket/Index'))->with('message', 'Item not found in basket.');
    }

    public function Product_Delete(int $id)
    {
        if (! $this->ensureProductsTable()) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product !== null) {
            $productModel->delete($id);
            return redirect()->to(site_url('Store/Index'))->with('message', 'Product deleted successfully.');
        }

        return redirect()->to(site_url('Store/Index'))->with('message', 'Product not found.');
    }
}
