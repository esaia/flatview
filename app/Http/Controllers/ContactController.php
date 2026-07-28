<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\HomepageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        $stored = HomepageSetting::where('key', 'like', 'contact_%')
            ->pluck('value', 'key')
            ->toArray();

        $get = fn (string $key, $default = '') => filled($stored[$key] ?? null) ? $stored[$key] : $default;
        $getJson = fn (string $key, array $default = []) => $this->json($stored[$key] ?? null, $default);

        $settings = [
            'kicker' => $get('contact_kicker', 'Contact'),
            'headline' => $get('contact_headline', 'Get in touch'),

            'formLabel' => $get('contact_form_label', 'Start a project'),
            'formSubtitle' => $get('contact_form_subtitle', 'Fill out the form below'),

            'emailLabel' => $get('contact_email_label', 'Just say hi'),
            'email' => $get('contact_email', 'hello@flatview.com'),

            'locationLabel' => $get('contact_location_label', 'Location'),
            'location' => $get('contact_location', 'Tbilisi, Georgia'),
            'locationNote' => $get('contact_location_note', 'Available for remote projects worldwide'),

            'socialLabel' => $get('contact_social_label', 'Follow us'),
            'socials' => $getJson('contact_socials', [
                ['label' => 'LinkedIn', 'url' => '#'],
                ['label' => 'Instagram', 'url' => '#'],
            ]),

            'whatsappLabel' => $get('contact_whatsapp_label', 'Scan to chat on WhatsApp'),
            'whatsappQr' => filled($stored['contact_whatsapp_qr'] ?? null)
                ? Storage::disk('public')->url($stored['contact_whatsapp_qr'])
                : null,
        ];

        $images = $this->json($stored['contact_images'] ?? null, []);

        $settings['images'] = count($images)
            ? array_map(fn (string $path) => Storage::disk('public')->url($path), $images)
            : [
                'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80',
                'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800&q=80',
                'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=800&q=80',
                'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&q=80',
            ];

        $turnstileSiteKey = config('services.turnstile.site_key');

        return Inertia::render('Contact', compact('settings', 'turnstileSiteKey'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:32',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'cf_turnstile_response' => 'required|string',
        ], [
            'cf_turnstile_response.required' => 'Please complete the verification challenge.',
        ]);

        if (! $this->verifyTurnstile($validated['cf_turnstile_response'], $request->ip())) {
            throw ValidationException::withMessages([
                'cf_turnstile_response' => 'Verification failed. Please try again.',
            ]);
        }

        ContactSubmission::create(collect($validated)->except('cf_turnstile_response')->all());

        return back()->with('success', 'Message sent. We will be in touch soon.');
    }

    /**
     * Validate a Turnstile token with Cloudflare's siteverify endpoint.
     */
    protected function verifyTurnstile(string $token, ?string $ip): bool
    {
        $secret = config('services.turnstile.secret_key');

        // Fail closed if the secret is missing so the form can't be bypassed.
        if (blank($secret)) {
            return false;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

        return $response->successful() && $response->json('success') === true;
    }

    /**
     * Decode a JSON setting value, falling back to a default when empty/invalid.
     */
    protected function json(?string $value, array $default): array
    {
        if (blank($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
