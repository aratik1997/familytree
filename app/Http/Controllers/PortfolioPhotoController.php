<?php

namespace App\Http\Controllers;

use App\Support\PortfolioOwners;
use App\Support\PortfolioPhoto;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves a portfolio's stored portrait back to whoever may edit it.
 *
 * The file sits under storage/, outside the web root, so that an unpublished
 * photograph is not readable by address before its owner has put it on their
 * page. Published copies live beside the page itself and are public there.
 */
class PortfolioPhotoController extends Controller
{
    public function __invoke(Request $request, string $slug): BinaryFileResponse
    {
        $user = $request->user();

        if (! PortfolioOwners::owns($user, $slug) && ! $user?->canManageTree()) {
            throw new NotFoundHttpException;
        }

        if (! PortfolioPhoto::exists($slug)) {
            throw new NotFoundHttpException;
        }

        // Cached hard, because the address carries the file's timestamp: a new
        // photograph is a new address, so a stale one can never be shown.
        return response()
            ->file(PortfolioPhoto::path($slug), ['Content-Type' => 'image/jpeg'])
            ->setMaxAge(31536000)
            ->setPrivate();
    }
}
