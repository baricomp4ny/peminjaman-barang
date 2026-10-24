{{-- Logo IPB (public/images/logo-ipb.png, latar transparan) dipakai di navbar, sidebar admin, login, dan footer --}}
<img src="{{ asset('images/logo-ipb.png') }}" alt="Logo IPB University" width="40" height="40"
     {{ $attributes->merge(['class' => 'h-10 w-10 shrink-0 rounded-full object-contain']) }}>
