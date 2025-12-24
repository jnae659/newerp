<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\AccountantService;
use App\Models\AccountantInvitation;
use App\Mail\AccountantInvitationSent;
use App\Models\Utility;

class AccountantMarketplaceController extends Controller
{
    /**
     * Display the accountant marketplace for companies.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (!auth()->check() || auth()->user()->type === 'accountant') {
            return redirect()->route('dashboard');
        }

        // Get all accountants who are available for hire
        $accountants = User::where('type', 'accountant')
            ->where('email_verified_at', '!=', null)
            ->select('id', 'name', 'email', 'avatar', 'created_at', 'experience_years', 'specialties', 'bio')
            ->paginate(12);

        // Set default values for fields not yet implemented
        $accountants->each(function ($accountant) {
            // Map bio to description for view compatibility
            $accountant->description = $accountant->bio ?? 'Professional accountant with extensive experience in financial management and compliance.';

            // Set default values
            $accountant->rating = $accountant->rating ?? number_format(4.5, 1);
            $accountant->review_count = $accountant->review_count ?? 0;
            $accountant->hourly_rate = $accountant->hourly_rate ?? 75;

            // Ensure specialties is an array
            $accountant->specialties = $accountant->specialties ?? ['General Accounting'];
        });

        return view('accountant-marketplace.index', compact('accountants'));
    }

    /**
     * Display details of a specific accountant.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        if (!auth()->check() || auth()->user()->type === 'accountant') {
            return redirect()->route('dashboard');
        }

        $accountant = User::where('type', 'accountant')
            ->where('id', $id)
            ->where('email_verified_at', '!=', null)
            ->firstOrFail();

        // Map bio to description for view compatibility
        $accountant->description = $accountant->bio ?? 'Professional accountant with extensive experience in financial management and compliance.';

        // Parse certifications string into array for view compatibility
        $accountant->certifications = $accountant->certifications
            ? array_map('trim', explode(',', $accountant->certifications))
            : ['Not specified'];

        // Set default values for fields not yet implemented
        $accountant->rating = $accountant->rating ?? number_format(4.5, 1);
        $accountant->review_count = $accountant->review_count ?? 0;
        $accountant->hourly_rate = $accountant->hourly_rate ?? 75;

        // Get accountant's available services
        $services = AccountantService::where('accountant_id', $accountant->id)
            ->where('is_available', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('accountant-marketplace.show', compact('accountant', 'services'));
    }

    /**
     * Send a service request to an accountant.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestService(Request $request, $id)
    {
        if (!auth()->check() || auth()->user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'service_type' => 'required|string',
            'description' => 'required|string|min:10',
            'duration' => 'required|string',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $accountant = User::where('type', 'accountant')
            ->where('id', $id)
            ->where('email_verified_at', '!=', null)
            ->firstOrFail();

        // Check if invitation already exists
        $existingInvitation = AccountantInvitation::where('company_id', auth()->id())
            ->where('accountant_id', $accountant->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingInvitation) {
            return redirect()->back()->withErrors(['invitation' => 'An invitation already exists for this accountant.']);
        }

        // Create invitation
        $invitation = AccountantInvitation::create([
            'company_id' => auth()->id(),
            'accountant_id' => $accountant->id,
            'email' => $accountant->email,
            'message' => $request->description,
            'permissions' => ['read'],
            'status' => 'pending',
        ]);

        // Send email notification
        try {
            Mail::to($accountant->email)->send(new AccountantInvitationSent($invitation));
        } catch (\Exception $e) {
            // Log the error but don't fail the invitation creation
            \Log::error('Failed to send accountant invitation email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', __('Service request sent successfully to ') . $accountant->name);
    }

    /**
     * Send a service request for a specific service.
     *
     * @param int $accountant_id
     * @param int $service_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestSpecificService($accountant_id, $service_id)
    {
        if (!auth()->check() || auth()->user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $accountant = User::where('type', 'accountant')
            ->where('id', $accountant_id)
            ->where('email_verified_at', '!=', null)
            ->firstOrFail();

        $service = AccountantService::where('id', $service_id)
            ->where('accountant_id', $accountant_id)
            ->where('is_available', true)
            ->firstOrFail();

        // Check if invitation already exists
        $existingInvitation = AccountantInvitation::where('company_id', auth()->id())
            ->where('accountant_id', $accountant->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingInvitation) {
            return redirect()->back()->withErrors(['invitation' => 'An invitation already exists for this accountant.']);
        }

        // Create invitation
        $invitation = AccountantInvitation::create([
            'company_id' => auth()->id(),
            'accountant_id' => $accountant->id,
            'email' => $accountant->email,
            'message' => __('Service request for: ') . $service->service_name . ($service->description ? ' - ' . $service->description : ''),
            'permissions' => ['read'],
            'status' => 'pending',
        ]);

        // Send email notification
        try {
            Mail::to($accountant->email)->send(new AccountantInvitationSent($invitation));
        } catch (\Exception $e) {
            // Log the error but don't fail the invitation creation
            \Log::error('Failed to send accountant invitation email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', __('Service request for "') . $service->service_name . __('" sent successfully to ') . $accountant->name);
    }

    /**
     * Display accountant services for the authenticated accountant.
     *
     * @return \Illuminate\View\View
     */
    public function accountantServices()
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $services = AccountantService::where('accountant_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('accountant.services.index', compact('services'));
    }

