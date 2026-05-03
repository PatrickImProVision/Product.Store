<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\RolesModel;
use App\Models\SeoSettingModel;
use App\Models\WebSettingModel;
use Config\CiTables;

class Store extends BaseController
{
    private function ensureCheckOutTable(): bool
    {
        try {
            $db            = \Config\Database::connect();
            $checkoutsTable = $db->prefixTable(CiTables::CHECKOUTS);
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $checkoutsTable . '` (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    profile_id VARCHAR(64) NOT NULL,
                    profile_name VARCHAR(255) NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    status VARCHAR(32) NOT NULL DEFAULT "pending"
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

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

    private function memberUserId(): ?int
    {
        $u = session()->get('member_user');

        if (! is_array($u) || empty($u['id'])) {
            return null;
        }

        return (int) $u['id'];
    }

    /** Legacy catalog rows with no user_id cannot be edited or deleted here. */
    private function memberOwnsProduct(array $product): bool
    {
        $uid = $this->memberUserId();
        if ($uid === null) {
            return false;
        }

        if (! array_key_exists('user_id', $product)) {
            return false;
        }

        $owner = $product['user_id'];

        return $owner !== null && $owner !== '' && (int) $owner === $uid;
    }

    private function memberIsAdministrator(): bool
    {
        $u = session()->get('member_user');

        return is_array($u)
            && ! empty($u['id'])
            && RolesModel::slugMayElevatedManageContent((string) ($u['role'] ?? ''));
    }

    /** Owner or signed-in administrator (admins may edit/delete legacy rows with no `user_id`). */
    private function memberCanManageProduct(array $product): bool
    {
        if ($this->memberIsAdministrator()) {
            return true;
        }

        return $this->memberOwnsProduct($product);
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
            $db           = \Config\Database::connect();
            $productsTable = $db->prefixTable(CiTables::PRODUCTS);
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $productsTable . '` (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    quantity INT NOT NULL DEFAULT 0,
                    description TEXT NULL,
                    remote_image VARCHAR(2048) NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );

            // Backward compatibility for existing catalog tables.
            try {
                $db->query('ALTER TABLE `' . $productsTable . '` ADD COLUMN remote_image VARCHAR(2048) NULL');
            } catch (\Throwable $e) {
                // Ignore "duplicate column" errors.
            }

            try {
                $db->query('ALTER TABLE `' . $productsTable . '` ADD COLUMN user_id INT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // Ignore "duplicate column" errors.
            }

            try {
                $db->query('ALTER TABLE `' . $productsTable . '` ADD KEY products_user_id_idx (user_id)');
            } catch (\Throwable $e) {
                // Index may already exist.
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function productFulltextIndexExists(): bool
    {
        try {
            $db = \Config\Database::connect();
            $row = $db->query('SHOW INDEX FROM `' . $db->prefixTable(CiTables::PRODUCTS) . '` WHERE Key_name = \'ft_products_name_description\'')->getRowArray();

            return $row !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ensureProductsFulltextIndex(): void
    {
        try {
            if ($this->productFulltextIndexExists()) {
                return;
            }

            $db = \Config\Database::connect();
            $db->query('ALTER TABLE `' . $db->prefixTable(CiTables::PRODUCTS) . '` ADD FULLTEXT INDEX ft_products_name_description (name, description)');
        } catch (\Throwable $e) {
            // Duplicate index, engine limitation, or permissions — LIKE fallback still works.
        }
    }

    /**
     * @return list<string>
     */
    private function tokenizeSearchQuery(string $q): array
    {
        $parts = preg_split('/\s+/u', trim($q), -1, PREG_SPLIT_NO_EMPTY);
        $out   = [];

        foreach ($parts as $p) {
            $clean = preg_replace('/[^\p{L}\p{N}\-_\.]+/u', '', $p);
            if ($clean !== '' && mb_strlen($clean) >= 2) {
                $out[] = $clean;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function runFulltextProductSearch(
        string $q,
        string $mode,
        string $sort,
        ?float $minPrice,
        ?float $maxPrice,
        int $limit
    ): array {
        $db            = \Config\Database::connect();
        $productsTable = $db->prefixTable(CiTables::PRODUCTS);

        $againstMode = $mode === 'boolean' ? 'BOOLEAN MODE' : 'NATURAL LANGUAGE MODE';
        $needle      = $mode === 'boolean' ? mb_substr($q, 0, 512) : $q;

        $sql = 'SELECT id, name, price, quantity, description, remote_image,
                MATCH(name, description) AGAINST (? IN ' . $againstMode . ') AS relevance_score
            FROM `' . $productsTable . '`
            WHERE MATCH(name, description) AGAINST (? IN ' . $againstMode . ')';

        $params = [$needle, $needle];

        if ($minPrice !== null) {
            $sql .= ' AND price >= ?';
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $sql .= ' AND price <= ?';
            $params[] = $maxPrice;
        }

        switch ($sort) {
            case 'name':
                $sql .= ' ORDER BY name ASC';

                break;

            case 'price_asc':
                $sql .= ' ORDER BY price ASC';

                break;

            case 'price_desc':
                $sql .= ' ORDER BY price DESC';

                break;

            default:
                $sql .= ' ORDER BY relevance_score DESC';
        }

        $sql .= ' LIMIT ' . $limit;

        return $db->query($sql, $params)->getResultArray();
    }

    private function countFulltextProductSearch(
        string $q,
        string $mode,
        ?float $minPrice,
        ?float $maxPrice
    ): int {
        $db            = \Config\Database::connect();
        $productsTable = $db->prefixTable(CiTables::PRODUCTS);

        $againstMode = $mode === 'boolean' ? 'BOOLEAN MODE' : 'NATURAL LANGUAGE MODE';
        $needle      = $mode === 'boolean' ? mb_substr($q, 0, 512) : $q;

        $sql = 'SELECT COUNT(*) AS c FROM `' . $productsTable . '` WHERE MATCH(name, description) AGAINST (? IN ' . $againstMode . ')';
        $params = [$needle];

        if ($minPrice !== null) {
            $sql .= ' AND price >= ?';
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $sql .= ' AND price <= ?';
            $params[] = $maxPrice;
        }

        $row = $db->query($sql, $params)->getRowArray();

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function runLikeProductSearch(
        string $q,
        string $sort,
        ?float $minPrice,
        ?float $maxPrice,
        int $limit
    ): array {
        $tokens = $this->tokenizeSearchQuery($q);

        $productModel = new ProductModel();

        if ($tokens !== []) {
            foreach ($tokens as $t) {
                $productModel = $productModel->groupStart()->like('name', $t)->orLike('description', $t)->groupEnd();
            }
        } else {
            // Single loose substring if tokenization removed everything (e.g. one-letter queries).
            $productModel = $productModel->groupStart()->like('name', $q)->orLike('description', $q)->groupEnd();
        }

        if ($minPrice !== null) {
            $productModel = $productModel->where('price >=', $minPrice);
        }

        if ($maxPrice !== null) {
            $productModel = $productModel->where('price <=', $maxPrice);
        }

        switch ($sort) {
            case 'name':
                $productModel = $productModel->orderBy('name', 'ASC');

                break;

            case 'price_asc':
                $productModel = $productModel->orderBy('price', 'ASC');

                break;

            case 'price_desc':
                $productModel = $productModel->orderBy('price', 'DESC');

                break;

            case 'relevance':
            default:
                // No relevance ranking without FULLTEXT; newest first as a stable default.
                $productModel = $productModel->orderBy('id', 'DESC');
        }

        return $productModel->findAll($limit);
    }

    private function countLikeProductSearch(
        string $q,
        ?float $minPrice,
        ?float $maxPrice
    ): int {
        $tokens = $this->tokenizeSearchQuery($q);

        $productModel = new ProductModel();

        if ($tokens !== []) {
            foreach ($tokens as $t) {
                $productModel = $productModel->groupStart()->like('name', $t)->orLike('description', $t)->groupEnd();
            }
        } else {
            $productModel = $productModel->groupStart()->like('name', $q)->orLike('description', $q)->groupEnd();
        }

        if ($minPrice !== null) {
            $productModel = $productModel->where('price >=', $minPrice);
        }

        if ($maxPrice !== null) {
            $productModel = $productModel->where('price <=', $maxPrice);
        }

        return $productModel->countAllResults();
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

            // Ensure the SEO settings table exists for this minimal setup.
            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $db->prefixTable(CiTables::SEO_SETTINGS) . '` (
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

        $layout = $this->getSiteLayoutData();

        return view('store/seo_settings', array_merge($layout, [
            'pageTitle' => 'SEO settings',
            'seo'       => $seo,
            'message'       => session()->getFlashdata('message'),
        ]));
    }

    public function Web_Settings()
    {
        $isPost = $this->request->is('post');

        try {
            $db = \Config\Database::connect();

            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $db->prefixTable(CiTables::WEB_SETTINGS) . '` (
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

        $layout = $this->getSiteLayoutData();

        return view('store/web_settings', array_merge($layout, [
            'pageTitle' => 'Web settings',
            'web'       => $web,
            'message'       => session()->getFlashdata('message'),
        ]));
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
            'products'               => $products,
            'search'                 => $search,
            'message'                => session()->getFlashdata('message'),
            'memberSignedIn'         => $this->memberUserId() !== null,
            'memberUserId'           => $this->memberUserId(),
            'memberIsAdministrator'  => $this->memberIsAdministrator(),
        ];

        if ($this->request->isAJAX()) {
            return view('store/partials/products_feed', $viewData);
        }

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle']  = 'Store';
        $layout['bodyClass'] = static::STOREFRONT_BODY_CLASS;

        return view('store/products_index', array_merge($layout, $viewData));
    }

    public function Search_Index()
    {
        if (! $this->ensureProductsTable()) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $this->ensureProductsFulltextIndex();

        $q = trim((string) ($this->request->getPost('q') ?? $this->request->getGet('q')));

        $mode = (string) $this->request->getGet('mode');
        if (! in_array($mode, ['natural', 'boolean', 'like'], true)) {
            $mode = 'natural';
        }

        $sort = (string) $this->request->getGet('sort');
        if (! in_array($sort, ['relevance', 'name', 'price_asc', 'price_desc'], true)) {
            $sort = 'relevance';
        }

        $minRaw = $this->request->getGet('min_price');
        $maxRaw = $this->request->getGet('max_price');
        $minPrice = ($minRaw !== null && $minRaw !== '') ? (float) $minRaw : null;
        $maxPrice = ($maxRaw !== null && $maxRaw !== '') ? (float) $maxRaw : null;

        $limitGet = $this->request->getGet('limit');
        $limit    = min(200, max(12, (int) ($limitGet !== null && $limitGet !== '' ? $limitGet : 48)));

        $fulltextAvailable = $this->productFulltextIndexExists();

        $products      = [];
        $totalMatched  = 0;
        $engineKey     = 'idle';
        $engineLabel   = 'Enter a query to search the catalog.';
        $errorNote     = null;

        $t0 = microtime(true);

        if ($q !== '') {
            $usedFt = false;

            if ($fulltextAvailable && $mode !== 'like') {
                try {
                    $products = $this->runFulltextProductSearch($q, $mode, $sort, $minPrice, $maxPrice, $limit);
                    $totalMatched = $this->countFulltextProductSearch($q, $mode, $minPrice, $maxPrice);
                    $usedFt       = true;
                    $engineKey    = $mode === 'boolean' ? 'fulltext_boolean' : 'fulltext_natural';
                    $engineLabel  = $mode === 'boolean'
                        ? 'MySQL InnoDB FULLTEXT — BOOLEAN MODE (indexed: name + description)'
                        : 'MySQL InnoDB FULLTEXT — NATURAL LANGUAGE MODE (indexed: name + description)';
                } catch (\Throwable $e) {
                    $products   = [];
                    $errorNote = 'Full-text search failed; LIKE fallback was used. (' . $e->getMessage() . ')';
                }
            }

            if (! $usedFt) {
                $products = $this->runLikeProductSearch($q, $sort, $minPrice, $maxPrice, $limit);
                $totalMatched = $this->countLikeProductSearch($q, $minPrice, $maxPrice);
                $engineKey    = 'like';
                $engineLabel  = $fulltextAvailable
                    ? 'SQL LIKE — token AND-groups across name + description (pick “LIKE only” or if FULLTEXT returns nothing useful)'
                    : 'SQL LIKE — FULLTEXT index unavailable on this database table; extended matching only.';
            }
        }

        $elapsedMs = round((microtime(true) - $t0) * 1000, 2);

        $highlightTokens = $this->tokenizeSearchQuery($q);
        if ($highlightTokens === [] && $q !== '') {
            $highlightTokens = [trim(mb_substr($q, 0, 64))];
        }

        $viewData = [
            'q'                  => $q,
            'mode'               => $mode,
            'sort'               => $sort,
            'min_price'          => $minRaw !== null && $minRaw !== '' ? (string) $minRaw : '',
            'max_price'          => $maxRaw !== null && $maxRaw !== '' ? (string) $maxRaw : '',
            'limit'              => $limit,
            'products'           => $products,
            'totalMatched'       => $totalMatched,
            'engineKey'          => $engineKey,
            'engineLabel'        => $engineLabel,
            'fulltextAvailable'  => $fulltextAvailable,
            'elapsedMs'          => $elapsedMs,
            'errorNote'          => $errorNote,
            'highlightTokens'       => $highlightTokens,
            'message'               => session()->getFlashdata('message'),
            'memberUserId'          => $this->memberUserId(),
            'memberIsAdministrator' => $this->memberIsAdministrator(),
        ];

        if ($this->request->isAJAX()) {
            return view('store/partials/search_results', $viewData);
        }

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle'] = 'Search';

        return view('store/search_index', array_merge($layout, $viewData));
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

        // At least 3 letters/digits (ignore spaces/punctuation). Fallback if Unicode regex fails.
        $significant = preg_replace('/[^\p{L}\p{N}]+/u', '', $q);
        if ($significant === null || $significant === '') {
            $significant = preg_replace('/[^a-zA-Z0-9]/', '', $q) ?? '';
        }

        if ($significant === '' || mb_strlen($significant, 'UTF-8') < 3) {
            return $this->response->setJSON([
                'success'     => true,
                'suggestions' => [],
            ]);
        }

        /**
         * Default Search_Index uses FULLTEXT NATURAL LANGUAGE when an index exists: rows match if they
         * contain any significant word (not necessarily every token). Whisper previously used only LIKE
         * token-AND, which is stricter — empty dropdown while results appeared below.
         * Merge FULLTEXT name hits with LIKE hits so suggestions track what Search shows.
         */
        $namesSeen = [];

        $addNamesFromRows = static function (array $rows) use (&$namesSeen): void {
            foreach ($rows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name !== '') {
                    $namesSeen[$name] = true;
                }
            }
        };

        if ($this->productFulltextIndexExists()) {
            try {
                $addNamesFromRows($this->runFulltextProductSearch($q, 'natural', 'name', null, null, 48));
            } catch (\Throwable $e) {
                // Ignore; LIKE path still runs.
            }
        }

        // LIKE: same AND-token behaviour as runLikeProductSearch when tokens exist; covers prefixes FULLTEXT may skip.
        $tokens = $this->tokenizeSearchQuery($q);

        $productModel = new ProductModel();
        $productModel->select('name');

        if ($tokens !== []) {
            foreach ($tokens as $t) {
                $productModel = $productModel->groupStart()->like('name', $t)->orLike('description', $t)->groupEnd();
            }
        } else {
            $productModel = $productModel
                ->groupStart()
                ->like('name', $q)
                ->orLike('description', $q)
                ->groupEnd();

            if ($significant !== '' && $significant !== $q) {
                $productModel = $productModel
                    ->orGroupStart()
                    ->like('name', $significant)
                    ->orLike('description', $significant)
                    ->groupEnd();
            }
        }

        $addNamesFromRows($productModel->orderBy('name', 'ASC')->findAll(48));

        $suggestions = array_keys($namesSeen);
        sort($suggestions, SORT_STRING);
        $suggestions = array_slice($suggestions, 0, 24);

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

        $ownerId = $this->memberUserId();
        if ($ownerId === null) {
            if ($isPost) {
                return $this->request->isAJAX()
                    ? $this->jsonResponse(false, 'Please sign in to create a product.')
                    : redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to create a product.');
            }

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to create a product.');
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
                'user_id'     => $ownerId,
            ]);

            if (! $saved) {
                return $this->jsonResponse(false, 'Unable to create product.');
            }

            if ($this->request->isAJAX()) {
                return $this->jsonResponse(true, 'Product created successfully.', ['redirect' => site_url('Store/Index')]);
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Product created successfully.');
        }

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle'] = 'Create Product';

        return view('store/product_form', array_merge($layout, [
            'mode'    => 'create',
            'action'  => site_url('Store/Product/Create'),
            'product' => null,
            'message' => session()->getFlashdata('message'),
        ]));
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

        if ($this->memberUserId() === null) {
            if ($isPost) {
                return $this->request->isAJAX()
                    ? $this->jsonResponse(false, 'Please sign in to edit products.')
                    : redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to edit products.');
            }

            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to edit products.');
        }

        if (! $this->memberCanManageProduct($product)) {
            if ($isPost) {
                return $this->request->isAJAX()
                    ? $this->jsonResponse(false, 'You do not have permission to edit this product.')
                    : redirect()->to(site_url('Store/Index'))->with('message', 'You do not have permission to edit this product.');
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'You do not have permission to edit this product.');
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

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle'] = 'Edit Product';

        return view('store/product_form', array_merge($layout, [
            'mode'    => 'edit',
            'action'  => site_url('Store/Product/Edit/' . $id),
            'product' => $product,
            'message' => session()->getFlashdata('message'),
        ]));
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

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle']   = trim((string) ($product['name'] ?? '')) !== ''
            ? trim((string) $product['name'])
            : 'Product';
        $layout['bodyClass']   = static::STOREFRONT_BODY_CLASS;

        return view('store/product_view', array_merge($layout, [
            'product'           => $product,
            'message'           => session()->getFlashdata('message'),
            'canManageProduct'  => $this->memberCanManageProduct($product),
        ]));
    }

    public function Basket_Add(int $id)
    {
        $isAjax = $this->request->isAJAX();

        if (! $this->ensureProductsTable()) {
            if ($isAjax) {
                return $this->jsonResponse(false, 'Database connection failed. Please check DB settings.');
            }

            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product === null) {
            if ($isAjax) {
                return $this->jsonResponse(false, 'Product not found.');
            }

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

        if ($isAjax) {
            $lineQty = (int) ($basket[$id]['quantity'] ?? 0);
            $lines   = count($basket);
            $pieces  = 0;

            foreach ($basket as $row) {
                $pieces += (int) ($row['quantity'] ?? 0);
            }

            return $this->jsonResponse(true, 'Product added to basket.', [
                'productId'             => $id,
                'lineQuantity'          => $lineQty,
                'basketLineCount'       => $lines,
                'basketTotalQuantity'   => $pieces,
                'viewBasketUrl'         => site_url('Store/Basket/Index'),
            ]);
        }

        return redirect()->to(site_url('Store/Product/View/' . $id))->with('message', 'Product added to basket.');
    }

    public function Basket_Create()
    {
        return redirect()->to(site_url('Store/Basket/Index'));
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

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle']  = 'Basket';
        $layout['bodyClass']  = static::STOREFRONT_BODY_CLASS;

        return view('store/basket_index', array_merge($layout, [
            'profile'    => $profile,
            'basket'     => $basket,
            'grandTotal' => $grandTotal,
            'message'    => session()->getFlashdata('message'),
        ]));
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

    public function Basket_Edit(int $id)
    {
        $basket = $this->getBasketForVisitor();

        if (! isset($basket[$id])) {
            return redirect()->to(site_url('Store/Basket/Index'))->with('message', 'Item not found in basket.');
        }

        if ($this->request->is('post')) {
            $qty = (int) $this->request->getPost('quantity');
            $qty = max(1, $qty);
            $basket[$id]['quantity'] = $qty;
            $this->saveBasketForVisitor($basket);
            return redirect()->to(site_url('Store/Basket/Index'))->with('message', 'Basket item updated.');
        }

        return view('store/basket_edit', array_merge($this->getSiteLayoutData(), [
            'item'      => $basket[$id],
            'pageTitle' => 'Edit basket item',
        ]));
    }

    public function CheckOut_Index()
    {
        if (! $this->ensureCheckOutTable()) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table(CiTables::CHECKOUTS)->orderBy('id', 'DESC')->get()->getResultArray();

        return view('store/checkout_index', array_merge($this->getSiteLayoutData(), [
            'rows'      => $rows,
            'message'   => session()->getFlashdata('message'),
            'pageTitle' => 'Checkout',
        ]));
    }

    public function CheckOut_Create()
    {
        if (! $this->ensureCheckOutTable()) {
            return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        if ($this->request->is('post')) {
            $profile = $this->getVisitorProfile();
            $basket = $this->getBasketForVisitor();
            $total = 0.0;
            foreach ($basket as $item) {
                $total += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0));
            }

            $db = \Config\Database::connect();
            $db->table(CiTables::CHECKOUTS)->insert([
                'profile_id' => (string) $profile['id'],
                'profile_name' => (string) $profile['name'],
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Checkout created successfully.');
        }

        return view('store/checkout_form', array_merge($this->getSiteLayoutData(), [
            'mode'      => 'create',
            'row'       => null,
            'action'    => site_url('Store/CheckOut/Create'),
            'pageTitle' => 'Create checkout',
        ]));
    }

    public function CheckOut_Edit(int $id)
    {
        if (! $this->ensureCheckOutTable()) {
            return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $row = $db->table(CiTables::CHECKOUTS)->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Checkout not found.');
        }

        if ($this->request->is('post')) {
            $status = trim((string) $this->request->getPost('status'));
            if ($status === '') {
                $status = 'pending';
            }
            $db->table(CiTables::CHECKOUTS)->where('id', $id)->update(['status' => $status]);
            return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Checkout updated successfully.');
        }

        return view('store/checkout_form', array_merge($this->getSiteLayoutData(), [
            'mode'      => 'edit',
            'row'       => $row,
            'action'    => site_url('Store/CheckOut/Edit/' . $id),
            'pageTitle' => 'Edit checkout',
        ]));
    }

    public function CheckOut_Delete(int $id)
    {
        if (! $this->ensureCheckOutTable()) {
            return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $db->table(CiTables::CHECKOUTS)->where('id', $id)->delete();
        return redirect()->to(site_url('Store/CheckOut/Index'))->with('message', 'Checkout deleted successfully.');
    }

    public function Product_Delete(int $id)
    {
        if (! $this->ensureProductsTable()) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product === null) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'Product not found.');
        }

        if ($this->memberUserId() === null) {
            return redirect()->to(site_url('Member/User/Login'))->with('message', 'Please sign in to delete products.');
        }

        if (! $this->memberCanManageProduct($product)) {
            return redirect()->to(site_url('Store/Index'))->with('message', 'You do not have permission to delete this product.');
        }

        if ($this->request->is('post')) {
            $productModel->delete($id);

            return redirect()->to(site_url('Store/Index'))->with('message', 'Product deleted successfully.');
        }

        $layout = $this->getSiteLayoutData();
        $layout['pageTitle'] = 'Delete product';

        return view('store/product_delete', array_merge($layout, ['product' => $product]));
    }
}
