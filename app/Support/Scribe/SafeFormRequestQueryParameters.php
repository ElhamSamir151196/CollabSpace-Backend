<?php

namespace App\Support\Scribe;

use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromFormRequest;
use ReflectionClass;
use Throwable;

/**
 * Query parameters from the FormRequests of GET endpoints (filters, sorting,
 * pagination), without needing a "Query parameters" doc comment on each one.
 */
class SafeFormRequestQueryParameters extends GetFromFormRequest
{
    /**
     * @param  array<string, mixed>  $routeRules
     * @return array<string, mixed>|null
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        return in_array('GET', $endpointData->httpMethods, true) ? parent::__invoke($endpointData, $routeRules) : null;
    }

    /**
     * @param  ReflectionClass<FormRequest>  $formRequestReflectionClass
     */
    protected function isFormRequestMeantForThisStrategy(ReflectionClass $formRequestReflectionClass): bool
    {
        return true;
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
