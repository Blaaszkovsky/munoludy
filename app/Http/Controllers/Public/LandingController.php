<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Edition;
use App\Models\PageContent;
use App\Services\Registration\RegistrationService;
use App\Services\Security\TurnstileVerifier;
use Artesaos\SEOTools\Facades\SEOTools;

class LandingController extends Controller
{
    public function show()
    {
        $edition = Edition::active();
        abort_unless($edition, 404);
        $content = PageContent::where('edition_id', $edition->id)->where('view', 'landing')->firstOrFail();
        SEOTools::setTitle($content->og_title ?? 'Munoludy');
        SEOTools::setDescription($content->og_description ?? '');
        SEOTools::opengraph()->setUrl(request()->url());
        return view('landing', [
            'edition' => $edition,
            'content' => $content->content,
            'renderTs' => time(),
        ]);
    }

    public function register(RegisterRequest $request, TurnstileVerifier $turnstile, RegistrationService $service)
    {
        $edition = Edition::active();
        abort_unless($edition && $edition->isVotingOpen(), 403);

        if (!$turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withErrors(['email' => 'Weryfikacja Turnstile nie powiodła się.'])->withInput();
        }

        $participant = $service->register($edition, $request->input('email'), [
            'privacy' => $request->boolean('privacy_consent'),
            'marketing' => $request->boolean('marketing_consent'),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return back()->with('registered_email', $participant->email);
    }
}
