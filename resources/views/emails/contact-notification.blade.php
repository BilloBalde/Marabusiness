{{-- resources/views/emails/contact-notification.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Nouveau message de contact</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #D4AF37; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MARA BUSINESS</h1>
            <p>Nouveau message de contact</p>
        </div>
        <div class="content">
            <div class="info">
                <p><strong>Nom :</strong> {{ $contact->name }}</p>
                <p><strong>Email :</strong> {{ $contact->email }}</p>
                <p><strong>Phone :</strong> {{ $contact->phone }}</p>
                <p><strong>Sujet :</strong> {{ $contact->subject }}</p>
                <p><strong>Date :</strong> {{ $contact->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <p><strong>Message :</strong></p>
            <p>{{ $contact->message }}</p>
        </div>
    </div>
</body>
</html>