<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EditsPortfolios;
use App\Support\PortfolioContent;
use App\Support\PortfolioOwners;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A person editing their own portfolio, signed in with their family tree login.
 *
 * The slug is never taken from the request. It is resolved from who is signed
 * in, so there is no address anyone can type to reach somebody else's page —
 * the eight portfolios are one per person and the ownership is the identity.
 */
class MyPortfolioController extends Controller
{
    use EditsPortfolios;

    /** The portfolio belonging to whoever is signed in, or a 404. */
    private function ownSlug(Request $request): string
    {
        $slug = PortfolioOwners::slugFor($request->user());

        if ($slug === null) {
            // Not "forbidden": someone with no portfolio should not learn that
            // this address means anything at all.
            throw new NotFoundHttpException;
        }

        return $slug;
    }

    public function edit(Request $request)
    {
        $slug = $this->ownSlug($request);

        return view('portfolio.mine', [
            'slug' => $slug,
            'form' => PortfolioContent::formFor($slug),
            'url' => "https://{$slug}.khandanilegacy.com/",
            ...$this->photoView($slug),
        ]);
    }

    public function update(Request $request)
    {
        $slug = $this->ownSlug($request);

        $validated = $request->validate($this->portfolioRules($slug));
        $this->savePortfolio($request, $slug, $validated);

        // Saved and published together. An admin edits several people at once
        // and wants a moment to check before any of it is live; a person
        // editing their own page has pressed the button that means "put this
        // on my website", and a second button to finish the job is a trap.
        $result = PortfolioContent::publish([$slug])[$slug];

        return redirect()
            ->route('my-portfolio.edit')
            ->with('status', 'portfolio-updated')
            ->with('publish_result', $result);
    }
}
