<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .summary { margin: 20px 0; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generado: {{ $generated_at->format('d/m/Y H:i') }}</p>
        <p>Período: {{ $period['start'] ?? 'N/A' }} - {{ $period['end'] ?? 'N/A' }}</p>
    </div>
    
    <div class="summary">
        <h2>Resumen</h2>
        <pre>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</body>
</html>