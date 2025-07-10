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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            color: #333;
            line-height: 1.4;
            background: white;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
        }
        
        .header {
            background: #ff8359;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .invoice-title {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .invoice-meta {
            text-align: center;
        }
        
        .meta-item {
            display: inline-block;
            margin: 0 10px;
            border-right: 1px solid rgba(255,255,255,0.3);
            padding-right: 10px;
        }
        
        .meta-item:last-child {
            border-right: none;
        }
        
        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
            opacity: 0.8;
            display: block;
        }
        
        .meta-value {
            font-size: 12px;
            font-weight: bold;
            display: block;
        }
        
        .content {
            padding: 20px;
        }
        
        .details-section {
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .detail-card {
            width: 48%;
            float: left;
            padding: 15px;
            border: 1px solid #eee;
            margin-right: 2%;
            background: #f9f9f9;
        }
        
        .detail-card:last-child {
            margin-right: 0;
        }
        
        .detail-card-title {
            font-size: 14px;
            font-weight: bold;
            color: #ff8359;
            margin-bottom: 10px;
            border-bottom: 2px solid #ff8359;
            padding-bottom: 3px;
        }
        
        .detail-item {
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 70px;
        }
        
        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        .service-table th {
            background: #f8f9fa;
            color: #333;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
            font-size: 12px;
        }
        
        .service-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .service-table tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-section {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 10px;
            float: right;
            width: 250px;
        }
        
        .total-row {
            margin-bottom: 5px;
            overflow: hidden;
            font-size: 12px;
        }
        
        .total-label {
            font-weight: bold;
            float: left;
        }
        
        .total-amount {
            font-weight: bold;
            float: right;
        }
        
        .total-final {
            border-top: 2px solid #ff8359;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .total-final .total-label,
        .total-final .total-amount {
            font-size: 14px;
            color: #ff8359;
        }
        
        .clear {
            clear: both;
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 10px;
            margin-top: 15px;
        }
        
        .footer-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #ff8359;
        }
        
        .service-description {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .service-details {
            font-size: 10px;
            color: #666;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-name">Trouve ta Babysitter</div>
            <div class="invoice-title">FACTURE DE SERVICE</div>
            
            <div class="invoice-meta">
                <div class="meta-item">
                    <span class="meta-label">Numéro</span>
                    <span class="meta-value">{{ $invoiceNumber }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Date</span>
                    <span class="meta-value">{{ $invoiceDate }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Réservation</span>
                    <span class="meta-value">#{{ $reservation->id }}</span>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="details-section">
                <div class="detail-card">
                    <div class="detail-card-title">Client</div>
                    <div class="detail-item">
                        <span class="detail-label">Nom :</span>
                        {{ $reservation->parent->firstname }} {{ $reservation->parent->lastname }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email :</span>
                        {{ $reservation->parent->email }}
                    </div>
                    @if($reservation->parent->address)
                        <div class="detail-item">
                            <span class="detail-label">Adresse :</span>
                            {{ $reservation->parent->address->full_address }}
                        </div>
                    @endif
                </div>

                <div class="detail-card">
                    <div class="detail-card-title">Prestation</div>
                    <div class="detail-item">
                        <span class="detail-label">Babysitter :</span>
                        {{ $reservation->babysitter->firstname }} {{ $reservation->babysitter->lastname }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date :</span>
                        {{ $serviceDate }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Horaires :</span>
                        {{ $serviceTime }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Durée :</span>
                        {{ $duration }}h
                    </div>
                    @if($reservation->ad->address)
                        <div class="detail-item">
                            <span class="detail-label">Lieu :</span>
                            {{ $reservation->ad->address->full_address }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="clear"></div>

            <table class="service-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;">Durée</th>
                        <th style="width: 20%;">Taux horaire</th>
                        <th style="width: 15%;" class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="service-description">Service de garde d'enfants</div>
                            <div class="service-details">
                                {{ $serviceDate }} de {{ $serviceTime }}<br>
                                Avec {{ $reservation->babysitter->firstname }} {{ $reservation->babysitter->lastname }}
                            </div>
                        </td>
                        <td>{{ $duration }}h</td>
                        <td>{{ number_format($actualHourlyRate, 2, ',', ' ') }}€</td>
                        <td class="text-right">{{ number_format($actualServiceAmount, 2, ',', ' ') }}€</td>
                    </tr>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">Sous-total :</span>
                    <span class="total-amount">{{ number_format($actualServiceAmount, 2, ',', ' ') }}€</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Frais plateforme :</span>
                    <span class="total-amount">{{ number_format($actualServiceFee, 2, ',', ' ') }}€</span>
                </div>
                <div class="total-row total-final">
                    <span class="total-label">TOTAL PAYÉ :</span>
                    <span class="total-amount">{{ number_format($actualTotalPaid, 2, ',', ' ') }}€</span>
                </div>
            </div>

            <div class="clear"></div>
        </div>

        <div class="footer">
            <div class="footer-title">Trouve ta Babysitter</div>
            <div>
                Plateforme de mise en relation pour services de garde d'enfants<br>
                Contact : contact@trouvetababysitter.fr | Facture générée le {{ now()->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</body>
</html> 