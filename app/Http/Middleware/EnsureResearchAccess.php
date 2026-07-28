<?php

namespace App\Http\Middleware;

use App\Models\Research\Research;
use App\Services\ResearchAccessDecision;
use App\Services\ResearchAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level gate for research documents.
 *
 * Resolves the research from the route, asks ResearchAccessService for a
 * decision and refuses the request before the controller runs. The decision is
 * stashed on the request as `research_access` so the controller can reuse it
 * without evaluating the rules a second time.
 *
 * Usage:  ->middleware('research.access')          // view access
 *         ->middleware('research.access:download') // view + download access
 */
class EnsureResearchAccess
{
    public function __construct(private ResearchAccessService $access)
    {
    }

    public function handle(Request $request, Closure $next, string $ability = 'view'): Response
    {
        $researchId = $request->route('id') ?? $request->route('research');

        if ($researchId === null) {
            return $next($request);
        }

        $research = $researchId instanceof Research
            ? $researchId
            : Research::find($researchId);

        if ($research === null) {
            return response()->json([
                'success' => false,
                'message' => 'Research not found',
            ], 404);
        }

        // These routes are public — the token is optional, so resolve through
        // the sanctum guard rather than the (session-based) default guard.
        $decision = $this->access->decide(auth('sanctum')->user(), $research);

        $request->attributes->set('research_access', $decision);
        $request->attributes->set('research_model', $research);

        if ($decision->denied()) {
            return response()->json($decision->toErrorResponse(), $decision->httpStatus());
        }

        if ($ability === 'download' && ! $decision->canDownload) {
            return response()->json([
                'success' => false,
                'code'    => ResearchAccessDecision::DOWNLOAD_DISABLED,
                'message' => 'Downloading is not enabled for this research paper.',
            ], 403);
        }

        return $next($request);
    }
}