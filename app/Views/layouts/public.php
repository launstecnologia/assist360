<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title) . ' - ' : '' ?>KSS Seguros</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?= $content ?? '' ?>
    
    <!-- Custom Calendar Styles -->
    <style>
        .custom-calendar-wrapper input[readonly] {
            cursor: pointer;
            background-color: white;
        }
        
        .custom-calendar {
            animation: fadeIn 0.2s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .custom-calendar-day:not(:disabled):hover {
            background-color: #f3f4f6 !important;
        }
        
        .custom-calendar-day:disabled {
            opacity: 0.5;
        }
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .custom-calendar {
                width: calc(100vw - 20px) !important;
                max-width: 320px !important;
            }
        }
    </style>
    
    <!-- Custom Calendar Script -->
    <script src="<?= asset('js/custom-calendar.js') ?>"></script>
</body>
</html>

