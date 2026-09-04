{{-- resources/views/emails/welcome.blade.php --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur MARA BUSINESS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #D4AF37 0%, #C9A12F 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .content {
            background: #f9f9f9;
            padding: 40px 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e0e0e0;
        }
        .welcome-message {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #D4AF37;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #C9A12F;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .feature {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .feature i {
            font-size: 24px;
            color: #D4AF37;
            margin-bottom: 10px;
        }
        .feature h3 {
            margin: 10px 0 5px;
            color: #333;
        }
        .feature p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #D4AF37;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MARA BUSINESS</h1>
        <p>Votre partenaire de confiance</p>
    </div>
    
    <div class="content">
        <div class="welcome-message">
            <h2 style="color: #D4AF37; margin-top: 0;">Bienvenue {{ $user->name }} !</h2>
            
            <p>Nous sommes ravis de vous accueillir sur MARA BUSINESS. Votre compte a été créé avec succès et vous pouvez maintenant profiter de tous nos services.</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/login') }}" class="button">Se connecter à mon compte</a>
            </div>
            
            <div class="features">
                <div class="feature">
                    <div style="font-size: 32px; color: #D4AF37;">🛍️</div>
                    <h3>Shopping</h3>
                    <p>Parcourez des milliers de produits</p>
                </div>
                <div class="feature">
                    <div style="font-size: 32px; color: #D4AF37;">🚚</div>
                    <h3>Livraison</h3>
                    <p>Suivez vos commandes en temps réel</p>
                </div>
                <div class="feature">
                    <div style="font-size: 32px; color: #D4AF37;">💳</div>
                    <h3>Paiement sécurisé</h3>
                    <p>Plusieurs méthodes de paiement</p>
                </div>
                <div class="feature">
                    <div style="font-size: 32px; color: #D4AF37;">⭐</div>
                    <h3>Avis clients</h3>
                    <p>Partagez votre expérience</p>
                </div>
            </div>
            
            <h3 style="color: #333;">Vos informations :</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Nom :</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $user->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Email :</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Date d'inscription :</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $user->created_at->format('d/m/Y') }}</td>
                </tr>
            </table>
            
            <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p style="margin: 0; color: #666; font-size: 0.9em;">
                    <strong>💡 Astuce :</strong> Complétez votre profil dans votre espace personnel pour une meilleure expérience d'achat.
                </p>
            </div>
        </div>
        
        <div class="social-links">
            <p>Suivez-nous sur les réseaux sociaux :</p>
            <a href="#">Facebook</a> •
            <a href="#">Instagram</a> •
            <a href="#">WhatsApp</a>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} MARA BUSINESS. Tous droits réservés.</p>
            <p style="font-size: 0.8em;">
                <a href="{{ url('/terms') }}" style="color: #D4AF37;">Conditions d'utilisation</a> •
                <a href="{{ url('/privacy') }}" style="color: #D4AF37;">Politique de confidentialité</a>
            </p>
        </div>
    </div>
</body>
</html>