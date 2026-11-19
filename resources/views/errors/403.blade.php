<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak | {{ config('app.name', 'President PURTH') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6 text-slate-800">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center border border-slate-200">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
            !
        </div>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">403</h1>
        <h2 class="text-xl font-bold text-slate-800 mb-4">Akses Ditolak</h2>
        <p class="text-slate-600 text-sm mb-8 leading-relaxed">
            {{ $exception->getMessage() ?: 'Maaf, Anda tidak memiliki izin atau hak akses untuk membuka halaman ini.' }}
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/admin/') }}" class="inline-flex justify-center items-center px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition-all shadow-sm">
                Kembali ke Dashboard
            </a>
            <a href="{{ url('/') }}" class="inline-flex justify-center items-center px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition-all">
                Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
