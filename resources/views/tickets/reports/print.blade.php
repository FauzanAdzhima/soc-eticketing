<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Laporan Koordinator</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page { margin: 12mm; }
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }

        .print-body img {
            max-width: 100%;
            height: auto;
        }

        .print-body p:empty::before {
            content: "\00a0";
        }

        .print-body h1,
        .print-body h2,
        .print-body h3,
        .print-body h4 {
            font-weight: 700;
            line-height: 1.25;
            margin: 0.75rem 0 0.45rem;
        }

        .print-body h1 { font-size: 1.75rem; }
        .print-body h2 { font-size: 1.5rem; }
        .print-body h3 { font-size: 1.25rem; }
        .print-body h4 { font-size: 1.05rem; }

        .print-body strong,
        .print-body b {
            font-weight: 700;
        }

        .print-body em,
        .print-body i {
            font-style: italic;
        }

        .print-body ul,
        .print-body ol {
            margin: 0.5rem 0;
            padding-left: 1.25rem;
        }

        .print-body ul { list-style: disc; }
        .print-body ol { list-style: decimal; }

        .print-body li {
            margin: 0.15rem 0;
        }
    </style>
</head>
<body class="bg-surface text-foreground">
    <div class="no-print mb-4 flex items-center justify-end gap-2">
        <button type="button" class="rounded border border-border bg-muted px-3 py-1 text-sm text-foreground" onclick="window.print()">Print</button>
    </div>

    <div class="mx-auto max-w-4xl">
        <h1 class="text-lg font-semibold">Laporan Koordinator</h1>
        <p class="mt-1 text-sm">
            No. Tiket: <span class="font-medium">{{ $ticket->ticket_number ?? '—' }}</span>
        </p>
        <p class="text-sm">
            Judul: <span class="font-medium">{{ $ticket->title ?? '—' }}</span>
        </p>

        <hr class="my-3">

        <div class="print-body text-sm">
            @php
                $bodyHtml = \App\Support\ReportHtmlSanitizer::sanitize((string) ($ticketReport->body_markdown ?? ''));
                if ($bodyHtml === '') {
                    // Fallback: if body is empty, try printing JSON body.
                    $bodyJson = $ticketReport->body_json ?? null;
                    $bodyHtml = is_array($bodyJson)
                        ? '<pre>' . e((string) json_encode($bodyJson, JSON_PRETTY_PRINT)) . '</pre>'
                        : '<p>—</p>';
                }
            @endphp
            {!! $bodyHtml !!}
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>

