{{-- resources/views/emails/profile-updated.blade.php --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil mis à jour</title>
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
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e0e0e0;
        }
        .changes {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #D4AF37;
        }
        .change-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .change-item:last-child {
            border-bottom: none;
        }
        .old-value {
            color: #999;
            text-decoration: line-through;
            font-size: 0.9em;
        }
        .new-value {
            color: #D4AF37;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #D4AF37;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MARA BUSINESS</h1>
        <p>Votre profil a été mis à jour</p>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $user->name }}</strong>,</p>
        
        <p>Nous vous informons que les modifications suivantes ont été apportées à votre profil :</p>
        
        <div class="changes">
            @if($passwordChanged)
                <div class="change-item">
                    <strong>Mot de passe :</strong>
                    <div class="new-value">✓ Mot de passe modifié avec succès</div>
                </div>
            @endif
            
            @foreach($changes as $field => $newValue)
                <div class="change-item">
                    <strong>{{ ucfirst($field) }} :</strong>
                    @if(isset($oldData[$field]))
                        <div class="old-value">{{ $oldData[$field] }}</div>
                    @endif
                    <div class="new-value">{{ $newValue }}</div>
                </div>
            @endforeach
        </div>
        
        <p>Si vous n'êtes pas à l'origine de ces modifications, veuillez nous contacter immédiatement.</p>
        
        <div style="text-align: center;">
            <a href="{{ url('/profile') }}" class="button">Voir mon profil</a>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} MARA BUSINESS. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>