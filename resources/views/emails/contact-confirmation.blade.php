{{-- resources/views/emails/contact-confirmation.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Confirmation de contact</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #D4AF37; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { text-align: center; padding: 20px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MARA BUSINESS</h1>
            <p>Confirmation de votre message</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>{{ $contact->name }}</strong>,</p>
            <p>Nous avons bien reçu votre message et vous en remercions. Notre équipe vous répondra dans les plus brefs délais (généralement sous 24h).</p>
            <p><strong>Récapitulatif de votre message :</strong></p>
            <p>Sujet : {{ $contact->subject }}</p>
            <p>Message :</p>
            <p style="background: #f5f5f5; padding: 15px; border-radius: 5px;">{{ $contact->message }}</p>
            <p>À bientôt sur MARA BUSINESS !</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MARA BUSINESS. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>