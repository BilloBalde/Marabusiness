<?php
// app/Http/Controllers/Api/ContactController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactConfirmation;
use App\Mail\ContactNotification;

class ContactController extends Controller
{
    /**
     * Submit contact form - PUBLIC (no auth required)
     */
    public function submit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:255',
                'subject' => 'required|string|min:3|max:255',
                'message' => 'required|string|min:5|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Save to database
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'new',
            ]);

            // Send confirmation email to user
            try {
                Mail::to($contact->email)->send(new ContactConfirmation($contact));
            } catch (\Exception $e) {
                \Log::error('Failed to send confirmation email: ' . $e->getMessage());
            }

            // Send notification to admin
            try {
                Mail::to(config('mail.admin_address', 'contact@afrobridgeinnov.com'))
                    ->send(new ContactNotification($contact));
            } catch (\Exception $e) {
                \Log::error('Failed to send admin notification: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès ! Nous vous répondrons dans les 24h.',
                'data' => [
                    'id' => $contact->id,
                    'created_at' => $contact->created_at->toDateTimeString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Contact submission error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Échec de l\'envoi du message. Veuillez réessayer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}