    /**
     * Show the form for creating a new service.
     *
     * @return \Illuminate\View\View
     */
    public function createService()
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        return view('accountant.services.create');
    }

    /**
     * Store a newly created service.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeService(Request $request)
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'service_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_rate' => 'nullable|numeric|min:0',
            'fixed_rate' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
        ]);

        AccountantService::create([
            'accountant_id' => auth()->id(),
            'service_name' => $request->service_name,
            'description' => $request->description,
            'category' => $request->category,
            'hourly_rate' => $request->hourly_rate,
            'monthly_rate' => $request->monthly_rate,
            'fixed_rate' => $request->fixed_rate,
            'is_available' => $request->boolean('is_available', true),
        ]);

        return redirect()->route('accountant.services.index')->with('success', __('Service created successfully.'));
    }

    /**
     * Show the form for editing a service.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function editService($id)
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $service = AccountantService::where('id', $id)
            ->where('accountant_id', auth()->id())
            ->firstOrFail();

        return view('accountant.services.edit', compact('service'));
    }

    /**
     * Update the specified service.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateService(Request $request, $id)
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $service = AccountantService::where('id', $id)
            ->where('accountant_id', auth()->id())
            ->firstOrFail();

        $request->validate([
            'service_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_rate' => 'nullable|numeric|min:0',
            'fixed_rate' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
        ]);

        $service->update([
            'service_name' => $request->service_name,
            'description' => $request->description,
            'category' => $request->category,
            'hourly_rate' => $request->hourly_rate,
            'monthly_rate' => $request->monthly_rate,
            'fixed_rate' => $request->fixed_rate,
            'is_available' => $request->boolean('is_available', true),
        ]);

        return redirect()->route('accountant.services.index')->with('success', __('Service updated successfully.'));
    }

    /**
     * Remove the specified service.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteService($id)
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $service = AccountantService::where('id', $id)
            ->where('accountant_id', auth()->id())
            ->firstOrFail();

        $service->delete();

        return redirect()->route('accountant.services.index')->with('success', __('Service deleted successfully.'));
    }

    /**
     * Display the business profile edit form.
     *
     * @return \Illuminate\View\View
     */
    public function businessProfile()
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $user = auth()->user();

        return view('accountant.business-profile.edit', compact('user'));
    }

    /**
     * Update the business profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBusinessProfile(Request $request)
    {
        if (!auth()->check() || auth()->user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'accountant_type' => 'required|in:individual,firm',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'experience_years' => 'nullable|integer|min:0',
            'certifications' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'languages' => 'nullable|array',
            'bio' => 'required|string|max:1000',
            'specialties' => 'nullable|array',
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->accountant_type = $request->accountant_type;
        $user->experience_years = $request->experience_years;
        $user->certifications = $request->certifications;
        $user->education = $request->education;
        $user->languages = $request->languages;
        $user->bio = $request->bio;
        $user->specialties = $request->specialties;

        if ($request->hasFile('avatar')) {
            // Handle avatar upload
            $avatarName = time() . '.' . $request->avatar->extension();
            $request->avatar->move(public_path('uploads/avatar'), $avatarName);
            $user->avatar = 'uploads/avatar/' . $avatarName;
        }

        $user->save();

        return redirect()->route('accountant.business-profile')->with('success', __('Business profile updated successfully.'));
    }
}
