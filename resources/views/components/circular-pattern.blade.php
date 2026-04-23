{{-- Ported 1:1 from template/src/app/components/CircularPattern.tsx --}}
<div class="fixed inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
        <defs>
            {{-- Glowing gradients for colorful circles --}}
            <radialGradient id="pinkGlow">
                <stop offset="0%" stop-color="#ff80e3" stop-opacity="0.5" />
                <stop offset="100%" stop-color="#ff80e3" stop-opacity="0.1" />
            </radialGradient>
            <radialGradient id="greenGlow">
                <stop offset="0%" stop-color="#06d473" stop-opacity="0.5" />
                <stop offset="100%" stop-color="#06d473" stop-opacity="0.1" />
            </radialGradient>
        </defs>

        {{-- Pink circles --}}
        <circle cx="15%" cy="12%" r="120" fill="url(#pinkGlow)" class="animate-pulse" style="animation-duration: 6s" />
        <circle cx="78%" cy="25%" r="90" fill="url(#pinkGlow)" class="animate-pulse" style="animation-duration: 7s; animation-delay: 1s" />
        <circle cx="35%" cy="68%" r="110" fill="url(#pinkGlow)" class="animate-pulse" style="animation-duration: 8s; animation-delay: 2s" />
        <circle cx="88%" cy="78%" r="85" fill="url(#pinkGlow)" class="animate-pulse" style="animation-duration: 7s; animation-delay: 3s" />

        {{-- Green circles --}}
        <circle cx="92%" cy="15%" r="100" fill="url(#greenGlow)" class="animate-pulse" style="animation-duration: 7s; animation-delay: 0.5s" />
        <circle cx="50%" cy="45%" r="130" fill="url(#greenGlow)" class="animate-pulse" style="animation-duration: 8s; animation-delay: 1.5s" />
        <circle cx="12%" cy="75%" r="95" fill="url(#greenGlow)" class="animate-pulse" style="animation-duration: 6.5s; animation-delay: 2.5s" />
        <circle cx="65%" cy="88%" r="105" fill="url(#greenGlow)" class="animate-pulse" style="animation-duration: 7.5s; animation-delay: 1s" />

        {{-- Edge circles for depth --}}
        <circle cx="-8%" cy="35%" r="140" fill="url(#pinkGlow)" opacity="0.4" />
        <circle cx="108%" cy="55%" r="150" fill="url(#greenGlow)" opacity="0.4" />
        <circle cx="45%" cy="-10%" r="120" fill="url(#greenGlow)" opacity="0.3" />
        <circle cx="28%" cy="108%" r="130" fill="url(#pinkGlow)" opacity="0.3" />
    </svg>
</div>
