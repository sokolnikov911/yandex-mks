<?php

namespace sokolnikov911\YandexMKS;

use GuzzleHttp\RequestOptions;
use sokolnikov911\YandexMKS\Exceptions\YandexException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    private $token;
    private $apiUrl;
    private $currentHttpMethod = self::HTTP_METHOD_GET;
    private $apiVersion = 'v1';

    const HTTP_METHOD_GET = 'get';
    const HTTP_METHOD_POST = 'post';

    const ENDPOINT_SET_CLAIM    = 'vsps/claims';
    const ENDPOINT_GET_CLAIM    = 'vsps/claims/:id';
    const ENDPOINT_GET_CONTENTS = 'vsps/contents';

    /**
     * @param string $token JWT token
     * @param string $apiUrl Yandex MKS API URL
     */
    public function __construct(string $token, string $apiUrl)
    {
        $this->token = $token;
        $this->apiUrl = $apiUrl;
    }

    /**
     * Get claim by id
     *
     * @param int $claimId Claim id
     *
     * @throws YandexException
     * @throws GuzzleException
     *
     * @return string Data
     */
    public function getClaim(int $claimId): string
    {
        $paramsArray = [
            'id' => $claimId
        ];

        return $this->getData($this->getEndpointUrl(self::ENDPOINT_GET_CLAIM, $paramsArray));
    }

    /**
     * Set claim
     *
     * @param integer $contentId Content id
     * @param string $externalId External id
     * @param integer $rightholderId Rightholder id
     * @param array $urlsArray Array of URLs
     * @param boolean $autoSend Send URLs
     *
     * @throws YandexException
     * @throws GuzzleException
     *
     * @return string Data
     */
    public function setClaim(int $contentId, string $externalId, int $rightholderId, array $urlsArray, bool $autoSend = false): string
    {
        $bodyArray = [
            'contentId' => $contentId,
            'externalId' => $externalId,
            'rightholderId' => $rightholderId,
            'urls' => $urlsArray,
            'autosend' => $autoSend
        ];

        return $this->postData($this->getEndpointUrl(self::ENDPOINT_SET_CLAIM), $bodyArray);
    }

    /**
     * Get Contents list
     *
     * @throws YandexException
     * @throws GuzzleException
     *
     * @return string Data
     */
    public function getContents($page = 1, $per_page = 100): string
    {
        return $this->getData($this->getEndpointUrl(self::ENDPOINT_GET_CONTENTS), ['page' => $page, 'per_page' => $per_page]);
    }

    /**
     * @return string Used API version
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * Sends a request via POST
     *
     * @param string $url Full URL of end-point
     * @param array $bodyArray Array of params for body
     *
     * @throws YandexException
     * @throws GuzzleException
     *
     * @return string Response body
     */
    private function postData(string $url, array $bodyArray = []): string
    {
        $this->currentHttpMethod = self::HTTP_METHOD_POST;

        return $this->sendRequest($url, $bodyArray);
    }

    /**
     * Sends a request via GET
     *
     * @param string $url Full URL of end-point
     * @param array $bodyArray Array of params for body
     *
     * @throws YandexException
     * @throws GuzzleException
     *
     * @return string Response body
     */
    private function getData(string $url, array $bodyArray = []): string
    {
        $this->currentHttpMethod = self::HTTP_METHOD_GET;

        $url = $url . '?' . http_build_query($bodyArray);

        return $this->sendRequest($url);
    }

    /**
     * Sends a request
     *
     * @param string $url Full URL of end-point
     * @param array $bodyArray Array of params for body
     *
     * @return string Response body
     *
     * @throws GuzzleException
     * @throws YandexException
     */
    private function sendRequest(string $url, array $bodyArray = []): string
    {
        $client = new HttpClient();

        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
        ];

        try {
            $response = $client->request($this->currentHttpMethod, $url, [
                'headers' => $headers,
                RequestOptions::JSON => $bodyArray ? ['data' => $bodyArray] : ''
            ]);
        } catch (GuzzleException $e) {
            $response = $e->getResponse();
            $responseData = $response->getBody()->getContents();

            $dataArray = json_decode($responseData, true);

            if (!is_array($dataArray) || isset($dataArray['errors'])) {
                throw new YandexException($dataArray['errors'][0]['detail']);
            } else throw $e;
        }

        return $response->getBody();
    }

    /**
     * Sends a request
     *
     * @param string $type Type of end-point
     * @param array $dataArray Additional data
     *
     * @return string Full end-point URL
     */
    private function getEndpointUrl(string $type, array $dataArray = []): string
    {
        foreach ($dataArray as $key => $value) {
            $type = str_replace(':' . $key, $value, $type);
        }

        return $this->apiUrl . $this->apiVersion . DIRECTORY_SEPARATOR . $type;
    }
}