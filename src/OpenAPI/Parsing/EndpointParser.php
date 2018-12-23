<?php
/*
 * This file is part of Swagger Mock.
 *
 * (c) Igor Lazarev <strider2038@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\OpenAPI\Parsing;

use App\Mock\Parameters\MockParameters;
use App\Mock\Parameters\MockResponse;
use App\OpenAPI\SpecificationObjectMarkerInterface;

/**
 * @author Igor Lazarev <strider2038@yandex.ru>
 */
class EndpointParser implements ContextualParserInterface
{
    /** @var ContextualParserInterface */
    private $responseParser;

    /** @var ReferenceResolvingParser */
    private $resolvingParser;

    public function __construct(ContextualParserInterface $responseParser, ReferenceResolvingParser $resolvingParser)
    {
        $this->responseParser = $responseParser;
        $this->resolvingParser = $resolvingParser;
    }

    public function parsePointedSchema(SpecificationAccessor $specification, SpecificationPointer $pointer): SpecificationObjectMarkerInterface
    {
        $mockParameters = new MockParameters();
        $schema = $specification->getSchema($pointer);

        if (array_key_exists('responses', $schema)) {
            $responsesPointer = $pointer->withPathElement('responses');
            foreach ($schema['responses'] as $statusCode => $responseSpecification) {
                $responsePointer = $responsesPointer->withPathElement($statusCode);
                $this->validateResponse($statusCode, $responseSpecification, $responsePointer);

                /** @var MockResponse $response */
                $response = $this->resolvingParser->resolveReferenceAndParsePointedSchema($specification, $responsePointer, $this->responseParser);
                $parsedStatusCode = $this->parseStatusCode($statusCode);
                $response->statusCode = $parsedStatusCode;
                $mockParameters->responses->set($parsedStatusCode, $response);
            }
        }

        return $mockParameters;
    }

    private function validateResponse($statusCode, $responseSpecification, SpecificationPointer $pointer): void
    {
        if (!\is_int($statusCode) && $statusCode !== 'default') {
            throw new ParsingException('Invalid status code. Must be integer or "default".', $pointer);
        }
        if (!\is_array($responseSpecification)) {
            throw new ParsingException('Invalid response specification.', $pointer);
        }
    }

    private function parseStatusCode($statusCode): int
    {
        $parsedStatusCode = (int)$statusCode;

        if ($parsedStatusCode === 0) {
            $parsedStatusCode = MockResponse::DEFAULT_STATUS_CODE;
        }

        return $parsedStatusCode;
    }
}
