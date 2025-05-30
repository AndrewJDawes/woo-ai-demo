<?php

/*
 * This file is part of the Solarium package.
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code.
 */

namespace Solarium\QueryType\Ping;

use Solarium\Core\Client\Request;
use Solarium\Core\Query\AbstractRequestBuilder as BaseRequestBuilder;
use Solarium\Core\Query\QueryInterface;

/**
 * Build a ping request.
 */
class RequestBuilder extends BaseRequestBuilder
{
    /**
     * Build request for a ping query.
     *
     * @param QueryInterface|Query $query
     *
     * @return Request
     */
    public function build(QueryInterface|Query $query): Request
    {
        $request = parent::build($query);
        $request->setMethod(Request::METHOD_GET);

        return $request;
    }
}
