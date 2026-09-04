<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de vendeur soumise</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #D4AF37 0%, #C9A12F 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #D4AF37;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box p {
            margin: 5px 0;
        }
        .info-box strong {
            color: #D4AF37;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #D4AF37;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .button:hover {
            background: #C9A12F;
        }
        .footer {
            background: #f4f4f4;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #ffc107;
            color: #000;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MARA BUSINESS</h1>
        </div>
        
        <div class="content">
            <h2>Bonjour {{ $user->name }},</h2>
            
            <p>Nous avons bien reçu votre demande pour devenir vendeur sur <strong>MARA BUSINESS</strong>.</p>
            
            <div class="info-box">
                <h3 style="margin-top: 0; color: #D4AF37;">Récapitulatif de votre demande</h3>
                <p><strong>Boutique:</strong> {{ $vendor->store_name }}</p>
                <p><strong>Date de soumission:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                <p><strong>Statut:</strong> <span class="status-badge">En attente d'approbation</span></p>
            </div>
            
            <p>Votre demande est actuellement en cours d'examen par notre équipe. Vous recevrez un email dès que votre compte vendeur sera approuvé.</p>
            
            <p><strong>Prochaines étapes:</strong></p>
            <ul>
                <li>Notre équipe examinera votre demande dans les plus brefs délais</li>
                <li>Vous recevrez une notification par email dès que votre compte sera activé</li>
                <li>Une fois approuvé, vous pourrez commencer à ajouter vos produits et gérer votre boutique</li>
            </ul>

            <p>Vous pourrez apres vous connecter a votre compte en suivant ce lien: <a href="https://afrobridgeinnov.com/vendor">AFROBRIDGE VENDOR</a></p>
            
            <p>En attendant, vous pouvez déjà préparer vos produits et réfléchir à votre stratégie de vente.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('vendors.list') }}" class="button">Voir les boutiques</a>
            </div>
            
            <p style="margin-top: 30px;">Si vous avez des questions, n'hésitez pas à nous contacter.</p>
            
            <p>Cordialement,<br>L'équipe MARA BUSINESS</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} MARA BUSINESS. Tous droits réservés.</p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>