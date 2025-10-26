<?php

namespace App\Services\Gateway;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RequestRouter
{
    /**
     * @var array<int, string>
     */
    protected array $supportedVersions = ['v1', 'v2'];

    public function __construct(private Application $app)
    {
    }

    /**
     * Prepare the incoming request by normalizing the payload and validating basic headers.
     */
    public function prepare(Request $request): string
    {
        $this->normalizeInputs($request);

        $version = $this->resolveVersion($request);

        $validator = Validator::make(
            ['Accept-Version' => $request->headers->get('Accept-Version')],
            ['Accept-Version' => ['nullable', 'regex:/^v\d+$/i']]
        );

        if ($validator->fails()) {
            // Merge validation errors into the request for downstream consumers if needed.
            $request->attributes->set('api_version_errors', $validator->errors()->toArray());
        }

        if (!in_array($version, $this->supportedVersions, true)) {
            $version = $this->supportedVersions[0];
        }

        return $version;
    }

    /**
     * Forward a v2 request to the legacy v1 routes after preparing the payload.
     */
    public function forwardToLegacy(Request $request, string $path): Response
    {
        $version = $request->attributes->get('resolved_api_version') ?? $this->prepare($request);

        if ($version !== 'v2') {
            return $this->app->make('router')->dispatch($request);
        }

        $forward = Request::create(
            '/api/v1/' . ltrim($path, '/'),
            $request->getMethod(),
            $request->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        foreach ($request->headers->all() as $key => $values) {
            $forward->headers->set($key, $values, false);
        }

        $forward->headers->set('Accept-Version', 'v1');
        $forward->attributes->set('resolved_api_version', 'v1');
        $forward->setUserResolver($request->getUserResolver());
        $forward->setRouteResolver($request->getRouteResolver());

        return $this->app->make('router')->dispatch($forward);
    }

    /**
     * Supported API versions.
     *
     * @return array<int, string>
     */
    public function supportedVersions(): array
    {
        return $this->supportedVersions;
    }

    protected function normalizeInputs(Request $request): void
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });

        $request->replace($input);
    }

    protected function resolveVersion(Request $request): string
    {
        $version = $request->headers->get('Accept-Version');

        if (is_string($version)) {
            $version = strtolower($version);
        }

        return $version ?: $this->supportedVersions[0];
    }
}
