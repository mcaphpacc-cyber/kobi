<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Logger;
use App\Services\PublicSearchService;
use InvalidArgumentException;
use Throwable;

class PublicSearchController extends Controller
{
    public function __construct(private PublicSearchService $service) {}

    public function index(): void
    {
        header('X-Robots-Tag: noindex, follow');
        $result = null;
        $error = null;
        $query = '';
        try {
            $result = $this->service->search($_GET['q'] ?? '');
            $query = $result['query'];
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            http_response_code(503);
            Logger::error('Public search failed: ' . get_class($exception));
            $error = 'Search is unavailable right now. Please try again.';
            try { $query = PublicSearchService::normalize($_GET['q'] ?? ''); } catch (InvalidArgumentException) {}
        }
        $this->view('search/index', ['title' => 'Search KOBI', 'query' => $query, 'result' => $result, 'error' => $error]);
    }
}
