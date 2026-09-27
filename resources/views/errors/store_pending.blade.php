<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>অ্যাকাউন্ট অনুমোদনের অপেক্ষায় | TrustCash</title>
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
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl"></div>
        <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl"></div>

        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mx-auto flex items-center justify-center shadow-lg shadow-amber-500/10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div class="space-y-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">
                অনুমোদনের অপেক্ষায় (Pending Approval)
            </span>
            <h1 class="text-xl font-bold text-white pt-2">{{ $tenant->name ?? 'আপনার শপ' }}</h1>
            <p class="text-xs text-slate-400 leading-relaxed">
                আপনার শপ রেজিস্ট্রেশন রিকোয়েস্টটি সফলভাবে গ্রহণ করা হয়েছে। সিকিউরিটি ভেরিফিকেশন ও কোয়ালিটি নিশ্চিতের জন্য আমাদের অ্যাডমিন টিম এটি পর্যালোচনা করছে।
            </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-left text-xs space-y-2">
            <div class="flex justify-between">
                <span class="text-slate-500">শপের নাম:</span>
                <span class="font-semibold text-white">{{ $tenant->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">ডোমেইন:</span>
                <span class="font-mono text-emerald-400">{{ request()->getHost() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">স্ট্যাটাস:</span>
                <span class="font-bold text-amber-400">Under Review</span>
            </div>
        </div>

        <div class="text-[11px] text-slate-400 pt-2 border-t border-slate-800">
            জরুরি প্রয়োজনে হেল্পলাইনে যোগাযোগ করুন: <strong class="text-slate-200">+880 1700-000000</strong>
            <br>ইমেইল: <span class="text-emerald-400">support@trustcash.com</span>
        </div>
    </div>
</body>
</html>