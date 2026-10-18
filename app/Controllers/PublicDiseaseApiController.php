<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\PublicApiResponse;
use App\Services\PublicApiException;
use App\Services\PublicApiService;

class PublicDiseaseApiController extends Controller
{
    public function __construct(private PublicApiService $service) {}

    private function respond(callable $read): void
    {
        try {
            $result = $read();
            PublicApiResponse::success($result['data'], $result['meta']);
        } catch (PublicApiException $error) {
            PublicApiResponse::error($error->status, $error->errorCode, $error->getMessage(), $error->fields);
        }
    }

    public function index(): void { $this->respond(fn() => $this->service->listDiseases($_GET)); }
    public function search(): void { $this->respond(fn() => $this->service->searchDiseases($_GET)); }
    public function show(string $slug): void { $this->respond(fn() => $this->service->detail($slug, $_GET)); }
}
