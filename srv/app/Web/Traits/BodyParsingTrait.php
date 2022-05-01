<?php

namespace Server\App\Web\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait BodyParsingTrait
{
    /**
     * @param ServerRequestInterface $request
     * @return string|null The serverRequest media type, minus content-type params
     */
    protected function getMediaType(ServerRequestInterface $request): ?string
    {
        $contentType = $request->getHeader('Content-Type')[0] ?? null;

        if (is_string($contentType) && trim($contentType) !== '') {
            $contentTypeParts = explode(';', $contentType);
            return strtolower(trim($contentTypeParts[0]));
        }

        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $type
     * @return bool
     */
    protected function shouldParseRequestBodyAsType(ServerRequestInterface $request, string $type): bool
    {
        $mediaType = $this->getMediaType($request);
        if ($mediaType === null) {
            return false;
        }

        // Check if this specific media type has a parser registered first
        if (!isset($this->bodyParsers[$mediaType])) {
            // If not, look for a media type with a structured syntax suffix (RFC 6839)
            $parts = explode('+', $mediaType);
            if (count($parts) >= 2) {
                $mediaType = 'application/' . $parts[count($parts) - 1];
            }
        }
        return $type == $mediaType;
    }
}
