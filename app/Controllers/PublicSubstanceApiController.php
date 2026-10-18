<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\PublicApiResponse;
use App\Services\PublicApiException;
use App\Services\PublicApiService;

class PublicSubstanceApiController extends Controller
{
    public function __construct(private PublicApiService $service) {}

    public function search(): void
    {
        try {
            $result = $this->service->searchSubstances($_GET);
            PublicApiResponse::success($result['data'], $result['meta']);
        } catch (PublicApiException $error) {
            PublicApiResponse::error($error->status, $error->errorCode, $error->getMessage(), $error->fields);
        }
    }
}
