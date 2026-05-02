<?php

namespace App\Controllers;

use App\Libraries\RolesSchema;
use App\Models\SeoSettingModel;
use App\Models\WebSettingModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        RolesSchema::ensure();

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Shared SEO + site title/description for layouts matching the home page chrome.
     *
     * @return array{metaTitle: string, metaDescription: string, metaKeywords: string, webTitle: string, webDescription: string}
     */
    protected function getSiteLayoutData(): array
    {
        $seo = null;
        $web = null;

        try {
            $db = \Config\Database::connect();

            if ($db->tableExists('seo_settings')) {
                $seoModel = new SeoSettingModel();
                $seo      = $seoModel->first();
            }

            if ($db->tableExists('web_settings')) {
                $webModel = new WebSettingModel();
                $web      = $webModel->first();
            }
        } catch (\Throwable $e) {
            $seo = null;
            $web = null;
        }

        $siteName = trim((string) ($web['title'] ?? '')) !== '' ? $web['title'] : 'Product Store';

        return [
            'metaTitle'       => $seo['meta_title'] ?? $siteName,
            'metaDescription' => $seo['meta_description'] ?? ($siteName . ' powered by CodeIgniter'),
            'metaKeywords'    => $seo['meta_keywords'] ?? '',
            'webTitle'        => $siteName,
            'webDescription'  => $web['description'] ?? 'This project now uses Bootstrap styling instead of the default CodeIgniter welcome style.',
        ];
    }
}
