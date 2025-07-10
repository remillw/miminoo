<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - {{ $reservation->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .details-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .client-details, .service-details {
            width: 48%;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .detail-item {
            margin-bottom: 5px;
            font-size: 14px;
        }
        .label {
            font-weight: bold;
        }
        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .service-table th, .service-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .service-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .total-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .total-table .label {
            text-align: right;
            font-weight: bold;
        }
        .total-table .amount {
            text-align: right;
            width: 100px;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Trouve ta Babysitter</div>
        <div class="invoice-title">FACTURE</div>
        <div class="invoice-number">N° {{ $invoiceNumber }}</div>
        <div class="invoice-number">Date : {{ $invoiceDate }}</div>
    </div>

    <div class="details-section">
        <div class="client-details">
            <div class="section-title">Facturé à</div>
            <div class="detail-item">
                <span class="label">{{ $reservation->parent->firstname }} {{ $reservation->parent->lastname }}</span>
            </div>
            <div class="detail-item">{{ $reservation->parent->email }}</div>
            @if($reservation->parent->address)
                <div class="detail-item">{{ $reservation->parent->address->full_address }}</div>
            @endif
        </div>

        <div class="service-details">
            <div class="section-title">Détails du service</div>
            <div class="detail-item">
                <span class="label">Babysitter :</span> {{ $reservation->babysitter->firstname }} {{ $reservation->babysitter->lastname }}
            </div>
            <div class="detail-item">
                <span class="label">Date :</span> {{ $serviceDate }}
            </div>
            <div class="detail-item">
                <span class="label">Heure :</span> {{ $serviceTime }}
            </div>
            <div class="detail-item">
                <span class="label">Durée :</span> {{ $duration }}h
            </div>
            @if($reservation->ad->address)
                <div class="detail-item">
                    <span class="label">Lieu :</span> {{ $reservation->ad->address->full_address }}
                </div>
            @endif
        </div>
    </div>

    <table class="service-table">
        <thead>
            <tr>
                <th>Description du service</th>
                <th>Durée</th>
                <th>Tarif horaire</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Service de garde d'enfants<br>
                    <small>{{ $serviceDate }} de {{ $serviceTime }}</small>
                </td>
                <td>{{ $duration }}h</td>
                <td>{{ number_format($reservation->hourly_rate, 2, ',', ' ') }}€</td>
                <td>{{ number_format($serviceAmount, 2, ',', ' ') }}€</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <table class="total-table">
            <tr>
                <td class="label">Sous-total :</td>
                <td class="amount">{{ number_format($serviceAmount, 2, ',', ' ') }}€</td>
            </tr>
            <tr>
                <td class="label">Frais de service :</td>
                <td class="amount">{{ number_format($reservation->service_fee, 2, ',', ' ') }}€</td>
            </tr>
            <tr class="total-final">
                <td class="label">Total TTC :</td>
                <td class="amount">{{ number_format($reservation->total_deposit, 2, ',', ' ') }}€</td>
            </tr>
        </table>
    </div>

    <div class="clear"></div>

    <div class="footer">
        <p>
            <strong>Trouve ta Babysitter</strong><br>
            Plateforme de mise en relation pour services de garde d'enfants<br>
            Email : contact@trouvetababysitter.fr<br>
            Cette facture a été générée automatiquement le {{ now()->format('d/m/Y à H:i') }}
        </p>
        <p>
            <small>
                Service payé via notre plateforme sécurisée.<br>
                Pour toute question, contactez notre service client.
            </small>
        </p>
    </div>
</body>
</html> 