<?php

namespace App\Http\Controllers;

use App\Models\AccountantInvitation;
use App\Models\User;
use App\Mail\AccountantInvitationSent;
use App\Mail\AccountantInvitationAccepted;
use App\Mail\AccountantInvitationRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AccountantInvitationController extends Controller
{
    /**
     * Display a listing of accountant invitations for companies.
     */
    public function index()
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $invitations = AccountantInvitation::where('company_id', Auth::id())
            ->with('accountant')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('accountant-invitations.index', compact('invitations'));
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create()
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        return view('accountant-invitations.create');
    }

    /**
     * Store a newly created invitation.
     */
    public function store(Request $request)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
        ]);

        // Check if accountant exists
        $accountant = User::where('email', $request->email)
            ->where('type', 'accountant')
            ->first();

        if (!$accountant) {
            return back()->withErrors(['email' => 'No accountant found with this email address.']);
        }

        // Check if invitation already exists
        $existingInvitation = AccountantInvitation::where('company_id', Auth::id())
            ->where('accountant_id', $accountant->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'An invitation already exists for this accountant.']);
        }

        // Create invitation
        $invitation = AccountantInvitation::create([
            'company_id' => Auth::id(),
            'accountant_id' => $accountant->id,
            'email' => $request->email,
            'message' => $request->message,
            'permissions' => $request->permissions ?? ['read'],
            'status' => 'pending',
        ]);

        // Send email notification
        try {
            Mail::to($accountant->email)->send(new AccountantInvitationSent($invitation));
        } catch (\Exception $e) {
            // Log the error but don't fail the invitation creation
            \Log::error('Failed to send accountant invitation email: ' . $e->getMessage());
        }

        return redirect()->route('accountant-invitations.index')
            ->with('success', 'Invitation sent successfully!');
    }

    /**
     * Display pending invitations for accountants.
     */
    public function pending()
    {
        if (Auth::user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $invitations = AccountantInvitation::where('accountant_id', Auth::id())
            ->where('status', 'pending')
            ->with('company')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('accountant-invitations.pending', compact('invitations'));
    }

    /**
     * Accept an invitation.
     */
    public function accept($id)
    {
        if (Auth::user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $invitation = AccountantInvitation::where('id', $id)
            ->where('accountant_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->accept();

        // Send email notification to company
        try {
            $company = User::find($invitation->company_id);
            if ($company) {
                Mail::to($company->email)->send(new AccountantInvitationAccepted($invitation));
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the acceptance
            \Log::error('Failed to send accountant invitation accepted email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation accepted successfully!');
    }

    /**
     * Reject an invitation.
     */
    public function reject($id)
    {
        if (Auth::user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $invitation = AccountantInvitation::where('id', $id)
            ->where('accountant_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->reject();

        // Send email notification to company
        try {
            $company = User::find($invitation->company_id);
            if ($company) {
                Mail::to($company->email)->send(new AccountantInvitationRejected($invitation));
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the rejection
            \Log::error('Failed to send accountant invitation rejected email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation rejected.');
    }

    /**
     * Cancel an invitation (for companies).
     */
    public function cancel($id)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $invitation = AccountantInvitation::where('id', $id)
            ->where('company_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->cancel();

        return redirect()->back()->with('success', 'Invitation cancelled.');
    }

    /**
     * Remove an accountant connection (for companies).
     */
    public function remove($id)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $invitation = AccountantInvitation::where('id', $id)
            ->where('company_id', Auth::id())
            ->where('status', 'accepted')
            ->firstOrFail();

        $invitation->cancel();

        return redirect()->back()->with('success', 'Accountant removed from your company.');
    }

    /**
     * Show the form for editing the specified invitation.
     */
    public function edit($id)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $invitation = AccountantInvitation::where('id', $id)
            ->where('company_id', Auth::id())
            ->whereIn('status', ['pending', 'accepted'])
            ->with('accountant')
            ->firstOrFail();

        return view('accountant-invitations.edit', compact('invitation'));
    }

    /**
     * Update the specified invitation.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        $invitation = AccountantInvitation::where('id', $id)
            ->where('company_id', Auth::id())
            ->whereIn('status', ['pending', 'accepted'])
            ->firstOrFail();

        $invitation->update([
            'permissions' => $request->permissions ?? ['read'],
        ]);

        return redirect()->route('accountant-invitations.index')->with('success', 'Invitation updated successfully.');
    }

    /**
     * Update invitation permissions (for companies).
     */
    public function updatePermissions(Request $request, $id)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        $invitation = AccountantInvitation::where('id', $id)
            ->where('company_id', Auth::id())
            ->whereIn('status', ['pending', 'accepted'])
            ->firstOrFail();

        $invitation->update([
            'permissions' => $request->permissions ?? ['read'],
        ]);

        return redirect()->back()->with('success', 'Permissions updated successfully.');
    }

    /**
     * Display clients (accepted invitations) for accountants.
     */
    public function clients()
    {
        if (Auth::user()->type !== 'accountant') {
            return redirect()->route('dashboard');
        }

        $invitations = AccountantInvitation::where('accountant_id', Auth::id())
            ->where('status', 'accepted')
            ->with('company')
            ->orderBy('accepted_at', 'desc')
            ->paginate(15);

        return view('accountant-invitations.accepted', compact('invitations'));
    }
}
