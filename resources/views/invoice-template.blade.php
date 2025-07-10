<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - {{ $reservation->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 40px;
            color: #2d3748;
            line-height: 1.6;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .header {
            background: linear-gradient(135deg, #ff8359 0%, #ff6b35 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
                 .header::before {
             content: '';
             position: absolute;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background: rgba(255, 255, 255, 0.05);
             background-image: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                              radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                              radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
             background-size: 50px 50px, 60px 60px, 40px 40px;
         }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ff8359;
            font-weight: bold;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .invoice-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 16px;
            opacity: 0.95;
        }
        
        .invoice-meta {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        
        .invoice-meta-item {
            text-align: center;
        }
        
        .invoice-meta-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .invoice-meta-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .content {
            padding: 40px;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .detail-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            border-left: 4px solid #ff8359;
        }
        
        .detail-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-card-title::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #ff8359;
            border-radius: 50%;
        }
        
        .detail-item {
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 80px;
            display: inline-block;
        }
        
        .detail-value {
            color: #2d3748;
        }
        
        .service-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #ff8359, #ff6b35);
            border-radius: 2px;
        }
        
        .service-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .service-table th {
            background: linear-gradient(135deg, #ff8359, #ff6b35);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .service-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        .service-table tr:last-child td {
            border-bottom: none;
        }
        
        .service-description {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .service-details {
            font-size: 12px;
            color: #718096;
        }
        
        .price-cell {
            text-align: right;
            font-weight: 600;
            color: #2d3748;
        }
        
        .total-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin-top: 40px;
            border: 2px solid #e2e8f0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .total-row:last-child {
            border-bottom: none;
            margin-top: 8px;
            padding-top: 16px;
            border-top: 2px solid #ff8359;
        }
        
        .total-label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .total-amount {
            font-weight: 600;
            color: #2d3748;
        }
        
        .total-final .total-label,
        .total-final .total-amount {
            font-size: 18px;
            color: #ff8359;
            font-weight: 700;
        }
        
        .payment-info {
            background: linear-gradient(135deg, #e6fffa, #f0fff4);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid #9ae6b4;
        }
        
        .payment-info-title {
            font-weight: 600;
            color: #276749;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-info-title::before {
            content: '✓';
            background: #48bb78;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .payment-info-text {
            font-size: 14px;
            color: #276749;
            line-height: 1.5;
        }
        
        .footer {
            background: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .footer-logo {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #ff8359;
        }
        
        .footer-info {
            opacity: 0.8;
            margin-bottom: 16px;
        }
        
        .footer-contact {
            padding-top: 16px;
            border-top: 1px solid #4a5568;
            opacity: 0.6;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            border: 1px solid #fc8181;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }
        
        .highlight-title {
            font-weight: 600;
            color: #c53030;
            margin-bottom: 8px;
        }
        
        .highlight-text {
            font-size: 14px;
            color: #742a2a;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="header-content">
                <div class="company-logo">🧸</div>
                <div class="company-name">Trouve ta Babysitter</div>
                <div class="invoice-title">FACTURE DE SERVICE</div>
                
                <div class="invoice-meta">
                    <div class="invoice-meta-item">
                        <div class="invoice-meta-label">Numéro</div>
                        <div class="invoice-meta-value">{{ $invoiceNumber }}</div>
                    </div>
                    <div class="invoice-meta-item">
                        <div class="invoice-meta-label">Date d'émission</div>
                        <div class="invoice-meta-value">{{ $invoiceDate }}</div>
                    </div>
                    <div class="invoice-meta-item">
                        <div class="invoice-meta-label">Réservation</div>
                        <div class="invoice-meta-value">#{{ $reservation->id }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="details-grid">
                <div class="detail-card">
                    <div class="detail-card-title">Client</div>
                    <div class="detail-item">
                        <span class="detail-label">Nom :</span>
                        <span class="detail-value">{{ $reservation->parent->firstname }} {{ $reservation->parent->lastname }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email :</span>
                        <span class="detail-value">{{ $reservation->parent->email }}</span>
                    </div>
                    @if($reservation->parent->address)
                        <div class="detail-item">
                            <span class="detail-label">Adresse :</span>
                            <span class="detail-value">{{ $reservation->parent->address->full_address }}</span>
                        </div>
                    @endif
                </div>

                <div class="detail-card">
                    <div class="detail-card-title">Prestation</div>
                    <div class="detail-item">
                        <span class="detail-label">Babysitter :</span>
                        <span class="detail-value">{{ $reservation->babysitter->firstname }} {{ $reservation->babysitter->lastname }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date :</span>
                        <span class="detail-value">{{ $serviceDate }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Horaires :</span>
                        <span class="detail-value">{{ $serviceTime }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Durée :</span>
                        <span class="detail-value">{{ $duration }}h</span>
                    </div>
                    @if($reservation->ad->address)
                        <div class="detail-item">
                            <span class="detail-label">Lieu :</span>
                            <span class="detail-value">{{ $reservation->ad->address->full_address }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="service-section">
                <div class="section-title">Détail de la facturation</div>
                
                <table class="service-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 15%;">Durée</th>
                            <th style="width: 15%;">Taux horaire</th>
                            <th style="width: 20%;">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="service-description">Service de garde d'enfants</div>
                                <div class="service-details">
                                    {{ $serviceDate }} • {{ $serviceTime }}<br>
                                    Avec {{ $reservation->babysitter->firstname }} {{ $reservation->babysitter->lastname }}
                                </div>
                            </td>
                            <td class="price-cell">{{ $duration }}h</td>
                            <td class="price-cell">{{ number_format($actualHourlyRate, 2, ',', ' ') }}€</td>
                            <td class="price-cell">{{ number_format($actualServiceAmount, 2, ',', ' ') }}€</td>
                        </tr>
                    </tbody>
                </table>

                <div class="total-section">
                    <div class="total-row">
                        <span class="total-label">Sous-total service :</span>
                        <span class="total-amount">{{ number_format($actualServiceAmount, 2, ',', ' ') }}€</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Frais de plateforme :</span>
                        <span class="total-amount">{{ number_format($actualServiceFee, 2, ',', ' ') }}€</span>
                    </div>
                    <div class="total-row total-final">
                        <span class="total-label">TOTAL PAYÉ :</span>
                        <span class="total-amount">{{ number_format($actualTotalPaid, 2, ',', ' ') }}€</span>
                    </div>
                </div>
            </div>

            <div class="payment-info">
                <div class="payment-info-title">Paiement effectué</div>
                <div class="payment-info-text">
                    Ce montant a été débité de votre compte et versé à la babysitter via notre plateforme sécurisée. 
                    Le paiement correspond exactement au service rendu selon les termes convenus.
                </div>
            </div>

            <div class="highlight-box">
                <div class="highlight-title">Important</div>
                <div class="highlight-text">
                    Cette facture correspond au montant réellement payé pour le service de garde d'enfants. 
                    Elle fait foi du service rendu et du paiement effectué via notre plateforme.
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-logo">Trouve ta Babysitter</div>
            <div class="footer-info">
                Plateforme de mise en relation pour services de garde d'enfants<br>
                Service client : contact@trouvetababysitter.fr
            </div>
            <div class="footer-contact">
                Facture générée automatiquement le {{ now()->format('d/m/Y à H:i') }}<br>
                En cas de question, contactez notre service client.
            </div>
        </div>
    </div>
</body>
</html> 