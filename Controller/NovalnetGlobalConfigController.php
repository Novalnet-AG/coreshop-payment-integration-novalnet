<?php
/**
 * Novalnet payment bundle
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment bundle for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

declare(strict_types=1);
namespace NovalnetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use NovalnetBundle\Model\Constants;

class NovalnetGlobalConfigController extends AbstractController
{
    /**
     * Process handling action
     *
     * @param Request $request
     * @return Response
     */
    public function processAction(Request $request): Response
    {
        $apiProcess = $this->getParameterFromRequest($request, 'apiProcess');

        if ($apiProcess == 'vendorAutoConfig') {
            return $this->doVendorAutoConfig($request);
        } elseif ($apiProcess == 'webhookConfigure') {
            return $this->doWebhookConfigure($request);
        }

        return $this->redirectToRoute('coreshop_index');
    }

    /**
     * Vendor auto configuration api call
     *
     * @param Request $request
     * @return Response
     */
    private function doVendorAutoConfig(Request $request): Response
    {
        $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
        $options = [];

        $options['headers'] = [
            'Content-Type' => 'application/json',
            'Charset' => 'utf-8',
            'Accept' => 'application/json',
            'X-NN-Access-Key' => base64_encode($this->getParameterFromRequest($request, 'paymentAccessKey'))
        ];

        $params = [
            'merchant' => [
                'signature' => $this->getParameterFromRequest($request, 'signature')
            ],
            'custom' => [
                'lang' => $novalnetHelper->getLanguageCode()
            ]
        ];

        $options['body'] = json_encode($params);

        $response = $this->httpClient->request('POST', Constants::NOVALNET_MERCHANT_DETAIL_URL, $options);
        $response = $response->getContent();

        return Response::create($response);
    }

    /**
     * Webhook configuration api call
     *
     * @param Request $request
     * @return Response
     */
    private function doWebhookConfigure(Request $request): Response
    {
        $novalnetHelper = \Pimcore::getContainer()->get(\NovalnetBundle\Helper\NovalnetHelper::class);
        $options = [];

        $options['headers'] = [
            'Content-Type' => 'application/json',
            'Charset' => 'utf-8',
            'Accept' => 'application/json',
            'X-NN-Access-Key' => base64_encode($this->getParameterFromRequest($request, 'paymentAccessKey'))
        ];

        $params = [
            'merchant' => [
                'signature' => $this->getParameterFromRequest($request, 'signature')
            ],
            'webhook' => [
                'url' => $this->getParameterFromRequest($request, 'webhookUrl')
            ],
            'custom' => [
                'lang' => $novalnetHelper->getLanguageCode()
            ]
        ];

        $options['body'] = json_encode($params);

        $response = $this->httpClient->request('POST', Constants::NOVALNET_WEBHOOK_CONFIG_URL, $options);
        $response = $response->getContent();

        return Response::create($response);
    }

    /**
     * To set HTTP client
     *
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return mixed
     *
     * based on Symfony\Component\HttpFoundation\Request::get
     */
    protected function getParameterFromRequest(Request $request, string $key, $default = null)
    {
        if ($request !== $result = $request->attributes->get($key, $request)) {
            return $result;
        }

        if ($request->query->has($key)) {
            return $request->query->all()[$key];
        }

        if ($request->request->has($key)) {
            return $request->request->all()[$key];
        }

        return $default;
    }
}
