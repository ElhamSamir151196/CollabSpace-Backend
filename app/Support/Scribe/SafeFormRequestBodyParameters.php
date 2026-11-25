<?php

namespace App\Support\Scribe;

use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromFormRequest;
use Throwable;

/**
 * Body parameters from FormRequests, for POST / PUT / PATCH / DELETE only
 * (GET requests are documented as query parameters, see SafeFormRequestQueryParameters).
 *
 * Scribe reads rules() without a real request, so route models (e.g. {task})
 * are not bound. A few rules depend on them (a reviewer must be a member of
 * the task's project ...). For those, the endpoint is documented without
 * parameters instead of failing the whole generation.
 */
class SafeFormRequestBodyParameters extends GetFromFormRequest
{
    /**
     * @param  array<string, mixed>  $routeRules
     * @return array<string, mixed>|null
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        return in_array('GET', $endpointData->httpMethods, true) ? null : parent::__invoke($endpointData, $routeRules);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRouteValidationRules(FormRequest $formRequest): array
    {
        try {
            return (array) parent::getRouteValidationRules($formRequest);
        } catch (Throwable) {
            return [];
        }
    }
}
