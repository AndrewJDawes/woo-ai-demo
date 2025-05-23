<?php

/*
 * This file is part of the Solarium package.
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code.
 */

namespace Solarium\QueryType\Server\Api;

use Solarium\Core\Client\Request;
use Solarium\Core\Query\AbstractRequestBuilder as BaseRequestBuilder;
use Solarium\Core\Query\QueryInterface;
use Solarium\QueryType\Server\Api\Query as ApiQuery;

/**
 * Build an API request.
 */
class RequestBuilder extends BaseRequestBuilder
{
    /**
     * Build request for a API query.
     *
     * @param QueryInterface|ApiQuery $query
     *
     * @return Request
     */
    public function build(QueryInterface|ApiQuery $query): Request
    {
        $request = parent::build($query);

        $method = $query->getMethod();

        $request->setMethod($method);
        $request->setApi($query->getVersion());
        $request->setIsServerRequest(true);

        if (null !== $contentType = $query->getContentType()) {
            $request->setContentType($query->getContentType());
        } elseif (Request::METHOD_POST === $method) {
            $request->setContentType(Request::CONTENT_TYPE_APPLICATION_JSON);
        } elseif (Request::METHOD_PUT === $method) {
            $request->setContentType(Request::CONTENT_TYPE_APPLICATION_OCTET_STREAM);
        }
        $request->setContentTypeParams($query->getContentTypeParams());

        if ($accept = $query->getAccept()) {
            $request->addHeader('Accept: '.$accept);
        }
        if ($rawData = $query->getRawData()) {
            $request->setRawData($rawData);
        }

        return $request;
    }
}
