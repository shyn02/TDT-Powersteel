<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use App\Models\Referral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    /**
     * One shared endpoint for four different frontend forms — matches
     * Django's submit_quote_view, which branched on which fields were
     * present in the POST body since all four forms post to the same
     * /api/submit-quote/ URL.
     */
    public function submit(Request $request): JsonResponse
    {
        // SEC-07: Layered abuse controls — payload size guard (10KB) before validation
        if (strlen(json_encode($request->all())) > 10240) {
            return response()->json(['status' => 'error', 'message' => 'Payload too large.'], 413);
        }

        // Honeypot anti-spam: hidden field "website" should stay empty for humans.
        // Bots that fill it are silently treated as success to avoid revealing the trap.
        if (trim((string) $request->input('website', '')) !== '') {
            try { \Illuminate\Support\Facades\Log::warning('Honeypot triggered on submit-quote', ['ip' => $request->ip(), 'ua' => $request->userAgent()]); } catch (\Throwable $e) {}
            return response()->json(['status' => 'success', 'message' => 'Your request has been received and saved!']);
        }

        // SEC-07: Duplicate/replay + time-check (bot submits <2s) + payload guard already above
        $clientIp = $request->ip();
        // Minimum human interaction time: 2 seconds (forms that include form_started_at are checked server-side)
        if ($request->filled('form_started_at')) {
            $started = (int) $request->input('form_started_at');
            $nowMs = (int) round(microtime(true) * 1000);
            if ($started > 0 && ($nowMs - $started) < 2000) {
                try { \Illuminate\Support\Facades\Log::warning('Rapid form submission (bot) blocked', ['ip' => $clientIp, 'elapsed_ms' => $nowMs - $started]); } catch (\Throwable $e) {}
                // Silent success to not reveal trap
                return response()->json(['status' => 'success', 'message' => 'Your request has been received and saved!']);
            }
        }
        // Per-IP rapid-fire guard (2s) even without form_started_at
        $lastKey = 'submit_last_' . md5($clientIp);
        $lastTs = \Illuminate\Support\Facades\Cache::get($lastKey);
        if ($lastTs && (microtime(true) - $lastTs) < 2) {
            return response()->json(['status' => 'error', 'message' => 'Please wait a moment before submitting again.'], 429);
        }
        $dupKey = 'submit_quote_dup_' . md5($clientIp . '|' . json_encode($request->only(['clientEmail','cEmail','ref_email','email','clientContact','cPhone','ref_phone','mobile'])));
        if (\Illuminate\Support\Facades\Cache::has($dupKey)) {
            return response()->json(['status' => 'error', 'message' => 'Please wait a moment before submitting again.'], 429);
        }
        \Illuminate\Support\Facades\Cache::put($lastKey, microtime(true), 60);

        // ---- Case 1: quoteForm modal (embedded on Home, Products, and every category page) ----
        if ($request->hasAny(['clientName', 'clientContact', 'estimatedQty'])) {
            $request->validate([
                'clientName' => ['required', 'string', 'max:150'],
                'clientCompany' => ['nullable', 'string', 'max:150'],
                'clientEmail' => ['nullable', 'email:rfc', 'max:255'],
                'clientContact' => ['nullable', 'string', 'max:50'],
                'clientAddress' => ['nullable', 'string', 'max:255'],
                'estimatedQty' => ['required', 'string', 'max:150'],
                'qHowHeard' => ['nullable', 'string', 'max:100'],
                'qHowHeardOther' => ['nullable', 'string', 'max:100'],
                'subProduct' => ['nullable', 'string', 'max:200'],
                'sizeSpec' => ['nullable', 'string', 'max:100'],
                'productCategory' => ['nullable', 'string', 'max:100'],
                'sourcePage' => ['nullable', 'in:home,product'],
                'website' => ['nullable', 'string', 'max:100'],
            ]);
            $fullName = trim((string) $request->input('clientName', ''));
            $companyName = trim((string) $request->input('clientCompany', ''));
            $email = trim((string) $request->input('clientEmail', ''));
            $phone = trim((string) $request->input('clientContact', ''));
            $address = trim((string) $request->input('clientAddress', ''));

            $howHeard = trim((string) $request->input('qHowHeard', ''));
            $howHeardOther = trim((string) $request->input('qHowHeardOther', ''));
            $howHeardLabel = ($howHeard === 'others' && $howHeardOther !== '') ? $howHeardOther : $howHeard;

            $estimatedQty = trim((string) $request->input('estimatedQty', ''));
            $subProduct = trim((string) $request->input('subProduct', ''));
            $sizeSpec = trim((string) $request->input('sizeSpec', ''));
            $extraBits = implode(' | ', array_filter([$subProduct, $sizeSpec]));
            if ($extraBits !== '') {
                $estimatedQty = $estimatedQty !== '' ? "{$estimatedQty} ({$extraBits})" : $extraBits;
            }

            $categoryName = trim((string) $request->input('productCategory', ''));
            $category = $categoryName !== ''
                ? ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first()
                : null;

            $sourcePage = strtolower(trim((string) $request->input('sourcePage', '')));
            if (! in_array($sourcePage, ['home', 'product'], true)) {
                $sourcePage = $category ? 'product' : 'home';
            }

            QuoteRequest::create([
                'category_id' => $category?->id,
                'full_name' => $fullName ?: 'Guest',
                'company_name' => $companyName ?: null,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'address' => $address ?: null,
                'how_heard' => $howHeardLabel ?: null,
                'estimated_qty' => $estimatedQty ?: '-',
                'status' => 'new',
                'source' => $sourcePage,
                'created_at' => now(),
            ]);
            \Illuminate\Support\Facades\Cache::put($dupKey, true, 60);

            return response()->json(['status' => 'success', 'message' => 'Your request has been received and saved!']);
        }

        // ---- Case 2: directContactForm (Contact page) -> ContactMessage, kept separate from QuoteRequest ----
        if ($request->hasAny(['cName', 'cEmail', 'cPhone'])) {
            $request->validate([
                'cName' => ['required', 'string', 'max:150'],
                'cCompany' => ['nullable', 'string', 'max:150'],
                'cEmail' => ['nullable', 'email:rfc', 'max:255'],
                'cPhone' => ['nullable', 'string', 'max:50'],
                'cLandline' => ['nullable', 'string', 'max:50'],
                'cAddress' => ['nullable', 'string', 'max:255'],
                'cHowHeard' => ['nullable', 'string', 'max:100'],
                'cHowHeardOther' => ['nullable', 'string', 'max:100'],
                'cSubject' => ['nullable', 'string', 'max:200'],
                'cMessage' => ['nullable', 'string', 'max:2000'],
                'website' => ['nullable', 'string', 'max:100'],
            ]);
            $howHeard = trim((string) $request->input('cHowHeard', ''));
            $howHeardOther = trim((string) $request->input('cHowHeardOther', ''));
            $howHeardLabel = ($howHeard === 'others' && $howHeardOther !== '') ? $howHeardOther : $howHeard;

            $subject = trim((string) $request->input('cSubject', ''));
            $message = trim((string) $request->input('cMessage', ''));
            $fullMessage = implode(' - ', array_filter([$subject, $message]));

            ContactMessage::create([
                'full_name' => trim((string) $request->input('cName', '')) ?: 'Guest',
                'company_name' => trim((string) $request->input('cCompany', '')) ?: null,
                'email' => trim((string) $request->input('cEmail', '')) ?: null,
                'phone' => trim((string) $request->input('cPhone', '')) ?: null,
                'landline' => trim((string) $request->input('cLandline', '')) ?: null,
                'address' => trim((string) $request->input('cAddress', '')) ?: null,
                'how_heard' => $howHeardLabel ?: null,
                'message' => $fullMessage,
                'status' => 'unread',
                'created_at' => now(),
            ]);
            \Illuminate\Support\Facades\Cache::put($dupKey, true, 60);

            return response()->json(['status' => 'success', 'message' => 'Your message has been received!']);
        }

        // ---- Case 3: Referral form (Referral page) -> Referral ----
        if ($request->hasAny(['ref_fullname', 'ref_email', 'ref_contact_person', 'ref_referred_company'])) {
            $request->validate([
                'ref_fullname' => ['required', 'string', 'max:200'],
                'ref_company' => ['nullable', 'string', 'max:200'],
                'ref_phone' => ['nullable', 'string', 'max:50'],
                'ref_email' => ['nullable', 'email:rfc', 'max:255'],
                'ref_contact_person' => ['required', 'string', 'max:200'],
                'ref_referred_company' => ['required', 'string', 'max:200'],
                'ref_project_type' => ['required', 'string', 'max:100'],
                'ref_project_scale' => ['required', 'string', 'max:100'],
                'ref_region' => ['required', 'string', 'max:150'],
                'ref_remarks' => ['nullable', 'string', 'max:2000'],
                'website' => ['nullable', 'string', 'max:100'],
            ]);
            Referral::create([
                'referrer_name' => trim((string) $request->input('ref_fullname', $request->input('referrer_name', ''))) ?: 'Guest',
                'referrer_company' => trim((string) $request->input('ref_company', $request->input('referrer_company', ''))) ?: null,
                'referrer_phone' => trim((string) $request->input('ref_phone', $request->input('referrer_phone', ''))) ?: null,
                'referrer_email' => trim((string) $request->input('ref_email', $request->input('referrer_email', ''))) ?: null,
                'contact_person' => trim((string) $request->input('ref_contact_person', $request->input('contact_person', ''))) ?: '-',
                'referred_company' => trim((string) $request->input('ref_referred_company', $request->input('referred_company', ''))) ?: '-',
                'project_type' => trim((string) $request->input('ref_project_type', $request->input('project_type', ''))) ?: '-',
                'project_scale' => trim((string) $request->input('ref_project_scale', $request->input('project_scale', ''))) ?: '-',
                'region' => trim((string) $request->input('ref_region', $request->input('region', ''))) ?: '-',
                'remarks' => trim((string) $request->input('ref_remarks', $request->input('remarks', ''))) ?: null,
                'status' => 'new',
                'created_at' => now(),
            ]);
            \Illuminate\Support\Facades\Cache::put($dupKey, true, 60);

            return response()->json(['status' => 'success', 'message' => 'Thank you! Your referral has been received and recorded in the system.']);
        }

        // ---- Case 4: heroQuoteForm (Home page hero card) -> QuoteRequest ----
        if ($request->hasAny(['name', 'email', 'mobile'])) {
            $request->validate([
                'name' => ['required', 'string', 'max:150'],
                'company' => ['nullable', 'string', 'max:150'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'mobile' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string', 'max:255'],
                'howHeard' => ['nullable', 'string', 'max:100'],
                'howHeardOther' => ['nullable', 'string', 'max:100'],
                'remarks' => ['nullable', 'string', 'max:150'],
                'website' => ['nullable', 'string', 'max:100'],
            ]);
            $howHeard = trim((string) $request->input('howHeard', ''));
            $howHeardOther = trim((string) $request->input('howHeardOther', ''));
            $howHeardLabel = ($howHeard === 'others' && $howHeardOther !== '') ? $howHeardOther : $howHeard;

            QuoteRequest::create([
                'category_id' => null,
                'full_name' => trim((string) $request->input('name', '')) ?: 'Guest',
                'company_name' => trim((string) $request->input('company', '')) ?: null,
                'email' => trim((string) $request->input('email', '')) ?: null,
                'phone' => trim((string) $request->input('mobile', '')) ?: null,
                'address' => trim((string) $request->input('address', '')) ?: null,
                'how_heard' => $howHeardLabel ?: null,
                'estimated_qty' => trim((string) $request->input('remarks', '')) ?: '-',
                'status' => 'new',
                // NOTE: Django's original code wrote source='quote' here, which
                // isn't actually one of the model's valid choices (home/product)
                // — normalized to 'home' here since this form lives on the Home page.
                'source' => 'home',
                'created_at' => now(),
            ]);
            \Illuminate\Support\Facades\Cache::put($dupKey, true, 60);

            return response()->json(['status' => 'success', 'message' => 'Your request has been received and saved!']);
        }

        return response()->json(['status' => 'error', 'message' => 'No recognized fields in the submitted form.'], 400);
    }

    /**
     * JSON for the "Request a Quote" popup's cascading Category -> Product
     * -> Size dropdowns.
     */
    public function productData(Request $request): JsonResponse
    {
        $data = [];

        $categories = ProductCategory::where('is_active', true)->with(['products' => function ($q) {
            $q->where('is_active', true);
        }])->get();

        foreach ($categories as $category) {
            $items = [];
            foreach ($category->products as $product) {
                $entry = ['name' => $product->name];
                $sizes = $product->sizesList();
                if (count($sizes)) {
                    $entry['sizes'] = $sizes;
                }
                $items[] = $entry;
            }
            if (count($items)) {
                $data[$category->name] = $items;
            }
        }

        $data['General Steel Inquiry'] = [
            ['name' => 'Custom Steel Fabrication'],
            ['name' => 'Bulk Construction Project Supply'],
            ['name' => 'Other Steel Materials'],
        ];

        return response()->json($data);
    }

    /**
     * The quick-quote modal's "Your Name / Company" field is one plain
     * text input, so people sometimes type "Juan Dela Cruz (ABC
     * Construction)". Split that pattern out if present.
     */
    private function splitNameCompany(?string $raw): array
    {
        $raw = trim((string) $raw);
        if (preg_match('/^(?<name>.*?)\s*\((?<company>[^()]+)\)\s*$/', $raw, $m)) {
            return [trim($m['name']), trim($m['company'])];
        }

        return [$raw, ''];
    }

    /**
     * The quick-quote modal only has one combined "contact number or
     * email" text input. Best-effort guess: if it looks like an email,
     * store as email; otherwise treat as a phone number.
     */
    private function splitContact(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return ['', ''];
        }
        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return [$raw, ''];
        }

        return ['', $raw];
    }
}
