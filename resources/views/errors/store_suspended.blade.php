<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>অ্যাকাউন্ট স্থগিত রয়েছে | TrustCash</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', 'Hind Siliguri', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center shadow-2xl space-y-6 relative overflow-hidden">
        <div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 mx-auto flex items-center justify-center shadow-lg shadow-rose-500/10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-6V7a4 4 0 10-8 0v4m0 0H4a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2v-8a2 2 0 00-2-2h-2" />
            </svg>
        </div>

        <div class="space-y-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                অ্যাকাউন্ট সাময়িকভাবে স্থগিত
            </span>
            <h1 class="text-xl font-bold text-white pt-2">{{ $tenant->name ?? 'আপনার শপ' }}</h1>
            <p class="text-xs text-slate-400 leading-relaxed">
                আপনার শপের সাবস্ক্রিপশন মেয়াদ শেষ হয়েছে অথবা বিল বকেয়া থাকায় অ্যাক্সেস স্থগিত করা হয়েছে। অ্যাকাউন্ট পুনরায় সক্রিয় করতে যোগাযোগ করুন।
            </p>
        </div>

        <div class="text-[11px] text-slate-400 pt-4 border-t border-slate-800">
            কাস্টমার কেয়ার: <strong class="text-slate-200">+880 1700-000000</strong>
            <br>সাপোর্ট: <span class="text-emerald-400">support@trustcash.com</span>
        </div>
    </div>
</body>
</html>